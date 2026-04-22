@extends('layouts.dashboard')

@section('env-name', 'HOSTO')
@section('env-color', '#388E3C')
@section('env-color-dark', '#2E7D32')
@section('title', 'Mon espace')
@section('page-title', 'Mon espace sante')
@section('user-role', 'Patient')

@section('sidebar-nav')
<a href="/compte" class="active">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
    Tableau de bord
</a>
<a href="/compte/profil/completer">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    Completer mon profil
</a>
<a href="/annuaire">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
    Annuaire
</a>
<div class="sidebar-section">A venir</div>
<a href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
    Mes rendez-vous
</a>
<a href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
    Mes ordonnances
</a>
<a href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
    Mon dossier medical
</a>
@endsection

@section('content')
@php $user = auth()->user(); @endphp

{{-- Profile completion banner --}}
@php $pct = $user->profileCompletionPercent(); @endphp
@if($pct < 100)
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <div>
            <div style="font-size:.88rem;font-weight:600;color:#1B2A1B;">Completez votre profil</div>
            <div style="font-size:.78rem;color:#757575;">Remplissez vos informations pour profiter de tous les services.</div>
        </div>
        <a href="{{ route('compte.complete-profile') }}" style="padding:8px 18px;background:#388E3C;color:white;border-radius:100px;font-size:.78rem;font-weight:600;text-decoration:none;white-space:nowrap;">Completer</a>
    </div>
    <div style="background:#EEE;border-radius:100px;height:6px;overflow:hidden;">
        <div style="height:100%;width:{{ $pct }}%;background:linear-gradient(90deg,#66BB6A,#388E3C);border-radius:100px;transition:width .5s;"></div>
    </div>
    <div style="font-size:.72rem;color:#757575;margin-top:4px;">{{ $pct }}% complete</div>
</div>
@endif

@if(!$user->email_verified_at)
<div style="background:#FFF3E0;border:1px solid #FFB74D;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:12px;">
    <span style="font-size:1.2rem;">&#9888;</span>
    <div>
        <div style="font-size:.85rem;font-weight:600;color:#E65100;">Verifiez votre adresse email</div>
        <div style="font-size:.78rem;color:#BF360C;">Pour acceder a toutes les fonctionnalites, <a href="{{ route('verification.notice') }}" style="color:#E65100;font-weight:600;">verifiez votre compte</a>.</div>
    </div>
</div>
@elseif(!$user->phone_verified_at)
<div style="background:#FFF3E0;border:1px solid #FFB74D;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:12px;">
    <span style="font-size:1.2rem;">&#9888;</span>
    <div>
        <div style="font-size:.85rem;font-weight:600;color:#E65100;">Verifiez votre numero de telephone</div>
        <div style="font-size:.78rem;color:#BF360C;">La verification du telephone est obligatoire pour prendre un rendez-vous, utiliser la teleconsultation ou acheter des medicaments. <a href="{{ route('compte.complete-profile') }}" style="color:#E65100;font-weight:600;">Completer mon profil</a>.</div>
    </div>
</div>
@endif

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;margin-bottom:32px;">
    <div style="background:white;border-radius:14px;padding:24px;border:1px solid #EEE;">
        <div style="font-size:.78rem;color:#757575;margin-bottom:4px;">Bienvenue</div>
        <div style="font-size:1.2rem;font-weight:700;color:#1B2A1B;">{{ $user->name }}</div>
    </div>
    <div style="background:white;border-radius:14px;padding:24px;border:1px solid #EEE;">
        <div style="font-size:.78rem;color:#757575;margin-bottom:4px;">Rendez-vous</div>
        <div style="font-size:1.2rem;font-weight:700;color:#388E3C;">0</div>
        <div style="font-size:.72rem;color:#757575;">a venir</div>
    </div>
    <div style="background:white;border-radius:14px;padding:24px;border:1px solid #EEE;">
        <div style="font-size:.78rem;color:#757575;margin-bottom:4px;">Ordonnances</div>
        <div style="font-size:1.2rem;font-weight:700;color:#388E3C;">0</div>
        <div style="font-size:.72rem;color:#757575;">en cours</div>
    </div>
</div>
<div style="background:white;border-radius:14px;padding:32px;border:1px solid #EEE;text-align:center;color:#757575;">
    <p style="font-size:.9rem;">Votre espace patient est en cours de construction.</p>
    <p style="font-size:.82rem;margin-top:8px;">Les fonctionnalites de rendez-vous, dossier medical et ordonnances seront bientot disponibles.</p>
    <a href="/annuaire" style="display:inline-block;margin-top:16px;padding:10px 24px;background:#388E3C;color:white;border-radius:100px;font-size:.85rem;font-weight:600;">Explorer l'annuaire</a>
</div>
@endsection
