<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Http\Controllers;

use App\Modules\Annuaire\Http\Resources\HostoResource;
use App\Modules\Annuaire\Models\Hosto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Public endpoints for the health structures directory.
 *
 * No authentication required. All structures returned are active.
 *
 * Supports:
 *   - Multi-criteria filtering (type, specialty, service, city, garde, q)
 *   - Geolocation proximity search (lat + lng + rayon in km)
 *   - Sorting by distance, name or relevance
 *   - Pagination
 */
final class HostosController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Hosto::active()
            ->with(['city.region', 'structureTypes', 'specialties']);

        $hasGeo = $request->filled('lat') && $request->filled('lng');

        // --- Geolocation: proximity search ---
        if ($hasGeo && DB::getDriverName() === 'pgsql') {
            $lat = (float) $request->input('lat');
            $lng = (float) $request->input('lng');
            $rayonKm = (float) ($request->input('rayon', 10));
            $rayonMeters = $rayonKm * 1000;

            // Only structures within the radius.
            $query->whereRaw(
                'ST_DWithin(hostos.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                [$lng, $lat, $rayonMeters],
            );

            // Select distance for sorting and display.
            $query->selectRaw(
                'hostos.*, ST_Distance(hostos.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS distance_meters',
                [$lng, $lat],
            );
        }

        // --- Filter by city UUID ---
        if ($request->filled('city')) {
            $query->whereHas('city', fn ($q) => $q->where('uuid', $request->input('city')));
        }

        // --- Filter by region UUID ---
        if ($request->filled('region')) {
            $query->whereHas('city.region', fn ($q) => $q->where('uuid', $request->input('region')));
        }

        // --- Filter by structure type slug (multiple: ?type=hopital,laboratoire) ---
        if ($request->filled('type')) {
            $types = explode(',', (string) $request->input('type'));
            foreach ($types as $type) {
                $query->ofType(trim($type));
            }
        }

        // --- Filter by specialty code ---
        if ($request->filled('specialty')) {
            $query->withSpecialty((string) $request->input('specialty'));
        }

        // --- Filter by service code ---
        if ($request->filled('service')) {
            $serviceCode = (string) $request->input('service');
            $query->whereHas(
                'services',
                fn ($q) => $q->where('services.code', $serviceCode)->where('hosto_service.is_available', true),
            );
        }

        // --- Guard service only ---
        if ($request->boolean('garde')) {
            $query->guardService();
        }

        // --- Public/private filter ---
        if ($request->filled('public')) {
            $query->where('hostos.is_public', $request->boolean('public'));
        }

        // --- Fuzzy search by name ---
        if ($request->filled('q')) {
            $search = (string) $request->input('q');
            $query->where('hostos.name', 'ILIKE', '%'.$search.'%');
        }

        // --- Sorting ---
        $sort = (string) ($request->input('sort', $hasGeo ? 'distance' : 'name'));

        match ($sort) {
            'distance' => $hasGeo
                ? $query->orderByRaw('distance_meters ASC NULLS LAST')
                : $query->orderBy('hostos.name'),
            'name' => $query->orderBy('hostos.name'),
            default => $query->orderBy('hostos.name'),
        };

        $hostos = $query
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return HostoResource::collection($hostos);
    }

    public function show(string $uuid): HostoResource
    {
        $query = Hosto::whereUuid($uuid)
            ->with(['city.region.country', 'structureTypes', 'specialties', 'services']);

        // Add distance if coordinates provided.
        if (request()->filled('lat') && request()->filled('lng') && DB::getDriverName() === 'pgsql') {
            $lat = (float) request()->input('lat');
            $lng = (float) request()->input('lng');
            $query->selectRaw(
                'hostos.*, ST_Distance(hostos.location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS distance_meters',
                [$lng, $lat],
            );
        }

        $hosto = $query->firstOrFail();

        return new HostoResource($hosto);
    }
}
