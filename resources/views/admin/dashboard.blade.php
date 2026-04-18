@extends('layouts.dashboard')

@section('env-name', 'HOSTO Admin')
@section('env-color', '#B71C1C')
@section('env-color-dark', '#880E0E')
@section('title', 'Administration')
@section('page-title', 'Administration HOSTO')
@section('user-role', 'Administrateur')

@section('sidebar-nav')
<a href="/admin" class="active">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
    Tableau de bord
</a>
<div class="sidebar-section">Gestion</div>
<a href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    Utilisateurs
</a>
<a href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
    Structures
</a>
<a href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/></svg>
    Demandes validation
</a>
<div class="sidebar-section">Systeme</div>
<a href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
    Statistiques
</a>
<a href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    Audit & securite
</a>
@endsection

@section('content')
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:32px;">
    <div style="background:white;border-radius:14px;padding:24px;border:1px solid #EEE;">
        <div style="font-size:.78rem;color:#757575;">Structures</div>
        <div style="font-size:1.5rem;font-weight:700;color:#B71C1C;">{{ \App\Modules\Annuaire\Models\Hosto::count() }}</div>
    </div>
    <div style="background:white;border-radius:14px;padding:24px;border:1px solid #EEE;">
        <div style="font-size:.78rem;color:#757575;">Utilisateurs</div>
        <div style="font-size:1.5rem;font-weight:700;color:#B71C1C;">{{ \App\Models\User::count() }}</div>
    </div>
    <div style="background:white;border-radius:14px;padding:24px;border:1px solid #EEE;">
        <div style="font-size:.78rem;color:#757575;">Roles</div>
        <div style="font-size:1.5rem;font-weight:700;color:#B71C1C;">{{ \App\Modules\Core\Models\Role::count() }}</div>
    </div>
</div>
@endsection
