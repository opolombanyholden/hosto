@extends('layouts.dashboard')

@section('env-name', 'HOSTO Pro')
@section('env-color', '#1565C0')
@section('env-color-dark', '#0D47A1')
@section('title', 'Mes patients')
@section('page-title', 'Mes patients')
@section('user-role', 'Professionnel de santé')

@section('sidebar-nav')
@include('pro.partials.sidebar', ['active' => 'patients'])
@endsection

@section('content')
@if(!$practitioner)
<div style="background:#FFF3E0;border:1px solid #FFB74D;border-radius:12px;padding:20px;color:#E65100;font-size:.85rem;">
    Aucun profil praticien n'est associé à votre compte. Contactez l'administration.
</div>
@else
<form method="GET" action="/pro/patients" style="display:flex;gap:10px;margin-bottom:18px;">
    <input type="search" name="q" value="{{ $search }}" placeholder="Rechercher par nom ou NIP" aria-label="Rechercher un patient" style="flex:1;padding:10px 14px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;outline:none;">
    <button type="submit" style="padding:10px 22px;border:none;border-radius:8px;background:#1565C0;color:white;font-family:Poppins,sans-serif;font-size:.82rem;font-weight:600;cursor:pointer;">Rechercher</button>
</form>

@forelse($patients as $patient)
<a href="/pro/patients/{{ $patient->uuid }}" style="display:flex;justify-content:space-between;align-items:center;gap:12px;background:white;border:1px solid #EEE;border-radius:14px;padding:14px 18px;margin-bottom:10px;text-decoration:none;">
    <div>
        <div style="font-size:.9rem;font-weight:600;color:#1B2A1B;">{{ $patient->name }}</div>
        <div style="font-size:.78rem;color:#757575;">
            @if($patient->nip)NIP : {{ $patient->nip }}@endif
            @if($patient->date_of_birth) {{ $patient->nip ? '—' : '' }} {{ $patient->date_of_birth->age }} ans @endif
        </div>
    </div>
    <span style="font-size:.78rem;font-weight:600;color:#1565C0;">Ouvrir la fiche</span>
</a>
@empty
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:28px;text-align:center;color:#757575;font-size:.85rem;">
    @if($search !== '')
        Aucun patient ne correspond à « {{ $search }} ».
    @else
        Aucun patient pour le moment. Vos patients apparaissent ici dès le premier rendez-vous ou la première consultation.
    @endif
</div>
@endforelse

<div style="margin-top:14px;">{{ $patients->links() }}</div>
@endif
@endsection
