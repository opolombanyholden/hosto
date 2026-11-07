<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\MedicalRecordGrant;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\MedicalRecordGrantService;
use App\Modules\Pro\Models\Consultation;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Practitioner home: daily activity, patient list and patient file.
 *
 * A practitioner only ever sees patients linked to them by a consultation or
 * a non-cancelled appointment. History written by other practitioners
 * requires an active grant from the patient, and each such read is logged.
 */
final class ProDashboardController
{
    public function dashboard(Request $request): View
    {
        $practitioner = $this->currentPractitioner($request);

        if ($practitioner === null) {
            return view('pro.dashboard', ['practitioner' => null, 'stats' => [], 'upcoming' => collect()]);
        }

        $today = now()->toDateString();
        $upcoming = Appointment::upcoming()
            ->where('practitioner_id', $practitioner->id)
            ->with(['timeSlot', 'patient', 'structure'])
            ->orderBy(TimeSlot::select('date')->whereColumn('time_slots.id', 'appointments.time_slot_id'))
            ->orderBy(TimeSlot::select('start_time')->whereColumn('time_slots.id', 'appointments.time_slot_id'))
            ->limit(10)
            ->get();

        $stats = [
            'today' => Appointment::upcoming()
                ->where('practitioner_id', $practitioner->id)
                ->whereHas('timeSlot', fn ($q) => $q->where('date', $today))
                ->count(),
            'pending' => Appointment::upcoming()
                ->where('practitioner_id', $practitioner->id)
                ->where('status', 'pending')
                ->count(),
            'consultations' => Consultation::forPractitioner($practitioner->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'patients' => $this->linkedPatientIds($practitioner)->count(),
        ];

        return view('pro.dashboard', compact('practitioner', 'stats', 'upcoming'));
    }

    public function patients(Request $request): View
    {
        $practitioner = $this->currentPractitioner($request);
        $search = trim((string) $request->query('q', ''));

        $patients = $practitioner === null
            ? collect()
            : User::whereIn('id', $this->linkedPatientIds($practitioner))
                ->when($search !== '', function ($query) use ($search): void {
                    $term = '%'.addcslashes($search, '\\%_').'%';
                    $query->where(fn ($q) => $q->where('name', 'ilike', $term)->orWhere('nip', 'ilike', $term));
                })
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString();

        return view('pro.patients', compact('practitioner', 'patients', 'search'));
    }

    public function showPatient(Request $request, string $uuid, MedicalRecordGrantService $grants): View
    {
        $practitioner = $this->currentPractitioner($request);
        abort_if($practitioner === null, 404);

        $patient = User::where('uuid', $uuid)->firstOrFail();
        abort_unless($this->linkedPatientIds($practitioner)->contains($patient->id), 404);

        $consultations = Consultation::forPractitioner($practitioner->id)
            ->where('patient_id', $patient->id)
            ->with('structure')
            ->orderByDesc('created_at')
            ->get();

        $appointments = Appointment::where('practitioner_id', $practitioner->id)
            ->where('patient_id', $patient->id)
            ->with(['timeSlot', 'structure'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $grant = MedicalRecordGrant::where('patient_id', $patient->id)
            ->where('practitioner_id', $practitioner->id)
            ->whereNull('revoked_at')
            ->first();

        $sharedConsultations = collect();
        if ($grant !== null) {
            $sharedConsultations = Consultation::where('patient_id', $patient->id)
                ->where('practitioner_id', '!=', $practitioner->id)
                ->with(['practitioner', 'structure'])
                ->orderByDesc('created_at')
                ->get();

            $grants->logAccess($grant, $request->user(), ['medical_profile', 'consultations'], $request);
        }

        return view('pro.patient-show', compact(
            'practitioner', 'patient', 'consultations', 'appointments', 'grant', 'sharedConsultations',
        ));
    }

    public function confirmAppointment(Request $request, string $uuid, AuditLogger $audit): RedirectResponse
    {
        $practitioner = $this->currentPractitioner($request);
        abort_if($practitioner === null, 404);

        $appointment = Appointment::whereUuid($uuid)
            ->where('practitioner_id', $practitioner->id)
            ->firstOrFail();

        if ($appointment->status !== 'pending') {
            return redirect('/pro')->with('error', 'Ce rendez-vous ne peut plus être confirmé.');
        }

        $appointment->update(['status' => 'confirmed', 'confirmed_at' => now()]);
        $audit->record('appointment.confirmed', 'appointment', $appointment->uuid);

        return redirect('/pro')->with('success', 'Rendez-vous confirmé.');
    }

    /**
     * @return Collection<int, int>
     */
    private function linkedPatientIds(Practitioner $practitioner): Collection
    {
        return Consultation::forPractitioner($practitioner->id)->pluck('patient_id')
            ->merge(
                Appointment::where('practitioner_id', $practitioner->id)
                    ->where('status', 'not like', 'cancelled%')
                    ->pluck('patient_id'),
            )
            ->unique()
            ->values();
    }

    private function currentPractitioner(Request $request): ?Practitioner
    {
        return Practitioner::where('user_id', $request->user()?->id)->first();
    }
}
