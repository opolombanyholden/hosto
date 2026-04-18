<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Http\Controllers;

use App\Modules\Referentiel\Http\Resources\MedicationResource;
use App\Modules\Referentiel\Models\Medication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public endpoints for the medications catalog.
 */
final class MedicationsController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Medication::active()->with('brands');

        if ($request->filled('q')) {
            $search = (string) $request->input('q');
            $query->where(fn ($q) => $q
                ->where('dci', 'ILIKE', "%{$search}%")
                ->orWhereHas('brands', fn ($bq) => $bq->where('brand_name', 'ILIKE', "%{$search}%")));
        }

        if ($request->filled('class')) {
            $query->where('therapeutic_class', 'ILIKE', '%'.$request->input('class').'%');
        }

        if ($request->boolean('rx_only')) {
            $query->where('prescription_required', true);
        }

        return MedicationResource::collection(
            $query->orderBy('dci')->paginate($request->integer('per_page', 25))->withQueryString(),
        );
    }

    public function show(string $uuid): MedicationResource
    {
        return new MedicationResource(
            Medication::whereUuid($uuid)->with('brands')->firstOrFail(),
        );
    }
}
