@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Hopitaux et Cliniques') @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'hopitaux']) @endsection

@section('styles')
<style>
    .page-icon { width:48px; height:48px; margin-bottom:12px; }
    .page-title { font-size:1.1rem; font-weight:700; color:#1B2A1B; margin-bottom:2px; }
    .page-sub { font-size:.82rem; color:#757575; margin-bottom:20px; }
    .search-row { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
    .search-row input, .search-row select { flex:1; min-width:160px; padding:10px 14px; border:2px solid #EEE; border-radius:10px; font-family:Poppins,sans-serif; font-size:.85rem; outline:none; }
    .search-row input:focus, .search-row select:focus { border-color:#388E3C; }
    .search-row button { padding:10px 20px; background:#388E3C; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:600; cursor:pointer; }
    .map-wrap { border-radius:14px; overflow:hidden; height:300px; margin-bottom:20px; }
    .results-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:12px; margin-bottom:20px; }
    .hcard { background:white; border:1px solid #EEE; border-radius:14px; padding:16px; text-decoration:none; color:#1B2A1B; transition:all .2s; display:block; }
    .hcard:hover { border-color:#C8E6C9; box-shadow:0 4px 12px rgba(56,142,60,.08); }
    .hcard-top { display:flex; gap:12px; align-items:start; margin-bottom:6px; }
    .hcard-img { width:44px; height:44px; border-radius:10px; object-fit:cover; background:#E8F5E9; flex-shrink:0; }
    .hcard-name { font-size:.85rem; font-weight:600; }
    .hcard-type { font-size:.68rem; color:#388E3C; }
    .hcard-city { font-size:.68rem; color:#757575; }
    .hcard-specs { font-size:.68rem; color:#1565C0; margin-top:4px; }
    .hcard-tags { display:flex; gap:4px; margin-top:6px; flex-wrap:wrap; }
    .tag { padding:2px 8px; border-radius:100px; font-size:.62rem; font-weight:600; }
    .tag-garde { background:#FFF3E0; color:#E65100; }
    .tag-public { background:#E3F2FD; color:#1565C0; }
    .tag-partner { background:#E8F5E9; color:#2E7D32; }
    .loading,.empty { text-align:center; padding:40px; color:#757575; font-size:.85rem; }
    .pagination { display:flex; justify-content:center; gap:6px; padding:16px 0; }
    .pagination button { padding:6px 14px; border:1px solid #EEE; border-radius:6px; background:white; font-family:Poppins,sans-serif; font-size:.78rem; cursor:pointer; }
    .pagination button.active { background:#388E3C; color:white; border-color:#388E3C; }
</style>
@endsection

@section('content')
<img src="/images/icons/icon-hopitaux.png" class="page-icon" alt="">
<div class="page-title">Hopitaux et Cliniques</div>
<div class="page-sub">Trouvez un hopital, une clinique ou un centre de sante pres de chez vous.</div>

<div class="search-row">
    <input type="text" id="searchQ" placeholder="Rechercher..." autofocus>
    <select id="searchSpec"><option value="">Specialite</option></select>
    <button onclick="doSearch()">Rechercher</button>
</div>

<div id="map" class="map-wrap"></div>
<div id="loading" class="loading" style="display:none;">Recherche...</div>
<div id="results" class="results-grid"></div>
<div id="empty" class="empty" style="display:none;">Aucun etablissement trouve.</div>
<div id="pagination" class="pagination"></div>

<script>
let map = L.map('map').setView([0.39,9.45],12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap',maxZoom:19}).addTo(map);
let markers=[], currentPage=1;

// Load specialties dropdown
(async()=>{try{const r=await fetch(`${API}/referentiel/specialties`);const d=await r.json();const s=document.getElementById('searchSpec');d.data.forEach(sp=>{const o=document.createElement('option');o.value=sp.code;o.textContent=sp.name;s.appendChild(o);(sp.children||[]).forEach(c=>{const co=document.createElement('option');co.value=c.code;co.textContent='\u00A0\u00A0\u00A0'+c.name;s.appendChild(co);});});}catch(e){}})();

doSearch();
async function doSearch(e, page) {
    currentPage = page || 1;
    const params = new URLSearchParams({per_page:'12', page:currentPage});
    // Filter: hopital, clinique, polyclinique, chu, centre-de-sante
    params.set('type','hopital,clinique');
    const q = document.getElementById('searchQ').value.trim();
    const spec = document.getElementById('searchSpec').value;
    if (q) params.set('q', q);
    if (spec) params.set('specialty', spec);

    document.getElementById('loading').style.display='block';
    document.getElementById('results').innerHTML='';
    document.getElementById('empty').style.display='none';
    document.getElementById('pagination').innerHTML='';

    try {
        const res = await fetch(`${API}/annuaire/hostos?${params}`);
        const data = await res.json();
        document.getElementById('loading').style.display='none';
        if (!data.data?.length) { document.getElementById('empty').style.display='block'; return; }

        // Map
        markers.forEach(m=>m.remove()); markers=[];
        const icon = L.divIcon({className:'',html:'<div style="background:#388E3C;width:24px;height:24px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,.3);"></div>',iconSize:[24,24],iconAnchor:[12,24],popupAnchor:[0,-24]});
        const bounds = L.latLngBounds();
        data.data.forEach(h => {
            if (h.coordinates?.latitude) {
                const m = L.marker([h.coordinates.latitude,h.coordinates.longitude],{icon}).addTo(map)
                    .bindPopup(`<div style="font-family:Poppins,sans-serif;font-size:.82rem;min-width:140px;"><strong><a href="/annuaire/${h.slug}" target="_blank" style="color:#388E3C;text-decoration:none;">${h.name}</a></strong><br><span style="color:#757575;font-size:.72rem;">${(h.types||[]).map(t=>t.name).join(', ')}</span>${h.phone?'<br><a href="tel:'+h.phone+'" style="color:#388E3C;font-size:.75rem;">'+h.phone+'</a>':''}</div>`);
                markers.push(m); bounds.extend([h.coordinates.latitude,h.coordinates.longitude]);
            }
        });
        if (markers.length) map.fitBounds(bounds,{padding:[30,30],maxZoom:14});
        setTimeout(()=>map.invalidateSize(),100);

        // Cards
        const grid = document.getElementById('results');
        data.data.forEach(h => {
            const types = (h.types||[]).map(t=>t.name).join(', ');
            const specs = (h.specialties||[]).slice(0,4).map(s=>s.name).join(', ');
            let tags = '';
            if (h.is_guard_service) tags += '<span class="tag tag-garde">Garde</span>';
            if (h.is_public) tags += '<span class="tag tag-public">Public</span>';
            if (h.is_partner) tags += '<span class="tag tag-partner">Partenaire</span>';
            const card = document.createElement('a');
            card.className='hcard'; card.href='/annuaire/'+h.slug; card.target='_blank';
            card.innerHTML=`<div class="hcard-top"><img src="${h.profile_image||'/images/icons/icon-hopitaux.png'}" class="hcard-img"><div><div class="hcard-name">${h.name}</div><div class="hcard-type">${types}</div><div class="hcard-city">${h.city?.name||''}</div></div></div>${specs?'<div class="hcard-specs">'+specs+'</div>':''}<div class="hcard-tags">${tags}</div>`;
            grid.appendChild(card);
        });

        // Pagination
        if (data.meta?.last_page > 1) {
            const p = document.getElementById('pagination');
            for (let i=1;i<=data.meta.last_page;i++) {
                const btn=document.createElement('button'); btn.textContent=i;
                if(i===data.meta.current_page) btn.className='active';
                btn.onclick=()=>doSearch(null,i); p.appendChild(btn);
            }
        }
    } catch(e) { document.getElementById('loading').style.display='none'; }
}
document.getElementById('searchQ').addEventListener('keydown',e=>{if(e.key==='Enter')doSearch();});
</script>
@endsection
