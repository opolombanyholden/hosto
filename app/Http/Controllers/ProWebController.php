<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Pro\Models\Consultation;
use App\Modules\Pro\Models\Prescription;
use App\Modules\Pro\Models\PrescriptionItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProWebController
{
    /**
     * List consultations for the current practitioner.
     */
    public function consultations(Request $request): View
    {
        $practitioner = $this->currentPractitioner($request);
        $consultations = $practitioner
            ? Consultation::forPractitioner($practitioner->id)
                ->with(['patient', 'structure'])
                ->orderByDesc('created_at')
                ->paginate(20)
            : collect();

        return view('pro.consultations', compact('consultations', 'practitioner'));
    }

    /**
     * New consultation form.
     */
    public function newConsultation(Request $request): View
    {
        $practitioner = $this->currentPractitioner($request);
        $structures = $practitioner !== null ? $practitioner->structures : collect();

        return view('pro.consultation-form', compact('practitioner', 'structures'));
    }

    /**
     * Store a consultation.
     */
    public function storeConsultation(Request $request, AuditLogger $audit): RedirectResponse
    {
        $practitioner = $this->currentPractitioner($request);
        if (! $practitioner) {
            return redirect('/pro')->with('error', 'Profil praticien introuvable.');
        }

        $data = $request->validate([
            'patient_email' => 'required|email|exists:users,email',
            'hosto_id' => 'required|exists:hostos,id',
            'motif' => 'required|string|max:255',
            'anamnesis' => 'nullable|string',
            'examen_clinique' => 'nullable|string',
            'diagnostic' => 'nullable|string',
            'diagnostic_code' => 'nullable|string|max:20',
            'conduite_a_tenir' => 'nullable|string',
            'notes_internes' => 'nullable|string',
        ]);

        $patient = User::where('email', $data['patient_email'])->firstOrFail();

        $consultation = Consultation::create([
            'practitioner_id' => $practitioner->id,
            'patient_id' => $patient->id,
            'hosto_id' => $data['hosto_id'],
            'status' => 'completed',
            'motif' => $data['motif'],
            'anamnesis' => $data['anamnesis'],
            'examen_clinique' => $data['examen_clinique'],
            'diagnostic' => $data['diagnostic'],
            'diagnostic_code' => $data['diagnostic_code'],
            'conduite_a_tenir' => $data['conduite_a_tenir'],
            'notes_internes' => $data['notes_internes'],
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $audit->record(AuditLogger::ACTION_CREATE, 'consultation', $consultation->uuid, [
            'patient' => $patient->uuid,
        ]);

        return redirect("/pro/consultations/{$consultation->uuid}")->with('success', 'Consultation enregistree.');
    }

    /**
     * View a consultation.
     */
    public function showConsultation(Request $request, string $uuid): View
    {
        $practitioner = $this->currentPractitioner($request);
        $consultation = Consultation::whereUuid($uuid)
            ->where('practitioner_id', $practitioner?->id)
            ->with(['patient', 'structure', 'prescriptions.items', 'examRequests'])
            ->firstOrFail();

        return view('pro.consultation-show', compact('consultation', 'practitioner'));
    }

    /**
     * Create a prescription for a consultation.
     */
    public function storePrescription(Request $request, string $consultationUuid, AuditLogger $audit): RedirectResponse
    {
        $practitioner = $this->currentPractitioner($request);
        $consultation = Consultation::whereUuid($consultationUuid)
            ->where('practitioner_id', $practitioner?->id)
            ->firstOrFail();

        $data = $request->validate([
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.medication_name' => 'required|string|max:255',
            'items.*.dosage' => 'nullable|string|max:100',
            'items.*.posology' => 'nullable|string|max:255',
            'items.*.duration' => 'nullable|string|max:100',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.instructions' => 'nullable|string|max:500',
        ]);

        $prescription = Prescription::create([
            'consultation_id' => $consultation->id,
            'practitioner_id' => $practitioner->id,
            'patient_id' => $consultation->patient_id,
            'status' => 'active',
            'reference' => Prescription::generateReference(),
            'valid_until' => now()->addMonths(3),
            'notes' => $data['notes'],
        ]);

        foreach ($data['items'] as $i => $item) {
            PrescriptionItem::create([
                'prescription_id' => $prescription->id,
                'medication_name' => $item['medication_name'],
                'dosage' => $item['dosage'] ?? null,
                'posology' => $item['posology'] ?? null,
                'duration' => $item['duration'] ?? null,
                'quantity' => $item['quantity'] ?? null,
                'instructions' => $item['instructions'] ?? null,
                'display_order' => $i,
            ]);
        }

        $audit->record(AuditLogger::ACTION_CREATE, 'prescription', $prescription->uuid, [
            'consultation' => $consultation->uuid,
            'items_count' => count($data['items']),
        ]);

        return redirect("/pro/consultations/{$consultationUuid}")->with('success', "Ordonnance {$prescription->reference} creee.");
    }

    private function currentPractitioner(Request $request): ?Practitioner
    {
        return Practitioner::where('user_id', $request->user()?->id)
            ->with('structures')
            ->first();
    }
}
