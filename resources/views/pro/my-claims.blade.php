@extends('layouts.dashboard')

@section('env-name', 'HOSTO Pro')
@section('env-color', '#1565C0')
@section('env-color-dark', '#0D47A1')
@section('title', 'Mes demandes')
@section('page-title', 'Mes demandes d\'enregistrement')
@section('user-role', 'Professionnel')

@section('sidebar-nav')
<a href="/pro"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg> Tableau de bord</a>
<a href="/pro/enregistrer-structure"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> Enregistrer structure</a>
<a href="/pro/mes-demandes" class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/></svg> Mes demandes</a>
@endsection

@section('content')
@if(session('success'))
<div style="background:#E8F5E9;color:#2E7D32;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:.85rem;">{{ session('success') }}</div>
@endif

@if($claims->isEmpty())
    <div style="text-align:center;padding:60px 20px;">
        <p style="color:#757575;margin-bottom:16px;">Vous n'avez aucune demande d'enregistrement.</p>
        <a href="/pro/enregistrer-structure" style="padding:10px 24px;background:#1565C0;color:white;border-radius:100px;font-size:.85rem;font-weight:600;text-decoration:none;display:inline-block;">Enregistrer une structure</a>
    </div>
@else
    @php
        $statusColors = ['draft'=>'#F5F5F5;color:#757575','submitted'=>'#FFF3E0;color:#E65100','under_review'=>'#E3F2FD;color:#1565C0','approved'=>'#E8F5E9;color:#2E7D32','rejected'=>'#FFEBEE;color:#C62828','suspended'=>'#FFF3E0;color:#E65100'];
        $statusLabels = ['draft'=>'Brouillon','submitted'=>'Soumise','under_review'=>'En cours d\'examen','approved'=>'Approuvee','rejected'=>'Rejetee','suspended'=>'Suspendue'];
    @endphp
    @foreach($claims as $claim)
    <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:12px;">
            <div>
                <div style="font-size:.95rem;font-weight:600;color:#1B2A1B;">{{ $claim->structure_name }}</div>
                <div style="font-size:.78rem;color:#757575;">{{ $claim->structure_city }} — {{ $claim->representative_name }}</div>
                <div style="font-size:.72rem;color:#757575;margin-top:4px;">Soumise le {{ $claim->submitted_at?->format('d/m/Y') }}</div>
            </div>
            <span style="padding:4px 14px;border-radius:100px;font-size:.72rem;font-weight:600;background:{{ $statusColors[$claim->status] ?? '#F5F5F5' }};">{{ $statusLabels[$claim->status] ?? $claim->status }}</span>
        </div>
        @if($claim->status === 'rejected' && $claim->rejection_reason)
        <div style="background:#FFEBEE;border-radius:8px;padding:10px;margin-top:12px;font-size:.78rem;color:#C62828;">
            <strong>Motif :</strong> {{ $claim->rejection_reason }}
        </div>
        @endif
    </div>
    @endforeach
@endif
@endsection
