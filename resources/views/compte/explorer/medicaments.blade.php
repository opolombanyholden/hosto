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
    .explorer-header h2 { font-size:1.1rem; font-weight:700; color:#1B2A1B; margin-bottom:4px; }
    .explorer-header p { font-size:.82rem; color:#757575; }
    .explorer-search { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
    .explorer-search input { flex:1; min-width:180px; padding:10px 14px; border:2px solid #EEE; border-radius:10px; font-family:Poppins,sans-serif; font-size:.85rem; outline:none; }
    .explorer-search input:focus { border-color:#388E3C; }
    .explorer-search button { padding:10px 20px; background:#388E3C; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:600; cursor:pointer; }
    .pharm-group { background:white; border:1px solid #EEE; border-radius:14px; margin-bottom:12px; overflow:hidden; }
    .pharm-header { padding:14px 16px; border-bottom:1px solid #F5F5F5; }
    .pharm-name { font-size:.88rem; font-weight:600; color:#1B2A1B; }
    .pharm-city { font-size:.72rem; color:#757575; }
    .pharm-ins { display:flex; gap:4px; flex-wrap:wrap; margin-top:4px; }
    .pharm-ins span { padding:2px 8px; background:#E3F2FD; color:#1565C0; border-radius:100px; font-size:.6rem; font-weight:600; }
    .med-row { display:flex; justify-content:space-between; align-items:center; padding:10px 16px; border-bottom:1px solid #FAFAFA; font-size:.82rem; }
    .med-row:last-child { border-bottom:none; }
    .med-price { font-weight:700; color:#388E3C; }
    .explorer-loading,.explorer-empty { text-align:center; padding:40px; color:#757575; font-size:.85rem; }
</style>
@endsection

@section('content')
<div class="explorer-header">
    <h2>Trouver un medicament</h2>
    <p>Recherchez un medicament et trouvez les pharmacies qui le proposent.</p>
</div>
<div class="explorer-search">
    <input type="text" id="medQ" placeholder="Nom du medicament..." autofocus>
    <input type="text" id="medCity" placeholder="Ville..." value="Libreville" style="max-width:180px;">
    <button onclick="searchMed()">Rechercher</button>
</div>
<div id="loading" class="explorer-loading" style="display:none;">Recherche...</div>
<div id="results"></div>
<div id="empty" class="explorer-empty" style="display:none;">Aucun resultat.</div>

<script>
async function searchMed() {
    const q = document.getElementById('medQ').value.trim();
    const city = document.getElementById('medCity').value.trim();
    if (!q) return;
    const params = new URLSearchParams({medication:q, per_page:'30'});
    if (city) params.set('city', city);
    document.getElementById('loading').style.display = 'block';
    document.getElementById('results').innerHTML = '';
    document.getElementById('empty').style.display = 'none';
    try {
        const res = await fetch(`${API}/pharma/stock?${params}`);
        const data = await res.json();
        document.getElementById('loading').style.display = 'none';
        if (!data.data.length) { document.getElementById('empty').style.display = 'block'; return; }
        const grouped = {};
        data.data.forEach(item => {
            const k = item.pharmacy.uuid;
            if (!grouped[k]) grouped[k] = {pharmacy:item.pharmacy, meds:[]};
            grouped[k].meds.push(item);
        });
        const container = document.getElementById('results');
        Object.values(grouped).forEach(g => {
            const ins = (g.pharmacy.accepted_insurances||[]).map(i => `<span>${i}</span>`).join('');
            let rows = g.meds.map(m => `<div class="med-row"><div>${m.medication.dci} ${m.medication.strength||''}</div><div class="med-price">${m.unit_price ? new Intl.NumberFormat('fr-FR').format(m.unit_price)+' XAF' : '—'}</div></div>`).join('');
            container.insertAdjacentHTML('beforeend', `<div class="pharm-group"><div class="pharm-header"><div class="pharm-name">${g.pharmacy.name}</div><div class="pharm-city">${g.pharmacy.city||''}</div>${ins?`<div class="pharm-ins">${ins}</div>`:''}</div>${rows}</div>`);
        });
    } catch(e) { document.getElementById('loading').style.display = 'none'; }
}
document.getElementById('medQ').addEventListener('keydown', e => { if(e.key==='Enter') searchMed(); });
</script>
@endsection
