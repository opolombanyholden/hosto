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
    {{ auth()->user()->isProfileComplete() ? 'Mon profil' : 'Completer mon profil' }}
</a>
<a href="/compte/rendez-vous">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
    Mes rendez-vous
</a>
<a href="/compte/dossier-medical">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
    Mon dossier medical
</a>
<div class="sidebar-section">Explorer</div>
<a href="/annuaire">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
    Structures de sante
</a>
<a href="/annuaire/medecins">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    Medecins
</a>
<a href="/medicaments">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.5 1.5H8A6.5 6.5 0 0 0 1.5 8v0a6.5 6.5 0 0 0 6.5 6.5h2.5"/><path d="M13.5 1.5H16A6.5 6.5 0 0 1 22.5 8v0A6.5 6.5 0 0 1 16 14.5h-2.5"/></svg>
    Medicaments
</a>
<a href="/examens">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
    Examens
</a>
@endsection

@section('styles')
<style>
    .dash-search { background:white; border:1px solid #EEE; border-radius:14px; padding:16px 20px; margin-bottom:20px; display:flex; gap:10px; }
    .dash-search input { flex:1; padding:10px 14px; border:2px solid #EEE; border-radius:10px; font-family:Poppins,sans-serif; font-size:.85rem; outline:none; }
    .dash-search input:focus { border-color:#388E3C; }
    .dash-search button { padding:10px 20px; background:#388E3C; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:600; cursor:pointer; }

    .dash-services { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-bottom:24px; }
    .dash-service { background:white; border:1px solid #EEE; border-radius:14px; padding:20px 14px; text-align:center; text-decoration:none; color:#1B2A1B; transition:all .2s; }
    .dash-service:hover { border-color:#C8E6C9; box-shadow:0 4px 16px rgba(56,142,60,.08); transform:translateY(-2px); }
    .dash-service-icon { width:48px; height:48px; border-radius:14px; display:inline-flex; align-items:center; justify-content:center; margin-bottom:10px; }
    .dash-service-icon svg { width:24px; height:24px; }
    .dash-service-label { font-size:.78rem; font-weight:600; line-height:1.3; }

    .dash-counters { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-bottom:24px; }
    .dash-counter { background:white; border:1px solid #EEE; border-radius:14px; padding:16px; }
    .dash-counter-value { font-size:1.5rem; font-weight:700; color:#388E3C; }
    .dash-counter-label { font-size:.72rem; color:#757575; margin-top:2px; }
    .dash-counter-sub { font-size:.65rem; color:#BDBDBD; }

    .dash-quick { display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-bottom:24px; }
    .dash-quick-card { background:white; border:1px solid #EEE; border-radius:14px; padding:16px; display:flex; align-items:center; gap:12px; text-decoration:none; color:#1B2A1B; transition:all .2s; }
    .dash-quick-card:hover { border-color:#C8E6C9; background:#FAFAFA; }
    .dash-quick-icon { width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .dash-quick-label { font-size:.82rem; font-weight:600; }
    .dash-quick-desc { font-size:.68rem; color:#757575; }

    .dash-section-title { font-size:.82rem; font-weight:600; color:#757575; text-transform:uppercase; letter-spacing:1px; margin-bottom:10px; }

    @media(max-width:900px) { .dash-services { grid-template-columns:repeat(2, 1fr); } .dash-counters { grid-template-columns:repeat(2, 1fr); } .dash-quick { grid-template-columns:1fr; } }
    @media(max-width:500px) { .dash-services { grid-template-columns:repeat(2, 1fr); } .dash-counters { grid-template-columns:repeat(2, 1fr); } }
</style>
@endsection

@section('content')
@php
    $user = auth()->user();
    $pct = $user->profileCompletionPercent();
    $rdvCount = \App\Modules\RendezVous\Models\Appointment::where('patient_id', $user->id)->whereIn('status', ['pending', 'confirmed'])->count();
    $consultCount = \App\Modules\Pro\Models\Consultation::where('patient_id', $user->id)->count();
@endphp

{{-- Profile completion banner --}}
@if($pct < 100)
<div style="background:linear-gradient(135deg,#E8F5E9,#C8E6C9);border:1px solid #A5D6A7;border-radius:14px;padding:20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
    <div>
        <div style="font-size:.88rem;font-weight:600;color:#1B5E20;">Completez votre profil — {{ $pct }}%</div>
        <div style="font-size:.78rem;color:#2E7D32;">Remplissez vos informations pour profiter de tous les services.</div>
        <div style="background:rgba(255,255,255,.5);border-radius:100px;height:6px;overflow:hidden;margin-top:8px;max-width:300px;">
            <div style="height:100%;width:{{ $pct }}%;background:#388E3C;border-radius:100px;"></div>
        </div>
    </div>
    <a href="{{ route('compte.complete-profile') }}" style="padding:10px 20px;background:#388E3C;color:white;border-radius:100px;font-size:.78rem;font-weight:600;text-decoration:none;white-space:nowrap;">Completer</a>
</div>
@endif

{{-- Verification alerts --}}
@if(!$user->email_verified_at)
<div style="background:#FFF3E0;border:1px solid #FFB74D;border-radius:12px;padding:14px 20px;margin-bottom:16px;display:flex;align-items:center;gap:10px;font-size:.82rem;">
    <span style="font-size:1.1rem;">&#9888;</span>
    <span style="color:#E65100;">Verifiez votre <a href="/verification" style="color:#E65100;font-weight:600;">adresse email</a> pour acceder a toutes les fonctionnalites.</span>
</div>
@elseif(!$user->phone_verified_at)
<div style="background:#FFF3E0;border:1px solid #FFB74D;border-radius:12px;padding:14px 20px;margin-bottom:16px;display:flex;align-items:center;gap:10px;font-size:.82rem;">
    <span style="font-size:1.1rem;">&#9888;</span>
    <span style="color:#E65100;">Verifiez votre <a href="/verification" style="color:#E65100;font-weight:600;">numero de telephone</a> pour prendre des rendez-vous et teleconsulter.</span>
</div>
@endif

{{-- Search bar --}}
<div class="dash-search">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#388E3C" stroke-width="2" style="flex-shrink:0;align-self:center;"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
    <input type="text" id="dashSearch" placeholder="Trouvez un medecin, un medicament, un examen..." onkeydown="if(event.key==='Enter')goSearch()">
    <button onclick="goSearch()">Rechercher</button>
</div>

{{-- Services grid --}}
<div class="dash-section-title">Services</div>
<div class="dash-services">
    <a href="/annuaire" class="dash-service">
        <div class="dash-service-icon" style="background:#E3F2FD;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#1565C0" stroke-width="2"><path d="M15.05 5A5 5 0 0 1 19 8.95M15.05 1A9 9 0 0 1 23 8.94"/><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/></svg>
        </div>
        <div class="dash-service-label">Consultation en ligne</div>
    </a>
    <a href="/annuaire" class="dash-service">
        <div class="dash-service-icon" style="background:#E8F5E9;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#388E3C" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        </div>
        <div class="dash-service-label">Prise de rendez-vous</div>
    </a>
    <a href="#" class="dash-service">
        <div class="dash-service-icon" style="background:#FFEBEE;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#E53935" stroke-width="2"><path d="M5 17h14M5 17l-2 4h18l-2-4M7 17V7a4 4 0 0 1 4-4h2a4 4 0 0 1 4 4v10"/><path d="M12 3v4"/></svg>
        </div>
        <div class="dash-service-label">Ambulance / Evacuation</div>
    </a>
    <a href="#" class="dash-service">
        <div class="dash-service-icon" style="background:#FFF3E0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#E65100" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
        </div>
        <div class="dash-service-label">Soins a domicile</div>
    </a>
</div>

{{-- Activity counters --}}
<div class="dash-section-title">Mon activite</div>
<div class="dash-counters">
    <div class="dash-counter">
        <div class="dash-counter-value">{{ $rdvCount }}</div>
        <div class="dash-counter-label">Rendez-vous</div>
        <div class="dash-counter-sub">en attente</div>
    </div>
    <div class="dash-counter">
        <div class="dash-counter-value">{{ $consultCount }}</div>
        <div class="dash-counter-label">Consultations</div>
        <div class="dash-counter-sub">effectuees</div>
    </div>
    <div class="dash-counter">
        <div class="dash-counter-value" style="color:#E65100;">0</div>
        <div class="dash-counter-label">Paiements</div>
        <div class="dash-counter-sub">en attente</div>
    </div>
    <div class="dash-counter">
        <div class="dash-counter-value" style="color:#1565C0;">0</div>
        <div class="dash-counter-label">Traitements</div>
        <div class="dash-counter-sub">en cours</div>
    </div>
</div>

{{-- Quick access --}}
<div class="dash-section-title">Acces rapide</div>
<div class="dash-quick">
    <a href="/annuaire?type=hopital-general" class="dash-quick-card">
        <div class="dash-quick-icon" style="background:#E8F5E9;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#388E3C" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        </div>
        <div>
            <div class="dash-quick-label">Hopitaux</div>
            <div class="dash-quick-desc">Trouver un hopital</div>
        </div>
    </a>
    <a href="/annuaire?type=pharmacie" class="dash-quick-card">
        <div class="dash-quick-icon" style="background:#E8F5E9;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#388E3C" stroke-width="2"><path d="M12 2v20M2 12h20"/></svg>
        </div>
        <div>
            <div class="dash-quick-label">Pharmacies</div>
            <div class="dash-quick-desc">Trouver une pharmacie</div>
        </div>
    </a>
    <a href="/annuaire?type=laboratoire" class="dash-quick-card">
        <div class="dash-quick-icon" style="background:#E3F2FD;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1565C0" stroke-width="2"><path d="M9 3h6v4l4 8H5l4-8V3z"/><path d="M3 21h18"/></svg>
        </div>
        <div>
            <div class="dash-quick-label">Laboratoires</div>
            <div class="dash-quick-desc">Trouver un laboratoire</div>
        </div>
    </a>
</div>

<script>
function goSearch() {
    const q = document.getElementById('dashSearch').value.trim();
    if (q) window.location.href = '/annuaire/medecins?q=' + encodeURIComponent(q);
}
</script>
@endsection
