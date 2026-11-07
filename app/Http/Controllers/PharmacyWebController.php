<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStaffStructure;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Pharma\Models\Dispensation;
use App\Modules\Pharma\Models\PharmacyStock;
use App\Modules\Referentiel\Models\Medication;
use App\Modules\Referentiel\Models\ReferenceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pharmacist space: incoming orders, preparation, delivery and stock.
 *
 * The delivery code is known to the patient only, so it is never rendered here.
 */
final class PharmacyWebController
{
    use ResolvesStaffStructure;

    private const MAX_CODE_ATTEMPTS = 5;

    private const CODE_LOCK_SECONDS = 900;

    public function index(Request $request): View
    {
        $pharmacy = $this->staffStructure($request, 'pharmacist');

        return view('pro.pharmacie.index', [
            'pharmacy' => $pharmacy,
            'orders' => Dispensation::where('hosto_id', $pharmacy->id)
                ->whereIn('status', ['pending', 'dispensed'])
                ->with(['patient', 'items'])
                ->orderBy('created_at')
                ->get(),
            'stocks' => PharmacyStock::where('hosto_id', $pharmacy->id)
                ->with('medication')
                ->get()
                ->sortBy(fn (PharmacyStock $s) => $s->medication->dci),
            'medications' => Medication::where('is_active', true)
                ->whereNotIn('id', PharmacyStock::where('hosto_id', $pharmacy->id)->select('medication_id'))
                ->orderBy('dci')
                ->get(),
        ]);
    }

    public function show(Request $request, string $uuid): View
    {
        $pharmacy = $this->staffStructure($request, 'pharmacist');

        return view('pro.pharmacie.order', [
            'order' => $this->order($pharmacy, $uuid)->load(['patient', 'items', 'prescription.items']),
            'stocks' => PharmacyStock::where('hosto_id', $pharmacy->id)
                ->where('quantity_in_stock', '>', 0)
                ->with('medication')
                ->get()
                ->sortBy(fn (PharmacyStock $s) => $s->medication->dci),
            'paymentMethods' => ReferenceData::forCategory('payment_method'),
        ]);
    }

