@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Structures de sante') @section('page-title', 'Structures') @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'structures']) @endsection

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
    .explorer-card { background:white; border:1px solid #EEE; border-radius:14px; padding:16px; text-decoration:none; color:#1B2A1B; transition:all .2s; }
    .explorer-card:hover { border-color:#C8E6C9; box-shadow:0 4px 12px rgba(56,142,60,.08); }
    .explorer-card-name { font-size:.88rem; font-weight:600; }
    .explorer-card-type { font-size:.72rem; color:#388E3C; }
    .explorer-card-city { font-size:.75rem; color:#757575; margin-top:2px; }
    .explorer-loading,.explorer-empty { text-align:center; padding:40px; color:#757575; font-size:.85rem; }
</style>
@endsection

@section('content')
<div class="explorer-header">
    <h2>Structures de sante</h2>
    <p>Trouvez un hopital, une clinique, une pharmacie ou un laboratoire.</p>
</div>
<div class="explorer-search">
    <input type="text" id="searchQ" placeholder="Rechercher une structure..." autofocus onkeydown="if(event.key==='Enter')searchStructures()">
    <button onclick="searchStructures()">Rechercher</button>
</div>
<div id="loading" class="explorer-loading" style="display:none;">Recherche...</div>
<div id="results" class="explorer-grid"></div>
<div id="empty" class="explorer-empty" style="display:none;">Aucun resultat.</div>

<script>
searchStructures();
async function searchStructures() {
    const q = document.getElementById('searchQ').value.trim();
    const params = new URLSearchParams();
    if (q) params.set('q', q);
    params.set('per_page', '30');
    document.getElementById('loading').style.display = 'block';
    document.getElementById('results').innerHTML = '';
    document.getElementById('empty').style.display = 'none';
    try {
        const res = await fetch(`${API}/annuaire?${params}`);
        const data = await res.json();
        document.getElementById('loading').style.display = 'none';
        if (!data.data.length) { document.getElementById('empty').style.display = 'block'; return; }
        const grid = document.getElementById('results');
        data.data.forEach(h => {
            const card = document.createElement('a');
            card.className = 'explorer-card';
            card.href = '/annuaire/' + h.slug;
            card.target = '_blank';
            card.innerHTML = `<div class="explorer-card-name">${h.name}</div><div class="explorer-card-type">${(h.types||[]).join(', ')}</div><div class="explorer-card-city">${h.city||''}</div>`;
            grid.appendChild(card);
        });
    } catch(e) { document.getElementById('loading').style.display = 'none'; }
}
</script>
@endsection
