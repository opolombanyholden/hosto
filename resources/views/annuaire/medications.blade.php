@extends('layouts.app')

@section('title', 'Trouver un medicament — HOSTO')
@section('breadcrumb')
<li><span class="sep">/</span> <span class="current">Medicaments</span></li>
@endsection

@section('styles')
<style>
    .med-header { background:linear-gradient(135deg,#2E7D32,#43A047); padding:56px 0 100px; color:white; text-align:center; }
    .med-header h1 { font-size:clamp(1.6rem,4vw,2.2rem); font-weight:700; margin-bottom:8px; }
    .search-wrapper { margin-top:-50px; position:relative; z-index:10; margin-bottom:32px; }
    .search-bar { background:white; border-radius:16px; padding:14px; box-shadow:0 12px 48px rgba(0,0,0,.12); display:flex; gap:10px; border:1px solid #EEE; flex-wrap:wrap; align-items:center; }
    .search-field { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; flex:1; min-width:180px; border:2px solid #EEE; }
    .search-field:focus-within { border-color:#388E3C; }
    .search-field svg { flex-shrink:0; color:#388E3C; }
    .search-field input { border:none; outline:none; font-family:Poppins,sans-serif; font-size:.85rem; width:100%; }
    .search-btn { padding:12px 28px; background:#388E3C; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.85rem; font-weight:600; cursor:pointer; white-space:nowrap; }
    .search-btn:hover { background:#2E7D32; }

    .results-info { font-size:.82rem; color:#757575; margin-bottom:16px; }
    .results-count { font-weight:600; color:#388E3C; }

    .pharm-list { display:flex; flex-direction:column; gap:12px; margin-bottom:40px; }
    .pharm-card { background:white; border:1px solid #EEE; border-radius:14px; padding:20px; display:grid; grid-template-columns:1fr auto; gap:16px; align-items:start; }
    .pharm-card:hover { border-color:#C8E6C9; box-shadow:0 4px 16px rgba(56,142,60,.08); }

    .pharm-name { font-size:.95rem; font-weight:700; color:#1B2A1B; }
    .pharm-name a { color:inherit; text-decoration:none; }
    .pharm-name a:hover { color:#388E3C; }
    .pharm-location { font-size:.78rem; color:#757575; margin-top:2px; display:flex; align-items:center; gap:4px; }
    .pharm-phone { font-size:.78rem; color:#388E3C; margin-top:2px; }

    .med-info { margin-top:8px; }
    .med-dci { font-size:.85rem; font-weight:600; color:#1B2A1B; }
    .med-detail { font-size:.72rem; color:#757575; }
    .med-brands { margin-top:4px; display:flex; gap:4px; flex-wrap:wrap; }
    .med-brand { padding:2px 8px; background:#F5F5F5; border-radius:100px; font-size:.65rem; color:#424242; }
    .med-rx { padding:2px 8px; background:#FFEBEE; color:#C62828; border-radius:100px; font-size:.65rem; font-weight:600; }

    .pharm-right { text-align:right; display:flex; flex-direction:column; align-items:flex-end; gap:8px; }
    .price-tag { font-size:1.1rem; font-weight:700; color:#388E3C; white-space:nowrap; }
    .price-currency { font-size:.72rem; font-weight:400; color:#757575; }
    .stock-badge { padding:3px 10px; border-radius:100px; font-size:.68rem; font-weight:600; }
    .stock-ok { background:#E8F5E9; color:#2E7D32; }
    .stock-low { background:#FFF3E0; color:#E65100; }

    .insurance-list { display:flex; gap:4px; flex-wrap:wrap; margin-top:6px; }
    .insurance-badge { padding:2px 8px; background:#E3F2FD; color:#1565C0; border-radius:100px; font-size:.62rem; font-weight:600; }

    .loading { text-align:center; padding:40px; color:#757575; font-size:.85rem; }
    .empty-state { text-align:center; padding:60px 20px; color:#757575; }
    .empty-state svg { width:60px; height:60px; color:#BDBDBD; margin-bottom:16px; }
    .empty-state p { font-size:.88rem; }

    .load-more { display:block; margin:0 auto 40px; padding:10px 32px; background:white; border:2px solid #388E3C; color:#388E3C; border-radius:10px; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:600; cursor:pointer; }
    .load-more:hover { background:#E8F5E9; }

    @media(max-width:768px) {
        .search-bar { flex-direction:column; }
        .pharm-card { grid-template-columns:1fr; }
        .pharm-right { align-items:flex-start; flex-direction:row; gap:12px; }
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
            <div class="search-field">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <input type="text" id="medCity" placeholder="Ville (Libreville, Owendo...)" value="Libreville">
            </div>
            <button type="submit" class="search-btn">Rechercher</button>
        </form>
    </div>

    <div id="resultsInfo" class="results-info" style="display:none;"></div>
    <div id="medLoading" class="loading" style="display:none;">
        <div style="margin-bottom:8px;">Recherche en cours...</div>
    </div>
    <div id="medResults" class="pharm-list"></div>
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

async function searchMed(e) {
    if (e) e.preventDefault();
    currentPage = 1;
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
    params.set('per_page', '20');
    params.set('page', currentPage);

    document.getElementById('medLoading').style.display = 'block';
    if (!append) {
        document.getElementById('medResults').innerHTML = '';
        document.getElementById('medEmpty').style.display = 'none';
    }
    document.getElementById('loadMoreBtn').style.display = 'none';

    try {
        const res = await fetch(`${API}/pharma/stock?${params}`);
        const data = await res.json();
        document.getElementById('medLoading').style.display = 'none';

        lastPage = data.meta.last_page;

        if (!data.data.length && !append) {
            document.getElementById('medEmpty').style.display = 'block';
            document.getElementById('resultsInfo').style.display = 'none';
            return;
        }

        // Results info
        document.getElementById('resultsInfo').style.display = 'block';
        document.getElementById('resultsInfo').innerHTML = `<span class="results-count">${data.meta.total}</span> resultat${data.meta.total > 1 ? 's' : ''} trouve${data.meta.total > 1 ? 's' : ''}${city ? ' a <strong>' + escHtml(city) + '</strong>' : ''}`;

        const container = document.getElementById('medResults');
        data.data.forEach(item => {
            const card = document.createElement('div');
            card.className = 'pharm-card';

            const brands = (item.medication.brands || [])
                .map(b => `<span class="med-brand">${escHtml(b.name)}${b.manufacturer ? ' — ' + escHtml(b.manufacturer) : ''}</span>`)
                .join('');
            const rx = item.medication.prescription_required ? '<span class="med-rx">Sur ordonnance</span>' : '';

            const insurances = (item.pharmacy.accepted_insurances || [])
                .map(ins => `<span class="insurance-badge">${escHtml(ins)}</span>`)
                .join('');

            const stockLevel = item.quantity_in_stock > 20 ? 'stock-ok' : 'stock-low';
            const stockLabel = item.quantity_in_stock > 20 ? 'En stock' : 'Stock limite';

            const price = item.unit_price
                ? `<div class="price-tag">${new Intl.NumberFormat('fr-FR').format(item.unit_price)} <span class="price-currency">${item.currency}</span></div>`
                : '<div style="font-size:.82rem;color:#757575;">Prix non communique</div>';

            card.innerHTML = `
                <div>
                    <div class="pharm-name"><a href="/annuaire/${escHtml(item.pharmacy.slug)}">${escHtml(item.pharmacy.name)}</a></div>
                    <div class="pharm-location">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        ${escHtml(item.pharmacy.city || '')}${item.pharmacy.address ? ' — ' + escHtml(item.pharmacy.address) : ''}
                    </div>
                    ${item.pharmacy.phone ? `<div class="pharm-phone">${escHtml(item.pharmacy.phone)}</div>` : ''}
                    <div class="med-info">
                        <div class="med-dci">${escHtml(item.medication.dci)} ${escHtml(item.medication.strength || '')} ${rx}</div>
                        <div class="med-detail">${escHtml(item.medication.dosage_form || '')}</div>
                        ${brands ? `<div class="med-brands">${brands}</div>` : ''}
                    </div>
                    ${insurances ? `<div class="insurance-list" title="Assurances acceptees">${insurances}</div>` : ''}
                </div>
                <div class="pharm-right">
                    ${price}
                    <span class="stock-badge ${stockLevel}">${stockLabel}</span>
                </div>`;
            container.appendChild(card);
        });

        // Load more button
        document.getElementById('loadMoreBtn').style.display = currentPage < lastPage ? 'block' : 'none';
    } catch (err) {
        document.getElementById('medLoading').style.display = 'none';
        console.error(err);
    }
}

function escHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// Auto-search on page load if user came with intent
document.addEventListener('DOMContentLoaded', () => {
    const urlQ = new URLSearchParams(window.location.search).get('q');
    if (urlQ) {
        document.getElementById('medQ').value = urlQ;
        searchMed();
    }
});
</script>
@endsection
