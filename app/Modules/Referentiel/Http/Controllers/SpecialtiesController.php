<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Http\Controllers;

use App\Modules\Referentiel\Http\Resources\SpecialtyResource;
use App\Modules\Referentiel\Models\Specialty;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class SpecialtiesController
{
    public function index(): AnonymousResourceCollection
    {
        $specialties = Specialty::active()
            ->roots()
            ->with(['children' => fn ($q) => $q->active()->orderBy('display_order')])
            ->withCount('children')
            ->orderBy('display_order')
            ->orderBy('name_fr')
            ->get();

        return SpecialtyResource::collection($specialties);
    }

    public function show(string $uuid): SpecialtyResource
    {
        $specialty = Specialty::whereUuid($uuid)
            ->with(['parent', 'children' => fn ($q) => $q->active()->orderBy('display_order')])
            ->withCount('children')
            ->firstOrFail();

        return new SpecialtyResource($specialty);
    }
}
