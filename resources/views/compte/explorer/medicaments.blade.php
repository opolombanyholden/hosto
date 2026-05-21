@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Medicaments') @section('page-title', 'Medicaments') @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'medicaments']) @endsection

@section('breadcrumb')
<span style="color:#BDBDBD;margin:0 6px;">/</span>
<span style="color:#424242;">Medicaments</span>
@endsection

@section('styles')
<style>
    .explorer-header { margin-bottom:20px; }
    .explorer-header h2 { font-size:1.2rem; font-weight:700; color:#1B2A1B; margin-bottom:4px; }
    .explorer-header p { font-size:.82rem; color:#757575; }

    .search-wrapper { margin-bottom:24px; }
    .search-bar { background:white; border-radius:14px; padding:12px; box-shadow:0 4px 16px rgba(0,0,0,.06); display:flex; gap:10px; border:1px solid #EEE; flex-wrap:wrap; align-items:center; }
    .search-field { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; flex:1; min-width:180px; border:2px solid #EEE; position:relative; }
    .search-field:focus-within { border-color:#388E3C; }
    .search-field svg { flex-shrink:0; color:#388E3C; }
    .search-field input { border:none; outline:none; font-family:Poppins,sans-serif; font-size:.85rem; width:100%; background:transparent; }
    .search-btn { padding:12px 24px; background:#388E3C; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.85rem; font-weight:600; cursor:pointer; white-space:nowrap; }
    .search-btn:hover { background:#2E7D32; }

    .ac-dropdown { position:absolute; top:100%; left:-2px; right:-2px; background:white; border:1px solid #EEE; border-top:none; border-radius:0 0 10px 10px; box-shadow:0 8px 24px rgba(0,0,0,.1); max-height:240px; overflow-y:auto; display:none; z-index:30; }
    .ac-dropdown.open { display:block; }
    .ac-item { padding:10px 14px; font-size:.82rem; cursor:pointer; display:flex; justify-content:space-between; align-items:center; }
    .ac-item:hover, .ac-item.active { background:#E8F5E9; }
    .ac-item-name { font-weight:500; color:#1B2A1B; }
    .ac-item-region { font-size:.72rem; color:#757575; }

    .results-info { font-size:.82rem; color:#757575; margin-bottom:16px; }
    .results-count { font-weight:600; color:#388E3C; }

    .pharm-group { background:white; border:1px solid #EEE; border-radius:14px; margin-bottom:14px; overflow:hidden; }
    .pharm-group:hover { border-color:#C8E6C9; box-shadow:0 4px 16px rgba(56,142,60,.08); }
    .pharm-header { padding:16px 20px; display:flex; justify-content:space-between; align-items:start; gap:16px; flex-wrap:wrap; border-bottom:1px solid #F5F5F5; }
    .pharm-name { font-size:.95rem; font-weight:700; color:#1B2A1B; }
    .pharm-name a { color:inherit; text-decoration:none; }
    .pharm-name a:hover { color:#388E3C; }
    .pharm-location { font-size:.78rem; color:#757575; margin-top:2px; display:flex; align-items:center; gap:4px; }
    .pharm-phone { font-size:.78rem; color:#388E3C; margin-top:2px; }
    .insurance-list { display:flex; gap:4px; flex-wrap:wrap; }
    .insurance-badge { padding:2px 8px; background:#E3F2FD; color:#1565C0; border-radius:100px; font-size:.62rem; font-weight:600; }

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
<div class="explorer-header">
    <h2>Trouver un medicament</h2>
    <p>Recherchez un medicament et trouvez les pharmacies qui le proposent pres de chez vous.</p>
</div>

<div class="search-wrapper">
    <form class="search-bar" onsubmit="searchMed(event)">
        <div class="search-field">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" id="medQ" placeholder="Nom du medicament (Paracetamol, Doliprane...)" autofocus>
        </div>
        <div class="search-field" id="cityFieldWrap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <input type="text" id="medCity" placeholder="Ville..." value="{{ auth()->user()->city_of_residence ?? 'Libreville' }}" autocomplete="off" onfocus="onCityFocus()" oninput="onCityInput()" onblur="onCityBlur()">
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
@endsection

@section('scripts')
<script>
const API = '{{ url('/api/v1') }}';
let currentPage = 1, lastPage = 1, allResults = [], cityDebounce = null, acActiveIdx = -1;

function onCityFocus() { const i = document.getElementById('medCity'); if (i.value.length >= 1) fetchCities(i.value); }
function onCityInput() {
    clearTimeout(cityDebounce); acActiveIdx = -1;
    cityDebounce = setTimeout(() => {
        const v = document.getElementById('medCity').value.trim();
        if (v.length >= 1) fetchCities(v); else closeCityDropdown();
    }, 250);
}
function onCityBlur() { setTimeout(closeCityDropdown, 200); }

async function fetchCities(q) {
    try {
        const res = await fetch(`${API}/referentiel/cities?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        const dd = document.getElementById('cityDropdown');
        clearChildren(dd);
        if (!data.data.length) { closeCityDropdown(); return; }
        data.data.forEach((c, i) => {
            const it = document.createElement('div');
            it.className = 'ac-item';
            it.dataset.idx = i;
            const name = document.createElement('span'); name.className = 'ac-item-name'; name.textContent = c.name;
            const region = document.createElement('span'); region.className = 'ac-item-region'; region.textContent = (c.region || '') + (c.country ? ', ' + c.country : '');
            it.appendChild(name); it.appendChild(region);
            it.addEventListener('mousedown', () => selectCity(c.name));
            dd.appendChild(it);
        });
        dd.classList.add('open');
    } catch (e) { closeCityDropdown(); }
}
function selectCity(name) { document.getElementById('medCity').value = name; closeCityDropdown(); }
function closeCityDropdown() { document.getElementById('cityDropdown').classList.remove('open'); }

document.getElementById('medCity').addEventListener('keydown', function (e) {
    const dd = document.getElementById('cityDropdown');
    const items = dd.querySelectorAll('.ac-item');
    if (!dd.classList.contains('open') || !items.length) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); acActiveIdx = Math.min(acActiveIdx + 1, items.length - 1); highlight(items); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); acActiveIdx = Math.max(acActiveIdx - 1, 0); highlight(items); }
    else if (e.key === 'Enter' && acActiveIdx >= 0) { e.preventDefault(); items[acActiveIdx].dispatchEvent(new Event('mousedown')); }
    else if (e.key === 'Escape') { closeCityDropdown(); }
});
function highlight(items) { items.forEach((it, i) => it.classList.toggle('active', i === acActiveIdx)); }

function clearChildren(el) { while (el && el.firstChild) el.removeChild(el.firstChild); }
function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

async function searchMed(e) {
    if (e) e.preventDefault();
    currentPage = 1; allResults = [];
    clearChildren(document.getElementById('medResults'));
    await fetchResults();
}
async function loadMore() { currentPage++; await fetchResults(true); }

async function fetchResults(append = false) {
    const q = document.getElementById('medQ').value.trim();
    const city = document.getElementById('medCity').value.trim();
    if (!q) {
        clearChildren(document.getElementById('medResults'));
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
    if (!append) { clearChildren(document.getElementById('medResults')); document.getElementById('medEmpty').style.display = 'none'; allResults = []; }
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
        const grouped = {};
        allResults.forEach(item => {
            const k = item.pharmacy.uuid;
            if (!grouped[k]) grouped[k] = { pharmacy: item.pharmacy, medications: [] };
            grouped[k].medications.push(item);
        });
        const total = data.meta.total;
        const totalP = Object.keys(grouped).length;
        document.getElementById('resultsInfo').style.display = 'block';
        document.getElementById('resultsInfo').textContent = `${total} resultat${total > 1 ? 's' : ''} dans ${totalP} pharmacie${totalP > 1 ? 's' : ''}${city ? ' a ' + city : ''}`;
        renderGrouped(grouped);
        document.getElementById('loadMoreBtn').style.display = currentPage < lastPage ? 'block' : 'none';
    } catch (err) { document.getElementById('medLoading').style.display = 'none'; }
}

function renderGrouped(grouped) {
    const container = document.getElementById('medResults');
    clearChildren(container);
    Object.values(grouped).forEach(group => {
        const ph = group.pharmacy;
        const card = document.createElement('div');
        card.className = 'pharm-group';

        const header = document.createElement('div'); header.className = 'pharm-header';
        const headerLeft = document.createElement('div');
        const nameWrap = document.createElement('div'); nameWrap.className = 'pharm-name';
        const link = document.createElement('a'); link.href = '/annuaire/' + ph.slug; link.textContent = ph.name;
        nameWrap.appendChild(link); headerLeft.appendChild(nameWrap);
        const loc = document.createElement('div'); loc.className = 'pharm-location';
        loc.textContent = (ph.city || '') + (ph.address ? ' — ' + ph.address : '');
        headerLeft.appendChild(loc);
        if (ph.phone) {
            const ph_ = document.createElement('div'); ph_.className = 'pharm-phone'; ph_.textContent = ph.phone;
            headerLeft.appendChild(ph_);
        }
        header.appendChild(headerLeft);
        if ((ph.accepted_insurances || []).length) {
            const ins = document.createElement('div'); ins.className = 'insurance-list'; ins.title = 'Assurances acceptees';
            ph.accepted_insurances.forEach(name => {
                const b = document.createElement('span'); b.className = 'insurance-badge'; b.textContent = name; ins.appendChild(b);
            });
            header.appendChild(ins);
        }
        card.appendChild(header);

        const rows = document.createElement('div'); rows.className = 'med-rows';
        group.medications.forEach(item => {
            const row = document.createElement('div'); row.className = 'med-row';
            const left = document.createElement('div');
            const dci = document.createElement('div'); dci.className = 'med-dci';
            dci.textContent = (item.medication.dci || '') + ' ' + (item.medication.strength || '');
            if (item.medication.prescription_required) {
                const rx = document.createElement('span'); rx.className = 'med-rx'; rx.textContent = 'Sur ordonnance';
                rx.style.marginLeft = '6px'; dci.appendChild(rx);
            }
            left.appendChild(dci);
            if (item.medication.dosage_form) {
                const det = document.createElement('div'); det.className = 'med-detail'; det.textContent = item.medication.dosage_form;
                left.appendChild(det);
            }
            if ((item.medication.brands || []).length) {
                const br = document.createElement('div'); br.className = 'med-brands';
                item.medication.brands.forEach(b => {
                    const tag = document.createElement('span'); tag.className = 'med-brand';
                    tag.textContent = b.name + (b.manufacturer ? ' — ' + b.manufacturer : '');
                    br.appendChild(tag);
                });
                left.appendChild(br);
            }
            const right = document.createElement('div'); right.className = 'med-right';
            if (item.unit_price) {
                const price = document.createElement('span'); price.className = 'price-tag';
                price.textContent = new Intl.NumberFormat('fr-FR').format(item.unit_price) + ' ';
                const cur = document.createElement('span'); cur.className = 'price-currency'; cur.textContent = item.currency || 'XAF';
                price.appendChild(cur);
                right.appendChild(price);
            } else {
                const dash = document.createElement('span'); dash.style.cssText = 'font-size:.78rem;color:#757575;'; dash.textContent = '—';
                right.appendChild(dash);
            }
            const stock = document.createElement('span');
            stock.className = 'stock-badge ' + (item.quantity_in_stock > 20 ? 'stock-ok' : 'stock-low');
            stock.textContent = item.quantity_in_stock > 20 ? 'En stock' : 'Stock limite';
            right.appendChild(stock);
            row.appendChild(left); row.appendChild(right);
            rows.appendChild(row);
        });
        card.appendChild(rows);
        container.appendChild(card);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const urlQ = new URLSearchParams(window.location.search).get('q');
    if (urlQ) { document.getElementById('medQ').value = urlQ; searchMed(); }
});
</script>
@endsection
