<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Controllers;

use App\Modules\Billing\Models\InsuranceCard;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Billing API for patients and structures.
 */
final class BillingController
{
    /**
     * Patient's invoices.
     */
    public function invoices(Request $request): JsonResponse
    {
        $invoices = Invoice::where('patient_id', $request->user()->id)
            ->with(['hosto', 'payments'])
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => $invoices->map(fn ($inv) => [
                'uuid' => $inv->uuid,
                'reference' => $inv->reference,
                'status' => $inv->status,
                'total_amount' => $inv->total_amount,
                'insurance_amount' => $inv->insurance_amount,
                'patient_amount' => $inv->patient_amount,
                'currency' => $inv->currency_code,
                'structure' => $inv->hosto->name,
                'issued_at' => $inv->issued_at?->toIso8601String(),
                'paid_at' => $inv->paid_at?->toIso8601String(),
                'payments_count' => $inv->payments->count(),
            ]),
            'meta' => ['total' => $invoices->total()],
        ]);
    }

    /**
     * Patient's insurance cards.
     */
    public function insuranceCards(Request $request): JsonResponse
    {
        $cards = InsuranceCard::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $cards->map(fn ($c) => [
                'uuid' => $c->uuid,
                'provider' => $c->provider,
                'card_number' => $c->card_number,
                'holder_name' => $c->holder_name,
                'coverage_percent' => $c->coverage_percent,
                'valid_until' => $c->valid_until,
            ]),
        ]);
    }

    /**
     * Available payment methods.
     */
    public function paymentMethods(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['method' => 'cash', 'label_fr' => 'Especes', 'label_en' => 'Cash', 'icon' => 'cash'],
                ['method' => 'mobile_money', 'label_fr' => 'Mobile Money', 'label_en' => 'Mobile Money', 'icon' => 'mobile',
                    'providers' => [
                        ['code' => 'airtel_money', 'name' => 'Airtel Money'],
                        ['code' => 'moov_money', 'name' => 'Moov Money'],
                    ],
                ],
                ['method' => 'visa', 'label_fr' => 'Visa', 'label_en' => 'Visa', 'icon' => 'card'],
                ['method' => 'mastercard', 'label_fr' => 'Mastercard', 'label_en' => 'Mastercard', 'icon' => 'card'],
                ['method' => 'ebanking', 'label_fr' => 'e-Banking', 'label_en' => 'e-Banking', 'icon' => 'bank',
                    'providers' => [
                        ['code' => 'bgfi', 'name' => 'BGFI Bank'],
                        ['code' => 'uba', 'name' => 'UBA'],
                        ['code' => 'bicig', 'name' => 'BICIG'],
                    ],
                ],
                ['method' => 'insurance', 'label_fr' => 'Assurance', 'label_en' => 'Insurance', 'icon' => 'shield'],
            ],
        ]);
    }
}
