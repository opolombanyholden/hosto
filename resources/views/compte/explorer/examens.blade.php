@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Examens') @section('page-title', 'Examens') @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'examens']) @endsection

@section('styles')
<style>
    .explorer-header { margin-bottom:20px; }
    .explorer-header h2 { font-size:1.1rem; font-weight:700; color:#1B2A1B; margin-bottom:4px; }
    .explorer-header p { font-size:.82rem; color:#757575; }
    .explorer-search { display:flex; gap:10px; margin-bottom:12px; flex-wrap:wrap; }
    .explorer-search input { flex:1; min-width:180px; padding:10px 14px; border:2px solid #EEE; border-radius:10px; font-family:Poppins,sans-serif; font-size:.85rem; outline:none; }
    .explorer-search input:focus { border-color:#1565C0; }
    .explorer-search button { padding:10px 20px; background:#1565C0; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:600; cursor:pointer; }
    .popular-chips { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:20px; }
    .popular-chip { padding:5px 12px; background:white; border:1px solid #E0E0E0; border-radius:100px; font-size:.72rem; color:#424242; cursor:pointer; font-family:Poppins,sans-serif; }
    .popular-chip:hover { background:#E3F2FD; border-color:#1565C0; color:#1565C0; }
    .lab-group { background:white; border:1px solid #EEE; border-radius:14px; margin-bottom:12px; overflow:hidden; }
    .lab-header { padding:14px 16px; border-bottom:1px solid #F5F5F5; }
    .lab-name { font-size:.88rem; font-weight:600; color:#1B2A1B; }
    .lab-city { font-size:.72rem; color:#757575; }
    .lab-ins { display:flex; gap:4px; flex-wrap:wrap; margin-top:4px; }
    .lab-ins span { padding:2px 8px; background:#E3F2FD; color:#1565C0; border-radius:100px; font-size:.6rem; font-weight:600; }
    .exam-row { display:flex; justify-content:space-between; align-items:center; padding:10px 16px; border-bottom:1px solid #FAFAFA; font-size:.82rem; }
    .exam-row:last-child { border-bottom:none; }
    .exam-price { font-weight:700; color:#1565C0; }
    .explorer-loading,.explorer-empty { text-align:center; padding:40px; color:#757575; font-size:.85rem; }
</style>
@endsection

@section('content')
<div class="explorer-header">
    <h2>Trouver un examen medical</h2>
    <p>Recherchez un examen et trouvez les laboratoires qui le proposent.</p>
</div>
<div class="explorer-search">
    <input type="text" id="examQ" placeholder="Nom de l'examen..." autofocus>
    <input type="text" id="examCity" placeholder="Ville..." value="Libreville" style="max-width:180px;">
    <button onclick="searchExam()">Rechercher</button>
</div>
<div class="popular-chips">
    <span class="popular-chip" onclick="quickSearch('bilan sanguin')">Bilan sanguin</span>
    <span class="popular-chip" onclick="quickSearch('echographie')">Echographie</span>
    <span class="popular-chip" onclick="quickSearch('radiographie')">Radiographie</span>
    <span class="popular-chip" onclick="quickSearch('paludisme')">Paludisme</span>
    <span class="popular-chip" onclick="quickSearch('VIH')">VIH</span>
    <span class="popular-chip" onclick="quickSearch('scanner')">Scanner</span>
    <span class="popular-chip" onclick="quickSearch('ECG')">ECG</span>
</div>
<div id="loading" class="explorer-loading" style="display:none;">Recherche...</div>
<div id="results"></div>
<div id="empty" class="explorer-empty" style="display:none;">Aucun resultat.</div>

<script>
function quickSearch(term) { document.getElementById('examQ').value = term; searchExam(); }
async function searchExam() {
    const q = document.getElementById('examQ').value.trim();
    const city = document.getElementById('examCity').value.trim();
    if (!q) return;
    const params = new URLSearchParams({exam:q, per_page:'20'});
    if (city) params.set('city', city);
    document.getElementById('loading').style.display = 'block';
    document.getElementById('results').innerHTML = '';
    document.getElementById('empty').style.display = 'none';
    try {
        const res = await fetch(`${API}/lab/exams/search?${params}`);
        const data = await res.json();
        document.getElementById('loading').style.display = 'none';
        if (!data.data.length) { document.getElementById('empty').style.display = 'block'; return; }
        const container = document.getElementById('results');
        data.data.forEach(item => {
            const lab = item.laboratory;
            const ins = (lab.accepted_insurances||[]).map(i => `<span>${i}</span>`).join('');
            const rows = (item.exams||[]).map(e => `<div class="exam-row"><div>${e.name} <span style="font-size:.68rem;color:#757575;">${e.code}</span></div><div class="exam-price">${e.tarif_min&&e.tarif_max ? new Intl.NumberFormat('fr-FR').format(e.tarif_min)+' - '+new Intl.NumberFormat('fr-FR').format(e.tarif_max)+' XAF' : '—'}</div></div>`).join('');
            container.insertAdjacentHTML('beforeend', `<div class="lab-group"><div class="lab-header"><div class="lab-name">${lab.name}</div><div class="lab-city">${lab.city||''}</div>${ins?`<div class="lab-ins">${ins}</div>`:''}</div>${rows}</div>`);
        });
    } catch(e) { document.getElementById('loading').style.display = 'none'; }
}
document.getElementById('examQ').addEventListener('keydown', e => { if(e.key==='Enter') searchExam(); });
</script>
@endsection