    public function prepare(Request $request, string $uuid, AuditLogger $audit): RedirectResponse
    {
        $pharmacy = $this->staffStructure($request, 'pharmacist');
        $order = $this->order($pharmacy, $uuid);

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.stock' => 'nullable|string',
            'items.*.quantity' => 'nullable|integer|min:1|max:1000',
            'items.*.substitution_reason' => 'nullable|string|max:255',
        ]);

        if ($order->status !== 'pending') {
            return redirect("/pro/pharmacie/commandes/{$order->uuid}")->with('error', 'Cette commande est déjà préparée.');
        }

        DB::transaction(function () use ($order, $pharmacy, $data, $request): void {
            $total = 0;
            $served = 0;

            foreach ($order->items as $item) {
                $line = $data['items'][$item->id] ?? null;

                if (empty($line['stock'])) {
                    $item->delete();

                    continue;
                }

                $stock = PharmacyStock::where('hosto_id', $pharmacy->id)
                    ->where('uuid', $line['stock'])
                    ->with('medication')
                    ->lockForUpdate()
                    ->first();
                $quantity = (int) ($line['quantity'] ?? $item->quantity);

                if ($stock === null || $stock->quantity_in_stock < $quantity) {
                    throw ValidationException::withMessages(['items' => "Stock insuffisant pour {$item->medication_name}."]);
                }

                $reason = trim((string) ($line['substitution_reason'] ?? ''));
                $isSubstituted = ! self::isPrescribedProduct($item->medication_name, $stock);

                if ($isSubstituted && $reason === '') {
                    throw ValidationException::withMessages([
                        'items' => "Le produit choisi n'est pas celui prescrit pour {$item->medication_name} : indiquez le motif de substitution.",
                    ]);
                }

                $remaining = $stock->quantity_in_stock - $quantity;
                $stock->update(['quantity_in_stock' => $remaining, 'is_available' => $remaining > 0]);

                $item->update([
                    'medication_id' => $stock->medication_id,
                    'medication_name' => $isSubstituted ? trim("{$stock->medication->dci} {$stock->medication->strength}") : $item->medication_name,
                    'is_substituted' => $isSubstituted,
                    'substitution_reason' => $isSubstituted ? $reason : null,
                    'quantity' => $quantity,
                    'unit_price' => $stock->unit_price,
                    'total_price' => $stock->unit_price * $quantity,
                ]);

                $total += $stock->unit_price * $quantity;
                $served++;
            }

            if ($served === 0) {
                throw ValidationException::withMessages(['items' => 'Choisissez au moins un médicament en stock.']);
            }

            $order->update([
                'status' => 'dispensed',
                'total_amount' => $total,
                'dispensed_by_id' => $request->user()->id,
                'dispensed_at' => now(),
            ]);

            if ($served === $order->prescription?->items()->count()) {
                $order->prescription->update(['status' => 'dispensed']);
            }
        });

        $audit->record('pharmacy.order.prepared', 'dispensation', $order->uuid);

        return redirect("/pro/pharmacie/commandes/{$order->uuid}")->with('success', 'Commande préparée.');
    }

    public function deliver(Request $request, string $uuid, AuditLogger $audit): RedirectResponse
    {
        $pharmacy = $this->staffStructure($request, 'pharmacist');
        $order = $this->order($pharmacy, $uuid);

        $data = $request->validate([
            'code' => 'required|string|max:10',
            'payment_method' => ['required', 'string', Rule::exists('reference_data', 'code')->where('category', 'payment_method')],
        ]);

        if ($order->status !== 'dispensed') {
            return redirect("/pro/pharmacie/commandes/{$order->uuid}")->with('error', 'Cette commande ne peut pas être livrée.');
        }

        $limiterKey = "pharma-delivery:{$order->uuid}";

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_CODE_ATTEMPTS)) {
            throw ValidationException::withMessages(['code' => 'Trop de tentatives. Réessayez dans 15 minutes.']);
        }

        if (! hash_equals((string) $order->delivery_code, $data['code'])) {
            RateLimiter::hit($limiterKey, self::CODE_LOCK_SECONDS);
            $audit->record('pharmacy.delivery.code_failed', 'dispensation', $order->uuid);

            throw ValidationException::withMessages(['code' => 'Code de livraison incorrect.']);
        }

        RateLimiter::clear($limiterKey);

        $order->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'is_paid' => true,
            'payment_method' => $data['payment_method'],
        ]);

        $audit->record('pharmacy.order.delivered', 'dispensation', $order->uuid);

        return redirect("/pro/pharmacie/commandes/{$order->uuid}")->with('success', 'Commande remise au patient.');
    }

    public function cancel(Request $request, string $uuid, AuditLogger $audit): RedirectResponse
    {
        $pharmacy = $this->staffStructure($request, 'pharmacist');
        $order = $this->order($pharmacy, $uuid);

        if ($order->status !== 'pending') {
            return redirect("/pro/pharmacie/commandes/{$order->uuid}")->with('error', 'Seule une commande non préparée peut être annulée.');
        }

        $order->update(['status' => 'cancelled']);
        $audit->record('pharmacy.order.cancelled', 'dispensation', $order->uuid);

        return redirect('/pro/pharmacie')->with('success', 'Commande annulée.');
    }

    public function updateStock(Request $request, string $uuid, AuditLogger $audit): RedirectResponse
    {
        $pharmacy = $this->staffStructure($request, 'pharmacist');
        $stock = PharmacyStock::where('hosto_id', $pharmacy->id)->where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'quantity_in_stock' => 'required|integer|min:0',
            'unit_price' => 'required|integer|min:0',
        ]);

        $stock->update($data + ['is_available' => $data['quantity_in_stock'] > 0]);
        $audit->record('pharmacy.stock.updated', 'pharmacy_stock', $stock->uuid);

        return redirect('/pro/pharmacie')->with('success', 'Stock mis à jour.');
    }

    public function addStock(Request $request, AuditLogger $audit): RedirectResponse
    {
        $pharmacy = $this->staffStructure($request, 'pharmacist');

        $data = $request->validate([
            'medication_id' => [
                'required', 'integer', 'exists:medications,id',
                Rule::unique('pharmacy_stocks', 'medication_id')->where('hosto_id', $pharmacy->id)->whereNull('deleted_at'),
            ],
            'quantity_in_stock' => 'required|integer|min:0',
            'unit_price' => 'required|integer|min:0',
        ]);

        $stock = PharmacyStock::create($data + [
            'hosto_id' => $pharmacy->id,
            'currency_code' => 'XAF',
            'is_available' => $data['quantity_in_stock'] > 0,
        ]);
        $audit->record('pharmacy.stock.created', 'pharmacy_stock', $stock->uuid);

        return redirect('/pro/pharmacie')->with('success', 'Médicament ajouté au stock.');
    }

    /**
     * A prescription item is free text: it matches a stock line when it names its molecule.
     */
    public static function isPrescribedProduct(string $prescribedName, PharmacyStock $stock): bool
    {
        $normalize = fn (string $text): string => mb_strtolower(Str::ascii($text));

        return str_contains($normalize($prescribedName), $normalize($stock->medication->dci));
    }

    private function order(Hosto $pharmacy, string $uuid): Dispensation
    {
        return Dispensation::where('hosto_id', $pharmacy->id)->where('uuid', $uuid)->with('items')->firstOrFail();
    }
}
