<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Http\Controllers;

use App\Modules\Annuaire\Http\Resources\HostoResource;
use App\Modules\Annuaire\Models\Hosto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public endpoints for the health structures directory.
 *
 * No authentication required. All structures returned are active.
 * Filterable by city, type, specialty, guard service.
 */
final class HostosController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Hosto::active()
            ->with(['city.region', 'structureTypes', 'specialties']);

        // Filter by city UUID
        if ($request->filled('city')) {
            $query->whereHas('city', fn ($q) => $q->where('uuid', $request->input('city')));
        }

        // Filter by structure type slug (supports multiple: ?type=hopital,laboratoire)
        if ($request->filled('type')) {
            $types = explode(',', (string) $request->input('type'));
            foreach ($types as $type) {
                $query->ofType(trim($type));
            }
        }

        // Filter by specialty code
        if ($request->filled('specialty')) {
            $query->withSpecialty((string) $request->input('specialty'));
        }

        // Filter guard service
        if ($request->boolean('garde')) {
            $query->guardService();
        }

        // Fuzzy search by name
        if ($request->filled('q')) {
            $search = (string) $request->input('q');
            $query->where('hostos.name', 'ILIKE', '%'.$search.'%');
        }

        $hostos = $query
            ->orderBy('hostos.name')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return HostoResource::collection($hostos);
    }

    public function show(string $uuid): HostoResource
    {
        $hosto = Hosto::whereUuid($uuid)
            ->with(['city.region.country', 'structureTypes', 'specialties', 'services'])
            ->firstOrFail();

        return new HostoResource($hosto);
    }
}
