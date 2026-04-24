@extends('layouts.dashboard')

@section('env-name', 'HOSTO')
@section('env-color', '#388E3C')
@section('env-color-dark', '#2E7D32')
@section('title', 'Mon espace')
@section('page-title', 'Mon espace sante')
@section('user-role', 'Patient')

@section('sidebar-nav')
@include('compte.partials.sidebar', ['active' => 'dashboard'])
@endsection

@section('styles')
<style>
    /* Header vert avec recherche */
    .dash-header {
        background:linear-gradient(135deg, #2E7D32, #43A047);
        border-radius:14px; padding:20px; margin-bottom:20px; color:white;
    }
    .dash-header-top { display:flex; align-items:center; gap:12px; margin-bottom:4px; }
    .dash-header-name { font-size:1rem; font-weight:600; flex:1; }
    .dash-header-avatar {
        width:36px; height:36px; border-radius:50%; border:2px solid rgba(255,255,255,.4);
        background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center;
        overflow:hidden; flex-shrink:0;
    }
    .dash-header-avatar img { width:100%; height:100%; object-fit:cover; }
    .dash-search-bar {
        display:flex; align-items:center; gap:8px; background:white; border-radius:100px;
        padding:8px 16px; margin-top:12px;
    }
    .dash-search-bar input {
        flex:1; border:none; outline:none; font-family:Poppins,sans-serif; font-size:.82rem; color:#424242;
    }
    .dash-search-bar svg { flex-shrink:0; }

    /* Services 2 colonnes */
    .dash-services { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-bottom:16px; }
    .dash-service-card {
        background:white; border:2px solid #E8F5E9; border-radius:14px; padding:18px 12px;
        text-align:center; text-decoration:none; color:#1B2A1B; transition:all .2s;
    }
    .dash-service-card:hover { border-color:#388E3C; box-shadow:0 4px 12px rgba(56,142,60,.1); }
    .dash-svc-icon {
        width:44px; height:44px; margin:0 auto 8px; display:flex; align-items:center; justify-content:center;
    }
    .dash-svc-icon img, .dash-svc-icon svg { width:40px; height:40px; }
    .dash-svc-label { font-size:.78rem; font-weight:600; color:#388E3C; }

    /* Compteurs 4 colonnes */
    .dash-counters { display:grid; grid-template-columns:repeat(4, 1fr); gap:8px; margin-bottom:16px; }
    .dash-counter {
        border-radius:10px; padding:12px 8px; text-align:center;
    }
    .dash-counter-val { font-size:1.5rem; font-weight:800; }
    .dash-counter-lbl { font-size:.6rem; font-weight:600; line-height:1.2; margin-top:2px; }

    /* Bandeau info */
    .dash-info-banner {
        background:#FFF8E1; border:1px solid #FFE082; border-radius:10px;
        padding:10px 14px; margin-bottom:16px; font-size:.78rem; color:#F57F17;
        display:flex; align-items:center; gap:8px;
    }

    /* Acces rapides grille 2x2 */
    .dash-quick-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-bottom:20px; }
    .dash-quick-item {
        background:white; border:1px solid #EEE; border-radius:14px; padding:20px 14px;
        text-align:center; text-decoration:none; color:#1B2A1B; transition:all .2s;
    }
    .dash-quick-item:hover { border-color:#C8E6C9; background:#FAFAFA; }
    .dash-quick-icon {
        width:50px; height:50px; margin:0 auto 8px; display:flex; align-items:center; justify-content:center;
    }
    .dash-quick-icon svg { width:36px; height:36px; stroke:#388E3C; }
    .dash-quick-name { font-size:.78rem; font-weight:600; color:#1B2A1B; }

    /* Completion banner */
    .dash-completion {
        background:linear-gradient(135deg,#E8F5E9,#C8E6C9); border:1px solid #A5D6A7;
        border-radius:14px; padding:16px 20px; margin-bottom:16px;
        display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
    }

    @media(max-width:900px) {
        .dash-quick-grid { grid-template-columns:repeat(2, 1fr); }
    }
    @media(max-width:500px) {
        .dash-counters { grid-template-columns:repeat(2, 1fr); }
    }
</style>
@endsection

@section('content')
@php
    $user = auth()->user();
    $pct = $user->profileCompletionPercent();
    $rdvCount = \App\Modules\RendezVous\Models\Appointment::where('patient_id', $user->id)->whereIn('status', ['pending', 'confirmed'])->count();
    $consultCount = \App\Modules\Pro\Models\Consultation::where('patient_id', $user->id)->count();
@endphp

{{-- Profile completion --}}
@if($pct < 100)
<div class="dash-completion">
    <div>
        <div style="font-size:.85rem;font-weight:600;color:#1B5E20;">Completez votre profil — {{ $pct }}%</div>
        <div style="background:rgba(255,255,255,.5);border-radius:100px;height:5px;overflow:hidden;margin-top:6px;width:200px;">
            <div style="height:100%;width:{{ $pct }}%;background:#388E3C;border-radius:100px;"></div>
        </div>
    </div>
    <a href="{{ route('compte.complete-profile') }}" style="padding:8px 18px;background:#388E3C;color:white;border-radius:100px;font-size:.75rem;font-weight:600;text-decoration:none;">Completer</a>
</div>
@endif

{{-- Verification alerts --}}
@if(!$user->email_verified_at)
<div class="dash-info-banner">
    <span>&#9888;</span> Verifiez votre <a href="/verification" style="color:#F57F17;font-weight:600;margin-left:4px;">adresse email</a>.
</div>
@elseif(!$user->phone_verified_at)
<div class="dash-info-banner">
    <span>&#9888;</span> Verifiez votre <a href="/verification" style="color:#F57F17;font-weight:600;margin-left:4px;">telephone</a> pour teleconsulter et prendre RDV.
</div>
@endif

{{-- Header vert + recherche --}}
<div class="dash-header">
    <div class="dash-header-top">
        <div class="dash-header-name">Bonjour, {{ explode(' ', $user->name)[0] }}</div>
        <div class="dash-header-avatar">
            @if($user->profile_photo_path)
                <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="">
            @else
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            @endif
        </div>
    </div>
    <div class="dash-search-bar">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#BDBDBD" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input type="text" id="dashSearch" placeholder="Trouvez un Medecin ou Medicament..." onkeydown="if(event.key==='Enter')goSearch()">
    </div>
</div>

{{-- Services : Consultation en ligne + Prise de rendez-vous --}}
<div class="dash-services" style="grid-template-columns:1fr 1fr;">
    <a href="/annuaire/medecins" class="dash-service-card">
        <div class="dash-svc-icon">
            <img src="/images/icons/icon-medecin.png" alt="Consultation">
        </div>
        <div class="dash-svc-label">Consultation en ligne</div>
    </a>
    <a href="/annuaire" class="dash-service-card">
        <div class="dash-svc-icon">
            <img src="/images/icons/icon-rendez-vous.png" alt="Rendez-vous">
        </div>
        <div class="dash-svc-label">Prise de Rendez-vous</div>
    </a>
</div>

{{-- Compteurs : Medecins en ligne, RDV en attente, Traitement en cours, Paiement en attente --}}
<div class="dash-counters">
    <div class="dash-counter" style="background:#E8F5E9;">
        <div class="dash-counter-val" style="color:#2E7D32;">{{ $consultCount ?: '00' }}</div>
        <div class="dash-counter-lbl" style="color:#2E7D32;">Medecins<br>En ligne</div>
    </div>
    <div class="dash-counter" style="background:#E8F5E9;">
        <div class="dash-counter-val" style="color:#2E7D32;">{{ str_pad((string) $rdvCount, 2, '0', STR_PAD_LEFT) }}</div>
        <div class="dash-counter-lbl" style="color:#2E7D32;">Rendez-vous<br>en attente</div>
    </div>
    <div class="dash-counter" style="background:#FFF3E0;">
        <div class="dash-counter-val" style="color:#E65100;">00</div>
        <div class="dash-counter-lbl" style="color:#E65100;">Traitement<br>en cours</div>
    </div>
    <div class="dash-counter" style="background:#FFF3E0;">
        <div class="dash-counter-val" style="color:#E65100;">00</div>
        <div class="dash-counter-lbl" style="color:#E65100;">Paiement<br>en attente</div>
    </div>
</div>

{{-- Espace publicitaire --}}
<div style="background:#F5F5F5;border:1px solid #EEE;border-radius:10px;padding:24px;margin-bottom:16px;text-align:center;">
    <span style="font-size:.82rem;font-weight:600;color:#BDBDBD;text-transform:uppercase;letter-spacing:2px;">PUB</span>
</div>

{{-- Acces rapides 2x2 : Pharmacie, Hopitale, Soins a domicile, Evacuation/Ambulance --}}
<div class="dash-quick-grid">
    <a href="/annuaire?type=pharmacie" class="dash-quick-item">
        <div class="dash-quick-icon">
            <img src="/images/icons/icon-pharamcie.png" alt="Pharmacie" style="width:50px;height:50px;">
        </div>
        <div class="dash-quick-name">Pharmacie</div>
    </a>
    <a href="/annuaire?type=hopital-general" class="dash-quick-item">
        <div class="dash-quick-icon">
            <img src="/images/icons/icon-hopitaux.png" alt="Hopital" style="width:50px;height:50px;">
        </div>
        <div class="dash-quick-name">Hopitale</div>
    </a>
    <a href="#" class="dash-quick-item">
        <div class="dash-quick-icon">
            <img src="/images/icons/icon-soin-a-domicile.png" alt="Soins a domicile" style="width:50px;height:50px;">
        </div>
        <div class="dash-quick-name">Soins a domicile</div>
    </a>
    <a href="#" class="dash-quick-item">
        <div class="dash-quick-icon">
            <img src="/images/icons/icon-ambulance.png" alt="Ambulance" style="width:50px;height:50px;">
        </div>
        <div class="dash-quick-name">Evacuation / Ambulance</div>
    </a>
    <a href="/examens" class="dash-quick-item">
        <div class="dash-quick-icon">
            <img src="/images/icons/icon-labo.png" alt="Examens" style="width:50px;height:50px;">
        </div>
        <div class="dash-quick-name">Examens</div>
    </a>
    <a href="#" class="dash-quick-item" style="border-color:#BBDEFB;background:#F5F9FF;">
        <div class="dash-quick-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#1565C0" stroke-width="1.5" style="width:40px;height:40px;"><path d="M19 5c-1.5 0-2.8 1.4-3 2-3.5-1.4-11-.3-11 4 0 4.5 8 10 14 12.5 6-2.5 14-8 14-12.5 0-4.3-7.5-5.4-11-4-.2-.6-1.5-2-3-2z" transform="scale(.85) translate(1,1)"/><path d="M12 8v8M8 12h8"/></svg>
        </div>
        <div class="dash-quick-name" style="color:#1565C0;">Epargne Sante</div>
    </a>
</div>

<script>
function goSearch() {
    const q = document.getElementById('dashSearch').value.trim();
    if (q) window.location.href = '/annuaire/medecins?q=' + encodeURIComponent(q);
}
</script>
@endsection
