@extends('layouts.dashboard')

@section('env-name', 'HOSTO')
@section('env-color', '#388E3C')
@section('env-color-dark', '#2E7D32')
@section('title', 'Mon dossier medical')
@section('page-title', 'Mon dossier medical')
@section('user-role', 'Patient')

@section('sidebar-nav')
<a href="/compte"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg> Tableau de bord</a>
<a href="/compte/rendez-vous"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg> Mes rendez-vous</a>
<a href="/compte/dossier-medical" class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> Dossier medical</a>
<a href="/compte/profil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Mon profil</a>
@endsection

@section('content')
@if($consultations->isEmpty())
    <div style="text-align:center;padding:60px 20px;">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#EEE" stroke-width="1.5" style="margin:0 auto 16px;display:block;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        <p style="color:#757575;margin-bottom:16px;">Aucune consultation enregistree dans votre dossier.</p>
        <a href="/compte/medecins" style="padding:10px 24px;background:#388E3C;color:white;border-radius:100px;font-size:.85rem;font-weight:600;text-decoration:none;display:inline-block;">Trouver un medecin</a>
    </div>
@else
    <div style="font-size:.85rem;color:#757575;margin-bottom:20px;">{{ $consultations->count() }} consultation(s) dans votre dossier</div>

    @foreach($consultations as $c)
    <a href="/compte/dossier-medical/{{ $c->uuid }}" style="display:block;background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:12px;text-decoration:none;color:inherit;transition:border-color .2s;">
        <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:8px;">
            <div>
                <div style="font-size:.9rem;font-weight:600;color:#1B2A1B;">{{ $c->motif ?: 'Consultation' }}</div>
                <div style="font-size:.78rem;color:#757575;">{{ $c->practitioner?->full_name }} — {{ $c->structure?->name }}</div>
                <div style="font-size:.72rem;color:#757575;">{{ $c->created_at->format('d/m/Y H:i') }}</div>
                @if($c->diagnostic)<div style="font-size:.78rem;color:#388E3C;margin-top:4px;">Diagnostic : {{ \Illuminate\Support\Str::limit($c->diagnostic, 60) }}</div>@endif
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                @if($c->prescriptions->isNotEmpty())<span style="padding:2px 8px;background:#E3F2FD;color:#1565C0;border-radius:100px;font-size:.65rem;font-weight:600;">{{ $c->prescriptions->count() }} ordonnance(s)</span>@endif
                @if($c->examRequests->isNotEmpty())<span style="padding:2px 8px;background:#FFF3E0;color:#E65100;border-radius:100px;font-size:.65rem;font-weight:600;">{{ $c->examRequests->count() }} examen(s)</span>@endif
                @if($c->careActs->isNotEmpty())<span style="padding:2px 8px;background:#E8F5E9;color:#2E7D32;border-radius:100px;font-size:.65rem;font-weight:600;">{{ $c->careActs->count() }} soin(s)</span>@endif
                @if($c->treatments->isNotEmpty())<span style="padding:2px 8px;background:#F3E5F5;color:#7B1FA2;border-radius:100px;font-size:.65rem;font-weight:600;">{{ $c->treatments->count() }} traitement(s)</span>@endif
            </div>
        </div>
    </a>
    @endforeach
@endif
@endsection
