@extends('layouts.app')

@section('title', 'Trouver un examen — HOSTO')
@section('breadcrumb')
<li><span class="sep">/</span> <span class="current">Examens</span></li>
@endsection

@section('styles')
<style>
    .exam-header { background:linear-gradient(135deg,#1565C0,#1E88E5); padding:56px 0 100px; color:white; text-align:center; }
    .exam-header h1 { font-size:clamp(1.6rem,4vw,2.2rem); font-weight:700; margin-bottom:8px; }
    .search-wrapper { margin-top:-50px; position:relative; z-index:20; margin-bottom:32px; }
    .search-bar { background:white; border-radius:16px; padding:14px; box-shadow:0 12px 48px rgba(0,0,0,.12); display:flex; gap:10px; border:1px solid #EEE; flex-wrap:wrap; align-items:center; }
    .search-field { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; flex:1; min-width:180px; border:2px solid #EEE; position:relative; }
    .search-field:focus-within { border-color:#1565C0; }
    .search-field svg { flex-shrink:0; color:#1565C0; }
    .search-field input { border:none; outline:none; font-family:Poppins,sans-serif; font-size:.85rem; width:100%; background:transparent; }
    .search-btn { padding:12px 28px; background:#1565C0; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.85rem; font-weight:600; cursor:pointer; white-space:nowrap; }
    .search-btn:hover { background:#0D47A1; }

    /* Autocomplete dropdown */
    .ac-dropdown { position:absolute; top:100%; left:-2px; right:-2px; background:white; border:1px solid #EEE; border-top:none; border-radius:0 0 10px 10px; box-shadow:0 8px 24px rgba(0,0,0,.1); max-height:240px; overflow-y:auto; display:none; z-index:30; }
    .ac-dropdown.open { display:block; }
    .ac-item { padding:10px 14px; font-size:.82rem; cursor:pointer; display:flex; justify-content:space-between; align-items:center; }
    .ac-item:hover, .ac-item.active { background:#E3F2FD; }
    .ac-item-name { font-weight:500; color:#1B2A1B; }
    .ac-item-region { font-size:.72rem; color:#757575; }

    .results-info { font-size:.82rem; color:#757575; margin-bottom:16px; }
    .results-count { font-weight:600; color:#1565C0; }

    /* Lab group card */
    .lab-group { background:white; border:1px solid #EEE; border-radius:14px; margin-bottom:16px; overflow:hidden; }
    .lab-group:hover { border-color:#BBDEFB; box-shadow:0 4px 16px rgba(21,101,192,.08); }
    .lab-header { padding:16px 20px; display:flex; justify-content:space-between; align-items:start; gap:16px; flex-wrap:wrap; border-bottom:1px solid #F5F5F5; }
    .lab-name { font-size:.95rem; font-weight:700; color:#1B2A1B; }
    .lab-name a { color:inherit; text-decoration:none; }
    .lab-name a:hover { color:#1565C0; }
    .lab-location { font-size:.78rem; color:#757575; margin-top:2px; display:flex; align-items:center; gap:4px; }
    .lab-phone { font-size:.78rem; color:#1565C0; margin-top:2px; }
    .lab-types { display:flex; gap:4px; flex-wrap:wrap; margin-top:4px; }
    .lab-type-badge { padding:2px 8px; background:#F3E5F5; color:#6A1B9A; border-radius:100px; font-size:.62rem; font-weight:600; }
    .insurance-list { display:flex; gap:4px; flex-wrap:wrap; }
    .insurance-badge { padding:2px 8px; background:#E3F2FD; color:#1565C0; border-radius:100px; font-size:.62rem; font-weight:600; }

    /* Exam rows inside lab */
    .exam-rows { padding:0; }
    .exam-row { display:grid; grid-template-columns:1fr auto; gap:16px; align-items:center; padding:12px 20px; border-bottom:1px solid #FAFAFA; }
    .exam-row:last-child { border-bottom:none; }
    .exam-row:hover { background:#FAFAFA; }
    .exam-name { font-size:.85rem; font-weight:600; color:#1B2A1B; }
    .exam-code { font-size:.68rem; color:#757575; font-weight:500; }

    .exam-right { display:flex; align-items:center; gap:8px; }
    .price-range { font-size:.92rem; font-weight:700; color:#1565C0; white-space:nowrap; }
    .price-currency { font-size:.68rem; font-weight:400; color:#757575; }

    .loading { text-align:center; padding:40px; color:#757575; font-size:.85rem; }
    .empty-state { text-align:center; padding:60px 20px; color:#757575; }
    .empty-state svg { width:60px; height:60px; color:#BDBDBD; margin-bottom:16px; }

    .load-more { display:block; margin:0 auto 40px; padding:10px 32px; background:white; border:2px solid #1565C0; color:#1565C0; border-radius:10px; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:600; cursor:pointer; }
    .load-more:hover { background:#E3F2FD; }

    /* Popular exams chips */
    .popular-exams { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px; }
    .popular-chip { padding:6px 14px; background:white; border:1px solid #E0E0E0; border-radius:100px; font-size:.78rem; color:#424242; cursor:pointer; font-family:Poppins,sans-serif; transition:all .2s; }
    .popular-chip:hover { background:#E3F2FD; border-color:#1565C0; color:#1565C0; }

    @media(max-width:768px) {
        .search-bar { flex-direction:column; }
        .lab-header { flex-direction:column; }
        .exam-row { grid-template-columns:1fr; gap:8px; }
    }
</style>
@endsection

@section('content')
<div class="exam-header">
    <div class="container">
        <h1>Trouver un examen medical</h1>
        <p style="opacity:.85;">Recherchez un examen et trouvez les laboratoires qui le proposent pres de chez vous</p>
    </div>
</div>

<div class="container">
    <div class="search-wrapper">
        <form class="search-bar" onsubmit="searchExam(event)">
            <div class="search-field">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" id="examQ" placeholder="Nom de l'examen (bilan sanguin, echographie, IRM...)" autofocus>
            </div>
            <div class="search-field" id="cityFieldWrap">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <input type="text" id="examCity" placeholder="Ville..." value="Libreville" autocomplete="off" onfocus="onCityFocus()" oninput="onCityInput()" onblur="onCityBlur()">
                <div class="ac-dropdown" id="cityDropdown"></div>
            </div>
            <button type="submit" class="search-btn">Rechercher</button>
        </form>
    </div>

    {{-- Popular exams --}}
    <div class="popular-exams">
        <span class="popular-chip" onclick="quickSearch('bilan sanguin')">Bilan sanguin</span>
        <span class="popular-chip" onclick="quickSearch('echographie')">Echographie</span>
        <span class="popular-chip" onclick="quickSearch('radiographie')">Radiographie</span>
        <span class="popular-chip" onclick="quickSearch('paludisme')">Depistage paludisme</span>
        <span class="popular-chip" onclick="quickSearch('VIH')">Depistage VIH</span>
        <span class="popular-chip" onclick="quickSearch('scanner')">Scanner</span>
        <span class="popular-chip" onclick="quickSearch('IRM')">IRM</span>
        <span class="popular-chip" onclick="quickSearch('ECG')">ECG</span>
        <span class="popular-chip" onclick="quickSearch('mammographie')">Mammographie</span>
        <span class="popular-chip" onclick="quickSearch('urine')">Analyse d'urine</span>
    </div>

    <div id="resultsInfo" class="results-info" style="display:none;"></div>
    <div id="examLoading" class="loading" style="display:none;">Recherche en cours...</div>
    <div id="examResults"></div>
    <div id="examEmpty" class="empty-state" style="display:none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/><path d="M8 11h6"/></svg>
        <p>Aucun resultat.</p>
        <p style="font-size:.78rem;margin-top:4px;">Essayez un autre type d'examen ou une autre ville.</p>
    </div>
    <button id="loadMoreBtn" class="load-more" style="display:none;" onclick="loadMore()">Voir plus de resultats</button>
</div>
@endsection

@section('scripts')
<script>
let currentPage = 1;
let lastPage = 1;
let cityDebounce = null;
let acActiveIdx = -1;

function quickSearch(term) {
    document.getElementById('examQ').value = term;
    searchExam();
}

// ===== City autocomplete =====
function onCityFocus() {
    const input = document.getElementById('examCity');
    if (input.value.length >= 1) fetchCities(input.value);
}

function onCityInput() {
    clearTimeout(cityDebounce);
    acActiveIdx = -1;
    cityDebounce = setTimeout(() => {
        const val = document.getElementById('examCity').value.trim();
        if (val.length >= 1) fetchCities(val);
        else closeCityDropdown();
    }, 250);
}

function onCityBlur() {
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
    document.getElementById('examCity').value = name;
    closeCityDropdown();
}

function closeCityDropdown() {
    document.getElementById('cityDropdown').classList.remove('open');
}

document.getElementById('examCity').addEventListener('keydown', function(e) {
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

// ===== Exam search =====
async function searchExam(e) {
    if (e) e.preventDefault();
    currentPage = 1;
    document.getElementById('examResults').innerHTML = '';
    await fetchResults();
}

async function loadMore() {
    currentPage++;
    await fetchResults(true);
}

async function fetchResults(append = false) {
    const q = document.getElementById('examQ').value.trim();
    const city = document.getElementById('examCity').value.trim();

    if (!q) {
        document.getElementById('examResults').innerHTML = '';
        document.getElementById('resultsInfo').style.display = 'none';
        document.getElementById('examEmpty').style.display = 'none';
        document.getElementById('loadMoreBtn').style.display = 'none';
        return;
    }

    const params = new URLSearchParams();
    params.set('exam', q);
    if (city) params.set('city', city);
    params.set('per_page', '20');
    params.set('page', currentPage);

    document.getElementById('examLoading').style.display = 'block';
    if (!append) {
        document.getElementById('examResults').innerHTML = '';
        document.getElementById('examEmpty').style.display = 'none';
    }
    document.getElementById('loadMoreBtn').style.display = 'none';

    try {
        const res = await fetch(`${API}/lab/exams/search?${params}`);
        const data = await res.json();
        document.getElementById('examLoading').style.display = 'none';

        lastPage = data.meta.last_page;

        if (!data.data.length && !append) {
            document.getElementById('examEmpty').style.display = 'block';
            document.getElementById('resultsInfo').style.display = 'none';
            return;
        }

        document.getElementById('resultsInfo').style.display = 'block';
        document.getElementById('resultsInfo').innerHTML =
            `<span class="results-count">${data.meta.total}</span> laboratoire${data.meta.total > 1 ? 's' : ''}${city ? ' a <strong>' + esc(city) + '</strong>' : ''}`;

        const container = document.getElementById('examResults');
        data.data.forEach(item => {
            const lab = item.laboratory;
            const insurances = (lab.accepted_insurances || [])
                .map(ins => `<span class="insurance-badge">${esc(ins)}</span>`)
                .join('');
            const types = (lab.types || [])
                .map(t => `<span class="lab-type-badge">${esc(t)}</span>`)
                .join('');

            let examsHtml = '';
            (item.exams || []).forEach(ex => {
                const price = ex.tarif_min && ex.tarif_max
                    ? `<span class="price-range">${fmt(ex.tarif_min)} - ${fmt(ex.tarif_max)} <span class="price-currency">${ex.currency}</span></span>`
                    : `<span style="font-size:.78rem;color:#757575;">Prix non communique</span>`;

                examsHtml += `<div class="exam-row">
                    <div>
                        <span class="exam-name">${esc(ex.name)}</span>
                        <span class="exam-code">${esc(ex.code)}</span>
                    </div>
                    <div class="exam-right">${price}</div>
                </div>`;
            });

            const card = document.createElement('div');
            card.className = 'lab-group';
            card.innerHTML = `
                <div class="lab-header">
                    <div>
                        <div class="lab-name"><a href="/annuaire/${esc(lab.slug)}">${esc(lab.name)}</a></div>
                        <div class="lab-location">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            ${esc(lab.city || '')}${lab.address ? ' — ' + esc(lab.address) : ''}
                        </div>
                        ${lab.phone ? `<div class="lab-phone">${esc(lab.phone)}</div>` : ''}
                        ${types ? `<div class="lab-types">${types}</div>` : ''}
                    </div>
                    ${insurances ? `<div class="insurance-list" title="Assurances acceptees">${insurances}</div>` : ''}
                </div>
                <div class="exam-rows">${examsHtml}</div>`;
            container.appendChild(card);
        });

        document.getElementById('loadMoreBtn').style.display = currentPage < lastPage ? 'block' : 'none';
    } catch (err) {
        document.getElementById('examLoading').style.display = 'none';
    }
}

function fmt(n) { return new Intl.NumberFormat('fr-FR').format(n); }
function esc(str) { if (!str) return ''; const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }
function escAttr(str) { return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;'); }

document.addEventListener('DOMContentLoaded', () => {
    const urlQ = new URLSearchParams(window.location.search).get('q');
    if (urlQ) { document.getElementById('examQ').value = urlQ; searchExam(); }
});
</script>
@endsection
