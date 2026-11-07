<?php

declare(strict_types=1);

namespace App\Modules\Telecon\Http\Controllers;

use App\Modules\Telecon\Models\Teleconsultation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TeleconController
{
    public function show(Request $request, string $uuid): JsonResponse
    {
        $tc = Teleconsultation::whereUuid($uuid)
            ->with(['practitioner', 'patient'])
            ->firstOrFail();

        $userId = $request->user()->id;
        abort_unless($tc->patient_id === $userId || $tc->practitioner->user_id === $userId, 404);

        return response()->json([
            'data' => [
                'uuid' => $tc->uuid,
                'room_name' => $tc->room_name,
                'jitsi_domain' => $tc->jitsi_domain,
                'jitsi_url' => $tc->jitsiUrl(),
                'status' => $tc->status,
                'scheduled_at' => $tc->scheduled_at->toIso8601String(),
                'duration_minutes' => $tc->duration_minutes,
                'practitioner' => ['full_name' => $tc->practitioner->full_name],
                'patient' => ['name' => $tc->patient->name],
            ],
        ]);
    }
}
