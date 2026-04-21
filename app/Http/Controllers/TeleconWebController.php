<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\Telecon\Models\Teleconsultation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TeleconWebController
{
    /**
     * Join the teleconsultation room.
     */
    public function room(string $uuid): View
    {
        $tc = Teleconsultation::whereUuid($uuid)
            ->with(['practitioner', 'patient'])
            ->firstOrFail();

        return view('teleconsultation.room', ['teleconsultation' => $tc]);
    }

    /**
     * Mark participant joined (AJAX from Jitsi callback).
     */
    public function markJoined(Request $request, string $uuid, AuditLogger $audit): JsonResponse
    {
        $tc = Teleconsultation::whereUuid($uuid)->firstOrFail();
        $user = $request->user();

        $update = [];
        if ($user && $tc->patient_id === $user->id) {
            $update['patient_joined'] = true;
        } else {
            $update['practitioner_joined'] = true;
        }

        if ($tc->status === 'scheduled') {
            $update['status'] = 'in_progress';
            $update['started_at'] = now();
        }

        $tc->update($update);

        $audit->record('telecon.joined', 'teleconsultation', $tc->uuid);

        return response()->json(['data' => ['status' => 'joined']]);
    }

    /**
     * End the session (AJAX from Jitsi hangup).
     */
    public function endSession(Request $request, string $uuid, AuditLogger $audit): JsonResponse
    {
        $tc = Teleconsultation::whereUuid($uuid)->firstOrFail();

        if ($tc->status === 'in_progress') {
            $duration = $tc->started_at ? (int) now()->diffInMinutes($tc->started_at) : 0;
            $tc->update([
                'status' => 'completed',
                'ended_at' => now(),
                'actual_duration_minutes' => $duration,
            ]);
        }

        $audit->record('telecon.ended', 'teleconsultation', $tc->uuid, [
            'duration' => $tc->actual_duration_minutes,
        ]);

        return response()->json(['data' => ['status' => 'ended']]);
    }
}
