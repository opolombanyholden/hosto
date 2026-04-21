@extends('layouts.dashboard')

@section('env-name', 'HOSTO Pro')
@section('env-color', '#1565C0')
@section('env-color-dark', '#0D47A1')
@section('title', 'Mes consultations')
@section('page-title', 'Mes consultations')
@section('user-role', 'Professionnel')

@section('sidebar-nav')
<a href="/pro"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg> Tableau de bord</a>
<a href="/pro/consultations" class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> Consultations</a>
<a href="/pro/consultations/nouvelle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> Nouvelle consultation</a>
<a href="/pro/enregistrer-structure"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> Enregistrer structure</a>
<a href="/pro/profil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Mon profil</a>
@endsection

@section('content')
@if(!$practitioner)
<div style="background:#FFF3E0;border:1px solid #FFB74D;border-radius:12px;padding:20px;color:#E65100;font-size:.85rem;">
    Aucun profil praticien associe a votre compte. Contactez l'administration.
</div>
@else
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
    <span style="font-size:.85rem;color:#757575;">{{ $consultations instanceof \Illuminate\Pagination\LengthAwarePaginator ? $consultations->total() : $consultations->count() }} consultation(s)</span>
    <a href="/pro/consultations/nouvelle" style="padding:10px 24px;background:#1565C0;color:white;border-radius:100px;font-size:.82rem;font-weight:600;text-decoration:none;">+ Nouvelle consultation</a>
</div>

@forelse($consultations as $c)
<a href="/pro/consultations/{{ $c->uuid }}" style="display:block;background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:12px;text-decoration:none;color:inherit;transition:border-color .2s;">
    <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:8px;">
        <div>
            <div style="font-size:.9rem;font-weight:600;color:#1B2A1B;">{{ $c->patient?->name }}</div>
            <div style="font-size:.78rem;color:#757575;">{{ $c->motif }} — {{ $c->structure?->name }}</div>
            <div style="font-size:.72rem;color:#757575;">{{ $c->created_at->format('d/m/Y H:i') }}</div>
        </div>
        @php $sColors = ['in_progress'=>'#FFF3E0;color:#E65100','completed'=>'#E8F5E9;color:#2E7D32','signed'=>'#E3F2FD;color:#1565C0']; @endphp
        <span style="padding:4px 12px;border-radius:100px;font-size:.68rem;font-weight:600;background:{{ $sColors[$c->status] ?? '#F5F5F5' }};">{{ ucfirst($c->status) }}</span>
    </div>
</a>
@empty
<div style="text-align:center;padding:60px 20px;color:#757575;">
    <p>Aucune consultation enregistree.</p>
    <a href="/pro/consultations/nouvelle" style="display:inline-block;margin-top:16px;padding:10px 24px;background:#1565C0;color:white;border-radius:100px;font-size:.82rem;font-weight:600;text-decoration:none;">Creer une consultation</a>
</div>
@endforelse

@if($consultations instanceof \Illuminate\Pagination\LengthAwarePaginator)
<div style="margin-top:20px;">{{ $consultations->links() }}</div>
@endif
@endif
@endsection
