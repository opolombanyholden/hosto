@extends('layouts.dashboard')
@section('env-name', 'HOSTO Pro')
@section('env-color', '#1565C0')
@section('env-color-dark', '#0D47A1')
@section('user-role', 'Professionnel de santé')
@section('title', 'Ma pharmacie')
@section('page-title', $pharmacy->name)

@section('sidebar-nav')
@include('pro.partials.sidebar', ['active' => 'pharmacie'])
@endsection

@section('content')
<h1 style="font-size:1.25rem;font-weight:700;color:#1B2A1B;margin:0 0 16px;">{{ $pharmacy->name }}</h1>
@include('pro.partials.flash')
<h2 style="font-size:1rem;font-weight:600;color:#1B2A1B;margin:0 0 12px;">Commandes à traiter</h2>
@forelse($orders as $order)
<a href="/pro/pharmacie/commandes/{{ $order->uuid }}" style="display:flex;justify-content:space-between;align-items:center;gap:12px;background:white;border:1px solid #EEE;border-radius:14px;padding:14px 18px;margin-bottom:10px;text-decoration:none;">
    <div>
        <div style="font-size:.9rem;font-weight:600;color:#1B2A1B;">{{ $order->reference }} — {{ $order->patient?->name }}</div>
        <div style="font-size:.78rem;color:#757575;">{{ $order->items->count() }} médicament(s), reçue le {{ $order->created_at?->format('d/m/Y H:i') }}</div>
    </div>
    <span style="padding:4px 12px;border-radius:100px;font-size:.7rem;font-weight:600;background:{{ $order->status === 'pending' ? '#FFF3E0;color:#E65100' : '#E3F2FD;color:#1565C0' }};">{{ $order->status === 'pending' ? 'À préparer' : 'Prête à remettre' }}</span>
</a>
@empty
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:24px;text-align:center;color:#757575;font-size:.85rem;margin-bottom:10px;">Aucune commande à traiter. Les ordonnances envoyées par les patients apparaîtront ici.</div>
@endforelse

<h2 style="font-size:1rem;font-weight:600;color:#1B2A1B;margin:24px 0 12px;">Stock</h2>
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:6px 18px;">
    @forelse($stocks as $stock)
    <form method="POST" action="/pro/pharmacie/stock/{{ $stock->uuid }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:10px 0;border-bottom:1px solid #F5F5F5;margin:0;">
        @csrf
        <div style="flex:1;min-width:190px;font-size:.85rem;color:#1B2A1B;">
            <strong>{{ $stock->medication->dci }}</strong> {{ $stock->medication->strength }}
            @if($stock->quantity_in_stock <= $stock->quantity_min_alert)<span style="color:#C62828;font-size:.72rem;font-weight:600;"> Stock bas</span>@endif
        </div>
        <label style="font-size:.74rem;color:#757575;">Quantité <input type="number" name="quantity_in_stock" min="0" value="{{ $stock->quantity_in_stock }}" style="width:76px;padding:6px;border:1px solid #DDD;border-radius:6px;"></label>
        <label style="font-size:.74rem;color:#757575;">Prix (XAF) <input type="number" name="unit_price" min="0" value="{{ $stock->unit_price }}" style="width:90px;padding:6px;border:1px solid #DDD;border-radius:6px;"></label>
        <button type="submit" style="padding:6px 14px;border:none;border-radius:8px;background:#1565C0;color:white;font-size:.74rem;font-weight:600;cursor:pointer;">Mettre à jour</button>
    </form>
    @empty
    <p style="font-size:.85rem;color:#757575;padding:12px 0;">Votre stock est vide. Ajoutez votre premier médicament ci-dessous.</p>
    @endforelse
</div>

@if($medications->isNotEmpty())
<form method="POST" action="/pro/pharmacie/stock" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin-top:14px;background:white;border:1px solid #EEE;border-radius:14px;padding:14px 18px;">
    @csrf
    <label style="flex:1;min-width:200px;font-size:.74rem;color:#757575;">Médicament
        <select name="medication_id" required style="width:100%;padding:8px;border:1px solid #DDD;border-radius:6px;">
            @foreach($medications as $medication)<option value="{{ $medication->id }}">{{ $medication->dci }} {{ $medication->strength }}</option>@endforeach
        </select>
    </label>
    <label style="font-size:.74rem;color:#757575;">Quantité <input type="number" name="quantity_in_stock" min="0" required style="width:76px;padding:8px;border:1px solid #DDD;border-radius:6px;"></label>
    <label style="font-size:.74rem;color:#757575;">Prix (XAF) <input type="number" name="unit_price" min="0" required style="width:90px;padding:8px;border:1px solid #DDD;border-radius:6px;"></label>
    <button type="submit" style="padding:9px 16px;border:none;border-radius:8px;background:#1565C0;color:white;font-size:.76rem;font-weight:600;cursor:pointer;">Ajouter au stock</button>
</form>
@endif
@endsection
