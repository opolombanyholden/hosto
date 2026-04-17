<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Http\Controllers;

use App\Modules\Referentiel\Http\Resources\StructureTypeResource;
use App\Modules\Referentiel\Models\StructureType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class StructureTypesController
{
    public function index(): AnonymousResourceCollection
    {
        $types = StructureType::active()
            ->orderBy('display_order')
            ->orderBy('name_fr')
            ->get();

        return StructureTypeResource::collection($types);
    }

    public function show(string $slug): StructureTypeResource
    {
        $type = StructureType::where('slug', $slug)->firstOrFail();

        return new StructureTypeResource($type);
    }
}
