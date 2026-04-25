@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Soins a domicile') @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'soins']) @endsection

@section('styles')
<style>
    .page-icon { width:48px; height:48px; margin-bottom:12px; }
    .page-title { font-size:1.1rem; font-weight:700; color:#1B2A1B; margin-bottom:2px; }
    .page-sub { font-size:.82rem; color:#757575; margin-bottom:20px; }
    .search-row { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
    .search-row input { flex:1; min-width:180px; padding:10px 14px; border:2px solid #EEE; border-radius:10px; font-family:Poppins,sans-serif; font-size:.85rem; outline:none; }
    .search-row input:focus { border-color:#388E3C; }
    .search-row button { padding:10px 20px; background:#388E3C; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:600; cursor:pointer; }
    .results-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:12px; }
    .scard { background:white; border:1px solid #EEE; border-radius:14px; padding:16px; text-decoration:none; color:#1B2A1B; transition:all .2s; display:block; }
    .scard:hover { border-color:#C8E6C9; box-shadow:0 4px 12px rgba(56,142,60,.08); }
    .scard-name { font-size:.85rem; font-weight:600; }
    .scard-type { font-size:.68rem; color:#388E3C; }
    .scard-city { font-size:.68rem; color:#757575; margin-top:2px; }
    .scard-phone { font-size:.72rem; color:#388E3C; margin-top:4px; }
    .loading,.empty { text-align:center; padding:40px; color:#757575; font-size:.85rem; }
    .info-box { background:#FFF3E0; border:1px solid #FFE082; border-radius:10px; padding:14px 16px; margin-bottom:20px; font-size:.82rem; color:#795548; display:flex; gap:8px; align-items:start; }
</style>
@endsection

@section('content')
<img src="/images/icons/icon-soin-a-domicile.png" class="page-icon" alt="">
<div class="page-title">Soins a domicile</div>
<div class="page-sub">Trouvez un professionnel de sante pour des soins a domicile (infirmier, kinesitherapeute, sage-femme).</div>

<div class="info-box">
    <span style="font-size:1.1rem;">&#128161;</span>
    <span>Les soins a domicile comprennent les injections, pansements, perfusions, kinesitherapie, suivi post-operatoire et soins de maternite.</span>
</div>

<div class="search-row">
    <input type="text" id="searchQ" placeholder="Rechercher un praticien ou un cabinet..." autofocus>
    <button onclick="doSearch()">Rechercher</button>
</div>

<div id="loading" class="loading" style="display:none;">Recherche...</div>
<div id="results" class="results-grid"></div>
<div id="empty" class="empty" style="display:none;">Aucun resultat.</div>

<script>
doSearch();
async function doSearch() {
    const q = document.getElementById('searchQ').value.trim();
    const params = new URLSearchParams({type:'cabinet-medical', per_page:'30'});
    if (q) params.set('q', q);
    document.getElementById('loading').style.display='block';
    document.getElementById('results').innerHTML='';
    document.getElementById('empty').style.display='none';
    try {
        const res = await fetch(`${API}/annuaire/hostos?${params}`);
        const data = await res.json();
        document.getElementById('loading').style.display='none';
        if (!data.data?.length) { document.getElementById('empty').style.display='block'; return; }
        const grid = document.getElementById('results');
        data.data.forEach(h => {
            const card = document.createElement('a');
            card.className='scard'; card.href='/annuaire/'+h.slug; card.target='_blank';
            card.innerHTML=`<div class="scard-name">${h.name}</div><div class="scard-type">${(h.types||[]).map(t=>t.name).join(', ')}</div><div class="scard-city">${h.city?.name||''} ${h.quarter?'— '+h.quarter:''}</div>${h.phone?'<div class="scard-phone">'+h.phone+'</div>':''}`;
            grid.appendChild(card);
        });
    } catch(e) { document.getElementById('loading').style.display='none'; }
}
document.getElementById('searchQ').addEventListener('keydown',e=>{if(e.key==='Enter')doSearch();});
</script>
@endsection
