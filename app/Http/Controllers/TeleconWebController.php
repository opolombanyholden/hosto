<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\Telecon\Models\Teleconsultation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Teleconsultation sessions. Only the patient and the practitioner of a session
 * may reach it: anyone else gets a 404, so the session's existence is not revealed.
 */
final class TeleconWebController
{
    /**
     * Open (or reopen) the session of a confirmed telecon appointment.
     */
    public function start(Request $request, string $uuid, AuditLogger $audit): RedirectResponse
    {
        $practitioner = Practitioner::where('user_id', $request->user()?->id)->first();
        abort_if($practitioner === null, 404);

        $appointment = Appointment::whereUuid($uuid)
            ->where('practitioner_id', $practitioner->id)
            ->with('timeSlot')
            ->firstOrFail();

        if ($appointment->status !== 'confirmed' || ! $appointment->is_teleconsultation) {
            return redirect('/pro')->with('error', 'Seul un rendez-vous de téléconsultation confirmé peut être démarré.');
        }

        $session = Teleconsultation::firstOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'practitioner_id' => $practitioner->id,
                'patient_id' => $appointment->patient_id,
                'room_name' => Teleconsultation::generateRoomName(),
                'jitsi_domain' => config('hosto.telecon.jitsi_domain'),
                'status' => 'scheduled',
                'scheduled_at' => now(),
                'duration_minutes' => $appointment->timeSlot->duration_minutes,
            ],
        );

        if ($session->wasRecentlyCreated) {
            $audit->record('telecon.created', 'teleconsultation', $session->uuid);
        }

        return redirect("/teleconsultation/{$session->uuid}");
    }

    /**
     * Join the teleconsultation room.
     */
    public function room(Request $request, string $uuid): View
    {
        $tc = $this->sessionFor($request->user(), $uuid);
        abort_unless(in_array($tc->status, ['scheduled', 'in_progress'], true), 404);

        return view('teleconsultation.room', [
            'teleconsultation' => $tc,
            'homeUrl' => $this->isPractitioner($tc, $request->user()) ? '/pro' : '/compte/rendez-vous',
        ]);
    }

    /**
     * Mark participant joined (AJAX from Jitsi callback).
     */
    public function markJoined(Request $request, string $uuid, AuditLogger $audit): JsonResponse
    {
        $tc = $this->sessionFor($request->user(), $uuid);

        $update = $this->isPractitioner($tc, $request->user())
            ? ['practitioner_joined' => true]
            : ['patient_joined' => true];

        if ($tc->status === 'scheduled') {
            $update['status'] = 'in_progress';
            $update['started_at'] = now();
        }

        $tc->update($update);

        $audit->record('telecon.joined', 'teleconsultation', $tc->uuid);

        return response()->json(['data' => ['status' => 'joined']]);
    }

    /**
     * Leave the room (AJAX from Jitsi hangup). Only the practitioner closes
     * the session: a patient whose connection drops can come back.
     */
    public function endSession(Request $request, string $uuid, AuditLogger $audit): JsonResponse
    {
        $tc = $this->sessionFor($request->user(), $uuid);

        if (! $this->isPractitioner($tc, $request->user())) {
            return response()->json(['data' => ['status' => 'left']]);
        }

        if ($tc->status === 'in_progress') {
            $tc->update([
                'status' => 'completed',
                'ended_at' => now(),
                'actual_duration_minutes' => $tc->started_at ? (int) $tc->started_at->diffInMinutes(now()) : 0,
            ]);

            Appointment::whereKey($tc->appointment_id)
                ->update(['status' => 'completed', 'completed_at' => now()]);
        }

        $audit->record('telecon.ended', 'teleconsultation', $tc->uuid, [
            'duration' => $tc->actual_duration_minutes,
        ]);

        return response()->json(['data' => ['status' => 'ended']]);
    }

    private function sessionFor(?User $user, string $uuid): Teleconsultation
    {
        $tc = Teleconsultation::whereUuid($uuid)->with(['practitioner', 'patient'])->firstOrFail();

        abort_unless($user !== null && ($tc->patient_id === $user->id || $this->isPractitioner($tc, $user)), 404);

        return $tc;
    }

    private function isPractitioner(Teleconsultation $tc, ?User $user): bool
    {
        return $user !== null && $tc->practitioner?->user_id === $user->id;
    }
}
