<?php

declare(strict_types=1);

namespace App\Modules\RendezVous\Http\Controllers;

use App\Modules\RendezVous\Http\Resources\TimeSlotResource;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public endpoint: browse available time slots.
 * No authentication required — patients see availability before booking.
 */
final class TimeSlotsController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TimeSlot::available()->with(['practitioner', 'structure']);

        if ($request->filled('practitioner')) {
            $query->whereHas('practitioner', fn ($q) => $q->where('uuid', $request->input('practitioner')));
        }

        if ($request->filled('structure')) {
            $query->whereHas('structure', fn ($q) => $q->where('uuid', $request->input('structure')));
        }

        if ($request->filled('date')) {
            $query->where('date', $request->input('date'));
        }

        if ($request->filled('from')) {
            $query->where('date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('date', '<=', $request->input('to'));
        }

        if ($request->boolean('teleconsultation')) {
            $query->where('is_teleconsultation', true);
        }

        return TimeSlotResource::collection(
            $query->orderBy('date')->orderBy('start_time')
                ->paginate($request->integer('per_page', 50))
                ->withQueryString(),
        );
    }
}
