<?php
declare(strict_types=1);

namespace App\Modules\EVax\Http\Controllers;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\EVax\Models\Dependent;
use App\Modules\EVax\Models\Vaccine;
use App\Modules\EVax\Models\VaccinationRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class EVaxProController
{
    public function searchPatient(Request $request): JsonResponse
    {
        $this->ensureVerifiedPro($request);

        $q = trim((string) $request->input('q', ''));
        if ($q === '') {
            return response()->json(['data' => []]);
        }

        $users = User::query()
            ->where(function ($w) use ($q) {
                $w->where('name', 'ILIKE', '%'.$q.'%')
                  ->orWhere('nip', 'ILIKE', '%'.$q.'%')
                  ->orWhere('phone', 'ILIKE', '%'.$q.'%');
            })
            ->limit(20)
            ->with(['dependents:id,uuid,user_id,first_name,last_name,date_of_birth'])
            ->get();

        return response()->json([
            'data' => $users->map(fn (User $u) => $this->serializePatient($u))->all(),
        ]);
    }

    public function resolvePatientFromQr(Request $request): JsonResponse
    {
        $this->ensureVerifiedPro($request);
        $data = $request->validate(['qr' => 'required|string|max:1024']);

        $raw = $data['qr'];
        if (preg_match('#/(carnet/identity|c/v)/([A-Za-z0-9]{32})#', $raw, $m)) {
            $secret = $m[2];
        } elseif (preg_match('/^[A-Za-z0-9]{32}$/', $raw)) {
            $secret = $raw;
        } else {
            abort(404, 'Format QR non reconnu.');
        }

        $patient = User::where('carnet_qr_secret', $secret)->first();
        if (! $patient) {
            abort(404, 'Carnet introuvable.');
        }
        $patient->load('dependents');

        return response()->json(['data' => $this->serializePatient($patient)]);
    }

    public function storeVaccination(Request $request, AuditLogger $audit): JsonResponse
    {
        $this->ensureVerifiedPro($request);

        $data = $request->validate([
            'patient_uuid' => 'required_without:dependent_uuid|nullable|string|exists:users,uuid',
            'dependent_uuid' => 'required_without:patient_uuid|nullable|string|exists:dependents,uuid',
            'vaccine_code' => 'required_without:vaccine_name|nullable|string|max:30',
            'vaccine_name' => 'required_without:vaccine_code|nullable|string|max:255',
            'dose_number' => 'required|integer|min:1|max:20',
            'administered_at' => 'required|date|before_or_equal:today',
            'batch_number' => 'nullable|string|max:60',
            'next_dose_date' => 'nullable|date|after:administered_at',
            'notes' => 'nullable|string|max:1000',
            'hosto_id' => 'nullable|integer|exists:hostos,id',
        ]);

        if (! empty($data['patient_uuid']) && ! empty($data['dependent_uuid'])) {
            abort(422, 'Choisissez soit un patient soit un dependant, pas les deux.');
        }

        $vaccine = null;
        if (! empty($data['vaccine_code'])) {
            $vaccine = Vaccine::whereRaw('LOWER(code) = ?', [strtolower($data['vaccine_code'])])->first();
            if (! $vaccine) {
                abort(422, "Vaccin code '{$data['vaccine_code']}' inconnu.");
            }
        }

        $patient = ! empty($data['patient_uuid']) ? User::where('uuid', $data['patient_uuid'])->first() : null;
        $dependent = ! empty($data['dependent_uuid']) ? Dependent::where('uuid', $data['dependent_uuid'])->first() : null;

        $record = DB::transaction(function () use ($data, $vaccine, $patient, $dependent, $request) {
            $previousMaxRev = VaccinationRecord::query()
                ->when($patient, fn ($q) => $q->where('patient_id', $patient->id))
                ->when($dependent, fn ($q) => $q->where('dependent_id', $dependent->id))
                ->max('carnet_revision');

            return VaccinationRecord::create([
                'patient_id' => $patient?->id,
                'dependent_id' => $dependent?->id,
                'vaccine_id' => $vaccine?->id,
                'vaccine_code' => $vaccine?->code,
                'vaccine_name' => $vaccine?->name_fr ?? $data['vaccine_name'],
                'is_standardized' => (bool) $vaccine,
                'dose_number' => $data['dose_number'],
                'administered_at' => $data['administered_at'],
                'administered_by_id' => $request->user()->id,
                'hosto_id' => $data['hosto_id'] ?? null,
                'batch_number' => $data['batch_number'] ?? null,
                'next_dose_date' => $data['next_dose_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'signed_at' => now(),
                'signed_by_signature' => hash('sha256', $request->user()->id.'|'.now()->timestamp.'|'.($patient?->uuid ?? $dependent?->uuid)),
                'carnet_revision' => ((int) $previousMaxRev) + 1,
            ]);
        });

        $audit->record(AuditLogger::ACTION_CREATE, 'vaccination_record', $record->uuid, [
            'target' => $patient ? "user:{$patient->uuid}" : "dependent:{$dependent->uuid}",
            'vaccine' => $record->vaccine_code ?: $record->vaccine_name,
            'dose' => $record->dose_number,
        ]);

        return response()->json([
            'data' => [
                'uuid' => $record->uuid,
                'carnet_revision' => $record->carnet_revision,
                'message' => 'Vaccination enregistree.',
            ],
        ], 201);
    }

    public function showAddForm(Request $request): mixed
    {
        // Implemented in T10.t14 when the view exists.
        abort(501, 'Not implemented yet (T10.t14)');
    }

    /** @return array<string, mixed> */
    private function serializePatient(User $u): array
    {
        return [
            'uuid' => $u->uuid,
            'full_name' => $u->name,
            'nip' => $u->nip,
            'phone' => $u->phone,
            'date_of_birth' => $u->date_of_birth?->toDateString(),
            'dependents' => $u->dependents->map(fn (Dependent $d) => [
                'uuid' => $d->uuid,
                'full_name' => $d->first_name.' '.$d->last_name,
                'date_of_birth' => $d->date_of_birth?->toDateString(),
            ])->all(),
        ];
    }

    private function ensureVerifiedPro(Request $request): void
    {
        $user = $request->user();
        if (! $user || $user->pro_validated_at === null) {
            abort(403, 'Compte pro en attente de validation.');
        }
    }
}
