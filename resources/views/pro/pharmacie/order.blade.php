@extends('layouts.dashboard')
@section('env-name', 'HOSTO Pro')
@section('env-color', '#1565C0')
@section('env-color-dark', '#0D47A1')
@section('user-role', 'Professionnel de santé')
@section('title', $order->reference)
@section('page-title', 'Commande '.$order->reference)

@section('sidebar-nav')
@include('pro.partials.sidebar', ['active' => 'pharmacie'])
@endsection

@section('content')
<a href="/pro/pharmacie" style="display:inline-block;margin-bottom:14px;color:#1565C0;font-size:.82rem;text-decoration:none;">Retour à ma pharmacie</a>
@include('pro.partials.flash')
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:16px 20px;margin-bottom:16px;">
    <div style="font-size:1rem;font-weight:700;color:#1B2A1B;">{{ $order->patient?->name }}</div>
    <div style="font-size:.78rem;color:#757575;">Ordonnance {{ $order->prescription?->reference }}, reçue le {{ $order->created_at?->format('d/m/Y H:i') }}</div>
</div>

@if($order->status === 'pending')
<form method="POST" action="/pro/pharmacie/commandes/{{ $order->uuid }}/preparer" style="background:white;border:1px solid #EEE;border-radius:14px;padding:16px 20px;margin-bottom:12px;">
    @csrf
    <h2 style="font-size:.95rem;font-weight:600;color:#1565C0;margin:0 0 10px;">Préparer la commande</h2>
    @foreach($order->items as $item)
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:8px 0;border-bottom:1px solid #F5F5F5;">
        <div style="flex:1;min-width:180px;font-size:.85rem;color:#1B2A1B;"><strong>{{ $item->medication_name }}</strong> {{ $item->dosage }}</div>
        <select name="items[{{ $item->id }}][stock]" aria-label="Produit en stock pour {{ $item->medication_name }}" style="padding:7px;border:1px solid #DDD;border-radius:6px;font-size:.8rem;">
            <option value="">Non délivré</option>
            @foreach($stocks as $stock)<option value="{{ $stock->uuid }}" @selected(\App\Http\Controllers\PharmacyWebController::isPrescribedProduct($item, $stock))>{{ $stock->medication->dci }} {{ $stock->medication->strength }} — {{ $stock->unit_price }} XAF ({{ $stock->quantity_in_stock }} en stock)</option>@endforeach
        </select>
        <input type="number" name="items[{{ $item->id }}][quantity]" min="1" value="{{ $item->quantity }}" aria-label="Quantité" style="width:70px;padding:7px;border:1px solid #DDD;border-radius:6px;">
        <input type="text" name="items[{{ $item->id }}][substitution_reason]" maxlength="255" placeholder="Motif si produit différent" aria-label="Motif de substitution" style="flex-basis:100%;padding:7px;border:1px solid #DDD;border-radius:6px;font-size:.78rem;">
    </div>
    @endforeach
    <button type="submit" style="margin-top:12px;padding:9px 20px;border:none;border-radius:8px;background:#1565C0;color:white;font-size:.8rem;font-weight:600;cursor:pointer;">Valider la préparation</button>
</form>
<form method="POST" action="/pro/pharmacie/commandes/{{ $order->uuid }}/annuler" onsubmit="return confirm('Annuler cette commande ?')">
    @csrf
    <button type="submit" style="padding:7px 16px;border:1px solid #EEE;border-radius:8px;background:white;color:#C62828;font-size:.76rem;cursor:pointer;">Annuler la commande</button>
</form>
@else
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:16px 20px;margin-bottom:12px;">
    @foreach($order->items as $item)
    <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:.85rem;color:#1B2A1B;border-bottom:1px solid #F5F5F5;"><span>{{ $item->medication_name }} × {{ $item->quantity }}@if($item->is_substituted)<span style="display:block;font-size:.74rem;color:#E65100;">Substitution : {{ $item->substitution_reason }}</span>@endif</span><strong>{{ $item->total_price }} XAF</strong></div>
    @endforeach
    <div style="display:flex;justify-content:space-between;padding-top:10px;font-size:.95rem;font-weight:700;color:#1B2A1B;"><span>Total</span><span>{{ $order->total_amount }} XAF</span></div>
</div>
@if($order->status === 'dispensed')
<form method="POST" action="/pro/pharmacie/commandes/{{ $order->uuid }}/livrer" style="background:white;border:1px solid #EEE;border-radius:14px;padding:16px 20px;display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
    @csrf
    <label style="font-size:.74rem;color:#757575;">Code donné par le patient <input type="text" name="code" inputmode="numeric" maxlength="10" autocomplete="off" required style="display:block;width:140px;padding:8px;border:1px solid #DDD;border-radius:6px;"></label>
    <label style="font-size:.74rem;color:#757575;">Paiement
        <select name="payment_method" required style="display:block;padding:8px;border:1px solid #DDD;border-radius:6px;">
            @foreach($paymentMethods as $method)<option value="{{ $method->code }}">{{ $method->label_fr }}</option>@endforeach
        </select>
    </label>
    <button type="submit" style="padding:9px 20px;border:none;border-radius:8px;background:#2E7D32;color:white;font-size:.8rem;font-weight:600;cursor:pointer;">Remettre au patient</button>
</form>
@else
<p style="font-size:.85rem;color:#757575;">{{ $order->status === 'delivered' ? 'Commande remise le '.$order->delivered_at?->format('d/m/Y H:i').'.' : 'Commande annulée.' }}</p>
@endif
@endif
@endsection
