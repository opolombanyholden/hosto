{{-- Partial reutilisable pour toutes les vues de type "structures" --}}
{{-- Variables attendues : $pageTitle, $pageDesc, $pageIcon, $defaultType, $forceGarde, $markerColor --}}

@section('breadcrumb')
<span style="color:#BDBDBD;margin:0 6px;">/</span>
<span style="color:#424242;">{{ $pageTitle }}</span>
@endsection

@section('styles')
<style>
    .page-header { display:flex; align-items:center; gap:14px; margin-bottom:20px; }
    .page-header img { width:44px; height:44px; }
    .page-header h2 { font-size:1.1rem; font-weight:700; color:#1B2A1B; }
    .page-header p { font-size:.82rem; color:#757575; }

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
    .tag-urgence { background:#FFEBEE; color:#C62828; }
    .tag-evacuation { background:#F3E5F5; color:#6A1B9A; }
    .tag-domicile { background:#E8F5E9; color:#2E7D32; }
    .tag-phone { font-size:.68rem; color:#757575; }
    .hosto-card-insurances { display:flex; gap:3px; flex-wrap:wrap; margin-top:6px; }
    .tag-ins { padding:2px 7px; background:#E3F2FD; color:#1565C0; border-radius:100px; font-size:.58rem; font-weight:600; }

    .map-results { border-radius:14px; overflow:hidden; height:450px; display:none; margin-bottom:16px; position:relative; z-index:1; isolation:isolate; }
    .pagination { display:flex; justify-content:center; gap:6px; padding:16px 0; }
    .pagination button { padding:6px 14px; border:1px solid #EEE; border-radius:6px; background:white; font-family:Poppins,sans-serif; font-size:.78rem; cursor:pointer; }
    .pagination button:hover { border-color:#388E3C; color:#388E3C; }
    .pagination button.active { background:#388E3C; color:white; border-color:#388E3C; }
    .loading,.empty-state { text-align:center; padding:40px; color:#757575; font-size:.85rem; }

    /* Search FAB */
    .search-fab {
        position:fixed; bottom:24px; right:24px; width:52px; height:52px;
        background:#388E3C; border:none; border-radius:50%; cursor:pointer;
        box-shadow:0 4px 16px rgba(56,142,60,.3); display:flex; align-items:center;
        justify-content:center; z-index:60; transition:transform .2s;
    }
    .search-fab:hover { transform:scale(1.1); }
    .search-fab svg { width:24px; height:24px; stroke:white; fill:none; stroke-width:2; }

    /* Search panel overlay */
    .search-panel {
        position:fixed; top:0; right:-400px; width:380px; height:100%; background:white;
        box-shadow:-4px 0 24px rgba(0,0,0,.12); z-index:200; transition:right .3s ease;
        display:flex; flex-direction:column;
    }
    .search-panel.open { right:0; }
    .search-panel-header {
        padding:16px 20px; border-bottom:1px solid #EEE; display:flex;
        align-items:center; justify-content:space-between;
    }
    .search-panel-header h3 { font-size:.92rem; font-weight:600; color:#1B2A1B; }
    .search-panel-close {
        width:32px; height:32px; border:none; background:#F5F5F5; border-radius:8px;
        cursor:pointer; display:flex; align-items:center; justify-content:center;
    }
    .search-panel-body { padding:20px; flex:1; overflow-y:auto; }
    .search-panel-body .sp-field { margin-bottom:14px; }
    .search-panel-body .sp-field label { display:block; font-size:.78rem; font-weight:500; color:#424242; margin-bottom:4px; }
    .search-panel-body .sp-field input, .search-panel-body .sp-field select {
        width:100%; padding:10px 14px; border:2px solid #EEE; border-radius:8px;
        font-family:Poppins,sans-serif; font-size:.85rem; outline:none; box-sizing:border-box;
    }
    .search-panel-body .sp-field input:focus, .search-panel-body .sp-field select:focus { border-color:#388E3C; }
    .search-panel-actions { padding:16px 20px; border-top:1px solid #EEE; display:flex; gap:8px; }
    .search-panel-actions .sp-btn {
        flex:1; padding:10px; border:none; border-radius:8px; font-family:Poppins,sans-serif;
        font-size:.82rem; font-weight:600; cursor:pointer;
    }
    .sp-btn-primary { background:#388E3C; color:white; }
    .sp-btn-secondary { background:#F5F5F5; color:#757575; }
    .search-overlay {
        position:fixed; inset:0; background:rgba(0,0,0,.3); z-index:199;
        display:none; cursor:pointer;
    }
    .search-overlay.open { display:block; }

    /* Hide default search bar */
    .search-bar { display:none !important; }

    @media(max-width:768px) { .results-grid{grid-template-columns:1fr;} .map-results{height:300px;} .toolbar{flex-direction:column;} .search-panel{width:100%;right:-100%;} }
</style>
@endsection

@section('content')
@if(isset($alertHtml)){!! $alertHtml !!}@endif

<div class="page-header">
    <img src="{{ $pageIcon }}" alt="">
    <div>
        <h2>{{ $pageTitle }}</h2>
        <p>{{ $pageDesc }}</p>
    </div>
</div>

{{-- Search bar hidden (IDs kept for JS) --}}
<div class="search-bar">
    <input type="hidden" id="searchQ"><select id="searchCity" style="display:none;"></select>
    <select id="searchType" style="display:none;"></select><select id="searchSpecialty" style="display:none;"></select>
</div>

{{-- Search FAB --}}
<button class="search-fab" onclick="openSearchPanel()" title="Rechercher">
    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
</button>

{{-- Search panel (slide from right) --}}
<div class="search-overlay" id="searchOverlay" onclick="closeSearchPanel()"></div>
<div class="search-panel" id="searchPanel">
    <div class="search-panel-header">
        <h3>Rechercher</h3>
        <button class="search-panel-close" onclick="closeSearchPanel()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#424242" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="search-panel-body">
        <div class="sp-field">
            <label>Nom</label>
            <input type="text" id="spQ" placeholder="Rechercher...">
        </div>
        <div class="sp-field">
            <label>Ville</label>
            <select id="spCity"><option value="">Toutes les villes</option></select>
        </div>
        <div class="sp-field">
            <label>Type de structure</label>
            <select id="spType"><option value="">Tous les types</option></select>
        </div>
        <div class="sp-field">
            <label>Specialite</label>
            <select id="spSpecialty"><option value="">Toutes</option></select>
        </div>
        <div class="sp-field">
            <label>Assurance acceptee</label>
            <select id="spAssurance"><option value="">Toutes</option></select>
        </div>
        <div style="border-top:1px solid #EEE;padding-top:14px;margin-top:4px;">
            <label style="display:block;font-size:.78rem;font-weight:600;color:#424242;margin-bottom:8px;">Services proposes</label>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:.82rem;cursor:pointer;">
                    <input type="checkbox" id="spGarde" style="accent-color:#E65100;"> Service de garde
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:.82rem;cursor:pointer;">
                    <input type="checkbox" id="spUrgence" style="accent-color:#C62828;"> Service d'urgence
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:.82rem;cursor:pointer;">
                    <input type="checkbox" id="spEvacuation" style="accent-color:#6A1B9A;"> Service d'evacuation
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:.82rem;cursor:pointer;">
                    <input type="checkbox" id="spDomicile" style="accent-color:#2E7D32;"> Soins a domicile
                </label>
            </div>
        </div>
    </div>
    <div class="search-panel-actions">
        <button class="sp-btn sp-btn-secondary" onclick="resetSearch()">Reinitialiser</button>
        <button class="sp-btn sp-btn-primary" onclick="applySearch()">Rechercher</button>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-title" id="resultsTitle">{{ $pageTitle }}</div>
    <div class="toolbar-actions">
        <button id="btnProximite" onclick="toggleProximite()" class="btn-prox">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            A proximite
        </button>
        <div class="view-toggle">
            <button id="btnViewList" class="active" onclick="setView('list')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/></svg> Liste</button>
            <button id="btnViewMap" onclick="setView('map')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/></svg> Carte</button>
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
const FIXED_TYPE = '{{ $defaultType ?? '' }}';
const FORCE_GARDE = {{ ($forceGarde ?? false) ? 'true' : 'false' }};
const FORCE_URGENCE = {{ ($forceUrgence ?? false) ? 'true' : 'false' }};
const FORCE_DOMICILE = {{ ($forceDomicile ?? false) ? 'true' : 'false' }};
const MARKER_COLOR = '{{ $markerColor ?? '#388E3C' }}';
const PAGE_TITLE = '{{ $pageTitle }}';
let userLat=null, userLng=null, currentPage=1, currentView='list', proximiteActive=false, resultsMap=null, mapMarkers=[], lastResults=[];

async function init() {
    await Promise.all([loadDropdowns(), loadCities()]);
    if (FIXED_TYPE) document.getElementById('searchType').value = FIXED_TYPE;
    doSearch();
}

// Search panel
function openSearchPanel() {
    document.getElementById('searchPanel').classList.add('open');
    document.getElementById('searchOverlay').classList.add('open');
}
function closeSearchPanel() {
    document.getElementById('searchPanel').classList.remove('open');
    document.getElementById('searchOverlay').classList.remove('open');
}
function applySearch() {
    document.getElementById('searchQ').value = document.getElementById('spQ').value;
    document.getElementById('searchCity').value = document.getElementById('spCity').value;
    if (!FIXED_TYPE) document.getElementById('searchType').value = document.getElementById('spType').value;
    document.getElementById('searchSpecialty').value = document.getElementById('spSpecialty').value;
    closeSearchPanel();
    doSearch();
}
function getSelectedAssurance() { return document.getElementById('spAssurance').value; }
function isGardeChecked() { return document.getElementById('spGarde').checked; }
function isUrgenceChecked() { return document.getElementById('spUrgence').checked; }
function isEvacuationChecked() { return document.getElementById('spEvacuation').checked; }
function isDomicileChecked() { return document.getElementById('spDomicile').checked; }

async function loadDropdowns() {
    try {
        const [typesRes, specsRes] = await Promise.all([
            fetch(`${API}/referentiel/structure-types`).then(r=>r.json()),
            fetch(`${API}/referentiel/specialties`).then(r=>r.json()),
        ]);
        // Hidden selects
        const ts = document.getElementById('searchType');
        ts.innerHTML = '<option value="">Type de structure</option>';
        typesRes.data.forEach(t => { const o=document.createElement('option'); o.value=t.slug; o.textContent=t.name; ts.appendChild(o); });
        const ss = document.getElementById('searchSpecialty');
        ss.innerHTML = '<option value="">Specialite</option>';
        specsRes.data.forEach(s => {
            const o=document.createElement('option'); o.value=s.code; o.textContent=s.name; ss.appendChild(o);
            (s.children||[]).forEach(c => { const co=document.createElement('option'); co.value=c.code; co.textContent='\u00A0\u00A0\u00A0'+c.name; ss.appendChild(co); });
        });
        // Panel selects (clone)
        document.getElementById('spType').innerHTML = ts.innerHTML;
        document.getElementById('spSpecialty').innerHTML = ss.innerHTML;
        if (FIXED_TYPE) document.getElementById('spType').value = FIXED_TYPE;

        // Load insurances from reference_data
        try {
            const insRes = await fetch(`${API}/referentiel/reference-data?category=insurance_provider`);
            if (insRes.ok) {
                const insData = await insRes.json();
                const spIns = document.getElementById('spAssurance');
                (insData.data||[]).forEach(i => { const o=document.createElement('option'); o.value=i.code||i.label_fr; o.textContent=i.label_fr; spIns.appendChild(o); });
            }
        } catch(e2) {
            // Fallback static
            ['CNAMGS','ASCOMA','OGAR','AXA','NSIA','SUNU','Saham'].forEach(n => {
                const o=document.createElement('option'); o.value=n; o.textContent=n;
                document.getElementById('spAssurance').appendChild(o);
            });
        }
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
        // Clone to panel
        document.getElementById('spCity').innerHTML = cs.innerHTML;
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
    const type=FIXED_TYPE || document.getElementById('searchType').value;
    const specialty=document.getElementById('searchSpecialty').value;
    if (q) params.set('q',q);
    if (city) params.set('city',city);
    if (type) params.set('type',type);
    if (specialty) params.set('specialty',specialty);
    const assurance = getSelectedAssurance();
    if (assurance) params.set('assurance', assurance);
    if (FORCE_GARDE || isGardeChecked()) params.set('garde','1');
    if (FORCE_URGENCE || isUrgenceChecked()) params.set('urgence','1');
    if (isEvacuationChecked()) params.set('evacuation','1');
    if (FORCE_DOMICILE || isDomicileChecked()) params.set('domicile','1');
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
        document.getElementById('resultsTitle').textContent = total > 0 ? `${total} resultat${total>1?'s':''}` : PAGE_TITLE;
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
    card.href = `/annuaire/${h.slug}`; card.target = '_blank'; card.className = 'hosto-card';
    const img = h.profile_image || '/images/icons/icon-hopitaux.png';
    const types = (h.types||[]).map(t=>t.name).join(', ');
    const specs = (h.specialties||[]).slice(0,3).map(s=>s.name).join(', ');
    const dist = h.distance_km!=null ? `<span class="hosto-card-dist">${h.distance_km} km</span>` : '';
    let tags = '';
    if (h.is_guard_service) tags += '<span class="tag tag-garde">Garde</span>';
    if (h.is_emergency_service) tags += '<span class="tag tag-urgence">Urgence</span>';
    if (h.is_evacuation_service) tags += '<span class="tag tag-evacuation">Evacuation</span>';
    if (h.is_home_care_service) tags += '<span class="tag tag-domicile">Domicile</span>';
    const city = h.city?.name || '';
    const insurances = (h.accepted_insurances||[]).map(i=>`<span class="tag-ins">${i}</span>`).join('');
    card.innerHTML = `<div class="hosto-card-body"><div class="hosto-card-top"><img src="${img}" alt="" class="hosto-card-img"><div style="flex:1;min-width:0;"><div style="display:flex;justify-content:space-between;gap:6px;"><div class="hosto-card-name">${h.name}</div>${dist}</div><div class="hosto-card-type">${types}</div><div class="hosto-card-loc">${city}</div></div></div>${specs?`<div class="hosto-card-specs">${specs}</div>`:''}<div class="hosto-card-tags">${tags} ${h.phone?`<span class="tag-phone">${h.phone}</span>`:''}</div>${insurances?`<div class="hosto-card-insurances">${insurances}</div>`:''}</div>`;
    return card;
}

function buildPagination(meta) {
    if (!meta || meta.last_page<=1) return;
    const p = document.getElementById('pagination');
    for (let i=1; i<=meta.last_page; i++) {
        const btn = document.createElement('button'); btn.textContent = i;
        if (i===meta.current_page) btn.className='active';
        btn.onclick = () => doSearch(null,i); p.appendChild(btn);
    }
}

function renderMap() {
    const container = document.getElementById('mapResults');
    container.style.display = 'block';
    mapMarkers.forEach(m=>m.remove()); mapMarkers=[];
    const withCoords = lastResults.filter(h=>h.coordinates&&h.coordinates.latitude);
    if (!resultsMap) { resultsMap=L.map('mapResults').setView([0.39,9.45],12); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap',maxZoom:19}).addTo(resultsMap); }
    if (withCoords.length===0) return;
    const icon = L.divIcon({className:'',html:`<div style="background:${MARKER_COLOR};width:24px;height:24px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,.3);"></div>`,iconSize:[24,24],iconAnchor:[12,24],popupAnchor:[0,-24]});
    const bounds = L.latLngBounds();
    withCoords.forEach(h => {
        const types = (h.types||[]).map(t=>t.name).join(', ');
        const dist = h.distance_km!=null ? `<br><strong>${h.distance_km} km</strong>` : '';
        const marker = L.marker([h.coordinates.latitude,h.coordinates.longitude],{icon}).addTo(resultsMap)
            .bindPopup(`<div style="font-family:Poppins,sans-serif;font-size:.82rem;min-width:160px;"><strong><a href="/annuaire/${h.slug}" target="_blank" style="color:${MARKER_COLOR};text-decoration:none;">${h.name}</a></strong><br><span style="color:#757575;font-size:.72rem;">${types}</span>${dist}${h.phone?'<br><a href="tel:'+h.phone+'" style="color:'+MARKER_COLOR+';font-size:.75rem;">'+h.phone+'</a>':''}</div>`);
        mapMarkers.push(marker); bounds.extend([h.coordinates.latitude,h.coordinates.longitude]);
    });
    if (proximiteActive && userLat && userLng) { const um=L.circleMarker([userLat,userLng],{radius:8,color:'#1565C0',fillColor:'#42A5F5',fillOpacity:.8,weight:2}).addTo(resultsMap); mapMarkers.push(um); bounds.extend([userLat,userLng]); }
    resultsMap.fitBounds(bounds,{padding:[30,30],maxZoom:15});
    setTimeout(()=>resultsMap.invalidateSize(),100);
}

function resetSearch() {
    document.getElementById('searchQ').value='';
    document.getElementById('searchCity').value='';
    if (!FIXED_TYPE) document.getElementById('searchType').value='';
    document.getElementById('searchSpecialty').value='';
    document.getElementById('spQ').value='';
    document.getElementById('spCity').value='';
    if (!FIXED_TYPE) document.getElementById('spType').value='';
    document.getElementById('spSpecialty').value='';
    document.getElementById('spAssurance').value='';
    document.getElementById('spGarde').checked=false;
    document.getElementById('spUrgence').checked=false;
    document.getElementById('spEvacuation').checked=false;
    document.getElementById('spDomicile').checked=false;
    userLat=null; userLng=null; proximiteActive=false;
    document.getElementById('btnProximite').classList.remove('active');
    closeSearchPanel();
    doSearch();
}

document.getElementById('searchQ').addEventListener('keydown', e => { if(e.key==='Enter') doSearch(); });
init();
</script>
@endsection
