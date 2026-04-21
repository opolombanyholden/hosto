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
     */
    public function stock(Request $request): JsonResponse
    {
        $query = PharmacyStock::available()->with(['pharmacy.city', 'medication.brands']);

        if ($request->filled('medication')) {
            $query->whereHas('medication', fn ($q) => $q->where('dci', 'ILIKE', '%'.$request->input('medication').'%')
                ->orWhereHas('brands', fn ($bq) => $bq->where('brand_name', 'ILIKE', '%'.$request->input('medication').'%')));
        }

        if ($request->filled('pharmacy')) {
            $query->whereHas('pharmacy', fn ($q) => $q->where('uuid', $request->input('pharmacy')));
        }

        $stocks = $query->orderBy('unit_price')->paginate($request->integer('per_page', 25))->withQueryString();

        return response()->json([
            'data' => $stocks->map(fn ($s) => [
                'medication' => ['dci' => $s->medication->dci, 'strength' => $s->medication->strength],
                'pharmacy' => ['uuid' => $s->pharmacy->uuid, 'name' => $s->pharmacy->name, 'city' => $s->pharmacy->city->name_fr ?? null],
                'quantity_in_stock' => $s->quantity_in_stock,
                'unit_price' => $s->unit_price,
                'currency' => $s->currency_code,
                'is_available' => $s->is_available,
            ]),
            'meta' => ['total' => $stocks->total(), 'current_page' => $stocks->currentPage(), 'last_page' => $stocks->lastPage()],
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
