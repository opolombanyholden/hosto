@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Examens') @section('page-title', 'Examens') @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'examens']) @endsection

@section('breadcrumb')
<span style="color:#BDBDBD;margin:0 6px;">/</span>
<span style="color:#424242;">Examens</span>
@endsection

@section('styles')
<style>
    .explorer-header { margin-bottom:20px; }
    .explorer-header h2 { font-size:1.2rem; font-weight:700; color:#1B2A1B; margin-bottom:4px; }
    .explorer-header p { font-size:.82rem; color:#757575; }

    .search-wrapper { margin-bottom:24px; }
    .search-bar { background:white; border-radius:14px; padding:12px; box-shadow:0 4px 16px rgba(0,0,0,.06); display:flex; gap:10px; border:1px solid #EEE; flex-wrap:wrap; align-items:center; }
    .search-field { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; flex:1; min-width:200px; border:2px solid #EEE; position:relative; }
    .search-field:focus-within { border-color:#1565C0; }
    .search-field svg { flex-shrink:0; color:#1565C0; }
    .search-field input { border:none; outline:none; font-family:Poppins,sans-serif; font-size:.85rem; width:100%; background:transparent; }
    .search-btn { padding:12px 24px; background:#1565C0; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.85rem; font-weight:600; cursor:pointer; white-space:nowrap; }
    .search-btn:hover { background:#0D47A1; }

    .selected-chips { display:flex; flex-wrap:wrap; gap:6px; align-items:center; flex:1; min-width:0; }
    .sel-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; background:#E3F2FD; color:#1565C0; border-radius:100px; font-size:.78rem; font-weight:500; }
    .sel-chip button { border:none; background:none; cursor:pointer; color:#1565C0; font-size:1rem; padding:0; line-height:1; }
    .chip-input { border:none; outline:none; font-family:Poppins,sans-serif; font-size:.85rem; min-width:120px; flex:1; padding:6px 0; }

    .ac-dropdown { position:absolute; top:100%; left:-2px; right:-2px; background:white; border:1px solid #EEE; border-top:none; border-radius:0 0 10px 10px; box-shadow:0 8px 24px rgba(0,0,0,.1); max-height:240px; overflow-y:auto; display:none; z-index:30; }
    .ac-dropdown.open { display:block; }
    .ac-item { padding:10px 14px; font-size:.82rem; cursor:pointer; display:flex; justify-content:space-between; align-items:center; }
    .ac-item:hover, .ac-item.active { background:#E3F2FD; }

    .popular-exams { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px; }
    .popular-chip { padding:6px 14px; background:white; border:1px solid #E0E0E0; border-radius:100px; font-size:.78rem; color:#424242; cursor:pointer; font-family:Poppins,sans-serif; transition:all .2s; }
    .popular-chip:hover { background:#E3F2FD; border-color:#1565C0; color:#1565C0; }

    .lab-group { background:white; border:1px solid #EEE; border-radius:14px; margin-bottom:14px; overflow:hidden; }
    .lab-group:hover { border-color:#BBDEFB; box-shadow:0 4px 16px rgba(21,101,192,.08); }
    .lab-header { padding:16px 20px; display:flex; justify-content:space-between; align-items:start; gap:16px; flex-wrap:wrap; border-bottom:1px solid #F5F5F5; }
    .lab-name { font-size:.95rem; font-weight:700; color:#1B2A1B; }
    .lab-name a { color:inherit; text-decoration:none; }
    .lab-name a:hover { color:#1565C0; }
    .lab-location { font-size:.78rem; color:#757575; margin-top:2px; }
    .lab-phone { font-size:.78rem; color:#1565C0; margin-top:2px; }
    .insurance-list { display:flex; gap:4px; flex-wrap:wrap; }
    .insurance-badge { padding:2px 8px; background:#E3F2FD; color:#1565C0; border-radius:100px; font-size:.62rem; font-weight:600; }

    .exam-rows { padding:0; }
    .exam-row { display:grid; grid-template-columns:1fr auto; gap:16px; align-items:center; padding:12px 20px; border-bottom:1px solid #FAFAFA; }
    .exam-row:last-child { border-bottom:none; }
    .exam-row:hover { background:#FAFAFA; }
    .exam-name { font-size:.85rem; font-weight:600; color:#1B2A1B; }
    .exam-code { font-size:.68rem; color:#757575; font-weight:500; margin-left:6px; }
    .exam-right { display:flex; align-items:center; gap:8px; }
    .price-range { font-size:.92rem; font-weight:700; color:#1565C0; white-space:nowrap; }
    .price-currency { font-size:.68rem; font-weight:400; color:#757575; }

    .loading { text-align:center; padding:40px; color:#757575; font-size:.85rem; }
    .empty-state { text-align:center; padding:60px 20px; color:#757575; }
    .load-more { display:block; margin:0 auto 40px; padding:10px 32px; background:white; border:2px solid #1565C0; color:#1565C0; border-radius:10px; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:600; cursor:pointer; }

    .order-mini { padding:6px 12px; background:white; border:1px solid #1565C0; color:#1565C0; border-radius:8px; font-family:Poppins,sans-serif; font-size:.72rem; font-weight:600; cursor:pointer; margin-left:6px; }
    .order-mini:hover { background:#1565C0; color:white; }

    .order-modal-backdrop { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center; padding:20px; }
    .order-modal-backdrop.open { display:flex; }
    .order-modal { background:white; max-width:560px; width:100%; border-radius:16px; padding:24px; box-shadow:0 20px 60px rgba(0,0,0,.3); max-height:90vh; overflow:auto; }
    .order-modal h3 { font-size:1.1rem; font-weight:700; color:#1B2A1B; margin-bottom:8px; }
    .order-modal .item { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px dashed #EEE; font-size:.82rem; }
    .order-modal label { display:block; font-size:.78rem; font-weight:600; color:#424242; margin-top:14px; margin-bottom:6px; }
    .order-modal textarea, .order-modal select { width:100%; padding:10px; border:2px solid #EEE; border-radius:8px; font-family:Poppins,sans-serif; font-size:.85rem; outline:none; }
    .order-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:18px; }
    .order-btn { padding:10px 20px; border:none; border-radius:8px; font-family:Poppins,sans-serif; font-size:.85rem; font-weight:600; cursor:pointer; }
    .order-btn-primary { background:#1565C0; color:white; }
    .order-btn-secondary { background:#EEE; color:#424242; }
    .order-msg { margin-top:10px; padding:10px; border-radius:8px; font-size:.82rem; display:none; }
    .order-msg.ok { background:#E8F5E9; color:#2E7D32; display:block; }
    .order-msg.err { background:#FFEBEE; color:#C62828; display:block; }

    @media(max-width:768px) {
        .search-bar { flex-direction:column; }
        .lab-header { flex-direction:column; }
        .exam-row { grid-template-columns:1fr; gap:8px; }
    }
</style>
@endsection

@section('content')
<div class="explorer-header">
    <h2>Trouver un examen medical</h2>
    <p>Recherchez plusieurs examens en parallele et commandez-les en ligne ou en caisse.</p>
</div>

<div class="search-wrapper">
    <form class="search-bar" onsubmit="searchExam(event)">
        <div class="search-field" style="flex-wrap:wrap;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <div class="selected-chips" id="chipsArea">
                <input type="text" id="examQ" class="chip-input" placeholder="Examen (entree pour ajouter)" list="examList" autocomplete="off" autofocus>
                <datalist id="examList"></datalist>
            </div>
        </div>
        <div class="search-field" id="cityFieldWrap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <input type="text" id="examCity" placeholder="Ville..." value="{{ auth()->user()->city_of_residence ?? 'Libreville' }}" autocomplete="off" onfocus="onCityFocus()" oninput="onCityInput()" onblur="onCityBlur()">
            <div class="ac-dropdown" id="cityDropdown"></div>
        </div>
        <button type="submit" class="search-btn">Rechercher</button>
    </form>
</div>

<div class="popular-exams">
    <span class="popular-chip" onclick="quickSearch('bilan sanguin')">Bilan sanguin</span>
    <span class="popular-chip" onclick="quickSearch('echographie')">Echographie</span>
    <span class="popular-chip" onclick="quickSearch('radiographie')">Radiographie</span>
    <span class="popular-chip" onclick="quickSearch('paludisme')">Paludisme</span>
    <span class="popular-chip" onclick="quickSearch('VIH')">VIH</span>
    <span class="popular-chip" onclick="quickSearch('scanner')">Scanner</span>
    <span class="popular-chip" onclick="quickSearch('IRM')">IRM</span>
    <span class="popular-chip" onclick="quickSearch('ECG')">ECG</span>
</div>

<div id="resultsInfo" class="results-info" style="display:none;font-size:.82rem;color:#757575;margin-bottom:16px;"></div>
<div id="examLoading" class="loading" style="display:none;">Recherche en cours...</div>
<div id="examResults"></div>
<div id="examEmpty" class="empty-state" style="display:none;">Aucun resultat.</div>
<button id="loadMoreBtn" class="load-more" style="display:none;" onclick="loadMore()">Voir plus de resultats</button>

{{-- Order modal --}}
<div class="order-modal-backdrop" id="orderModal" onclick="if(event.target===this)closeOrder()">
    <div class="order-modal">
        <h3 id="orderLabName"></h3>
        <div id="orderItems" style="margin-top:10px;"></div>
        <div id="orderTotal" style="margin-top:10px;font-size:.95rem;font-weight:700;color:#1565C0;text-align:right;"></div>
        <label for="orderNotes">Indications (optionnel)</label>
        <textarea id="orderNotes" rows="2" maxlength="1000" placeholder="Symptomes, ordonnance, precisions..."></textarea>
        <label for="orderPayment">Mode de paiement</label>
        <select id="orderPayment"></select>
        <div id="orderMsg" class="order-msg"></div>
        <div class="order-actions">
            <button type="button" class="order-btn order-btn-secondary" onclick="closeOrder()">Annuler</button>
            <button type="button" class="order-btn order-btn-primary" onclick="submitOrder()">Confirmer la commande</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const API = '{{ url('/api/v1') }}';
let currentPage = 1, lastPage = 1, cityDebounce = null, acActiveIdx = -1;
const SELECTED_EXAMS = [];
let CURRENT_LAB_FOR_ORDER = null;
let CURRENT_ITEMS_FOR_ORDER = [];
const POPULAR_TERMS = ['Bilan sanguin','Echographie','Radiographie','Depistage paludisme','Depistage VIH','Scanner','IRM','ECG','Mammographie','Analyse urine','Glycemie','NFS','VS','Beta HCG','TSH','Cholesterol','Transaminases','Creatinine','HBA1c','Coproculture'];

function clearChildren(el) { while (el && el.firstChild) el.removeChild(el.firstChild); }
function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function quickSearch(term) { addExamChip(term); searchExam(); }
function addExamChip(term) {
    const t = (term || '').trim(); if (!t) return;
    if (SELECTED_EXAMS.some(x => x.toLowerCase() === t.toLowerCase())) return;
    SELECTED_EXAMS.push(t); renderChips();
}
function removeExamChip(term) {
    const idx = SELECTED_EXAMS.findIndex(x => x.toLowerCase() === term.toLowerCase());
    if (idx >= 0) { SELECTED_EXAMS.splice(idx, 1); renderChips(); searchExam(); }
}
function renderChips() {
    const area = document.getElementById('chipsArea'); const input = document.getElementById('examQ');
    Array.from(area.querySelectorAll('.sel-chip')).forEach(n => n.remove());
    SELECTED_EXAMS.forEach(term => {
        const chip = document.createElement('span'); chip.className = 'sel-chip'; chip.textContent = term;
        const btn = document.createElement('button'); btn.type = 'button'; btn.setAttribute('aria-label', 'Retirer ' + term); btn.textContent = '×';
        btn.addEventListener('click', () => removeExamChip(term));
        chip.appendChild(btn); area.insertBefore(chip, input);
    });
}
function initExamDatalist() {
    const dl = document.getElementById('examList');
    POPULAR_TERMS.forEach(t => { const o = document.createElement('option'); o.value = t; dl.appendChild(o); });
}

function onCityFocus() { const i = document.getElementById('examCity'); if (i.value.length >= 1) fetchCities(i.value); }
function onCityInput() {
    clearTimeout(cityDebounce); acActiveIdx = -1;
    cityDebounce = setTimeout(() => {
        const v = document.getElementById('examCity').value.trim();
        if (v.length >= 1) fetchCities(v); else closeCityDropdown();
    }, 250);
}
function onCityBlur() { setTimeout(closeCityDropdown, 200); }
async function fetchCities(q) {
    try {
        const res = await fetch(`${API}/referentiel/cities?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        const dd = document.getElementById('cityDropdown'); clearChildren(dd);
        if (!data.data.length) { closeCityDropdown(); return; }
        data.data.forEach((c, i) => {
            const it = document.createElement('div'); it.className = 'ac-item'; it.dataset.idx = i;
            const name = document.createElement('span'); name.textContent = c.name; name.style.fontWeight = '500';
            const region = document.createElement('span'); region.style.cssText = 'font-size:.72rem;color:#757575;';
            region.textContent = (c.region || '') + (c.country ? ', ' + c.country : '');
            it.appendChild(name); it.appendChild(region);
            it.addEventListener('mousedown', () => { document.getElementById('examCity').value = c.name; closeCityDropdown(); });
            dd.appendChild(it);
        });
        dd.classList.add('open');
    } catch (e) { closeCityDropdown(); }
}
function closeCityDropdown() { document.getElementById('cityDropdown').classList.remove('open'); }

async function searchExam(e) {
    if (e) e.preventDefault();
    currentPage = 1;
    clearChildren(document.getElementById('examResults'));
    await fetchResults();
}
async function loadMore() { currentPage++; await fetchResults(true); }

async function fetchResults(append = false) {
    const typed = document.getElementById('examQ').value.trim();
    if (typed && !SELECTED_EXAMS.some(x => x.toLowerCase() === typed.toLowerCase())) {
        addExamChip(typed); document.getElementById('examQ').value = '';
    }
    const city = document.getElementById('examCity').value.trim();
    const q = SELECTED_EXAMS.join(' ');
    if (!q) {
        clearChildren(document.getElementById('examResults'));
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
    if (!append) { clearChildren(document.getElementById('examResults')); document.getElementById('examEmpty').style.display = 'none'; }
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
        document.getElementById('resultsInfo').textContent = `${data.meta.total} laboratoire${data.meta.total > 1 ? 's' : ''}${city ? ' a ' + city : ''}`;

        const container = document.getElementById('examResults');
        data.data.forEach(item => renderLabCard(container, item));
        document.getElementById('loadMoreBtn').style.display = currentPage < lastPage ? 'block' : 'none';
    } catch (err) { document.getElementById('examLoading').style.display = 'none'; }
}

function renderLabCard(container, item) {
    const lab = item.laboratory;
    const card = document.createElement('div'); card.className = 'lab-group';
    card.dataset.labUuid = lab.uuid || ''; card.dataset.labSlug = lab.slug || '';
    card.dataset.labName = lab.name || ''; card.dataset.labPaymentOnline = lab.accepts_online_payment ? '1' : '0';
    card.dataset.labPaymentOnSite = lab.accepts_on_site_payment ? '1' : '0';

    const header = document.createElement('div'); header.className = 'lab-header';
    const left = document.createElement('div');
    const nameWrap = document.createElement('div'); nameWrap.className = 'lab-name';
    const a = document.createElement('a'); a.href = '/annuaire/' + lab.slug; a.textContent = lab.name;
    nameWrap.appendChild(a); left.appendChild(nameWrap);
    const loc = document.createElement('div'); loc.className = 'lab-location';
    loc.textContent = (lab.city || '') + (lab.address ? ' — ' + lab.address : ''); left.appendChild(loc);
    if (lab.phone) {
        const ph = document.createElement('div'); ph.className = 'lab-phone'; ph.textContent = lab.phone;
        left.appendChild(ph);
    }
    header.appendChild(left);
    if ((lab.accepted_insurances || []).length) {
        const ins = document.createElement('div'); ins.className = 'insurance-list'; ins.title = 'Assurances acceptees';
        lab.accepted_insurances.forEach(name => {
            const b = document.createElement('span'); b.className = 'insurance-badge'; b.textContent = name; ins.appendChild(b);
        });
        header.appendChild(ins);
    }
    card.appendChild(header);

    const rows = document.createElement('div'); rows.className = 'exam-rows';
    (item.exams || []).forEach(ex => {
        const row = document.createElement('div'); row.className = 'exam-row';
        row.dataset.examCode = ex.code; row.dataset.examName = ex.name;
        row.dataset.tarifMin = ex.tarif_min || ''; row.dataset.tarifMax = ex.tarif_max || '';
        row.dataset.currency = ex.currency || 'XAF';

        const l = document.createElement('div');
        const n = document.createElement('span'); n.className = 'exam-name'; n.textContent = ex.name;
        const c = document.createElement('span'); c.className = 'exam-code'; c.textContent = ex.code;
        l.appendChild(n); l.appendChild(c);

        const r = document.createElement('div'); r.className = 'exam-right';
        if (ex.tarif_min && ex.tarif_max) {
            const p = document.createElement('span'); p.className = 'price-range';
            p.textContent = (ex.tarif_min === ex.tarif_max)
                ? new Intl.NumberFormat('fr-FR').format(ex.tarif_min) + ' '
                : `${new Intl.NumberFormat('fr-FR').format(ex.tarif_min)} - ${new Intl.NumberFormat('fr-FR').format(ex.tarif_max)} `;
            const cur = document.createElement('span'); cur.className = 'price-currency'; cur.textContent = ex.currency || 'XAF';
            p.appendChild(cur);
            r.appendChild(p);
        }
        const btn = document.createElement('button'); btn.type = 'button'; btn.className = 'order-mini';
        btn.textContent = 'Commander';
        btn.addEventListener('click', () => openOrderModalFromBtn(btn));
        r.appendChild(btn);

        row.appendChild(l); row.appendChild(r);
        rows.appendChild(row);
    });
    card.appendChild(rows);
    container.appendChild(card);
}

function openOrderModalFromBtn(btn) {
    const row = btn.closest('.exam-row'); const group = btn.closest('.lab-group');
    if (!row || !group) return;
    CURRENT_LAB_FOR_ORDER = { uuid: group.dataset.labUuid, name: group.dataset.labName, online: group.dataset.labPaymentOnline === '1', on_site: group.dataset.labPaymentOnSite === '1' };
    CURRENT_ITEMS_FOR_ORDER = [{ code: row.dataset.examCode, name: row.dataset.examName, tarif_min: row.dataset.tarifMin ? parseInt(row.dataset.tarifMin, 10) : null, tarif_max: row.dataset.tarifMax ? parseInt(row.dataset.tarifMax, 10) : null, currency_code: row.dataset.currency || 'XAF' }];
    if (!CURRENT_LAB_FOR_ORDER.uuid) { alert('Identifiant laboratoire manquant.'); return; }
    document.getElementById('orderLabName').textContent = 'Commande chez ' + CURRENT_LAB_FOR_ORDER.name;
    const items = document.getElementById('orderItems'); clearChildren(items);
    let totalMax = 0;
    CURRENT_ITEMS_FOR_ORDER.forEach(it => {
        const r = document.createElement('div'); r.className = 'item';
        const a = document.createElement('span'); a.textContent = it.name;
        const b = document.createElement('span');
        if (it.tarif_min && it.tarif_max) {
            b.textContent = it.tarif_min === it.tarif_max
                ? `${it.tarif_min.toLocaleString('fr-FR')} ${it.currency_code}`
                : `${it.tarif_min.toLocaleString('fr-FR')} - ${it.tarif_max.toLocaleString('fr-FR')} ${it.currency_code}`;
            totalMax += it.tarif_max;
        } else { b.textContent = 'Prix sur place'; }
        r.appendChild(a); r.appendChild(b); items.appendChild(r);
    });
    document.getElementById('orderTotal').textContent = totalMax > 0 ? `Estimation max : ${totalMax.toLocaleString('fr-FR')} ${CURRENT_ITEMS_FOR_ORDER[0].currency_code}` : '';

    const sel = document.getElementById('orderPayment'); clearChildren(sel);
    if (CURRENT_LAB_FOR_ORDER.on_site) { const o = document.createElement('option'); o.value = 'on_site'; o.textContent = 'A la caisse du laboratoire'; sel.appendChild(o); }
    if (CURRENT_LAB_FOR_ORDER.online) { const o = document.createElement('option'); o.value = 'online'; o.textContent = 'En ligne'; sel.appendChild(o); }
    if (!sel.children.length) { const o = document.createElement('option'); o.value = ''; o.textContent = 'Aucun mode configure'; sel.appendChild(o); sel.disabled = true; } else sel.disabled = false;

    document.getElementById('orderNotes').value = '';
    const msg = document.getElementById('orderMsg'); msg.className = 'order-msg'; msg.textContent = '';
    document.getElementById('orderModal').classList.add('open');
}
function closeOrder() { document.getElementById('orderModal').classList.remove('open'); }

async function submitOrder() {
    if (!CURRENT_LAB_FOR_ORDER || !CURRENT_LAB_FOR_ORDER.uuid) return;
    const msg = document.getElementById('orderMsg'); msg.className = 'order-msg'; msg.textContent = '';
    const body = { hosto_uuid: CURRENT_LAB_FOR_ORDER.uuid, exam_items: CURRENT_ITEMS_FOR_ORDER, notes: document.getElementById('orderNotes').value.trim() || null, payment_method: document.getElementById('orderPayment').value || null };
    try {
        const res = await fetch('/web/exam-orders', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin', body: JSON.stringify(body),
        });
        const data = await res.json();
        if (!res.ok) {
            msg.className = 'order-msg err'; msg.textContent = data.error?.message || 'Erreur.';
            return;
        }
        msg.className = 'order-msg ok';
        msg.textContent = `Commande creee (${data.data.reference}). Redirection...`;
        setTimeout(() => { window.location.href = data.data.next_url; }, 1000);
    } catch (e) { msg.className = 'order-msg err'; msg.textContent = 'Erreur de connexion.'; }
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeOrder(); });

document.addEventListener('DOMContentLoaded', () => {
    initExamDatalist();
    const examQ = document.getElementById('examQ');
    examQ.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); const v = examQ.value.trim(); if (v) { addExamChip(v); examQ.value = ''; searchExam(); } }
        else if (e.key === 'Backspace' && !examQ.value && SELECTED_EXAMS.length) { removeExamChip(SELECTED_EXAMS[SELECTED_EXAMS.length - 1]); }
    });
    const urlQ = new URLSearchParams(window.location.search).get('q');
    if (urlQ) { addExamChip(urlQ); searchExam(); }
});
</script>
@endsection
