@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Medecins') @section('page-title', 'Medecins') @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'medecins']) @endsection

@section('breadcrumb')
<span style="color:#BDBDBD;margin:0 6px;">/</span>
<span style="color:#424242;">Medecins</span>
@endsection

@section('styles')
<style>
    .explorer-header { margin-bottom:20px; }
    .explorer-header h2 { font-size:1.1rem; font-weight:700; color:#1B2A1B; margin-bottom:4px; }
    .explorer-header p { font-size:.82rem; color:#757575; }
    .explorer-search { display:flex; gap:10px; margin-bottom:20px; }
    .explorer-search input { flex:1; padding:10px 14px; border:2px solid #EEE; border-radius:10px; font-family:Poppins,sans-serif; font-size:.85rem; outline:none; }
    .explorer-search input:focus { border-color:#388E3C; }
    .explorer-search button { padding:10px 20px; background:#388E3C; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:600; cursor:pointer; }
    .explorer-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:12px; }
    .explorer-card { background:white; border:1px solid #EEE; border-radius:14px; padding:16px; text-decoration:none; color:#1B2A1B; transition:all .2s; display:flex; gap:12px; align-items:center; }
    .explorer-card:hover { border-color:#C8E6C9; box-shadow:0 4px 12px rgba(56,142,60,.08); }
    .explorer-card-avatar { width:44px; height:44px; border-radius:12px; background:#E3F2FD; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .explorer-card-avatar svg { width:22px; height:22px; stroke:#1565C0; }
    .explorer-card-name { font-size:.85rem; font-weight:600; }
    .explorer-card-spec { font-size:.72rem; color:#1565C0; }
    .explorer-card-badge { font-size:.62rem; padding:2px 8px; border-radius:100px; background:#E3F2FD; color:#1565C0; font-weight:600; margin-top:3px; display:inline-block; }
    .explorer-loading,.explorer-empty { text-align:center; padding:40px; color:#757575; font-size:.85rem; }
</style>
@endsection

@section('content')
<div class="explorer-header">
    <h2>Medecins</h2>
    <p>Trouvez un medecin par nom ou specialite.</p>
</div>
<div class="explorer-search">
    <input type="text" id="searchQ" placeholder="Rechercher un medecin..." autofocus onkeydown="if(event.key==='Enter')searchDocs()">
    <button onclick="searchDocs()">Rechercher</button>
</div>
<div id="loading" class="explorer-loading" style="display:none;">Recherche...</div>
<div id="results" class="explorer-grid"></div>
<div id="empty" class="explorer-empty" style="display:none;">Aucun resultat.</div>

<script>
searchDocs();
async function searchDocs() {
    const q = document.getElementById('searchQ').value.trim();
    const params = new URLSearchParams();
    if (q) params.set('q', q);
    params.set('per_page', '30');
    document.getElementById('loading').style.display = 'block';
    document.getElementById('results').innerHTML = '';
    document.getElementById('empty').style.display = 'none';
    try {
        const res = await fetch(`${API}/annuaire/practitioners?${params}`);
        const data = await res.json();
        document.getElementById('loading').style.display = 'none';
        if (!data.data.length) { document.getElementById('empty').style.display = 'block'; return; }
        const grid = document.getElementById('results');
        data.data.forEach(p => {
            const card = document.createElement('a');
            card.className = 'explorer-card';
            card.href = '/annuaire/medecins/' + p.slug;
            card.target = '_blank';
            card.innerHTML = `<div class="explorer-card-avatar"><svg viewBox="0 0 24 24" fill="none" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div><div class="explorer-card-name">${p.full_name}</div><div class="explorer-card-spec">${(p.specialties||[]).join(' · ')}</div>${p.does_teleconsultation ? '<span class="explorer-card-badge">Teleconsultation</span>' : ''}</div>`;
            grid.appendChild(card);
        });
    } catch(e) { document.getElementById('loading').style.display = 'none'; }
}
</script>
@endsection
