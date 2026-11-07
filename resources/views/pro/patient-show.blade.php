@extends('layouts.dashboard')

@section('env-name', 'HOSTO Pro')
@section('env-color', '#1565C0')
@section('env-color-dark', '#0D47A1')
@section('title', $patient->name)
@section('page-title', 'Fiche patient')
@section('user-role', 'Professionnel de santé')

@section('sidebar-nav')
@include('pro.partials.sidebar', ['active' => 'patients'])
@endsection

@section('content')
<a href="/pro/patients" style="display:inline-block;margin-bottom:14px;color:#1565C0;font-size:.82rem;text-decoration:none;">Retour à mes patients</a>

<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:18px 20px;margin-bottom:16px;display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;">
    <div>
        <div style="font-size:1.05rem;font-weight:700;color:#1B2A1B;">{{ $patient->name }}</div>
        <div style="font-size:.8rem;color:#757575;margin-top:2px;">
            @if($patient->nip)NIP : {{ $patient->nip }} @endif
            @if($patient->date_of_birth) — {{ $patient->date_of_birth->age }} ans @endif
            @if($patient->blood_group) — Groupe {{ $patient->blood_group }} @endif
        </div>
    </div>
    <div>
        @if($grant)
        <span style="padding:5px 14px;border-radius:100px;font-size:.72rem;font-weight:600;background:#E8F5E9;color:#2E7D32;">Dossier partagé par le patient</span>
        @else
        <span style="padding:5px 14px;border-radius:100px;font-size:.72rem;font-weight:600;background:#F5F5F5;color:#616161;">Dossier non partagé</span>
        @endif
    </div>
</div>

@if($grant)
<section style="background:white;border:1px solid #EEE;border-radius:14px;padding:16px 20px;margin-bottom:16px;">
    <h2 style="font-size:.95rem;font-weight:600;color:#1565C0;margin:0 0 8px;">Informations médicales partagées</h2>
    <p style="font-size:.82rem;color:#1B2A1B;margin:0 0 4px;"><strong>Allergies :</strong> {{ $patient->allergies ?: 'Non renseigné' }}</p>
    <p style="font-size:.82rem;color:#1B2A1B;margin:0 0 4px;"><strong>Maladies chroniques :</strong> {{ $patient->chronic_conditions ?: 'Non renseigné' }}</p>
    <p style="font-size:.82rem;color:#1B2A1B;margin:0;"><strong>Traitements en cours :</strong> {{ $patient->current_medications ?: 'Non renseigné' }}</p>
    <p style="font-size:.72rem;color:#757575;margin:10px 0 0;">Cette consultation du dossier est enregistrée et visible par le patient.</p>
</section>
@endif

<section style="margin-bottom:16px;">
    <h2 style="font-size:.95rem;font-weight:600;color:#1B2A1B;margin:0 0 10px;">Mes consultations</h2>
    @forelse($consultations as $c)
    <a href="/pro/consultations/{{ $c->uuid }}" style="display:block;background:white;border:1px solid #EEE;border-radius:12px;padding:12px 16px;margin-bottom:8px;text-decoration:none;">
        <div style="font-size:.86rem;font-weight:600;color:#1B2A1B;">{{ $c->motif }}</div>
        <div style="font-size:.76rem;color:#757575;">{{ $c->created_at?->format('d/m/Y') }} — {{ $c->structure?->name }}</div>
    </a>
    @empty
    <p style="font-size:.82rem;color:#757575;">Aucune consultation enregistrée pour ce patient.</p>
    @endforelse
</section>

@if($grant)
<section style="margin-bottom:16px;">
    <h2 style="font-size:.95rem;font-weight:600;color:#1B2A1B;margin:0 0 10px;">Historique chez d'autres praticiens</h2>
    @forelse($sharedConsultations as $c)
    <div style="background:white;border:1px solid #EEE;border-radius:12px;padding:12px 16px;margin-bottom:8px;">
        <div style="font-size:.86rem;font-weight:600;color:#1B2A1B;">{{ $c->motif }}</div>
        <div style="font-size:.76rem;color:#757575;">{{ $c->created_at?->format('d/m/Y') }} — {{ $c->practitioner?->full_name }} — {{ $c->structure?->name }}</div>
        @if($c->diagnostic)<div style="font-size:.8rem;color:#1B2A1B;margin-top:4px;">Diagnostic : {{ $c->diagnostic }}</div>@endif
    </div>
    @empty
    <p style="font-size:.82rem;color:#757575;">Aucune consultation chez un autre praticien.</p>
    @endforelse
</section>
@endif

<section>
    <h2 style="font-size:.95rem;font-weight:600;color:#1B2A1B;margin:0 0 10px;">Rendez-vous</h2>
    @forelse($appointments as $rdv)
    <div style="background:white;border:1px solid #EEE;border-radius:12px;padding:12px 16px;margin-bottom:8px;font-size:.8rem;color:#1B2A1B;">
        {{ $rdv->timeSlot?->date?->format('d/m/Y') }} à {{ $rdv->timeSlot ? substr($rdv->timeSlot->start_time, 0, 5) : '' }} — {{ $rdv->structure?->name }}
        @if($rdv->reason)<span style="color:#757575;"> — {{ $rdv->reason }}</span>@endif
    </div>
    @empty
    <p style="font-size:.82rem;color:#757575;">Aucun rendez-vous.</p>
    @endforelse
</section>
@endsection
