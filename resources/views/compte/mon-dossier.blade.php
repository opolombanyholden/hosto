@extends('layouts.dashboard')

@section('env-name', 'HOSTO')
@section('env-color', '#388E3C')
@section('env-color-dark', '#2E7D32')
@section('title', 'Mon dossier')
@section('page-title', 'Mon dossier')
@section('user-role', 'Patient')

@section('sidebar-nav')
@include('compte.partials.sidebar', ['active' => 'dossier'])
@endsection

@section('breadcrumb')
<span style="color:#BDBDBD;margin:0 6px;">/</span>
<span style="color:#424242;">Mon dossier</span>
@endsection

@section('styles')
<style>
    .dossier-header { display:flex; align-items:center; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
    .dossier-avatar { width:64px; height:64px; border-radius:50%; background:#E8F5E9; display:flex; align-items:center; justify-content:center; overflow:hidden; border:3px solid #C8E6C9; flex-shrink:0; }
    .dossier-avatar img { width:100%; height:100%; object-fit:cover; }
    .dossier-info h1 { font-size:1.15rem; font-weight:700; color:#1B2A1B; }
    .dossier-info p { font-size:.78rem; color:#757575; }
    .dossier-badges { display:flex; gap:6px; margin-top:4px; flex-wrap:wrap; }
    .dossier-badge { padding:3px 10px; border-radius:100px; font-size:.65rem; font-weight:600; }

    .dossier-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-bottom:24px; }
    .dossier-card { background:white; border:1px solid #EEE; border-radius:14px; padding:18px; text-decoration:none; color:#1B2A1B; transition:all .2s; display:flex; align-items:center; gap:14px; }
    .dossier-card:hover { border-color:#C8E6C9; box-shadow:0 4px 12px rgba(56,142,60,.08); }
    .dossier-card-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .dossier-card-icon svg { width:22px; height:22px; }
    .dossier-card-label { font-size:.82rem; font-weight:600; }
    .dossier-card-count { font-size:.68rem; color:#757575; }

    .dossier-section { background:white; border:1px solid #EEE; border-radius:14px; padding:20px; margin-bottom:16px; }
    .dossier-section-title { font-size:.85rem; font-weight:600; color:#388E3C; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
    .dossier-section-title svg { width:16px; height:16px; }
    .dossier-item { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid #F5F5F5; font-size:.82rem; }
    .dossier-item:last-child { border-bottom:none; }
    .dossier-item-date { font-size:.72rem; color:#757575; white-space:nowrap; }
    .dossier-item-info { flex:1; }
    .dossier-item-title { font-weight:600; color:#1B2A1B; }
    .dossier-item-sub { font-size:.72rem; color:#757575; }
    .dossier-empty { text-align:center; padding:20px; color:#BDBDBD; font-size:.82rem; }
    .dossier-more { display:inline-block; margin-top:8px; font-size:.78rem; color:#388E3C; font-weight:500; text-decoration:none; }
    .dossier-pagination { display:flex; justify-content:center; gap:4px; padding:10px 0; }
    .dossier-pagination button { padding:4px 12px; border:1px solid #EEE; border-radius:6px; background:white; font-family:Poppins,sans-serif; font-size:.75rem; cursor:pointer; }
    .dossier-pagination button:hover { border-color:#388E3C; color:#388E3C; }
    .dossier-pagination button.active { background:#388E3C; color:white; border-color:#388E3C; }

    @media(max-width:768px) { .dossier-grid { grid-template-columns:1fr; } }
    @media(max-width:900px) { .dossier-grid { grid-template-columns:repeat(2, 1fr); } }
</style>
@endsection

@section('content')
@php
    $rdvCount = \App\Modules\RendezVous\Models\Appointment::where('patient_id', $user->id)->count();
    $consultCount = \App\Modules\Pro\Models\Consultation::where('patient_id', $user->id)->count();
@endphp

{{-- Header patient --}}
<div class="dossier-header">
    <div class="dossier-avatar">
        @if($user->profile_photo_path)
            <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="">
        @else
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#388E3C" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        @endif
    </div>
    <div class="dossier-info">
        <h1>{{ $user->name }}</h1>
        <p>{{ $user->email }} @if($user->phone) — {{ $user->phone }} @endif</p>
        <div class="dossier-badges">
            @if($user->nip) <span class="dossier-badge" style="background:#E8F5E9;color:#2E7D32;">NIP : {{ $user->nip }}</span> @endif
            @if($user->blood_group) <span class="dossier-badge" style="background:#FFEBEE;color:#C62828;">{{ $user->blood_group }}</span> @endif
            @if($user->gender) <span class="dossier-badge" style="background:#E3F2FD;color:#1565C0;">{{ $user->gender === 'male' ? 'Homme' : 'Femme' }}</span> @endif
            @if($user->date_of_birth) <span class="dossier-badge" style="background:#F5F5F5;color:#757575;">{{ $user->date_of_birth->age }} ans</span> @endif
        </div>
    </div>
    <a href="/compte/profil/completer" style="margin-left:auto;padding:8px 16px;border:1px solid #EEE;border-radius:8px;font-size:.78rem;color:#388E3C;font-weight:500;text-decoration:none;">Modifier le profil</a>
</div>

{{-- Thematiques medicales --}}
<div class="dossier-grid">
    <a href="/compte/rendez-vous" class="dossier-card">
        <div class="dossier-card-icon" style="background:#E8F5E9;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#388E3C" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        </div>
        <div>
            <div class="dossier-card-label">Rendez-vous</div>
            <div class="dossier-card-count">{{ $rdvCount }} enregistre{{ $rdvCount > 1 ? 's' : '' }}</div>
        </div>
    </a>
    <a href="/compte/dossier-medical" class="dossier-card">
        <div class="dossier-card-icon" style="background:#E3F2FD;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#1565C0" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <div>
            <div class="dossier-card-label">Consultations</div>
            <div class="dossier-card-count">{{ $consultCount }} effectuee{{ $consultCount > 1 ? 's' : '' }}</div>
        </div>
    </a>
    <a href="#" class="dossier-card">
        <div class="dossier-card-icon" style="background:#FFF3E0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#E65100" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
        </div>
        <div>
            <div class="dossier-card-label">Diagnostics</div>
            <div class="dossier-card-count">0 enregistre</div>
        </div>
    </a>
    <a href="#" class="dossier-card">
        <div class="dossier-card-icon" style="background:#F3E5F5;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#6A1B9A" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 14l2 2 4-4"/></svg>
        </div>
        <div>
            <div class="dossier-card-label">Examens</div>
            <div class="dossier-card-count">0 resultat</div>
        </div>
    </a>
    <a href="#" class="dossier-card">
        <div class="dossier-card-icon" style="background:#E8F5E9;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#388E3C" stroke-width="2"><path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/><path d="M12 8v8M8 12h8"/></svg>
        </div>
        <div>
            <div class="dossier-card-label">Traitements</div>
            <div class="dossier-card-count">0 en cours</div>
        </div>
    </a>
    <a href="#" class="dossier-card">
        <div class="dossier-card-icon" style="background:#E3F2FD;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#1565C0" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
            <div class="dossier-card-label">Vaccins</div>
            <div class="dossier-card-count">0 enregistre</div>
        </div>
    </a>
    <a href="#" class="dossier-card">
        <div class="dossier-card-icon" style="background:#FFEBEE;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#C62828" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
        </div>
        <div>
            <div class="dossier-card-label">Hospitalisations</div>
            <div class="dossier-card-count">0 enregistree</div>
        </div>
    </a>
    <a href="#" class="dossier-card">
        <div class="dossier-card-icon" style="background:#FFF3E0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#E65100" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
        </div>
        <div>
            <div class="dossier-card-label">Soins</div>
            <div class="dossier-card-count">0 enregistre</div>
        </div>
    </a>
    <a href="/compte/profil/completer" class="dossier-card">
        <div class="dossier-card-icon" style="background:#F5F5F5;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#757575" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
            <div class="dossier-card-label">Informations medicales</div>
            <div class="dossier-card-count">Profil, allergies, groupe sanguin</div>
        </div>
    </a>
</div>

{{-- Section Rendez-vous --}}
<div class="dossier-section">
    <div class="dossier-section-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        Rendez-vous
    </div>
    <div style="margin-bottom:10px;">
        <input type="text" id="rdvSearch" placeholder="Rechercher un rendez-vous..." oninput="debounce(loadRdv,300)()" style="width:100%;padding:8px 12px;border:1px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.82rem;outline:none;box-sizing:border-box;">
    </div>
    <div id="rdvList"></div>
    <div id="rdvEmpty" class="dossier-empty" style="display:none;">Aucun rendez-vous.</div>
    <div id="rdvPagination" class="dossier-pagination"></div>
</div>

{{-- Section Consultations --}}
<div class="dossier-section">
    <div class="dossier-section-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        Consultations
    </div>
    <div style="margin-bottom:10px;">
        <input type="text" id="consultSearch" placeholder="Rechercher une consultation..." oninput="debounce(loadConsult,300)()" style="width:100%;padding:8px 12px;border:1px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.82rem;outline:none;box-sizing:border-box;">
    </div>
    <div id="consultList"></div>
    <div id="consultEmpty" class="dossier-empty" style="display:none;">Aucune consultation.</div>
    <div id="consultPagination" class="dossier-pagination"></div>
</div>

<script>
let rdvPage = 1, consultPage = 1;
let debounceTimers = {};
function debounce(fn, ms) { return function() { clearTimeout(debounceTimers[fn.name]); debounceTimers[fn.name] = setTimeout(fn, ms); }; }

loadRdv(); loadConsult();

async function loadRdv(page) {
    rdvPage = page || 1;
    const q = document.getElementById('rdvSearch').value.trim();
    const params = new URLSearchParams({per_page:'5', page:rdvPage});
    if (q) params.set('q', q);
    try {
        const res = await fetch(`/compte/api/rendez-vous?${params}`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        const data = await res.json();
        const list = document.getElementById('rdvList');
        const empty = document.getElementById('rdvEmpty');
        list.innerHTML = '';
        if (!data.data.length) { empty.style.display='block'; } else {
            empty.style.display='none';
            data.data.forEach(r => {
                const statusBg = r.status==='confirmed'?'#E8F5E9;color:#2E7D32':r.status==='pending'?'#FFF3E0;color:#E65100':'#F5F5F5;color:#757575';
                list.insertAdjacentHTML('beforeend', `<div class="dossier-item"><div class="dossier-item-date">${r.created_at}</div><div class="dossier-item-info"><div class="dossier-item-title">${r.structure||'Structure'}</div><div class="dossier-item-sub">${r.practitioner||''} — ${r.date||''} ${r.time||''}</div></div><span class="dossier-badge" style="background:${statusBg};">${r.status}</span></div>`);
            });
        }
        buildPagination('rdvPagination', data.meta, loadRdv);
    } catch(e) {}
}

async function loadConsult(page) {
    consultPage = page || 1;
    const q = document.getElementById('consultSearch').value.trim();
    const params = new URLSearchParams({per_page:'5', page:consultPage});
    if (q) params.set('q', q);
    try {
        const res = await fetch(`/compte/api/consultations?${params}`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        const data = await res.json();
        const list = document.getElementById('consultList');
        const empty = document.getElementById('consultEmpty');
        list.innerHTML = '';
        if (!data.data.length) { empty.style.display='block'; } else {
            empty.style.display='none';
            data.data.forEach(c => {
                list.insertAdjacentHTML('beforeend', `<div class="dossier-item"><div class="dossier-item-date">${c.created_at}</div><div class="dossier-item-info"><div class="dossier-item-title">${c.practitioner||'Medecin'}</div><div class="dossier-item-sub">${c.structure||''} — ${c.motif||c.diagnostic||'Consultation'}</div></div></div>`);
            });
        }
        buildPagination('consultPagination', data.meta, loadConsult);
    } catch(e) {}
}

function buildPagination(containerId, meta, loadFn) {
    const p = document.getElementById(containerId);
    p.innerHTML = '';
    if (!meta || meta.last_page <= 1) return;
    for (let i=1; i<=meta.last_page; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        if (i===meta.current_page) btn.className='active';
        btn.onclick = () => loadFn(i);
        p.appendChild(btn);
    }
}
</script>
@endsection
