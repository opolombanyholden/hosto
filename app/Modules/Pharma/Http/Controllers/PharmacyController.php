<?php

declare(strict_types=1);

namespace App\Modules\Pharma\Http\Controllers;

use App\Modules\Pharma\Models\PharmacyStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public API for checking medication availability at pharmacies.
 */
final class PharmacyController
{
    /**
     * Search medication availability across all pharmacies.
     *
     * Filters: medication (DCI or brand), city, pharmacy UUID.
     * Returns: pharmacy info, price, stock, accepted insurances.
     */
    public function stock(Request $request): JsonResponse
    {
        $query = PharmacyStock::available()->with(['pharmacy.city', 'medication.brands']);

        if ($request->filled('medication')) {
            $term = $request->input('medication');
            $query->whereHas('medication', fn ($q) => $q->where('dci', 'ILIKE', '%'.$term.'%')
                ->orWhereHas('brands', fn ($bq) => $bq->where('brand_name', 'ILIKE', '%'.$term.'%')));
        }

        if ($request->filled('city')) {
            $query->whereHas('pharmacy.city', fn ($q) => $q->where('name_fr', 'ILIKE', '%'.$request->input('city').'%'));
        }

        if ($request->filled('pharmacy')) {
            $query->whereHas('pharmacy', fn ($q) => $q->where('uuid', $request->input('pharmacy')));
        }

        $stocks = $query->orderBy('unit_price')->paginate($request->integer('per_page', 25))->withQueryString();

        return response()->json([
            'data' => $stocks->map(fn ($s) => [
                'medication' => [
                    'uuid' => $s->medication->uuid,
                    'dci' => $s->medication->dci,
                    'strength' => $s->medication->strength,
                    'dosage_form' => $s->medication->dosage_form,
                    'prescription_required' => $s->medication->prescription_required,
                    'brands' => array_values($s->medication->brands->map(fn ($b) => [
                        'name' => $b->brand_name,
                        'manufacturer' => $b->manufacturer,
                    ])->toArray()),
                ],
                'pharmacy' => [
                    'uuid' => $s->pharmacy->uuid,
                    'name' => $s->pharmacy->name,
                    'slug' => $s->pharmacy->slug,
                    'city' => $s->pharmacy->city->name_fr ?? null,
                    'address' => $s->pharmacy->address,
                    'phone' => $s->pharmacy->phone,
                    'accepted_insurances' => $s->pharmacy->accepted_insurances ?? [],
                ],
                'unit_price' => $s->unit_price,
                'currency' => $s->currency_code,
                'quantity_in_stock' => $s->quantity_in_stock,
                'is_available' => $s->is_available,
            ]),
            'meta' => [
                'total' => $stocks->total(),
                'current_page' => $stocks->currentPage(),
                'last_page' => $stocks->lastPage(),
            ],
        ]);
    }

    /**
     * Stock for a specific pharmacy.
     */
    public function pharmacyStock(string $uuid): JsonResponse
    {
        $stocks = PharmacyStock::available()
            ->whereHas('pharmacy', fn ($q) => $q->where('uuid', $uuid))
            ->with('medication.brands')
            ->orderBy('unit_price')
            ->get();

        return response()->json([
            'data' => $stocks->map(fn ($s) => [
                'medication' => ['uuid' => $s->medication->uuid, 'dci' => $s->medication->dci, 'strength' => $s->medication->strength],
                'quantity_in_stock' => $s->quantity_in_stock,
                'unit_price' => $s->unit_price,
                'currency' => $s->currency_code,
                'brands' => $s->medication->brands->pluck('brand_name'),
            ]),
        ]);
    }
}
