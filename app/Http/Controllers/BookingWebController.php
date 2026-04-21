<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\HostoLike;
use App\Modules\Annuaire\Models\HostoRecommendation;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Web-based actions that require authentication (session cookies).
 *
 * These routes use the standard `web` + `auth` middleware (session),
 * not Sanctum API tokens, so they work seamlessly from the browser.
 */
final class BookingWebController
{
    /**
     * Book an appointment (from practitioner detail page).
     */
    public function bookAppointment(Request $request, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'time_slot_uuid' => 'required|exists:time_slots,uuid',
            'reason' => 'nullable|string|max:255',
        ]);

        $slot = TimeSlot::where('uuid', $data['time_slot_uuid'])->firstOrFail();

        if (! $slot->is_available) {
            return response()->json(['error' => ['code' => 'SLOT_UNAVAILABLE', 'message' => 'Ce creneau n\'est plus disponible.']], 409);
        }

        $existing = Appointment::where('time_slot_id', $slot->id)
            ->where('patient_id', $request->user()->id)
            ->whereNotIn('status', ['cancelled_by_patient', 'cancelled_by_practitioner'])
            ->exists();

        if ($existing) {
            return response()->json(['error' => ['code' => 'ALREADY_BOOKED', 'message' => 'Vous avez deja un rendez-vous sur ce creneau.']], 409);
        }

        $appointment = Appointment::create([
            'time_slot_id' => $slot->id,
            'patient_id' => $request->user()->id,
            'practitioner_id' => $slot->practitioner_id,
            'hosto_id' => $slot->hosto_id,
            'status' => 'pending',
            'reason' => $data['reason'] ?? null,
            'is_teleconsultation' => $slot->is_teleconsultation,
        ]);

        $slot->update(['is_available' => false]);

        $audit->record(AuditLogger::ACTION_CREATE, 'appointment', $appointment->uuid);

        return response()->json(['data' => ['uuid' => $appointment->uuid, 'status' => 'pending', 'message' => 'Rendez-vous pris avec succes !']], 201);
    }

    /**
     * Cancel an appointment.
     */
    public function cancelAppointment(Request $request, string $uuid, AuditLogger $audit): JsonResponse|RedirectResponse
    {
        $appointment = Appointment::where('uuid', $uuid)
            ->where('patient_id', $request->user()->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->firstOrFail();

        $appointment->update([
            'status' => 'cancelled_by_patient',
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()->uuid,
        ]);

        $appointment->timeSlot->update(['is_available' => true]);

        $audit->record(AuditLogger::ACTION_UPDATE, 'appointment', $appointment->uuid, ['action' => 'cancelled']);

        if ($request->expectsJson()) {
            return response()->json(['data' => ['status' => 'cancelled']]);
        }

        return redirect('/compte/rendez-vous')->with('success', 'Rendez-vous annule.');
    }

    /**
     * Toggle like on a structure.
     */
    public function toggleLike(Request $request, string $uuid, AuditLogger $audit): JsonResponse
    {
        $hosto = Hosto::where('uuid', $uuid)->where('is_partner', true)->firstOrFail();
        $user = $request->user();

        $existing = HostoLike::where('user_id', $user->id)->where('hosto_id', $hosto->id)->first();

        if ($existing) {
            $existing->delete();
            $hosto->decrement('likes_count');

            return response()->json(['data' => ['liked' => false, 'likes_count' => $hosto->fresh()->likes_count]]);
        }

        HostoLike::create(['user_id' => $user->id, 'hosto_id' => $hosto->id]);
        $hosto->increment('likes_count');

        return response()->json(['data' => ['liked' => true, 'likes_count' => $hosto->fresh()->likes_count]]);
    }

    /**
     * Submit a recommendation.
     */
    public function recommend(Request $request, string $uuid, AuditLogger $audit): JsonResponse
    {
        $hosto = Hosto::where('uuid', $uuid)->where('is_partner', true)->firstOrFail();

        $data = $request->validate(['content' => 'required|string|max:500']);

        $existing = HostoRecommendation::where('user_id', $request->user()->id)->where('hosto_id', $hosto->id)->first();
        if ($existing) {
            return response()->json(['error' => ['code' => 'ALREADY_RECOMMENDED', 'message' => 'Vous avez deja recommande cette structure.']], 409);
        }

        $reco = HostoRecommendation::create([
            'user_id' => $request->user()->id,
            'hosto_id' => $hosto->id,
            'content' => $data['content'],
            'is_approved' => false,
        ]);

        $audit->record(AuditLogger::ACTION_CREATE, 'recommendation', $reco->uuid);

        return response()->json(['data' => ['message' => 'Votre recommandation sera publiee apres moderation.']], 201);
    }
}
