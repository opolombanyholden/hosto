@extends('layouts.dashboard')

@section('env-name', 'HOSTO')
@section('env-color', '#388E3C')
@section('env-color-dark', '#2E7D32')
@section('title', 'Consultation du ' . $consultation->created_at->format('d/m/Y'))
@section('page-title', 'Detail de la consultation')
@section('user-role', 'Patient')

@section('sidebar-nav')
<a href="/compte"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg> Tableau de bord</a>
<a href="/compte/rendez-vous"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg> Mes rendez-vous</a>
<a href="/compte/dossier-medical" class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> Dossier medical</a>
<a href="/compte/profil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Mon profil</a>
@endsection

@section('content')
<a href="/compte/dossier-medical" style="font-size:.82rem;color:#388E3C;text-decoration:none;display:inline-block;margin-bottom:16px;">&larr; Retour au dossier</a>

{{-- Header --}}
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:20px;">
    <div style="font-size:1rem;font-weight:700;color:#1B2A1B;">{{ $consultation->motif ?: 'Consultation' }}</div>
    <div style="font-size:.82rem;color:#757575;margin-top:4px;">{{ $consultation->practitioner?->full_name }} — {{ $consultation->structure?->name }}</div>
    <div style="font-size:.78rem;color:#757575;">{{ $consultation->created_at->format('d/m/Y H:i') }}</div>
</div>

{{-- Diagnostic --}}
@if($consultation->diagnostic)
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:16px;">
    <div style="font-size:.72rem;font-weight:600;color:#388E3C;margin-bottom:6px;">DIAGNOSTIC @if($consultation->diagnostic_code)<span style="background:#E8F5E9;padding:2px 8px;border-radius:4px;font-size:.68rem;">{{ $consultation->diagnostic_code }}</span>@endif</div>
    <p style="font-size:.88rem;color:#424242;">{{ $consultation->diagnostic }}</p>
    @if($consultation->conduite_a_tenir)
    <div style="margin-top:12px;"><div style="font-size:.72rem;font-weight:600;color:#388E3C;margin-bottom:4px;">CONDUITE A TENIR</div><p style="font-size:.85rem;color:#424242;white-space:pre-line;">{{ $consultation->conduite_a_tenir }}</p></div>
    @endif
</div>
@endif

{{-- Examens --}}
@if($consultation->examRequests->isNotEmpty())
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:16px;">
    <div style="font-size:.72rem;font-weight:600;color:#E65100;margin-bottom:10px;">EXAMENS PRESCRITS</div>
    @foreach($consultation->examRequests as $ex)
    <div style="padding:10px 0;border-bottom:1px solid #F5F5F5;display:flex;justify-content:space-between;align-items:center;">
        <div>
            <div style="font-size:.88rem;font-weight:600;color:#1B2A1B;">{{ $ex->exam_type }}</div>
            @if($ex->clinical_info)<div style="font-size:.75rem;color:#757575;">{{ $ex->clinical_info }}</div>@endif
        </div>
        <span style="padding:2px 10px;border-radius:100px;font-size:.65rem;font-weight:600;background:{{ $ex->status==='completed' ? '#E8F5E9;color:#2E7D32' : '#FFF3E0;color:#E65100' }};">{{ $ex->status }}</span>
    </div>
    @endforeach
</div>
@endif

{{-- Soins --}}
@if($consultation->careActs->isNotEmpty())
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:16px;">
    <div style="font-size:.72rem;font-weight:600;color:#2E7D32;margin-bottom:10px;">SOINS</div>
    @foreach($consultation->careActs as $care)
    <div style="padding:10px 0;border-bottom:1px solid #F5F5F5;">
        <div style="display:flex;justify-content:space-between;"><div style="font-size:.88rem;font-weight:600;color:#1B2A1B;">{{ ucfirst($care->care_type) }}</div><span style="padding:2px 10px;border-radius:100px;font-size:.65rem;font-weight:600;background:{{ $care->status==='performed' ? '#E8F5E9;color:#2E7D32' : '#FFF3E0;color:#E65100' }};">{{ $care->status }}</span></div>
        <div style="font-size:.82rem;color:#424242;margin-top:2px;">{{ $care->description }}</div>
    </div>
    @endforeach
</div>
@endif

{{-- Traitements --}}
@if($consultation->treatments->isNotEmpty())
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:16px;">
    <div style="font-size:.72rem;font-weight:600;color:#7B1FA2;margin-bottom:10px;">TRAITEMENTS</div>
    @foreach($consultation->treatments as $tx)
    <div style="padding:10px 0;border-bottom:1px solid #F5F5F5;">
        <div style="font-size:.88rem;font-weight:600;color:#1B2A1B;">{{ ucfirst($tx->type) }}</div>
        <div style="font-size:.82rem;color:#424242;margin-top:2px;">{{ $tx->description }}</div>
        @if($tx->frequency)<div style="font-size:.72rem;color:#757575;">Frequence : {{ $tx->frequency }}</div>@endif
        @if($tx->duration)<div style="font-size:.72rem;color:#757575;">Duree : {{ $tx->duration }}</div>@endif
    </div>
    @endforeach
</div>
@endif

{{-- Ordonnances --}}
@if($consultation->prescriptions->isNotEmpty())
@foreach($consultation->prescriptions as $rx)
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:16px;">
    <div style="font-size:.72rem;font-weight:600;color:#1565C0;margin-bottom:10px;">ORDONNANCE {{ $rx->reference }}</div>
    <div style="font-size:.72rem;color:#757575;margin-bottom:10px;">Valide jusqu'au {{ $rx->valid_until?->format('d/m/Y') }}</div>
    @foreach($rx->items as $item)
    <div style="padding:8px 0;border-bottom:1px solid #F5F5F5;">
        <div style="font-size:.88rem;font-weight:600;color:#1B2A1B;">{{ $item->medication_name }} @if($item->dosage)<span style="font-weight:400;color:#757575;">{{ $item->dosage }}</span>@endif</div>
        @if($item->posology)<div style="font-size:.78rem;color:#424242;">{{ $item->posology }}</div>@endif
        @if($item->duration)<div style="font-size:.72rem;color:#757575;">Duree : {{ $item->duration }}</div>@endif
        @if($item->instructions)<div style="font-size:.72rem;color:#757575;font-style:italic;">{{ $item->instructions }}</div>@endif
    </div>
    @endforeach
</div>
@endforeach
@endif
@endsection
