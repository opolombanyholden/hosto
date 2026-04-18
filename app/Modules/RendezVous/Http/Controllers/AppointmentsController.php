<?php

declare(strict_types=1);

namespace App\Modules\RendezVous\Http\Controllers;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\RendezVous\Http\Resources\AppointmentResource;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Appointments management.
 *
 * - Patient: book, view own, cancel
 * - Practitioner: view own, confirm, cancel, complete (Phase 5)
 */
final class AppointmentsController
{
    /**
     * List current user's appointments.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Appointment::with(['timeSlot', 'practitioner', 'structure'])
            ->where('patient_id', $user->id)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->boolean('upcoming')) {
            $query->upcoming();
        }

        return AppointmentResource::collection(
            $query->paginate($request->integer('per_page', 25))->withQueryString(),
        );
    }

    /**
     * Book a new appointment.
     */
    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'time_slot_uuid' => 'required|exists:time_slots,uuid',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $slot = TimeSlot::where('uuid', $data['time_slot_uuid'])->firstOrFail();

        if (! $slot->is_available) {
            return response()->json([
                'error' => ['code' => 'SLOT_UNAVAILABLE', 'message' => 'Ce creneau n\'est plus disponible.'],
            ], 409);
        }

        // Check no double booking for same patient + same slot.
        $existing = Appointment::where('time_slot_id', $slot->id)
            ->where('patient_id', $request->user()->id)
            ->whereNotIn('status', ['cancelled_by_patient', 'cancelled_by_practitioner'])
            ->exists();

        if ($existing) {
            return response()->json([
                'error' => ['code' => 'ALREADY_BOOKED', 'message' => 'Vous avez deja un rendez-vous sur ce creneau.'],
            ], 409);
        }

        $appointment = Appointment::create([
            'time_slot_id' => $slot->id,
            'patient_id' => $request->user()->id,
            'practitioner_id' => $slot->practitioner_id,
            'hosto_id' => $slot->hosto_id,
            'status' => 'pending',
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_teleconsultation' => $slot->is_teleconsultation,
        ]);

        // Mark slot as taken.
        $slot->update(['is_available' => false]);

        $audit->record(AuditLogger::ACTION_CREATE, 'appointment', $appointment->uuid, [
            'practitioner' => $slot->practitioner_id,
            'date' => $slot->date->toDateString(),
            'time' => $slot->start_time,
        ]);

        $appointment->load(['timeSlot', 'practitioner', 'structure']);

        return response()->json(['data' => new AppointmentResource($appointment)], 201);
    }

    /**
     * Show a single appointment.
     */
    public function show(Request $request, string $uuid): AppointmentResource
    {
        $appointment = Appointment::whereUuid($uuid)
            ->where('patient_id', $request->user()->id)
            ->with(['timeSlot', 'practitioner', 'structure'])
            ->firstOrFail();

        return new AppointmentResource($appointment);
    }

    /**
     * Cancel an appointment (by patient).
     */
    public function cancel(Request $request, string $uuid, AuditLogger $audit): AppointmentResource
    {
        $data = $request->validate(['reason' => 'nullable|string|max:500']);

        $appointment = Appointment::whereUuid($uuid)
            ->where('patient_id', $request->user()->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->firstOrFail();

        $appointment->update([
            'status' => 'cancelled_by_patient',
            'cancellation_reason' => $data['reason'] ?? null,
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()->uuid,
        ]);

        // Free the slot back.
        $appointment->timeSlot->update(['is_available' => true]);

        $audit->record(AuditLogger::ACTION_UPDATE, 'appointment', $appointment->uuid, [
            'action' => 'cancelled_by_patient',
        ]);

        $appointment->load(['timeSlot', 'practitioner', 'structure']);

        return new AppointmentResource($appointment);
    }
}
