@extends('layouts.app')

@section('title', 'Annuaire des structures de sante — HOSTO')
@section('description', 'Trouvez un hopital, une pharmacie, un laboratoire pres de chez vous. Annuaire geolocalise des structures de sante.')

@section('styles')
<style>
    .annuaire-header {
        background: linear-gradient(135deg, var(--green-dark), var(--green-mid));
        padding: 48px 0 80px; color: white; text-align: center;
    }
    .annuaire-header h1 { font-size: clamp(1.6rem, 4vw, 2.2rem); font-weight: 700; margin-bottom: 8px; }
    .annuaire-header p { font-size: .95rem; opacity: .85; max-width: 500px; margin: 0 auto; }

    .search-wrapper { margin-top: -40px; position: relative; z-index: 10; margin-bottom: 32px; }
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
        .annuaire-header { padding: 32px 0 64px; }
        .annuaire-header h1 { font-size: 1.4rem; }
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
            <button onclick="geolocateMe()" class="btn btn-primary btn-sm">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                A proximite
            </button>
            <button onclick="resetSearch()" class="btn btn-outline-green btn-sm">Reinitialiser</button>
        </div>
    </div>

    <div id="loading" class="loading" style="display:none;">Recherche en cours...</div>
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

// Read URL params on load.
const urlParams = new URLSearchParams(window.location.search);

async function init() {
    await Promise.all([loadDropdowns(), loadCities()]);

    // Restore filters from URL.
    if (urlParams.get('q')) document.getElementById('searchQ').value = urlParams.get('q');
    if (urlParams.get('type')) document.getElementById('searchType').value = urlParams.get('type');
    if (urlParams.get('specialty')) document.getElementById('searchSpecialty').value = urlParams.get('specialty');
    if (urlParams.get('city')) document.getElementById('searchCity').value = urlParams.get('city');
    if (urlParams.get('garde') === '1') userLat = null; // handled in search

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
    if (userLat && userLng) { params.set('lat', userLat); params.set('lng', userLng); params.set('rayon', '20'); params.set('sort', 'distance'); }
    params.set('per_page', '12');
    params.set('page', currentPage);

    // Update URL without reload.
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

        if (total === 0) { document.getElementById('emptyState').style.display = 'block'; return; }

        const list = document.getElementById('resultsList');
        data.data.forEach(h => list.appendChild(buildCard(h)));

        buildPagination(data.meta);
    } catch(err) {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('emptyState').style.display = 'block';
    }
}

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
                    <div class="hosto-card-name">${h.name}</div>
                    ${dist}
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

function geolocateMe() {
    if (!navigator.geolocation) { alert('Geolocalisation non supportee.'); return; }
    navigator.geolocation.getCurrentPosition(
        pos => { userLat = pos.coords.latitude; userLng = pos.coords.longitude; doSearch(); },
        () => { userLat = 0.39; userLng = 9.45; doSearch(); }
    );
}

function resetSearch() {
    document.getElementById('searchQ').value = '';
    document.getElementById('searchCity').value = '';
    document.getElementById('searchType').value = '';
    document.getElementById('searchSpecialty').value = '';
    userLat = null; userLng = null;
    history.replaceState(null, '', '/annuaire');
    doSearch();
}

init();
</script>
@endsection
