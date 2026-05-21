<?php

declare(strict_types=1);

namespace App\Modules\Lab\Http\Controllers;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Lab\Models\ExamOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ExamOrdersController
{
    /**
     * Create a new exam order from a logged-in patient.
     * Body: { hosto_uuid, exam_items: [{code,name,tarif_min,tarif_max,currency_code}], notes?, payment_method? }
     */
    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Connectez-vous pour commander un examen.',
                    'login_url' => url('/compte/connexion'),
                ],
            ], 401);
        }

        $data = $request->validate([
            'hosto_uuid' => 'required|string|exists:hostos,uuid',
            'exam_items' => 'required|array|min:1|max:30',
            'exam_items.*.code' => 'required|string|max:50',
            'exam_items.*.name' => 'required|string|max:255',
            'exam_items.*.tarif_min' => 'nullable|integer|min:0',
            'exam_items.*.tarif_max' => 'nullable|integer|min:0',
            'exam_items.*.currency_code' => 'nullable|string|size:3',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|in:online,on_site',
        ]);

        $hosto = Hosto::where('uuid', $data['hosto_uuid'])->firstOrFail();

        $paymentMethod = $data['payment_method'] ?? null;
        if ($paymentMethod === ExamOrder::PAYMENT_ONLINE && ! $hosto->accepts_online_payment) {
            return response()->json([
                'error' => ['code' => 'PAYMENT_NOT_AVAILABLE', 'message' => 'Ce laboratoire n\'accepte pas le paiement en ligne.'],
            ], 422);
        }
        if ($paymentMethod === ExamOrder::PAYMENT_ON_SITE && ! $hosto->accepts_on_site_payment) {
            return response()->json([
                'error' => ['code' => 'PAYMENT_NOT_AVAILABLE', 'message' => 'Ce laboratoire n\'accepte pas le paiement en caisse.'],
            ], 422);
        }

        $totalMin = 0;
        $totalMax = 0;
        $currency = 'XAF';
        foreach ($data['exam_items'] as $it) {
            $totalMin += (int) ($it['tarif_min'] ?? 0);
            $totalMax += (int) ($it['tarif_max'] ?? $it['tarif_min'] ?? 0);
            if (! empty($it['currency_code'])) {
                $currency = $it['currency_code'];
            }
        }

        $order = ExamOrder::create([
            'user_id' => $user->id,
            'hosto_id' => $hosto->id,
            'exam_items' => $data['exam_items'],
            'total_amount' => $totalMax > 0 ? $totalMax : null,
            'currency_code' => $currency,
            'payment_method' => $paymentMethod,
            'payment_status' => ExamOrder::PAYMENT_PENDING,
            'status' => ExamOrder::STATUS_PENDING,
            'notes' => $data['notes'] ?? null,
        ]);

        $audit->record(AuditLogger::ACTION_CREATE, 'exam_order', $order->uuid, [
            'hosto_uuid' => $hosto->uuid,
            'items_count' => count($data['exam_items']),
            'total_estimated' => $order->total_amount,
        ]);

        return response()->json([
            'data' => [
                'uuid' => $order->uuid,
                'reference' => 'EX-'.strtoupper(substr($order->uuid, 0, 8)),
                'status' => $order->status,
                'total_estimated' => $order->total_amount,
                'currency_code' => $order->currency_code,
                'message' => 'Commande enregistree. Le laboratoire va valider votre demande.',
                'next_url' => $paymentMethod === ExamOrder::PAYMENT_ONLINE
                    ? url('/compte/commande-examen/'.$order->uuid.'/paiement')
                    : url('/compte/commande-examen/'.$order->uuid),
            ],
        ], 201);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        $order = ExamOrder::with('hosto')->where('uuid', $uuid)->firstOrFail();

        if ($order->user_id !== $user->id) {
            return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Acces refuse.']], 403);
        }

        return response()->json([
            'data' => [
                'uuid' => $order->uuid,
                'reference' => 'EX-'.strtoupper(substr($order->uuid, 0, 8)),
                'hosto' => ['name' => $order->hosto->name, 'phone' => $order->hosto->phone],
                'exam_items' => $order->exam_items,
                'total_amount' => $order->total_amount,
                'currency_code' => $order->currency_code,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ],
        ]);
    }
}
