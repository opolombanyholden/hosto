@extends('layouts.dashboard')

@section('env-name', 'HOSTO Pro')
@section('env-color', '#1565C0')
@section('env-color-dark', '#0D47A1')
@section('title', 'Espace professionnel')
@section('page-title', 'Espace professionnel')
@section('user-role', 'Professionnel de sante')

@section('sidebar-nav')
<a href="/pro" class="active">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
    Tableau de bord
</a>
<div class="sidebar-section">A venir</div>
<a href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    Mes patients
</a>
<a href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
    Rendez-vous
</a>
<a href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
    Ma structure
</a>
<a href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
    Ordonnances
</a>
@endsection

@section('content')
@php $user = auth()->user(); @endphp

@if(!$user->email_verified_at || !$user->phone_verified_at)
<div style="background:#FFF3E0;border:1px solid #FFB74D;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:12px;">
    <span style="font-size:1.2rem;">&#9888;</span>
    <div>
        <div style="font-size:.85rem;font-weight:600;color:#E65100;">Verification requise</div>
        <div style="font-size:.78rem;color:#BF360C;">Verifiez votre email et telephone pour soumettre votre dossier professionnel.</div>
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

<div style="background:white;border-radius:14px;padding:32px;border:1px solid #EEE;text-align:center;color:#757575;">
    <p style="font-size:.9rem;">Votre espace professionnel est en cours de construction.</p>
    <p style="font-size:.82rem;margin-top:8px;">La gestion des patients, rendez-vous et ordonnances sera disponible dans les prochaines phases.</p>
</div>
@endsection
