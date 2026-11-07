<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Lab\Models\LabResult;
use App\Modules\Pharma\Models\Dispensation;
use App\Modules\Pharma\Models\DispensationItem;
use App\Modules\Pro\Models\ExamRequest;
use App\Modules\Pro\Models\Prescription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The patient decides which pharmacy or laboratory receives a prescription
 * or an exam. Staff of a structure only see what was addressed to it.
 */
final class PatientCareController
{
    public function index(Request $request): View
    {
        $patientId = $request->user()->id;

        $orders = Dispensation::where('patient_id', $patientId)
            ->with(['pharmacy', 'items'])
            ->orderByDesc('created_at')
            ->get();

        $sentPrescriptionIds = $orders->whereIn('status', ['pending', 'dispensed'])->pluck('prescription_id');

        $prescriptions = Prescription::where('patient_id', $patientId)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>', now()))
            ->whereNotIn('id', $sentPrescriptionIds->filter())
            ->with(['items', 'practitioner'])
            ->orderByDesc('created_at')
            ->get();

        $labResults = LabResult::where('patient_id', $patientId)
            ->with(['laboratory', 'examRequest', 'items'])
            ->orderByDesc('created_at')
            ->get();

        $exams = ExamRequest::where('patient_id', $patientId)
            ->where('status', 'requested')
            ->whereNotIn('id', $labResults->pluck('exam_request_id'))
            ->with('practitioner')
            ->orderByDesc('created_at')
            ->get();

        return view('compte.soins', [
            'prescriptions' => $prescriptions,
            'orders' => $orders,
            'exams' => $exams,
            'labResults' => $labResults,
            'pharmacies' => $this->structuresOfType('pharmacie'),
            'laboratories' => $this->structuresOfType('laboratoire'),
        ]);
    }

    public function orderPrescription(Request $request, string $uuid, AuditLogger $audit): RedirectResponse
    {
        $prescription = Prescription::whereUuid($uuid)
            ->where('patient_id', $request->user()->id)
            ->with('items')
            ->firstOrFail();

        $pharmacy = $this->structureOfType($request, 'pharmacy_uuid', 'pharmacie');

        $expired = $prescription->valid_until !== null && $prescription->valid_until->isPast();
        $alreadySent = Dispensation::where('prescription_id', $prescription->id)
            ->whereIn('status', ['pending', 'dispensed'])
            ->exists();

        if ($prescription->status !== 'active' || $expired || $alreadySent) {
            return redirect('/compte/soins')->with('error', 'Cette ordonnance ne peut pas être envoyée.');
        }

        $order = DB::transaction(function () use ($prescription, $pharmacy, $request): Dispensation {
            $order = Dispensation::create([
                'prescription_id' => $prescription->id,
                'hosto_id' => $pharmacy->id,
                'patient_id' => $request->user()->id,
                'reference' => Dispensation::generateReference(),
                'status' => 'pending',
                'delivery_code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            ]);

            foreach ($prescription->items as $index => $item) {
                DispensationItem::create([
                    'dispensation_id' => $order->id,
                    'medication_id' => $item->medication_id,
                    'medication_name' => $item->medication_name,
                    'dosage' => $item->dosage,
                    'quantity' => $item->quantity ?? 1,
                    'display_order' => $index,
                ]);
            }

            return $order;
        });

        $audit->record('pharmacy.order.created', 'dispensation', $order->uuid);

        return redirect('/compte/soins')->with('success', 'Ordonnance envoyée à la pharmacie.');
    }

    public function sendExam(Request $request, string $uuid, AuditLogger $audit): RedirectResponse
    {
        $exam = ExamRequest::whereUuid($uuid)
            ->where('patient_id', $request->user()->id)
            ->firstOrFail();

        $laboratory = $this->structureOfType($request, 'laboratory_uuid', 'laboratoire');

        if ($exam->status !== 'requested' || LabResult::where('exam_request_id', $exam->id)->exists()) {
            return redirect('/compte/soins')->with('error', 'Cet examen a déjà été envoyé.');
        }

        $result = DB::transaction(function () use ($exam, $laboratory): LabResult {
            $exam->update(['status' => 'scheduled']);

            return LabResult::create([
                'exam_request_id' => $exam->id,
                'hosto_id' => $laboratory->id,
                'patient_id' => $exam->patient_id,
                'reference' => LabResult::generateReference(),
                'status' => 'received',
            ]);
        });

        $audit->record('lab.exam.sent', 'lab_result', $result->uuid);

        return redirect('/compte/soins')->with('success', 'Examen envoyé au laboratoire.');
    }

    /**
     * @return Collection<int, Hosto>
     */
    private function structuresOfType(string $slug): Collection
    {
        return Hosto::where('is_active', true)
            ->whereHas('structureTypes', fn ($q) => $q->where('slug', $slug))
            ->orderBy('name')
            ->get();
    }

    private function structureOfType(Request $request, string $field, string $slug): Hosto
    {
        $request->validate([$field => 'required|string']);

        $structure = Hosto::where('uuid', $request->input($field))
            ->where('is_active', true)
            ->whereHas('structureTypes', fn ($q) => $q->where('slug', $slug))
            ->first();

        if ($structure === null) {
            throw ValidationException::withMessages([$field => 'Choisissez une structure valide.']);
        }

        return $structure;
    }
}
