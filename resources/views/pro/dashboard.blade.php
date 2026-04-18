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
<div style="background:white;border-radius:14px;padding:32px;border:1px solid #EEE;text-align:center;color:#757575;">
    <p style="font-size:.9rem;">Votre espace professionnel est en cours de construction.</p>
    <p style="font-size:.82rem;margin-top:8px;">La gestion des patients, rendez-vous et ordonnances sera disponible dans les prochaines phases.</p>
</div>
@endsection
