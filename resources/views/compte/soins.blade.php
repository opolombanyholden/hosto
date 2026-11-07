@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Ordonnances et examens')
@section('page-title', 'Ordonnances et examens')
@section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'soins']) @endsection

@section('content')
@include('pro.partials.flash')
@php
    $card = 'background:white;border:1px solid #EEE;border-radius:14px;padding:14px 18px;margin-bottom:10px;';
    $title = 'font-size:1rem;font-weight:600;color:#1B2A1B;margin:22px 0 10px;';
    $select = 'padding:8px;border:1px solid #DDD;border-radius:8px;font-family:Poppins,sans-serif;font-size:.8rem;';
    $button = 'padding:8px 16px;border:none;border-radius:8px;background:#388E3C;color:white;font-family:Poppins,sans-serif;font-size:.76rem;font-weight:600;cursor:pointer;';
    $orderLabels = ['pending' => 'En préparation', 'dispensed' => 'Prête', 'delivered' => 'Remise', 'cancelled' => 'Annulée'];
    $labLabels = ['received' => 'Reçu par le laboratoire', 'sample_collected' => 'Prélèvement effectué', 'in_progress' => 'Analyse en cours', 'completed' => 'Analyse en cours'];
@endphp

<h2 style="{{ $title }}margin-top:0;">Mes ordonnances</h2>
@forelse($prescriptions as $prescription)
<div style="{{ $card }}">
    <div style="font-size:.88rem;font-weight:600;color:#1B2A1B;">{{ $prescription->reference }} — {{ $prescription->practitioner?->full_name }}</div>
    <ul style="margin:6px 0 10px 18px;font-size:.8rem;color:#424242;">
        @foreach($prescription->items as $item)<li>{{ $item->medication_name }} {{ $item->dosage }} @if($item->posology)— {{ $item->posology }}@endif</li>@endforeach
    </ul>
    @if($pharmacies->isEmpty())
    <p style="font-size:.78rem;color:#757575;margin:0;">Aucune pharmacie ne reçoit encore les ordonnances en ligne.</p>
    @else
    <form method="POST" action="/compte/soins/ordonnances/{{ $prescription->uuid }}/commander" style="display:flex;gap:8px;flex-wrap:wrap;margin:0;">
        @csrf
        <select name="pharmacy_uuid" required aria-label="Choisir une pharmacie" style="{{ $select }}">
            @foreach($pharmacies as $pharmacy)<option value="{{ $pharmacy->uuid }}">{{ $pharmacy->name }}</option>@endforeach
        </select>
        <button type="submit" style="{{ $button }}">Envoyer à cette pharmacie</button>
    </form>
    @endif
</div>
@empty
<div style="{{ $card }}color:#757575;font-size:.84rem;">Aucune ordonnance à envoyer. Vos prochaines ordonnances apparaîtront ici.</div>
@endforelse

@if($orders->isNotEmpty())
<h2 style="{{ $title }}">Mes commandes</h2>
@foreach($orders as $order)
<div style="{{ $card }}display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <div>
        <div style="font-size:.88rem;font-weight:600;color:#1B2A1B;">{{ $order->reference }} — {{ $order->pharmacy?->name }}</div>
        <div style="font-size:.78rem;color:#757575;">{{ $orderLabels[$order->status] ?? $order->status }}@if($order->total_amount) — {{ $order->total_amount }} XAF @endif</div>
        @foreach($order->items->where('is_substituted', true) as $item)
        <div style="font-size:.76rem;color:#E65100;margin-top:2px;">Produit remplacé par {{ $item->medication_name }} : {{ $item->substitution_reason }}</div>
        @endforeach
    </div>
    @if(in_array($order->status, ['pending', 'dispensed'], true))
    <div style="text-align:right;">
        <div style="font-size:.7rem;color:#757575;">Code à donner au retrait</div>
        <div style="font-size:1.15rem;font-weight:700;color:#2E7D32;letter-spacing:.08em;">{{ $order->delivery_code }}</div>
    </div>
    @endif
</div>
@endforeach
@endif

<h2 style="{{ $title }}">Mes examens prescrits</h2>
@forelse($exams as $exam)
<div style="{{ $card }}">
    <div style="font-size:.88rem;font-weight:600;color:#1B2A1B;margin-bottom:8px;">{{ $exam->exam_type }} — {{ $exam->practitioner?->full_name }}</div>
    @if($laboratories->isEmpty())
    <p style="font-size:.78rem;color:#757575;margin:0;">Aucun laboratoire ne reçoit encore les examens en ligne.</p>
    @else
    <form method="POST" action="/compte/soins/examens/{{ $exam->uuid }}/laboratoire" style="display:flex;gap:8px;flex-wrap:wrap;margin:0;">
        @csrf
        <select name="laboratory_uuid" required aria-label="Choisir un laboratoire" style="{{ $select }}">
            @foreach($laboratories as $laboratory)<option value="{{ $laboratory->uuid }}">{{ $laboratory->name }}</option>@endforeach
        </select>
        <button type="submit" style="{{ $button }}">Envoyer à ce laboratoire</button>
    </form>
    @endif
</div>
@empty
<div style="{{ $card }}color:#757575;font-size:.84rem;">Aucun examen à envoyer.</div>
@endforelse

@if($labResults->isNotEmpty())
<h2 style="{{ $title }}">Mes résultats</h2>
@foreach($labResults as $result)
<div style="{{ $card }}">
    <div style="font-size:.88rem;font-weight:600;color:#1B2A1B;">{{ $result->examRequest?->exam_type }} — {{ $result->laboratory?->name }}</div>
    @if(in_array($result->status, ['validated', 'transmitted'], true))
        @foreach($result->items as $line)
        <div style="font-size:.82rem;color:#1B2A1B;margin-top:4px;">{{ $line->test_name }} : <strong>{{ $line->value }}</strong> {{ $line->unit }} @if($line->reference_range)<span style="color:#757575;">(réf. {{ $line->reference_range }})</span>@endif @if(in_array($line->flag, ['H', 'L', 'C']))<span style="color:#C62828;font-weight:600;">{{ ['H' => 'Élevé', 'L' => 'Bas', 'C' => 'Critique'][$line->flag] }}</span>@endif</div>
        @endforeach
        @if($result->conclusion)<p style="font-size:.8rem;color:#424242;margin:6px 0 0;">Conclusion : {{ $result->conclusion }}</p>@endif
    @else
        <div style="font-size:.78rem;color:#757575;">{{ $labLabels[$result->status] ?? $result->status }}</div>
    @endif
</div>
@endforeach
@endif
@endsection
