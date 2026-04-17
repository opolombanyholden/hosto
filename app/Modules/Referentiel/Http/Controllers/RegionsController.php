<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Http\Controllers;

use App\Modules\Referentiel\Http\Resources\CityResource;
use App\Modules\Referentiel\Http\Resources\RegionResource;
use App\Modules\Referentiel\Models\Region;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public endpoints for regions and their cities.
 *
 * No authentication required.
 */
final class RegionsController
{
    public function show(string $uuid): RegionResource
    {
        $region = Region::whereUuid($uuid)
            ->with(['country', 'capitalCity'])
            ->withCount('cities')
            ->firstOrFail();

        return new RegionResource($region);
    }

    public function cities(string $uuid): AnonymousResourceCollection
    {
        $region = Region::whereUuid($uuid)->firstOrFail();

        $cities = $region->cities()
            ->active()
            ->orderByDesc('is_capital')
            ->orderByDesc('population')
            ->orderBy('name_fr')
            ->get();

        return CityResource::collection($cities);
    }
}
