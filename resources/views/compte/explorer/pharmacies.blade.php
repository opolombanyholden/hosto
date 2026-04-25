@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Pharmacies') @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'pharmacies']) @endsection

@section('styles')
<style>
    .page-icon { width:48px; height:48px; margin-bottom:12px; }
    .page-title { font-size:1.1rem; font-weight:700; color:#1B2A1B; margin-bottom:2px; }
    .page-sub { font-size:.82rem; color:#757575; margin-bottom:20px; }
    .search-row { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
    .search-row input { flex:1; min-width:180px; padding:10px 14px; border:2px solid #EEE; border-radius:10px; font-family:Poppins,sans-serif; font-size:.85rem; outline:none; }
    .search-row input:focus { border-color:#388E3C; }
    .search-row button { padding:10px 20px; background:#388E3C; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:600; cursor:pointer; }
    .pharm-card { background:white; border:1px solid #EEE; border-radius:14px; margin-bottom:12px; overflow:hidden; transition:border-color .2s; }
    .pharm-card:hover { border-color:#C8E6C9; }
    .pharm-header { padding:14px 16px; display:flex; align-items:center; gap:12px; border-bottom:1px solid #F5F5F5; }
    .pharm-header img { width:40px; height:40px; border-radius:10px; }
    .pharm-name { font-size:.88rem; font-weight:600; color:#1B2A1B; }
    .pharm-name a { color:inherit; text-decoration:none; }
    .pharm-name a:hover { color:#388E3C; }
    .pharm-city { font-size:.72rem; color:#757575; }
    .pharm-phone { font-size:.72rem; color:#388E3C; }
    .pharm-ins { display:flex; gap:4px; flex-wrap:wrap; margin-left:auto; }
    .pharm-ins span { padding:2px 8px; background:#E3F2FD; color:#1565C0; border-radius:100px; font-size:.6rem; font-weight:600; }
    .med-row { display:flex; justify-content:space-between; align-items:center; padding:8px 16px; border-bottom:1px solid #FAFAFA; font-size:.82rem; }
    .med-row:last-child { border-bottom:none; }
    .med-price { font-weight:700; color:#388E3C; }
    .stock-ok { padding:2px 8px; background:#E8F5E9; color:#2E7D32; border-radius:100px; font-size:.62rem; font-weight:600; }
    .stock-low { padding:2px 8px; background:#FFF3E0; color:#E65100; border-radius:100px; font-size:.62rem; font-weight:600; }
    .loading,.empty { text-align:center; padding:40px; color:#757575; font-size:.85rem; }
    .map-wrap { border-radius:14px; overflow:hidden; height:300px; margin-bottom:20px; }
</style>
@endsection

@section('content')
<img src="/images/icons/icon-pharamcie.png" class="page-icon" alt="">
<div class="page-title">Pharmacies</div>
<div class="page-sub">Trouvez un medicament et comparez les prix dans les pharmacies pres de chez vous.</div>

<div class="search-row">
    <input type="text" id="medQ" placeholder="Nom du medicament..." autofocus>
    <input type="text" id="medCity" placeholder="Ville..." value="Libreville" style="max-width:180px;">
    <button onclick="search()">Rechercher</button>
</div>

<div id="map" class="map-wrap"></div>
<div id="loading" class="loading" style="display:none;">Recherche...</div>
<div id="results"></div>
<div id="empty" class="empty" style="display:none;">Aucune pharmacie trouvee.</div>

<script>
// Init map with pharmacies
let map = L.map('map').setView([0.39, 9.45], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OpenStreetMap', maxZoom:19}).addTo(map);
let markers = [];

loadPharmacies();
async function loadPharmacies() {
    try {
        const res = await fetch(`${API}/annuaire/hostos?type=pharmacie&per_page=50`);
        const data = await res.json();
        const icon = L.divIcon({className:'',html:'<div style="background:#388E3C;width:24px;height:24px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,.3);"></div>',iconSize:[24,24],iconAnchor:[12,24],popupAnchor:[0,-24]});
        const bounds = L.latLngBounds();
        (data.data||[]).forEach(h => {
            if (!h.coordinates?.latitude) return;
            const m = L.marker([h.coordinates.latitude, h.coordinates.longitude], {icon}).addTo(map)
                .bindPopup(`<div style="font-family:Poppins,sans-serif;font-size:.82rem;min-width:140px;"><strong>${h.name}</strong><br><span style="color:#757575;font-size:.72rem;">${h.city?.name||''}</span>${h.phone?'<br><a href="tel:'+h.phone+'" style="color:#388E3C;font-size:.75rem;">'+h.phone+'</a>':''}</div>`);
            markers.push(m);
            bounds.extend([h.coordinates.latitude, h.coordinates.longitude]);
        });
        if (markers.length) map.fitBounds(bounds, {padding:[30,30], maxZoom:14});
    } catch(e) {}
}

async function search() {
    const q = document.getElementById('medQ').value.trim();
    const city = document.getElementById('medCity').value.trim();
    if (!q) return;
    const params = new URLSearchParams({medication:q, per_page:'30'});
    if (city) params.set('city', city);
    document.getElementById('loading').style.display='block';
    document.getElementById('results').innerHTML='';
    document.getElementById('empty').style.display='none';
    try {
        const res = await fetch(`${API}/pharma/stock?${params}`);
        const data = await res.json();
        document.getElementById('loading').style.display='none';
        if (!data.data.length) { document.getElementById('empty').style.display='block'; return; }
        const grouped = {};
        data.data.forEach(item => {
            const k = item.pharmacy.uuid;
            if (!grouped[k]) grouped[k] = {pharmacy:item.pharmacy, meds:[]};
            grouped[k].meds.push(item);
        });
        const container = document.getElementById('results');
        Object.values(grouped).forEach(g => {
            const ins = (g.pharmacy.accepted_insurances||[]).map(i=>`<span>${i}</span>`).join('');
            let rows = g.meds.map(m => {
                const stock = m.quantity_in_stock > 20 ? '<span class="stock-ok">En stock</span>' : '<span class="stock-low">Stock limite</span>';
                return `<div class="med-row"><div>${m.medication.dci} ${m.medication.strength||''}</div><div style="display:flex;gap:8px;align-items:center;"><span class="med-price">${m.unit_price ? new Intl.NumberFormat('fr-FR').format(m.unit_price)+' XAF' : '—'}</span>${stock}</div></div>`;
            }).join('');
            container.insertAdjacentHTML('beforeend', `<div class="pharm-card"><div class="pharm-header"><img src="/images/icons/icon-pharamcie.png" alt=""><div><div class="pharm-name"><a href="/annuaire/${g.pharmacy.slug}" target="_blank">${g.pharmacy.name}</a></div><div class="pharm-city">${g.pharmacy.city||''} ${g.pharmacy.address?'— '+g.pharmacy.address:''}</div>${g.pharmacy.phone?'<div class="pharm-phone">'+g.pharmacy.phone+'</div>':''}</div>${ins?'<div class="pharm-ins">'+ins+'</div>':''}</div>${rows}</div>`);
        });
    } catch(e) { document.getElementById('loading').style.display='none'; }
}
document.getElementById('medQ').addEventListener('keydown', e => { if(e.key==='Enter') search(); });
</script>
@endsection
