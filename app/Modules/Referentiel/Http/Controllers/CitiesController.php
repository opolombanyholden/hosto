<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Http\Controllers;

use App\Modules\Referentiel\Http\Resources\CityResource;
use App\Modules\Referentiel\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public endpoints for cities.
 */
final class CitiesController
{
    /**
     * Search cities by name (autocomplete).
     */
    public function index(Request $request): JsonResponse
    {
        $query = City::with('region.country')->orderBy('name_fr');

        if ($request->filled('q')) {
            $query->where('name_fr', 'ILIKE', '%'.$request->input('q').'%');
        }

        $cities = $query->limit(20)->get();

        return response()->json([
            'data' => $cities->map(fn ($c) => [
                'uuid' => $c->uuid,
                'name' => $c->name_fr,
                'region' => $c->region->name_fr,
                'country' => $c->region->country->name_fr,
            ]),
        ]);
    }

    public function show(string $uuid): CityResource
    {
        $city = City::whereUuid($uuid)
            ->with(['region.country'])
            ->firstOrFail();

        return new CityResource($city);
    }
}
