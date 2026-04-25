@extends('layouts.dashboard')

@section('env-name', 'HOSTO')
@section('env-color', '#388E3C')
@section('env-color-dark', '#2E7D32')
@section('title', 'Mes rendez-vous')
@section('page-title', 'Mes rendez-vous')
@section('user-role', 'Patient')

@section('sidebar-nav')
<a href="/compte">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
    Tableau de bord
</a>
<a href="/compte/rendez-vous" class="active">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
    Mes rendez-vous
</a>
<a href="/compte/medecins">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
    Trouver un medecin
</a>
<a href="/compte/profil">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    Mon profil
</a>
@endsection

@section('content')
@if(session('success'))
<div style="background:#E8F5E9;color:#2E7D32;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:.85rem;">{{ session('success') }}</div>
@endif

@if($appointments->isEmpty())
    <div style="text-align:center;padding:60px 20px;">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#EEE" stroke-width="1.5" style="margin:0 auto 16px;display:block;"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        <p style="color:#757575;margin-bottom:16px;">Vous n'avez aucun rendez-vous.</p>
        <a href="/compte/medecins" style="padding:10px 24px;background:#388E3C;color:white;border-radius:100px;font-size:.85rem;font-weight:600;text-decoration:none;display:inline-block;">Trouver un medecin</a>
    </div>
@else
    @php
        $statusColors = ['pending'=>'#FFF3E0;color:#E65100','confirmed'=>'#E8F5E9;color:#2E7D32','completed'=>'#E3F2FD;color:#1565C0','cancelled_by_patient'=>'#FFEBEE;color:#C62828','cancelled_by_practitioner'=>'#FFEBEE;color:#C62828'];
        $statusLabels = ['pending'=>'En attente','confirmed'=>'Confirme','completed'=>'Termine','cancelled_by_patient'=>'Annule','cancelled_by_practitioner'=>'Annule par le medecin'];
    @endphp
    @foreach($appointments as $rdv)
        @php $slot = $rdv->timeSlot; $canCancel = in_array($rdv->status, ['pending', 'confirmed']); @endphp
        <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:12px;display:flex;gap:16px;align-items:start;flex-wrap:wrap;">
            <div style="width:60px;text-align:center;flex-shrink:0;">
                <div style="font-size:1.5rem;font-weight:700;color:#388E3C;">{{ $slot?->date?->format('d') }}</div>
                <div style="font-size:.72rem;color:#757575;text-transform:uppercase;">{{ $slot?->date?->translatedFormat('M Y') }}</div>
            </div>
            <div style="flex:1;">
                <div style="font-size:.9rem;font-weight:600;color:#1B2A1B;">{{ $slot ? substr($slot->start_time, 0, 5) : '' }} — {{ $rdv->practitioner?->full_name }}</div>
                <div style="font-size:.82rem;color:#757575;">{{ $rdv->structure?->name }} {{ $rdv->is_teleconsultation ? '(Teleconsultation)' : '' }}</div>
                @if($rdv->reason)<div style="font-size:.78rem;color:#757575;margin-top:4px;">Motif : {{ $rdv->reason }}</div>@endif
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;align-items:end;">
                <span style="padding:4px 12px;border-radius:100px;font-size:.68rem;font-weight:600;background:{{ $statusColors[$rdv->status] ?? '#F5F5F5' }};">{{ $statusLabels[$rdv->status] ?? $rdv->status }}</span>
                @if($canCancel)
                <form method="POST" action="/web/rdv/{{ $rdv->uuid }}/cancel" style="margin:0;" onsubmit="return confirm('Annuler ce rendez-vous ?')">
                    @csrf
                    <button type="submit" style="padding:6px 14px;border:1px solid #EEE;border-radius:8px;background:white;cursor:pointer;font-family:Poppins,sans-serif;font-size:.72rem;color:#E53935;">Annuler</button>
                </form>
                @endif
            </div>
        </div>
    @endforeach
@endif
@endsection
