<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Http\Controllers;

use App\Modules\Referentiel\Http\Resources\CountryResource;
use App\Modules\Referentiel\Http\Resources\RegionResource;
use App\Modules\Referentiel\Models\Country;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public endpoints for the countries/regions referential.
 *
 * No authentication required.
 */
final class CountriesController
{
    public function index(): AnonymousResourceCollection
    {
        $countries = Country::active()
            ->withCount('regions')
            ->orderBy('display_order')
            ->orderBy('name_fr')
            ->get();

        return CountryResource::collection($countries);
    }

    public function show(string $iso2): CountryResource
    {
        $country = Country::where('iso2', strtoupper($iso2))
            ->withCount('regions')
            ->firstOrFail();

        return new CountryResource($country);
    }

    public function regions(string $iso2): AnonymousResourceCollection
    {
        $country = Country::where('iso2', strtoupper($iso2))->firstOrFail();

        $regions = $country->regions()
            ->active()
            ->with('capitalCity')
            ->withCount('cities')
            ->orderBy('display_order')
            ->orderBy('name_fr')
            ->get();

        return RegionResource::collection($regions);
    }
}
