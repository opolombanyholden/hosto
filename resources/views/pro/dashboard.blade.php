@extends('layouts.dashboard')

@section('env-name', 'HOSTO Pro')
@section('env-color', '#1565C0')
@section('env-color-dark', '#0D47A1')
@section('title', 'Espace professionnel')
@section('page-title', 'Espace professionnel')
@section('user-role', 'Professionnel de sante')

@section('sidebar-nav')
@include('pro.partials.sidebar', ['active' => 'dashboard'])
@endsection

@section('content')
@php $user = auth()->user(); @endphp

@if(!$user->email_verified_at || !$user->phone_verified_at)
<div style="background:#FFF3E0;border:1px solid #FFB74D;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:12px;">
    <span style="font-size:1.2rem;">&#9888;</span>
    <div>
        <div style="font-size:.85rem;font-weight:600;color:#E65100;">Vérification requise</div>
        <div style="font-size:.78rem;color:#BF360C;">Vérifiez votre email et téléphone pour soumettre votre dossier professionnel.</div>
    </div>
</div>
@elseif($user->pro_validation_status === null || $user->pro_validation_status === 'pending')
<div style="background:#E3F2FD;border:1px solid #64B5F6;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:12px;">
    <span style="font-size:1.2rem;">&#128203;</span>
    <div>
        <div style="font-size:.85rem;font-weight:600;color:#1565C0;">Soumettez votre dossier professionnel</div>
        <div style="font-size:.78rem;color:#0D47A1;">Pour activer votre compte, soumettez vos documents justificatifs (diplome, autorisation d'exercer, piece d'identite).</div>
    </div>
</div>
@elseif($user->pro_validation_status === 'submitted')
<div style="background:#E3F2FD;border:1px solid #64B5F6;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:12px;">
    <span style="font-size:1.2rem;">&#9203;</span>
    <div>
        <div style="font-size:.85rem;font-weight:600;color:#1565C0;">Dossier en cours de validation</div>
        <div style="font-size:.78rem;color:#0D47A1;">Nos equipes examinent votre dossier. Vous serez notifie des que la validation sera effectuee.</div>
    </div>
</div>
@elseif($user->pro_validation_status === 'rejected')
<div style="background:#FFEBEE;border:1px solid #EF9A9A;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:12px;">
    <span style="font-size:1.2rem;">&#10060;</span>
    <div>
        <div style="font-size:.85rem;font-weight:600;color:#C62828;">Dossier rejete</div>
        <div style="font-size:.78rem;color:#B71C1C;">{{ $user->pro_rejection_reason ?: 'Veuillez corriger votre dossier et le re-soumettre.' }}</div>
    </div>
</div>
@elseif($user->pro_validation_status === 'approved')
@if(session('pro_status'))
<div style="background:#E8F5E9;border:1px solid #81C784;border-radius:12px;padding:16px 20px;margin-bottom:20px;font-size:.85rem;color:#2E7D32;">{{ session('pro_status') }}</div>
@endif
@endif

@if(session('success'))
<div style="background:#E8F5E9;border:1px solid #81C784;border-radius:12px;padding:12px 16px;margin-bottom:16px;font-size:.85rem;color:#2E7D32;">{{ session('success') }}</div>
@endif
@if(session('error'))
<div style="background:#FFEBEE;border:1px solid #EF9A9A;border-radius:12px;padding:12px 16px;margin-bottom:16px;font-size:.85rem;color:#C62828;">{{ session('error') }}</div>
@endif

@if(!$practitioner)
<div style="background:#FFF3E0;border:1px solid #FFB74D;border-radius:12px;padding:20px;color:#E65100;font-size:.85rem;">
    Aucun profil praticien n'est associé à votre compte. Contactez l'administration.
</div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin-bottom:22px;">
    @foreach([
        ['Rendez-vous aujourd\'hui', $stats['today']],
        ['À confirmer', $stats['pending']],
        ['Consultations ce mois-ci', $stats['consultations']],
        ['Patients suivis', $stats['patients']],
    ] as [$label, $value])
    <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:16px 18px;">
        <div style="font-size:1.6rem;font-weight:700;color:#1565C0;line-height:1.1;">{{ $value }}</div>
        <div style="font-size:.78rem;color:#757575;margin-top:4px;">{{ $label }}</div>
    </div>
    @endforeach
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:12px;flex-wrap:wrap;">
    <h2 style="font-size:1rem;font-weight:600;color:#1B2A1B;margin:0;">Prochains rendez-vous</h2>
    <a href="/pro/consultations/nouvelle" style="padding:8px 18px;background:#1565C0;color:white;border-radius:100px;font-size:.78rem;font-weight:600;text-decoration:none;">Nouvelle consultation</a>
</div>

@forelse($upcoming as $rdv)
@php $slot = $rdv->timeSlot; @endphp
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:14px 18px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;">
    <div style="display:flex;gap:16px;align-items:center;">
        <div style="text-align:center;min-width:54px;">
            <div style="font-size:1.25rem;font-weight:700;color:#1565C0;line-height:1.1;">{{ $slot?->date?->format('d') }}</div>
            <div style="font-size:.68rem;color:#757575;text-transform:uppercase;">{{ $slot?->date?->translatedFormat('M') }}</div>
        </div>
        <div>
            <div style="font-size:.9rem;font-weight:600;color:#1B2A1B;">
                {{ $slot ? substr($slot->start_time, 0, 5) : '' }} — <a href="/pro/patients/{{ $rdv->patient?->uuid }}" style="color:inherit;">{{ $rdv->patient?->name }}</a>
            </div>
            <div style="font-size:.78rem;color:#757575;">
                {{ $rdv->structure?->name }}@if($rdv->is_teleconsultation) (téléconsultation)@endif
                @if($rdv->is_for_third_party && $rdv->third_party_name) — pour {{ $rdv->third_party_name }}@endif
            </div>
            @if($rdv->reason)<div style="font-size:.78rem;color:#757575;">Motif : {{ $rdv->reason }}</div>@endif
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        @if($rdv->status === 'pending')
        <form method="POST" action="/pro/rendez-vous/{{ $rdv->uuid }}/confirmer" style="margin:0;">
            @csrf
            <button type="submit" style="padding:7px 16px;border:none;border-radius:8px;background:#1565C0;color:white;font-family:Poppins,sans-serif;font-size:.76rem;font-weight:600;cursor:pointer;">Confirmer</button>
        </form>
        @elseif($rdv->is_teleconsultation)
        <form method="POST" action="/pro/rendez-vous/{{ $rdv->uuid }}/teleconsultation" style="margin:0;">
            @csrf
            <button type="submit" style="padding:7px 16px;border:none;border-radius:8px;background:#2E7D32;color:white;font-family:Poppins,sans-serif;font-size:.76rem;font-weight:600;cursor:pointer;">Démarrer la téléconsultation</button>
        </form>
        @else
        <span style="padding:4px 12px;border-radius:100px;font-size:.68rem;font-weight:600;background:#E8F5E9;color:#2E7D32;">Confirmé</span>
        @endif
    </div>
</div>
@empty
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:28px;text-align:center;color:#757575;font-size:.85rem;">
    Aucun rendez-vous à venir. Vos créneaux réservés apparaîtront ici.
</div>
@endforelse
@endif
@endsection
