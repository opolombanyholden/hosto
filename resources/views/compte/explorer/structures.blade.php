@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Structures de sante') @section('page-title', 'Structures') @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'structures']) @endsection

@section('styles')
<style>
    .search-bar {
        background:white; border-radius:14px; padding:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);
        display:grid; grid-template-columns:1fr 1fr 1fr 1fr auto; gap:8px; align-items:center;
        border:1px solid #EEE; margin-bottom:20px;
    }
    .search-field { display:flex; align-items:center; gap:8px; padding:8px 12px; border-radius:8px; }
    .search-field:hover { background:#FAFAFA; }
    .search-field svg { width:16px; height:16px; color:#388E3C; flex-shrink:0; }
    .search-field input, .search-field select { border:none; outline:none; font-family:Poppins,sans-serif; font-size:.82rem; background:transparent; width:100%; }
    .search-field select { cursor:pointer; }
    .search-btn { padding:10px 20px; background:#388E3C; color:white; border:none; border-radius:8px; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:600; cursor:pointer; white-space:nowrap; }
    .search-btn:hover { background:#2E7D32; }

    .toolbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px; }
    .toolbar-title { font-size:.95rem; font-weight:600; color:#1B2A1B; }
    .toolbar-actions { display:flex; gap:8px; }
    .view-toggle { display:flex; border:2px solid #EEE; border-radius:8px; overflow:hidden; }
    .view-toggle button { padding:6px 14px; border:none; background:white; font-family:Poppins,sans-serif; font-size:.75rem; font-weight:500; cursor:pointer; display:flex; align-items:center; gap:4px; color:#757575; }
    .view-toggle button.active { background:#388E3C; color:white; }
    .view-toggle button svg { width:14px; height:14px; }
    .btn-prox { padding:6px 14px; border:1px solid #EEE; border-radius:8px; background:white; font-family:Poppins,sans-serif; font-size:.75rem; cursor:pointer; color:#757575; display:flex; align-items:center; gap:4px; }
    .btn-prox.active { background:#388E3C; color:white; border-color:#388E3C; }
    .btn-prox svg { width:14px; height:14px; }
    .btn-reset { padding:6px 14px; border:1px solid #EEE; border-radius:8px; background:white; font-family:Poppins,sans-serif; font-size:.75rem; cursor:pointer; color:#757575; }

    .results-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:12px; margin-bottom:24px; }
    .hosto-card { background:white; border:1px solid #EEE; border-radius:14px; overflow:hidden; transition:all .2s; text-decoration:none; color:#1B2A1B; display:block; }
    .hosto-card:hover { border-color:#C8E6C9; transform:translateY(-2px); box-shadow:0 4px 12px rgba(56,142,60,.08); }
    .hosto-card-body { padding:14px 16px; }
    .hosto-card-top { display:flex; gap:12px; align-items:start; margin-bottom:8px; }
    .hosto-card-img { width:44px; height:44px; border-radius:10px; object-fit:cover; background:#E8F5E9; flex-shrink:0; }
    .hosto-card-name { font-size:.85rem; font-weight:600; line-height:1.3; }
    .hosto-card-type { font-size:.68rem; color:#757575; margin-top:1px; }
    .hosto-card-loc { font-size:.68rem; color:#757575; }
    .hosto-card-dist { font-size:.75rem; font-weight:600; color:#388E3C; white-space:nowrap; }
    .hosto-card-specs { font-size:.68rem; color:#388E3C; margin-bottom:6px; }
    .hosto-card-tags { display:flex; gap:4px; flex-wrap:wrap; }
    .tag { padding:2px 8px; border-radius:100px; font-size:.62rem; font-weight:600; }
    .tag-garde { background:#FFF3E0; color:#E65100; }
    .tag-open { color:#388E3C; font-size:.68rem; }
    .tag-closed { color:#E53935; font-size:.68rem; }
    .tag-phone { font-size:.68rem; color:#757575; }

    .map-results { border-radius:14px; overflow:hidden; height:450px; display:none; margin-bottom:16px; }
    .pagination { display:flex; justify-content:center; gap:6px; padding:16px 0; }
    .pagination button { padding:6px 14px; border:1px solid #EEE; border-radius:6px; background:white; font-family:Poppins,sans-serif; font-size:.78rem; cursor:pointer; }
    .pagination button:hover { border-color:#388E3C; color:#388E3C; }
    .pagination button.active { background:#388E3C; color:white; border-color:#388E3C; }
    .loading,.empty-state { text-align:center; padding:40px; color:#757575; font-size:.85rem; }

    @media(max-width:768px) { .search-bar{grid-template-columns:1fr !important;} .results-grid{grid-template-columns:1fr;} .map-results{height:300px;} .toolbar{flex-direction:column;} }
</style>
@endsection

@section('content')
<div class="search-bar" id="searchForm">
    <div class="search-field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input type="text" id="searchQ" placeholder="Nom de la structure..." value="{{ request('q') }}">
    </div>
    <div class="search-field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <select id="searchCity"><option value="">Toutes les villes</option></select>
    </div>
    <div class="search-field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        <select id="searchType"><option value="">Type de structure</option></select>
    </div>
    <div class="search-field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        <select id="searchSpecialty"><option value="">Specialite</option></select>
    </div>
    <button type="button" class="search-btn" onclick="doSearch()">Rechercher</button>
</div>

<div class="toolbar">
    <div class="toolbar-title" id="resultsTitle">Structures de sante</div>
    <div class="toolbar-actions">
        <button id="btnProximite" onclick="toggleProximite()" class="btn-prox">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            A proximite
        </button>
        <div class="view-toggle">
            <button id="btnViewList" class="active" onclick="setView('list')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/></svg>
                Liste
            </button>
            <button id="btnViewMap" onclick="setView('map')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/></svg>
                Carte
            </button>
        </div>
        <button onclick="resetSearch()" class="btn-reset">Reinitialiser</button>
    </div>
</div>

<div id="loading" class="loading" style="display:none;">Recherche en cours...</div>
<div id="mapResults" class="map-results"></div>
<div id="resultsList" class="results-grid"></div>
<div id="emptyState" class="empty-state" style="display:none;">Aucune structure trouvee.</div>
<div id="pagination" class="pagination"></div>

<script>
let userLat=null, userLng=null, currentPage=1, currentView='list', proximiteActive=false, resultsMap=null, mapMarkers=[], lastResults=[];

const urlP = new URLSearchParams(window.location.search);
const prefilterType = urlP.get('type') || '';
const prefilterGarde = urlP.get('garde') === '1';

// Contextual titles
const typeLabels = {pharmacie:'Pharmacies',hopital:'Hopitaux',clinique:'Cliniques',laboratoire:'Laboratoires','cabinet-medical':'Cabinets medicaux','centre-de-sante':'Centres de sante',maternite:'Maternites'};

async function init() {
    await Promise.all([loadDropdowns(), loadCities()]);
    if (urlP.get('q')) document.getElementById('searchQ').value = urlP.get('q');
    if (prefilterType) document.getElementById('searchType').value = prefilterType;
    if (urlP.get('specialty')) document.getElementById('searchSpecialty').value = urlP.get('specialty');

    // Update header title based on filter
    const h2 = document.querySelector('.explorer-header h2');
    const p = document.querySelector('.explorer-header p');
    if (prefilterType && typeLabels[prefilterType]) {
        h2.textContent = typeLabels[prefilterType];
        p.textContent = 'Trouvez un etablissement pres de chez vous.';
    } else if (prefilterGarde) {
        h2.textContent = 'Services de garde et urgences';
        p.textContent = 'Structures avec service de garde ou urgences.';
    }

    doSearch();
}

async function loadDropdowns() {
    try {
        const [typesRes, specsRes] = await Promise.all([
            fetch(`${API}/referentiel/structure-types`).then(r=>r.json()),
            fetch(`${API}/referentiel/specialties`).then(r=>r.json()),
        ]);
        const ts = document.getElementById('searchType');
        ts.innerHTML = '<option value="">Type de structure</option>';
        typesRes.data.forEach(t => { const o=document.createElement('option'); o.value=t.slug; o.textContent=t.name; ts.appendChild(o); });
        const ss = document.getElementById('searchSpecialty');
        ss.innerHTML = '<option value="">Specialite</option>';
        specsRes.data.forEach(s => {
            const o=document.createElement('option'); o.value=s.code; o.textContent=s.name; ss.appendChild(o);
            (s.children||[]).forEach(c => { const co=document.createElement('option'); co.value=c.code; co.textContent='\u00A0\u00A0\u00A0'+c.name; ss.appendChild(co); });
        });
    } catch(e) {}
}

async function loadCities() {
    try {
        const regRes = await fetch(`${API}/referentiel/countries/${currentCountryIso}/regions`);
        const regions = (await regRes.json()).data;
        const cs = document.getElementById('searchCity');
        cs.innerHTML = '<option value="">Toutes les villes</option>';
        for (const r of regions) {
            const citiesRes = await fetch(`${API}/referentiel/regions/${r.uuid}/cities`);
            const cities = (await citiesRes.json()).data;
            if (!cities.length) continue;
            const og = document.createElement('optgroup'); og.label = r.name;
            cities.forEach(c => { const o=document.createElement('option'); o.value=c.uuid; o.textContent=c.name; og.appendChild(o); });
            cs.appendChild(og);
        }
    } catch(e) {}
}

function toggleProximite() {
    if (proximiteActive) { proximiteActive=false; userLat=null; userLng=null; document.getElementById('btnProximite').classList.remove('active'); doSearch(); }
    else {
        if (!navigator.geolocation) { alert('Geolocalisation non supportee.'); return; }
        navigator.geolocation.getCurrentPosition(
            pos => { userLat=pos.coords.latitude; userLng=pos.coords.longitude; proximiteActive=true; document.getElementById('btnProximite').classList.add('active'); doSearch(); },
            () => { userLat=0.39; userLng=9.45; proximiteActive=true; document.getElementById('btnProximite').classList.add('active'); doSearch(); }
        );
    }
}

function setView(view) {
    currentView=view;
    document.getElementById('btnViewList').classList.toggle('active', view==='list');
    document.getElementById('btnViewMap').classList.toggle('active', view==='map');
    document.getElementById('resultsList').style.display = view==='list' ? '' : 'none';
    document.getElementById('mapResults').style.display = view==='map' ? 'block' : 'none';
    document.getElementById('pagination').style.display = view==='list' ? '' : 'none';
    if (view==='map') renderMap();
}

async function doSearch(e, page) {
    if (e) e.preventDefault();
    currentPage = page || 1;
    const params = new URLSearchParams();
    const q=document.getElementById('searchQ').value.trim();
    const city=document.getElementById('searchCity').value;
    const type=document.getElementById('searchType').value;
    const specialty=document.getElementById('searchSpecialty').value;
    if (q) params.set('q',q);
    if (city) params.set('city',city);
    if (type) params.set('type',type);
    if (specialty) params.set('specialty',specialty);
    if (prefilterGarde) params.set('garde','1');
    if (proximiteActive && userLat && userLng) { params.set('lat',userLat); params.set('lng',userLng); params.set('rayon','20'); params.set('sort','distance'); }
    params.set('per_page', currentView==='map' ? '50' : '12');
    params.set('page', currentPage);

    document.getElementById('loading').style.display='block';
    document.getElementById('resultsList').innerHTML='';
    document.getElementById('emptyState').style.display='none';
    document.getElementById('pagination').innerHTML='';

    try {
        const res = await fetch(`${API}/annuaire/hostos?${params}`);
        const data = await res.json();
        document.getElementById('loading').style.display='none';
        const total = data.meta?.total || 0;
        document.getElementById('resultsTitle').textContent = total > 0 ? `${total} structure${total>1?'s':''} trouvee${total>1?'s':''}` : 'Structures de sante';
        lastResults = data.data || [];
        if (total===0) { document.getElementById('emptyState').style.display='block'; return; }
        const list = document.getElementById('resultsList');
        lastResults.forEach(h => list.appendChild(buildCard(h)));
        buildPagination(data.meta);
        if (currentView==='map') renderMap();
    } catch(err) { document.getElementById('loading').style.display='none'; document.getElementById('emptyState').style.display='block'; }
}

function buildCard(h) {
    const card = document.createElement('a');
    card.href = `/annuaire/${h.slug}`;
    card.target = '_blank';
    card.className = 'hosto-card';
    const img = h.profile_image || '/images/icons/icon-hopitaux.png';
    const types = (h.types||[]).map(t=>t.name).join(', ');
    const specs = (h.specialties||[]).slice(0,3).map(s=>s.name).join(', ');
    const dist = h.distance_km!=null ? `<span class="hosto-card-dist">${h.distance_km} km</span>` : '';
    const guard = h.is_guard_service ? '<span class="tag tag-garde">Garde</span>' : '';
    const city = h.city?.name || '';
    card.innerHTML = `<div class="hosto-card-body"><div class="hosto-card-top"><img src="${img}" alt="" class="hosto-card-img"><div style="flex:1;min-width:0;"><div style="display:flex;justify-content:space-between;gap:6px;"><div class="hosto-card-name">${h.name}</div>${dist}</div><div class="hosto-card-type">${types}</div><div class="hosto-card-loc">${city}</div></div></div>${specs?`<div class="hosto-card-specs">${specs}</div>`:''}<div class="hosto-card-tags">${guard} ${h.phone?`<span class="tag-phone">${h.phone}</span>`:''}</div></div>`;
    return card;
}

function buildPagination(meta) {
    if (!meta || meta.last_page<=1) return;
    const p = document.getElementById('pagination');
    for (let i=1; i<=meta.last_page; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        if (i===meta.current_page) btn.className='active';
        btn.onclick = () => doSearch(null,i);
        p.appendChild(btn);
    }
}

function renderMap() {
    const container = document.getElementById('mapResults');
    container.style.display = 'block';
    mapMarkers.forEach(m=>m.remove()); mapMarkers=[];
    const withCoords = lastResults.filter(h=>h.coordinates&&h.coordinates.latitude);
    if (!resultsMap) { resultsMap=L.map('mapResults').setView([0.39,9.45],12); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap',maxZoom:19}).addTo(resultsMap); }
    if (withCoords.length===0) return;
    const greenIcon = L.divIcon({className:'',html:'<div style="background:#388E3C;width:24px;height:24px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,.3);"></div>',iconSize:[24,24],iconAnchor:[12,24],popupAnchor:[0,-24]});
    const bounds = L.latLngBounds();
    withCoords.forEach(h => {
        const types = (h.types||[]).map(t=>t.name).join(', ');
        const dist = h.distance_km!=null ? `<br><strong>${h.distance_km} km</strong>` : '';
        const marker = L.marker([h.coordinates.latitude,h.coordinates.longitude],{icon:greenIcon}).addTo(resultsMap)
            .bindPopup(`<div style="font-family:Poppins,sans-serif;font-size:.82rem;min-width:160px;">
                <strong><a href="/annuaire/${h.slug}" target="_blank" style="color:#388E3C;text-decoration:none;">${h.name}</a></strong>
                <br><span style="color:#757575;font-size:.72rem;">${types}</span>
                ${dist}
                ${h.phone ? '<br><a href="tel:'+h.phone+'" style="color:#388E3C;font-size:.75rem;">'+h.phone+'</a>' : ''}
            </div>`);
        mapMarkers.push(marker); bounds.extend([h.coordinates.latitude,h.coordinates.longitude]);
    });
    if (proximiteActive && userLat && userLng) { const um=L.circleMarker([userLat,userLng],{radius:8,color:'#1565C0',fillColor:'#42A5F5',fillOpacity:.8,weight:2}).addTo(resultsMap); mapMarkers.push(um); bounds.extend([userLat,userLng]); }
    resultsMap.fitBounds(bounds,{padding:[30,30],maxZoom:15});
    setTimeout(()=>resultsMap.invalidateSize(),100);
}

function resetSearch() {
    document.getElementById('searchQ').value='';
    document.getElementById('searchCity').value='';
    document.getElementById('searchType').value='';
    document.getElementById('searchSpecialty').value='';
    userLat=null; userLng=null; proximiteActive=false;
    document.getElementById('btnProximite').classList.remove('active');
    doSearch();
}

document.getElementById('searchQ').addEventListener('keydown', e => { if(e.key==='Enter') doSearch(); });
init();
</script>
@endsection
