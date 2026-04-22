<?php

declare(strict_types=1);

namespace App\Modules\Lab\Http\Controllers;

use App\Modules\Annuaire\Models\Hosto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public API for searching exam availability across laboratories.
 */
final class ExamSearchController
{
    /**
     * Search exam availability across labs/hospitals.
     *
     * Filters: exam (service name), city.
     * Returns: structure info, pricing, accepted insurances.
     */
    public function search(Request $request): JsonResponse
    {
        $query = Hosto::where('is_active', true)
            ->whereHas('services', fn ($q) => $q->where('category', 'examen')
                ->where('hosto_service.is_available', true))
            ->with(['city', 'structureTypes']);

        if ($request->filled('exam')) {
            $term = $request->input('exam');
            $query->whereHas('services', fn ($q) => $q->where('category', 'examen')
                ->where('hosto_service.is_available', true)
                ->where(fn ($sq) => $sq->where('name_fr', 'ILIKE', '%'.$term.'%')
                    ->orWhere('name_en', 'ILIKE', '%'.$term.'%')
                    ->orWhere('code', 'ILIKE', '%'.$term.'%')));
        }

        if ($request->filled('city')) {
            $query->whereHas('city', fn ($q) => $q->where('name_fr', 'ILIKE', '%'.$request->input('city').'%'));
        }

        $structures = $query->orderBy('name')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return response()->json([
            'data' => $structures->map(function (Hosto $hosto) use ($request) {
                $examQuery = $hosto->services()
                    ->where('category', 'examen')
                    ->where('hosto_service.is_available', true);

                if ($request->filled('exam')) {
                    $term = $request->input('exam');
                    $examQuery->where(fn ($q) => $q->where('name_fr', 'ILIKE', '%'.$term.'%')
                        ->orWhere('name_en', 'ILIKE', '%'.$term.'%')
                        ->orWhere('code', 'ILIKE', '%'.$term.'%'));
                }

                $exams = $examQuery->get();

                return [
                    'laboratory' => [
                        'uuid' => $hosto->uuid,
                        'name' => $hosto->name,
                        'slug' => $hosto->slug,
                        'city' => $hosto->city->name_fr ?? null,
                        'address' => $hosto->address,
                        'phone' => $hosto->phone,
                        'types' => $hosto->structureTypes->pluck('name_fr')->toArray(),
                        'accepted_insurances' => $hosto->accepted_insurances ?? [],
                    ],
                    'exams' => $exams->map(function ($s) {
                        /** @var \Illuminate\Database\Eloquent\Relations\Pivot $pivot */
                        $pivot = $s->getRelation('pivot');

                        return [
                            'code' => $s->code,
                            'name' => $s->name_fr,
                            'tarif_min' => $pivot->getAttribute('tarif_min'),
                            'tarif_max' => $pivot->getAttribute('tarif_max'),
                            'currency' => $pivot->getAttribute('currency_code'),
                        ];
                    })->toArray(),
                ];
            }),
            'meta' => [
                'total' => $structures->total(),
                'current_page' => $structures->currentPage(),
                'last_page' => $structures->lastPage(),
            ],
        ]);
    }
}
