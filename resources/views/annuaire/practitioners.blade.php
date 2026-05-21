@extends('layouts.app')

@section('title', 'Annuaire des medecins — HOSTO')
@section('breadcrumb')
<li><span class="sep">/</span> <a href="/annuaire">Annuaire</a></li>
<li><span class="sep">/</span> <span class="current">Medecins</span></li>
@endsection

@section('styles')
<style>
    .prac-header { background:linear-gradient(135deg,#0D47A1,#1565C0); padding:56px 0 100px; color:white; text-align:center; }
    .prac-header h1 { font-size:clamp(1.6rem,4vw,2.2rem); font-weight:700; margin-bottom:8px; }
    .search-wrapper { margin-top:-50px; position:relative; z-index:10; margin-bottom:32px; }
    .search-bar { background:white; border-radius:16px; padding:12px; box-shadow:0 12px 48px rgba(0,0,0,.12); display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:8px; align-items:center; border:1px solid #EEE; }
    .search-field { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; min-width:0; }
    .search-field input { border:none; outline:none; font-family:Poppins,sans-serif; font-size:.85rem; width:100%; }
    .search-btn { padding:12px 28px; background:#1565C0; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.85rem; font-weight:600; cursor:pointer; white-space:nowrap; }
    .prac-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:20px; margin-bottom:40px; }
    .prac-card { background:white; border:1px solid #EEE; border-radius:16px; padding:20px; transition:all .3s; display:block; color:inherit; text-decoration:none; }
    .prac-card:hover { transform:translateY(-4px); box-shadow:0 4px 24px rgba(0,0,0,.08); border-color:#1565C0; }
    .prac-avatar { width:48px; height:48px; border-radius:12px; background:#E3F2FD; color:#1565C0; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-weight:700; font-size:.95rem; }
    .loading { text-align:center; padding:40px; color:#757575; }
    .badge-partner { background:#E3F2FD; color:#1565C0; }
    .badge-tc { background:#E1F5FE; color:#0277BD; }
    .badge-home { background:#F3E5F5; color:#6A1B9A; }
    .prac-pill { font-size:.65rem; padding:2px 8px; border-radius:100px; font-weight:600; }
    @media(max-width:768px) { .search-bar{grid-template-columns:1fr;} .prac-grid{grid-template-columns:1fr;} }
</style>
@endsection

@section('content')
<div class="prac-header"><div class="container"><h1>Annuaire des medecins</h1><p style="opacity:.85;">Trouvez un medecin par specialite, ville ou nom</p></div></div>
<div class="container">
    <div class="search-wrapper">
        <form class="search-bar" onsubmit="searchPrac(event)">
            <div class="search-field">
                <input type="text" id="pracQ" placeholder="Nom du medecin...">
            </div>
            <div class="search-field">
                <input type="text" id="pracSpec" list="specList" placeholder="Specialite (autocomplete)" autocomplete="off">
                <datalist id="specList"></datalist>
            </div>
            <div class="search-field">
                <input type="text" id="pracCity" list="cityList" placeholder="Ville (autocomplete)" autocomplete="off">
                <datalist id="cityList"></datalist>
            </div>
            <button type="submit" class="search-btn">Rechercher</button>
        </form>
        <div style="margin-top:8px;display:flex;gap:14px;align-items:center;flex-wrap:wrap;font-size:.8rem;color:white;text-shadow:0 1px 2px rgba(0,0,0,.3);">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;"><input type="checkbox" id="pracPartner" onchange="searchPrac()"> Partenaires HOSTO uniquement</label>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;"><input type="checkbox" id="pracTC" onchange="searchPrac()"> Teleconsultation</label>
        </div>
    </div>
    <div id="pracLoading" class="loading" style="display:none;">Recherche...</div>
    <div id="pracResults" class="prac-grid"></div>
    <div id="pracEmpty" style="display:none;text-align:center;padding:40px;color:#757575;">Aucun medecin trouve.</div>
</div>
@endsection

@section('scripts')
<script>
const SPECS_BY_NAME = new Map();
const CITIES_BY_NAME = new Map();

async function loadSpecialties() {
    try {
        const res = await fetch(`${API}/referentiel/specialties`);
        const data = await res.json();
        const dl = document.getElementById('specList');
        (data.data || []).forEach(s => {
            SPECS_BY_NAME.set(s.name.toLowerCase(), s.code);
            const o = document.createElement('option');
            o.value = s.name;
            dl.appendChild(o);
        });
    } catch (e) { /* fail silently */ }
}

async function loadCities(q) {
    try {
        const url = q ? `${API}/referentiel/cities?q=${encodeURIComponent(q)}` : `${API}/referentiel/cities`;
        const res = await fetch(url);
        const data = await res.json();
        const dl = document.getElementById('cityList');
        while (dl.firstChild) dl.removeChild(dl.firstChild);
        (data.data || []).forEach(c => {
            CITIES_BY_NAME.set(c.name.toLowerCase(), c.uuid);
            const o = document.createElement('option');
            o.value = c.name;
            dl.appendChild(o);
        });
    } catch (e) { /* fail silently */ }
}

document.addEventListener('DOMContentLoaded', () => {
    const cityInput = document.getElementById('pracCity');
    let cityTimer = null;
    cityInput.addEventListener('input', () => {
        clearTimeout(cityTimer);
        cityTimer = setTimeout(() => loadCities(cityInput.value), 250);
    });
});

async function searchPrac(e) {
    if (e) e.preventDefault();
    const params = new URLSearchParams();
    const q = document.getElementById('pracQ').value.trim();
    const specInput = document.getElementById('pracSpec').value.trim();
    const cityInput = document.getElementById('pracCity').value.trim();

    if (q) params.set('q', q);
    if (specInput) {
        const code = SPECS_BY_NAME.get(specInput.toLowerCase());
        params.set('specialty', code || specInput);
    }
    if (cityInput) {
        const uuid = CITIES_BY_NAME.get(cityInput.toLowerCase());
        params.set('city', uuid || cityInput);
    }
    if (document.getElementById('pracPartner').checked) params.set('partner_only', '1');
    if (document.getElementById('pracTC').checked) params.set('teleconsultation', '1');
    params.set('per_page', '20');

    const loading = document.getElementById('pracLoading');
    const grid = document.getElementById('pracResults');
    const empty = document.getElementById('pracEmpty');
    loading.style.display = 'block';
    while (grid.firstChild) grid.removeChild(grid.firstChild);
    empty.style.display = 'none';

    try {
        const res = await fetch(`${API}/annuaire/practitioners?${params}`);
        const data = await res.json();
        loading.style.display = 'none';
        if (!data.data.length) { empty.style.display = 'block'; return; }
        data.data.forEach(p => grid.appendChild(buildCard(p)));
    } catch (err) { loading.style.display = 'none'; }
}

function initials(name) {
    return (name || '?').split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('');
}

function buildCard(p) {
    const specs = (p.specialties || []).map(s => s.name).join(', ');
    const structs = (p.structures || []).map(s => s.name).join(', ');
    const cities = [...new Set((p.structures || []).map(s => s.city?.name).filter(Boolean))].join(', ');

    const card = document.createElement('a');
    card.href = `/annuaire/medecins/${p.slug}`;
    card.className = 'prac-card';

    const inner = document.createElement('div');
    inner.style.cssText = 'display:flex;gap:12px;align-items:start;';

    const avatar = document.createElement('div');
    avatar.className = 'prac-avatar';
    avatar.textContent = initials(p.full_name);

    const body = document.createElement('div');
    body.style.flex = '1';

    function addLine(text, color, size, weight) {
        if (!text) return;
        const d = document.createElement('div');
        d.style.cssText = `font-size:${size};font-weight:${weight || 400};color:${color};margin-top:2px;`;
        d.textContent = text;
        body.appendChild(d);
    }

    addLine(p.full_name, '#1B2A1B', '.9rem', 600);
    addLine(specs, '#1565C0', '.72rem');
    addLine(structs, '#757575', '.72rem');
    if (cities) addLine(cities, '#9E9E9E', '.68rem');

    const badges = document.createElement('div');
    badges.style.cssText = 'display:flex;gap:6px;align-items:center;margin-top:6px;flex-wrap:wrap;';

    function addPill(text, klass) {
        const s = document.createElement('span');
        s.className = 'prac-pill ' + klass;
        s.textContent = text;
        badges.appendChild(s);
    }

    if (p.is_partner) addPill('Partenaire HOSTO', 'badge-partner');
    if (p.does_teleconsultation) addPill('Teleconsultation', 'badge-tc');
    if (p.does_home_care) addPill('Soins a domicile', 'badge-home');

    if (p.consultation_fee_min) {
        const fee = document.createElement('span');
        fee.style.cssText = 'font-size:.78rem;color:#388E3C;font-weight:600;margin-left:auto;';
        const max = p.consultation_fee_max && p.consultation_fee_max !== p.consultation_fee_min
            ? ` - ${p.consultation_fee_max.toLocaleString('fr-FR')}` : '';
        fee.textContent = `${p.consultation_fee_min.toLocaleString('fr-FR')}${max} XAF`;
        badges.appendChild(fee);
    }

    body.appendChild(badges);
    inner.appendChild(avatar);
    inner.appendChild(body);
    card.appendChild(inner);
    return card;
}

loadSpecialties().then(() => loadCities()).then(() => searchPrac());
</script>
@endsection
