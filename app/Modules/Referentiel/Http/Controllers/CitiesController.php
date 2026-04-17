<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Http\Controllers;

use App\Modules\Referentiel\Http\Resources\CityResource;
use App\Modules\Referentiel\Models\City;

/**
 * Public endpoint for individual city detail.
 *
 * No authentication required.
 */
final class CitiesController
{
    public function show(string $uuid): CityResource
    {
        $city = City::whereUuid($uuid)
            ->with(['region.country'])
            ->firstOrFail();

        return new CityResource($city);
    }
}
