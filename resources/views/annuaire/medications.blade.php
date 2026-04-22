@extends('layouts.app')

@section('title', 'Trouver un medicament — HOSTO')
@section('breadcrumb')
<li><span class="sep">/</span> <span class="current">Medicaments</span></li>
@endsection

@section('styles')
<style>
    .med-header { background:linear-gradient(135deg,#2E7D32,#43A047); padding:56px 0 100px; color:white; text-align:center; }
    .med-header h1 { font-size:clamp(1.6rem,4vw,2.2rem); font-weight:700; margin-bottom:8px; }
    .search-wrapper { margin-top:-50px; position:relative; z-index:20; margin-bottom:32px; }
    .search-bar { background:white; border-radius:16px; padding:14px; box-shadow:0 12px 48px rgba(0,0,0,.12); display:flex; gap:10px; border:1px solid #EEE; flex-wrap:wrap; align-items:center; }
    .search-field { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; flex:1; min-width:180px; border:2px solid #EEE; position:relative; }
    .search-field:focus-within { border-color:#388E3C; }
    .search-field svg { flex-shrink:0; color:#388E3C; }
    .search-field input { border:none; outline:none; font-family:Poppins,sans-serif; font-size:.85rem; width:100%; background:transparent; }
    .search-btn { padding:12px 28px; background:#388E3C; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.85rem; font-weight:600; cursor:pointer; white-space:nowrap; }
    .search-btn:hover { background:#2E7D32; }

    /* Autocomplete dropdown */
    .ac-dropdown { position:absolute; top:100%; left:-2px; right:-2px; background:white; border:1px solid #EEE; border-top:none; border-radius:0 0 10px 10px; box-shadow:0 8px 24px rgba(0,0,0,.1); max-height:240px; overflow-y:auto; display:none; z-index:30; }
    .ac-dropdown.open { display:block; }
    .ac-item { padding:10px 14px; font-size:.82rem; cursor:pointer; display:flex; justify-content:space-between; align-items:center; }
    .ac-item:hover, .ac-item.active { background:#E8F5E9; }
    .ac-item-name { font-weight:500; color:#1B2A1B; }
    .ac-item-region { font-size:.72rem; color:#757575; }

    .results-info { font-size:.82rem; color:#757575; margin-bottom:16px; }
    .results-count { font-weight:600; color:#388E3C; }

    /* Pharmacy group card */
    .pharm-group { background:white; border:1px solid #EEE; border-radius:14px; margin-bottom:16px; overflow:hidden; }
    .pharm-group:hover { border-color:#C8E6C9; box-shadow:0 4px 16px rgba(56,142,60,.08); }
    .pharm-header { padding:16px 20px; display:flex; justify-content:space-between; align-items:start; gap:16px; flex-wrap:wrap; border-bottom:1px solid #F5F5F5; }
    .pharm-name { font-size:.95rem; font-weight:700; color:#1B2A1B; }
    .pharm-name a { color:inherit; text-decoration:none; }
    .pharm-name a:hover { color:#388E3C; }
    .pharm-location { font-size:.78rem; color:#757575; margin-top:2px; display:flex; align-items:center; gap:4px; }
    .pharm-phone { font-size:.78rem; color:#388E3C; margin-top:2px; }
    .insurance-list { display:flex; gap:4px; flex-wrap:wrap; }
    .insurance-badge { padding:2px 8px; background:#E3F2FD; color:#1565C0; border-radius:100px; font-size:.62rem; font-weight:600; }

    /* Medication rows inside pharmacy */
    .med-rows { padding:0; }
    .med-row { display:grid; grid-template-columns:1fr auto; gap:16px; align-items:center; padding:12px 20px; border-bottom:1px solid #FAFAFA; }
    .med-row:last-child { border-bottom:none; }
    .med-row:hover { background:#FAFAFA; }
    .med-dci { font-size:.85rem; font-weight:600; color:#1B2A1B; }
    .med-detail { font-size:.72rem; color:#757575; }
    .med-brands { display:flex; gap:4px; flex-wrap:wrap; margin-top:3px; }
    .med-brand { padding:2px 8px; background:#F5F5F5; border-radius:100px; font-size:.62rem; color:#424242; }
    .med-rx { padding:2px 8px; background:#FFEBEE; color:#C62828; border-radius:100px; font-size:.62rem; font-weight:600; }

    .med-right { display:flex; align-items:center; gap:12px; }
    .price-tag { font-size:1rem; font-weight:700; color:#388E3C; white-space:nowrap; }
    .price-currency { font-size:.68rem; font-weight:400; color:#757575; }
    .stock-badge { padding:3px 10px; border-radius:100px; font-size:.65rem; font-weight:600; white-space:nowrap; }
    .stock-ok { background:#E8F5E9; color:#2E7D32; }
    .stock-low { background:#FFF3E0; color:#E65100; }

    .loading { text-align:center; padding:40px; color:#757575; font-size:.85rem; }
    .empty-state { text-align:center; padding:60px 20px; color:#757575; }
    .empty-state svg { width:60px; height:60px; color:#BDBDBD; margin-bottom:16px; }

    .load-more { display:block; margin:0 auto 40px; padding:10px 32px; background:white; border:2px solid #388E3C; color:#388E3C; border-radius:10px; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:600; cursor:pointer; }
    .load-more:hover { background:#E8F5E9; }

    @media(max-width:768px) {
        .search-bar { flex-direction:column; }
        .pharm-header { flex-direction:column; }
        .med-row { grid-template-columns:1fr; gap:8px; }
        .med-right { justify-content:flex-start; }
    }
</style>
@endsection

@section('content')
<div class="med-header">
    <div class="container">
        <h1>Trouver un medicament</h1>
        <p style="opacity:.85;">Recherchez un medicament et trouvez les pharmacies qui le proposent pres de chez vous</p>
    </div>
</div>

<div class="container">
    <div class="search-wrapper">
        <form class="search-bar" onsubmit="searchMed(event)">
            <div class="search-field">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" id="medQ" placeholder="Nom du medicament (Paracetamol, Doliprane...)" autofocus>
            </div>
            <div class="search-field" id="cityFieldWrap">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <input type="text" id="medCity" placeholder="Ville..." value="Libreville" autocomplete="off" onfocus="onCityFocus()" oninput="onCityInput()" onblur="onCityBlur()">
                <div class="ac-dropdown" id="cityDropdown"></div>
            </div>
            <button type="submit" class="search-btn">Rechercher</button>
        </form>
    </div>

    <div id="resultsInfo" class="results-info" style="display:none;"></div>
    <div id="medLoading" class="loading" style="display:none;">Recherche en cours...</div>
    <div id="medResults"></div>
    <div id="medEmpty" class="empty-state" style="display:none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/><path d="M8 11h6"/></svg>
        <p>Aucun resultat.</p>
        <p style="font-size:.78rem;margin-top:4px;">Essayez un autre nom de medicament ou une autre ville.</p>
    </div>
    <button id="loadMoreBtn" class="load-more" style="display:none;" onclick="loadMore()">Voir plus de resultats</button>
</div>
@endsection

@section('scripts')
<script>
let currentPage = 1;
let lastPage = 1;
let allResults = [];
let cityDebounce = null;
let acActiveIdx = -1;

// ===== City autocomplete =====
function onCityFocus() {
    const input = document.getElementById('medCity');
    if (input.value.length >= 1) fetchCities(input.value);
}

function onCityInput() {
    clearTimeout(cityDebounce);
    acActiveIdx = -1;
    cityDebounce = setTimeout(() => {
        const val = document.getElementById('medCity').value.trim();
        if (val.length >= 1) fetchCities(val);
        else closeCityDropdown();
    }, 250);
}

function onCityBlur() {
    // Delay to allow click on dropdown item
    setTimeout(closeCityDropdown, 200);
}

async function fetchCities(q) {
    try {
        const res = await fetch(`${API}/referentiel/cities?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        const dd = document.getElementById('cityDropdown');
        if (!data.data.length) { closeCityDropdown(); return; }
        dd.innerHTML = data.data.map((c, i) =>
            `<div class="ac-item" data-idx="${i}" onmousedown="selectCity('${escAttr(c.name)}')">`
            + `<span class="ac-item-name">${esc(c.name)}</span>`
            + `<span class="ac-item-region">${esc(c.region || '')}${c.country ? ', ' + esc(c.country) : ''}</span>`
            + `</div>`
        ).join('');
        dd.classList.add('open');
    } catch(e) { closeCityDropdown(); }
}

function selectCity(name) {
    document.getElementById('medCity').value = name;
    closeCityDropdown();
}

function closeCityDropdown() {
    document.getElementById('cityDropdown').classList.remove('open');
}

// Keyboard navigation in dropdown
document.getElementById('medCity').addEventListener('keydown', function(e) {
    const dd = document.getElementById('cityDropdown');
    const items = dd.querySelectorAll('.ac-item');
    if (!dd.classList.contains('open') || !items.length) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); acActiveIdx = Math.min(acActiveIdx + 1, items.length - 1); highlightAcItem(items); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); acActiveIdx = Math.max(acActiveIdx - 1, 0); highlightAcItem(items); }
    else if (e.key === 'Enter' && acActiveIdx >= 0) { e.preventDefault(); items[acActiveIdx].dispatchEvent(new Event('mousedown')); }
    else if (e.key === 'Escape') { closeCityDropdown(); }
});

function highlightAcItem(items) {
    items.forEach((it, i) => it.classList.toggle('active', i === acActiveIdx));
}

// ===== Medication search =====
async function searchMed(e) {
    if (e) e.preventDefault();
    currentPage = 1;
    allResults = [];
    document.getElementById('medResults').innerHTML = '';
    await fetchResults();
}

async function loadMore() {
    currentPage++;
    await fetchResults(true);
}

async function fetchResults(append = false) {
    const q = document.getElementById('medQ').value.trim();
    const city = document.getElementById('medCity').value.trim();

    if (!q) {
        document.getElementById('medResults').innerHTML = '';
        document.getElementById('resultsInfo').style.display = 'none';
        document.getElementById('medEmpty').style.display = 'none';
        document.getElementById('loadMoreBtn').style.display = 'none';
        return;
    }

    const params = new URLSearchParams();
    params.set('medication', q);
    if (city) params.set('city', city);
    params.set('per_page', '50');
    params.set('page', currentPage);

    document.getElementById('medLoading').style.display = 'block';
    if (!append) {
        document.getElementById('medResults').innerHTML = '';
        document.getElementById('medEmpty').style.display = 'none';
        allResults = [];
    }
    document.getElementById('loadMoreBtn').style.display = 'none';

    try {
        const res = await fetch(`${API}/pharma/stock?${params}`);
        const data = await res.json();
        document.getElementById('medLoading').style.display = 'none';

        lastPage = data.meta.last_page;
        allResults = allResults.concat(data.data);

        if (!allResults.length) {
            document.getElementById('medEmpty').style.display = 'block';
            document.getElementById('resultsInfo').style.display = 'none';
            return;
        }

        // Group results by pharmacy
        const grouped = groupByPharmacy(allResults);

        document.getElementById('resultsInfo').style.display = 'block';
        const totalPharmacies = Object.keys(grouped).length;
        document.getElementById('resultsInfo').innerHTML =
            `<span class="results-count">${data.meta.total}</span> resultat${data.meta.total > 1 ? 's' : ''} dans <span class="results-count">${totalPharmacies}</span> pharmacie${totalPharmacies > 1 ? 's' : ''}${city ? ' a <strong>' + esc(city) + '</strong>' : ''}`;

        renderGrouped(grouped);

        document.getElementById('loadMoreBtn').style.display = currentPage < lastPage ? 'block' : 'none';
    } catch (err) {
        document.getElementById('medLoading').style.display = 'none';
    }
}

function groupByPharmacy(results) {
    const map = {};
    results.forEach(item => {
        const key = item.pharmacy.uuid;
        if (!map[key]) {
            map[key] = { pharmacy: item.pharmacy, medications: [] };
        }
        map[key].medications.push(item);
    });
    return map;
}

function renderGrouped(grouped) {
    const container = document.getElementById('medResults');
    container.innerHTML = '';

    Object.values(grouped).forEach(group => {
        const ph = group.pharmacy;
        const insurances = (ph.accepted_insurances || [])
            .map(ins => `<span class="insurance-badge">${esc(ins)}</span>`)
            .join('');

        let medsHtml = '';
        group.medications.forEach(item => {
            const brands = (item.medication.brands || [])
                .map(b => `<span class="med-brand">${esc(b.name)}${b.manufacturer ? ' — ' + esc(b.manufacturer) : ''}</span>`)
                .join('');
            const rx = item.medication.prescription_required ? ' <span class="med-rx">Sur ordonnance</span>' : '';
            const stockLevel = item.quantity_in_stock > 20 ? 'stock-ok' : 'stock-low';
            const stockLabel = item.quantity_in_stock > 20 ? 'En stock' : 'Stock limite';
            const price = item.unit_price
                ? `<span class="price-tag">${new Intl.NumberFormat('fr-FR').format(item.unit_price)} <span class="price-currency">${item.currency}</span></span>`
                : `<span style="font-size:.78rem;color:#757575;">—</span>`;

            medsHtml += `<div class="med-row">
                <div>
                    <div class="med-dci">${esc(item.medication.dci)} ${esc(item.medication.strength || '')}${rx}</div>
                    <div class="med-detail">${esc(item.medication.dosage_form || '')}</div>
                    ${brands ? `<div class="med-brands">${brands}</div>` : ''}
                </div>
                <div class="med-right">
                    ${price}
                    <span class="stock-badge ${stockLevel}">${stockLabel}</span>
                </div>
            </div>`;
        });

        const card = document.createElement('div');
        card.className = 'pharm-group';
        card.innerHTML = `
            <div class="pharm-header">
                <div>
                    <div class="pharm-name"><a href="/annuaire/${esc(ph.slug)}">${esc(ph.name)}</a></div>
                    <div class="pharm-location">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        ${esc(ph.city || '')}${ph.address ? ' — ' + esc(ph.address) : ''}
                    </div>
                    ${ph.phone ? `<div class="pharm-phone">${esc(ph.phone)}</div>` : ''}
                </div>
                ${insurances ? `<div class="insurance-list" title="Assurances acceptees">${insurances}</div>` : ''}
            </div>
            <div class="med-rows">${medsHtml}</div>`;
        container.appendChild(card);
    });
}

function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
function escAttr(str) {
    return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

// Auto-search from URL param
document.addEventListener('DOMContentLoaded', () => {
    const urlQ = new URLSearchParams(window.location.search).get('q');
    if (urlQ) {
        document.getElementById('medQ').value = urlQ;
        searchMed();
    }
});
</script>
@endsection
