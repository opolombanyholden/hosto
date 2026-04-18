<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Http\Controllers;

use App\Modules\Annuaire\Http\Resources\PractitionerResource;
use App\Modules\Annuaire\Models\Practitioner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public endpoints for the practitioners directory.
 */
final class PractitionersController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Practitioner::active()
            ->with(['specialties', 'structures.city']);

        if ($request->filled('type')) {
            $query->ofType((string) $request->input('type'));
        }

        if ($request->filled('specialty')) {
            $query->whereHas('specialties', fn ($q) => $q->where('specialties.code', $request->input('specialty')));
        }

        if ($request->filled('structure')) {
            $query->whereHas('structures', fn ($q) => $q->where('hostos.uuid', $request->input('structure')));
        }

        if ($request->filled('q')) {
            $search = (string) $request->input('q');
            $query->where(fn ($q) => $q->where('last_name', 'ILIKE', "%{$search}%")->orWhere('first_name', 'ILIKE', "%{$search}%"));
        }

        if ($request->boolean('teleconsultation')) {
            $query->where('does_teleconsultation', true);
        }

        return PractitionerResource::collection(
            $query->orderBy('last_name')->orderBy('first_name')
                ->paginate($request->integer('per_page', 25))
                ->withQueryString(),
        );
    }

    public function show(string $slug): PractitionerResource
    {
        $prac = Practitioner::where('slug', $slug)
            ->with(['specialties', 'structures.city.region', 'structures.structureTypes'])
            ->firstOrFail();

        return new PractitionerResource($prac);
    }
}
