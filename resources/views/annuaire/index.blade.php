@extends('layouts.app')

@section('title', 'Annuaire des structures de sante — HOSTO')
@section('description', 'Trouvez un hopital, une pharmacie, un laboratoire pres de chez vous. Annuaire geolocalise des structures de sante.')

@section('styles')
<style>
    .annuaire-header {
        background: linear-gradient(135deg, var(--green-dark), var(--green-mid));
        padding: 56px 0 100px; color: white; text-align: center;
    }
    .annuaire-header h1 { font-size: clamp(1.6rem, 4vw, 2.2rem); font-weight: 700; margin-bottom: 8px; }
    .annuaire-header p { font-size: .95rem; opacity: .85; max-width: 500px; margin: 0 auto; }

    .search-wrapper { margin-top: -50px; position: relative; z-index: 10; margin-bottom: 32px; }
    .search-bar {
        background: var(--white); border-radius: var(--radius); padding: 12px;
        box-shadow: var(--shadow-lg); display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto;
        gap: 8px; align-items: center; border: 1px solid var(--gray-200);
    }
    .search-field { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: var(--radius-sm); }
    .search-field:hover { background: var(--gray-50); }
    .search-field svg { width: 18px; height: 18px; color: var(--green); flex-shrink: 0; }
    .search-field input, .search-field select {
        border: none; outline: none; font-family: 'Poppins',sans-serif;
        font-size: .85rem; color: var(--dark); background: transparent; width: 100%;
    }
    .search-field select { cursor: pointer; }
    .search-btn {
        padding: 12px 28px; background: var(--green); color: white; border: none;
        border-radius: var(--radius-sm); font-family: 'Poppins',sans-serif;
        font-size: .85rem; font-weight: 600; cursor: pointer; transition: all var(--transition);
        display: flex; align-items: center; gap: 8px; white-space: nowrap;
    }
    .search-btn:hover { background: var(--green-dark); }
    .search-btn svg { width: 16px; height: 16px; }

    .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
    .toolbar-title { font-size: 1.1rem; font-weight: 600; color: var(--dark); }
    .toolbar-actions { display: flex; gap: 8px; }

    .results-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px; margin-bottom: 40px;
    }
    .hosto-card {
        background: white; border: 1px solid var(--gray-200); border-radius: var(--radius);
        overflow: hidden; transition: all var(--transition); cursor: pointer;
    }
    .hosto-card:hover { border-color: var(--green-light); transform: translateY(-4px); box-shadow: var(--shadow-md); }
    .hosto-card-body { padding: 16px 20px; }
    .hosto-card-top { display: flex; gap: 14px; align-items: start; margin-bottom: 10px; }
    .hosto-card-img { width: 52px; height: 52px; border-radius: 12px; object-fit: cover; background: var(--green-pale); flex-shrink: 0; }
    .hosto-card-name { font-size: .9rem; font-weight: 600; color: var(--dark); line-height: 1.3; }
    .hosto-card-type { font-size: .72rem; color: var(--gray-600); margin-top: 2px; }
    .hosto-card-loc { font-size: .72rem; color: var(--gray-600); }
    .hosto-card-dist { font-size: .78rem; font-weight: 600; color: var(--green); white-space: nowrap; }
    .hosto-card-specs { font-size: .7rem; color: var(--green); margin-bottom: 8px; }
    .hosto-card-tags { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
    .tag { padding: 2px 10px; border-radius: 100px; font-size: .68rem; font-weight: 600; }
    .tag-garde { background: #FFF3E0; color: #E65100; }
    .tag-open { color: var(--green); font-size: .72rem; }
    .tag-closed { color: #E53935; font-size: .72rem; }
    .tag-phone { font-size: .7rem; color: var(--gray-600); }

    .empty-state { text-align: center; padding: 60px 20px; color: var(--gray-600); }
    .empty-state svg { width: 64px; height: 64px; color: var(--gray-200); margin-bottom: 16px; }
    .loading { text-align: center; padding: 40px; color: var(--gray-600); }

    /* View toggle (list / map) */
    .view-toggle { display: flex; border: 2px solid var(--gray-200); border-radius: 10px; overflow: hidden; }
    .view-toggle button {
        padding: 8px 16px; border: none; background: white; font-family: 'Poppins',sans-serif;
        font-size: .78rem; font-weight: 500; cursor: pointer; display: flex; align-items: center;
        gap: 6px; color: var(--gray-600); transition: all var(--transition);
    }
    .view-toggle button.active { background: var(--green); color: white; }
    .view-toggle button svg { width: 16px; height: 16px; }

    /* Proximity toggle */
    .btn-toggle { transition: all var(--transition); }
    .btn-toggle.active { background: var(--green-dark); box-shadow: inset 0 2px 4px rgba(0,0,0,.2); }

    /* Map container */
    .map-results { border-radius: var(--radius); overflow: hidden; height: 500px; display: none; margin-bottom: 24px; }

    .pagination { display: flex; justify-content: center; gap: 8px; padding: 20px 0; }
    .pagination button {
        padding: 8px 16px; border: 1px solid var(--gray-200); border-radius: 8px;
        background: white; font-family: 'Poppins',sans-serif; font-size: .82rem;
        cursor: pointer; transition: all var(--transition);
    }
    .pagination button:hover { border-color: var(--green); color: var(--green); }
    .pagination button.active { background: var(--green); color: white; border-color: var(--green); }
    .pagination button:disabled { opacity: .4; cursor: default; }

    @media (max-width: 768px) {
        .search-bar { grid-template-columns: 1fr !important; }
        .search-btn { justify-content: center; }
        .results-grid { grid-template-columns: 1fr; }
        .annuaire-header { padding: 32px 0 72px; }
        .annuaire-header h1 { font-size: 1.4rem; }
        .map-results { height: 350px; }
        .toolbar { flex-direction: column; align-items: stretch; }
        .toolbar-actions { justify-content: space-between; }
    }
</style>
@endsection

@section('content')
<div class="annuaire-header">
    <div class="container">
        <h1>Annuaire des structures de sante</h1>
        <p>Trouvez un hopital, une pharmacie, un laboratoire pres de chez vous</p>
    </div>
</div>

<div class="container">
    <div class="search-wrapper">
        <form class="search-bar" id="searchForm" onsubmit="doSearch(event)">
            <div class="search-field">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" id="searchQ" placeholder="Nom de la structure..." value="{{ request('q') }}">
            </div>
            <div class="search-field">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <select id="searchCity"><option value="">Toutes les villes</option></select>
            </div>
            <div class="search-field">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <select id="searchType"><option value="">Type de structure</option></select>
            </div>
            <div class="search-field">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                <select id="searchSpecialty"><option value="">Specialite</option></select>
            </div>
            <button type="submit" class="search-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                Rechercher
            </button>
        </form>
    </div>

    <div class="toolbar">
        <div class="toolbar-title" id="resultsTitle">Structures de sante</div>
        <div class="toolbar-actions">
            <button id="btnProximite" onclick="toggleProximite()" class="btn btn-outline-green btn-sm btn-toggle">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                A proximite
            </button>
            <div class="view-toggle">
                <button id="btnViewList" class="active" onclick="setView('list')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    Liste
                </button>
                <button id="btnViewMap" onclick="setView('map')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                    Carte
                </button>
            </div>
            <button onclick="resetSearch()" class="btn btn-outline-green btn-sm">Reinitialiser</button>
        </div>
    </div>

    <div id="loading" class="loading" style="display:none;">Recherche en cours...</div>
    <div id="mapResults" class="map-results"></div>
    <div id="resultsList" class="results-grid"></div>
    <div id="emptyState" class="empty-state" style="display:none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <p>Aucune structure trouvee pour cette recherche.</p>
    </div>
    <div id="pagination" class="pagination"></div>
</div>
@endsection

@section('scripts')
<script>
let userLat = null, userLng = null;
let currentPage = 1;
let currentView = 'list'; // 'list' or 'map'
let proximiteActive = false;
let resultsMap = null;
let mapMarkers = [];
let lastResults = [];

const urlParams = new URLSearchParams(window.location.search);

async function init() {
    await Promise.all([loadDropdowns(), loadCities()]);
    if (urlParams.get('q')) document.getElementById('searchQ').value = urlParams.get('q');
    if (urlParams.get('type')) document.getElementById('searchType').value = urlParams.get('type');
    if (urlParams.get('specialty')) document.getElementById('searchSpecialty').value = urlParams.get('specialty');
    if (urlParams.get('city')) document.getElementById('searchCity').value = urlParams.get('city');
    doSearch();
}

async function loadDropdowns() {
    try {
        const [typesRes, specsRes] = await Promise.all([
            fetch(`${API}/referentiel/structure-types`).then(r => r.json()),
            fetch(`${API}/referentiel/specialties`).then(r => r.json()),
        ]);
        const ts = document.getElementById('searchType');
        ts.innerHTML = '<option value="">Type de structure</option>';
        typesRes.data.forEach(t => { const o = document.createElement('option'); o.value = t.slug; o.textContent = t.name; ts.appendChild(o); });
        const ss = document.getElementById('searchSpecialty');
        ss.innerHTML = '<option value="">Specialite</option>';
        specsRes.data.forEach(s => {
            const o = document.createElement('option'); o.value = s.code; o.textContent = s.name; ss.appendChild(o);
            (s.children||[]).forEach(c => { const co = document.createElement('option'); co.value = c.code; co.textContent = '\u00A0\u00A0\u00A0'+c.name; ss.appendChild(co); });
        });
    } catch(e) { console.error(e); }
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
            cities.forEach(c => { const o = document.createElement('option'); o.value = c.uuid; o.textContent = c.name; og.appendChild(o); });
            cs.appendChild(og);
        }
    } catch(e) { console.error(e); }
}

function onCountryChanged() { loadCities(); doSearch(); }

// --- Proximity toggle (NOT active by default) ---
function toggleProximite() {
    if (proximiteActive) {
        proximiteActive = false;
        userLat = null; userLng = null;
        document.getElementById('btnProximite').classList.remove('active');
        doSearch();
    } else {
        if (!navigator.geolocation) { alert('Geolocalisation non supportee.'); return; }
        navigator.geolocation.getCurrentPosition(
            pos => {
                userLat = pos.coords.latitude; userLng = pos.coords.longitude;
                proximiteActive = true;
                document.getElementById('btnProximite').classList.add('active');
                doSearch();
            },
            () => {
                userLat = 0.39; userLng = 9.45;
                proximiteActive = true;
                document.getElementById('btnProximite').classList.add('active');
                doSearch();
            }
        );
    }
}

// --- View toggle ---
function setView(view) {
    currentView = view;
    document.getElementById('btnViewList').classList.toggle('active', view === 'list');
    document.getElementById('btnViewMap').classList.toggle('active', view === 'map');
    document.getElementById('resultsList').style.display = view === 'list' ? '' : 'none';
    document.getElementById('mapResults').style.display = view === 'map' ? 'block' : 'none';
    document.getElementById('pagination').style.display = view === 'list' ? '' : 'none';
    if (view === 'map') renderMap();
}

// --- Search ---
async function doSearch(e, page) {
    if (e) e.preventDefault();
    currentPage = page || 1;

    const params = new URLSearchParams();
    const q = document.getElementById('searchQ').value.trim();
    const city = document.getElementById('searchCity').value;
    const type = document.getElementById('searchType').value;
    const specialty = document.getElementById('searchSpecialty').value;

    if (q) params.set('q', q);
    if (city) params.set('city', city);
    if (type) params.set('type', type);
    if (specialty) params.set('specialty', specialty);
    if (urlParams.get('garde') === '1') params.set('garde', '1');
    if (proximiteActive && userLat && userLng) {
        params.set('lat', userLat); params.set('lng', userLng);
        params.set('rayon', '20'); params.set('sort', 'distance');
    }
    params.set('per_page', currentView === 'map' ? '50' : '12');
    params.set('page', currentPage);

    const newUrl = '/annuaire' + (params.toString() ? '?' + params : '');
    history.replaceState(null, '', newUrl);

    document.getElementById('loading').style.display = 'block';
    document.getElementById('resultsList').innerHTML = '';
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('pagination').innerHTML = '';

    try {
        const res = await fetch(`${API}/annuaire/hostos?${params}`);
        const data = await res.json();
        document.getElementById('loading').style.display = 'none';

        const total = data.meta?.total || 0;
        document.getElementById('resultsTitle').textContent = total > 0
            ? `${total} structure${total > 1 ? 's' : ''} trouvee${total > 1 ? 's' : ''}`
            : 'Structures de sante';

        lastResults = data.data || [];

        if (total === 0) { document.getElementById('emptyState').style.display = 'block'; return; }

        const list = document.getElementById('resultsList');
        lastResults.forEach(h => list.appendChild(buildCard(h)));
        buildPagination(data.meta);

        if (currentView === 'map') renderMap();
    } catch(err) {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('emptyState').style.display = 'block';
    }
}

// --- Build card ---
function buildCard(h) {
    const card = document.createElement('a');
    card.href = `/annuaire/${h.slug}`;
    card.className = 'hosto-card';
    const img = h.profile_image || '/images/icons/icon-hopitaux.png';
    const types = (h.types||[]).map(t => t.name).join(', ');
    const specs = (h.specialties||[]).slice(0,3).map(s => s.name).join(', ');
    const dist = h.distance_km != null ? `<span class="hosto-card-dist">${h.distance_km} km</span>` : '';
    const guard = h.is_guard_service ? '<span class="tag tag-garde">Garde</span>' : '';
    const open = h.is_open_now === true ? '<span class="tag-open">Ouvert</span>' : h.is_open_now === false ? '<span class="tag-closed">Ferme</span>' : '';
    const city = h.city?.name || '';
    const quarter = h.quarter ? ` - ${h.quarter}` : '';
    card.innerHTML = `<div class="hosto-card-body">
        <div class="hosto-card-top">
            <img src="${img}" alt="${h.name}" class="hosto-card-img">
            <div style="flex:1;min-width:0;">
                <div style="display:flex;justify-content:space-between;gap:8px;">
                    <div class="hosto-card-name">${h.name}</div>${dist}
                </div>
                <div class="hosto-card-type">${types}</div>
                <div class="hosto-card-loc">${city}${quarter}</div>
            </div>
        </div>
        ${specs ? `<div class="hosto-card-specs">${specs}</div>` : ''}
        <div class="hosto-card-tags">${guard} ${open} ${h.phone ? `<span class="tag-phone">${h.phone}</span>` : ''}</div>
    </div>`;
    return card;
}

function buildPagination(meta) {
    if (!meta || meta.last_page <= 1) return;
    const p = document.getElementById('pagination');
    for (let i = 1; i <= meta.last_page; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        if (i === meta.current_page) btn.className = 'active';
        btn.onclick = () => doSearch(null, i);
        p.appendChild(btn);
    }
}

// --- Map rendering ---
function renderMap() {
    const container = document.getElementById('mapResults');
    container.style.display = 'block';

    // Clear existing markers.
    mapMarkers.forEach(m => m.remove());
    mapMarkers = [];

    // Collect structures with coordinates.
    const withCoords = lastResults.filter(h => h.coordinates && h.coordinates.latitude);

    if (withCoords.length === 0) {
        if (!resultsMap) {
            resultsMap = L.map('mapResults').setView([0.39, 9.45], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap', maxZoom: 19,
            }).addTo(resultsMap);
        }
        return;
    }

    // Initialize map on first use.
    if (!resultsMap) {
        resultsMap = L.map('mapResults').setView([withCoords[0].coordinates.latitude, withCoords[0].coordinates.longitude], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap', maxZoom: 19,
        }).addTo(resultsMap);
    }

    // Custom green marker icon.
    const greenIcon = L.divIcon({
        className: '',
        html: '<div style="background:var(--green);width:28px;height:28px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,.3);"></div>',
        iconSize: [28, 28],
        iconAnchor: [14, 28],
        popupAnchor: [0, -28],
    });

    // Add markers.
    const bounds = L.latLngBounds();
    withCoords.forEach(h => {
        const lat = h.coordinates.latitude;
        const lng = h.coordinates.longitude;
        const types = (h.types||[]).map(t => t.name).join(', ');
        const dist = h.distance_km != null ? `<br><strong>${h.distance_km} km</strong>` : '';

        const marker = L.marker([lat, lng], { icon: greenIcon })
            .addTo(resultsMap)
            .bindPopup(`<div style="font-family:Poppins,sans-serif;font-size:.82rem;min-width:160px;">
                <strong><a href="/annuaire/${h.slug}" style="color:#388E3C;text-decoration:none;">${h.name}</a></strong>
                <br><span style="color:#757575;font-size:.72rem;">${types}</span>
                ${dist}
                ${h.phone ? `<br><a href="tel:${h.phone}" style="color:#388E3C;font-size:.75rem;">${h.phone}</a>` : ''}
            </div>`);

        mapMarkers.push(marker);
        bounds.extend([lat, lng]);
    });

    // Add user position marker if proximity active.
    if (proximiteActive && userLat && userLng) {
        const userMarker = L.circleMarker([userLat, userLng], {
            radius: 8, color: '#1565C0', fillColor: '#42A5F5', fillOpacity: 0.8, weight: 2,
        }).addTo(resultsMap).bindPopup('Votre position');
        mapMarkers.push(userMarker);
        bounds.extend([userLat, userLng]);
    }

    // Fit map to show all markers.
    resultsMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });

    // Fix Leaflet tiles not loading properly in hidden container.
    setTimeout(() => resultsMap.invalidateSize(), 100);
}

function resetSearch() {
    document.getElementById('searchQ').value = '';
    document.getElementById('searchCity').value = '';
    document.getElementById('searchType').value = '';
    document.getElementById('searchSpecialty').value = '';
    userLat = null; userLng = null;
    proximiteActive = false;
    document.getElementById('btnProximite').classList.remove('active');
    history.replaceState(null, '', '/annuaire');
    doSearch();
}

init();
</script>
@endsection
