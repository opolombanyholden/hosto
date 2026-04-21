@extends('layouts.dashboard')

@section('env-name', 'HOSTO Admin')
@section('env-color', '#B71C1C')
@section('env-color-dark', '#880E0E')
@section('title', 'Demandes de validation')
@section('page-title', 'Demandes de validation')
@section('user-role', 'Administrateur')

@section('sidebar-nav')
<a href="/admin"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg> Tableau de bord</a>
<a href="/admin/utilisateurs"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg> Utilisateurs</a>
<a href="/admin/structures"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> Structures</a>
<a href="/admin/demandes" class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/></svg> Demandes</a>
@endsection

@section('content')
@if(session('success'))
<div style="background:#E8F5E9;color:#2E7D32;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:.85rem;">{{ session('success') }}</div>
@endif

@php $statusColors = ['submitted'=>'#FFF3E0;color:#E65100','under_review'=>'#E3F2FD;color:#1565C0','approved'=>'#E8F5E9;color:#2E7D32','rejected'=>'#FFEBEE;color:#C62828','suspended'=>'#FFF3E0;color:#E65100']; @endphp

@forelse($claims as $claim)
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:12px;">
    <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:12px;">
        <div>
            <div style="font-size:.95rem;font-weight:600;color:#1B2A1B;">{{ $claim->structure_name }}</div>
            <div style="font-size:.78rem;color:#757575;">{{ $claim->structure_city }} — {{ $claim->representative_name }} ({{ $claim->representative_role }})</div>
            <div style="font-size:.72rem;color:#757575;">Par : {{ $claim->user?->name }} ({{ $claim->user?->email }}) — {{ $claim->submitted_at?->format('d/m/Y') }}</div>
            @if($claim->registration_number)<div style="font-size:.72rem;color:#757575;">RCCM : {{ $claim->registration_number }}</div>@endif
        </div>
        <span style="padding:4px 14px;border-radius:100px;font-size:.72rem;font-weight:600;background:{{ $statusColors[$claim->status] ?? '#F5F5F5' }};">{{ $claim->status }}</span>
    </div>

    @if(in_array($claim->status, ['submitted', 'under_review']))
    <div style="margin-top:16px;padding-top:16px;border-top:1px solid #EEE;display:flex;gap:8px;flex-wrap:wrap;align-items:end;">
        <form method="POST" action="/admin/demandes/{{ $claim->uuid }}/review" style="display:inline;">@csrf
            <input type="hidden" name="action" value="approve">
            <button type="submit" style="padding:8px 20px;background:#2E7D32;color:white;border:none;border-radius:8px;font-family:Poppins,sans-serif;font-size:.78rem;font-weight:600;cursor:pointer;">Approuver</button>
        </form>
        <form method="POST" action="/admin/demandes/{{ $claim->uuid }}/review" style="display:inline;">@csrf
            <input type="hidden" name="action" value="suspend">
            <button type="submit" style="padding:8px 20px;background:#FF9800;color:white;border:none;border-radius:8px;font-family:Poppins,sans-serif;font-size:.78rem;font-weight:600;cursor:pointer;">Suspendre</button>
        </form>
        <form method="POST" action="/admin/demandes/{{ $claim->uuid }}/review" style="display:inline-flex;gap:8px;align-items:end;">@csrf
            <input type="hidden" name="action" value="reject">
            <input type="text" name="rejection_reason" placeholder="Motif du rejet..." required style="padding:8px 12px;border:1px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.78rem;width:200px;">
            <button type="submit" style="padding:8px 20px;background:#C62828;color:white;border:none;border-radius:8px;font-family:Poppins,sans-serif;font-size:.78rem;font-weight:600;cursor:pointer;">Rejeter</button>
        </form>
    </div>
    @endif

    @if($claim->rejection_reason)
    <div style="margin-top:12px;background:#FFEBEE;border-radius:8px;padding:10px;font-size:.78rem;color:#C62828;">Motif : {{ $claim->rejection_reason }}</div>
    @endif
</div>
@empty
<div style="text-align:center;padding:40px;color:#757575;">Aucune demande.</div>
@endforelse
<div style="margin-top:16px;">{{ $claims->links() }}</div>
@endsection
