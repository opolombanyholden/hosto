@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Urgences et Evacuation') @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'urgences']) @endsection

@section('styles')
<style>
    .page-icon { width:48px; height:48px; margin-bottom:12px; }
    .page-title { font-size:1.1rem; font-weight:700; color:#C62828; margin-bottom:2px; }
    .page-sub { font-size:.82rem; color:#757575; margin-bottom:20px; }
    .urgence-alert { background:#FFEBEE; border:2px solid #EF9A9A; border-radius:14px; padding:16px 20px; margin-bottom:20px; display:flex; align-items:center; gap:12px; }
    .urgence-alert-icon { width:40px; height:40px; background:#C62828; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .urgence-alert-icon svg { width:22px; height:22px; stroke:white; }
    .urgence-alert-text { font-size:.85rem; color:#B71C1C; font-weight:600; }
    .urgence-alert-sub { font-size:.78rem; color:#C62828; }
    .map-wrap { border-radius:14px; overflow:hidden; height:350px; margin-bottom:20px; }
    .results-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:12px; }
    .ucard { background:white; border:1px solid #EEE; border-radius:14px; padding:16px; text-decoration:none; color:#1B2A1B; transition:all .2s; display:block; }
    .ucard:hover { border-color:#EF9A9A; box-shadow:0 4px 12px rgba(198,40,40,.08); }
    .ucard-top { display:flex; gap:12px; align-items:center; margin-bottom:6px; }
    .ucard-img { width:40px; height:40px; border-radius:10px; background:#FFEBEE; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .ucard-name { font-size:.85rem; font-weight:600; }
    .ucard-city { font-size:.68rem; color:#757575; }
    .ucard-phone { font-size:.82rem; color:#C62828; font-weight:600; margin-top:6px; }
    .ucard-phone a { color:#C62828; text-decoration:none; }
    .ucard-emergency { font-size:.78rem; color:#E53935; font-weight:600; margin-top:2px; }
    .tag-garde { padding:3px 10px; background:#FFF3E0; color:#E65100; border-radius:100px; font-size:.65rem; font-weight:600; }
    .loading,.empty { text-align:center; padding:40px; color:#757575; font-size:.85rem; }
</style>
@endsection

@section('content')
<img src="/images/icons/icon-ambulance.png" class="page-icon" alt="">
<div class="page-title">Urgences et Evacuation</div>
<div class="page-sub">Structures avec services de garde, urgences et numeros d'urgence.</div>

<div class="urgence-alert">
    <div class="urgence-alert-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/></svg>
    </div>
    <div>
        <div class="urgence-alert-text">En cas d'urgence vitale, appelez le 1300 ou le 011 76 22 44</div>
        <div class="urgence-alert-sub">SAMU / CHU de Libreville</div>
    </div>
</div>

<div id="map" class="map-wrap"></div>
<div id="loading" class="loading" style="display:none;">Recherche...</div>
<div id="results" class="results-grid"></div>
<div id="empty" class="empty" style="display:none;">Aucune structure trouvee.</div>

<script>
let map = L.map('map').setView([0.39,9.45],12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap',maxZoom:19}).addTo(map);

doSearch();
async function doSearch() {
    const params = new URLSearchParams({garde:'1', per_page:'50'});
    document.getElementById('loading').style.display='block';
    document.getElementById('results').innerHTML='';
    try {
        const res = await fetch(`${API}/annuaire/hostos?${params}`);
        const data = await res.json();
        document.getElementById('loading').style.display='none';

        // Also load hospitals with emergency
        const res2 = await fetch(`${API}/annuaire/hostos?type=hopital&per_page=50`);
        const data2 = await res2.json();

        // Merge and deduplicate
        const all = {};
        [...(data.data||[]), ...(data2.data||[])].forEach(h => { all[h.uuid] = h; });
        const list = Object.values(all);

        if (!list.length) { document.getElementById('empty').style.display='block'; return; }

        // Map markers
        const redIcon = L.divIcon({className:'',html:'<div style="background:#C62828;width:24px;height:24px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,.3);"></div>',iconSize:[24,24],iconAnchor:[12,24],popupAnchor:[0,-24]});
        const bounds = L.latLngBounds();
        list.forEach(h => {
            if (!h.coordinates?.latitude) return;
            L.marker([h.coordinates.latitude,h.coordinates.longitude],{icon:redIcon}).addTo(map)
                .bindPopup(`<div style="font-family:Poppins,sans-serif;font-size:.82rem;"><strong>${h.name}</strong><br>${h.phone?'<a href="tel:'+h.phone+'" style="color:#C62828;font-weight:600;">'+h.phone+'</a>':''}</div>`);
            bounds.extend([h.coordinates.latitude,h.coordinates.longitude]);
        });
        if (bounds.isValid()) map.fitBounds(bounds,{padding:[30,30],maxZoom:14});
        setTimeout(()=>map.invalidateSize(),100);

        // Cards
        const grid = document.getElementById('results');
        list.forEach(h => {
            const card = document.createElement('a');
            card.className='ucard'; card.href='/annuaire/'+h.slug; card.target='_blank';
            let tags = h.is_guard_service ? '<span class="tag-garde">Service de garde</span>' : '';
            card.innerHTML=`<div class="ucard-top"><div class="ucard-img"><img src="/images/icons/icon-hopitaux.png" style="width:28px;height:28px;"></div><div><div class="ucard-name">${h.name}</div><div class="ucard-city">${h.city?.name||''}</div></div></div>${h.phone?'<div class="ucard-phone"><a href="tel:'+h.phone+'">'+h.phone+'</a></div>':''}${h.emergency_phone?'<div class="ucard-emergency">Urgences : '+h.emergency_phone+'</div>':''}${tags}`;
            grid.appendChild(card);
        });
    } catch(e) { document.getElementById('loading').style.display='none'; }
}
</script>
@endsection
