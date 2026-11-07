@extends('layouts.dashboard')
@section('env-name', 'HOSTO Pro')
@section('env-color', '#1565C0')
@section('env-color-dark', '#0D47A1')
@section('user-role', 'Professionnel de santé')
@section('title', 'Mon laboratoire')
@section('page-title', $laboratory->name)

@section('sidebar-nav')
@include('pro.partials.sidebar', ['active' => 'laboratoire'])
@endsection

@section('content')
<h1 style="font-size:1.25rem;font-weight:700;color:#1B2A1B;margin:0 0 16px;">{{ $laboratory->name }}</h1>
@include('pro.partials.flash')
@php $labels = ['received' => 'À prélever', 'sample_collected' => 'Résultats à saisir', 'completed' => 'À valider']; @endphp
<h2 style="font-size:1rem;font-weight:600;color:#1B2A1B;margin:0 0 12px;">Analyses en attente</h2>
@forelse($analyses as $analysis)
<a href="/pro/laboratoire/analyses/{{ $analysis->uuid }}" style="display:flex;justify-content:space-between;align-items:center;gap:12px;background:white;border:1px solid #EEE;border-radius:14px;padding:14px 18px;margin-bottom:10px;text-decoration:none;">
    <div>
        <div style="font-size:.9rem;font-weight:600;color:#1B2A1B;">{{ $analysis->reference }} — {{ $analysis->patient?->name }}</div>
        <div style="font-size:.78rem;color:#757575;">{{ $analysis->examRequest?->exam_type }}</div>
    </div>
    <span style="padding:4px 12px;border-radius:100px;font-size:.7rem;font-weight:600;background:#E3F2FD;color:#1565C0;">{{ $labels[$analysis->status] ?? $analysis->status }}</span>
</a>
@empty
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:24px;text-align:center;color:#757575;font-size:.85rem;">Aucune analyse en attente. Les examens envoyés par les patients apparaîtront ici.</div>
@endforelse

@if($recent->isNotEmpty())
<h2 style="font-size:1rem;font-weight:600;color:#1B2A1B;margin:24px 0 12px;">Derniers résultats transmis</h2>
@foreach($recent as $analysis)
<a href="/pro/laboratoire/analyses/{{ $analysis->uuid }}" style="display:block;background:white;border:1px solid #EEE;border-radius:12px;padding:12px 16px;margin-bottom:8px;text-decoration:none;font-size:.82rem;color:#1B2A1B;">{{ $analysis->reference }} — {{ $analysis->patient?->name }} — {{ $analysis->examRequest?->exam_type }}</a>
@endforeach
@endif
@endsection
