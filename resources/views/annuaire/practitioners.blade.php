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
    .search-bar { background:white; border-radius:16px; padding:12px; box-shadow:0 12px 48px rgba(0,0,0,.12); display:flex; gap:8px; border:1px solid #EEE; flex-wrap:wrap; }
    .search-field { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; flex:1; min-width:200px; }
    .search-field input { border:none; outline:none; font-family:Poppins,sans-serif; font-size:.85rem; width:100%; }
    .search-btn { padding:12px 28px; background:#1565C0; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.85rem; font-weight:600; cursor:pointer; white-space:nowrap; }
    .prac-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:20px; margin-bottom:40px; }
    .prac-card { background:white; border:1px solid #EEE; border-radius:16px; padding:20px; transition:all .3s; display:block; color:inherit; text-decoration:none; }
    .prac-card:hover { transform:translateY(-4px); box-shadow:0 4px 24px rgba(0,0,0,.08); border-color:#1565C0; }
    .loading { text-align:center; padding:40px; color:#757575; }
    @media(max-width:768px) { .search-bar{flex-direction:column;} .prac-grid{grid-template-columns:1fr;} }
</style>
@endsection

@section('content')
<div class="prac-header"><div class="container"><h1>Annuaire des medecins</h1><p style="opacity:.85;">Trouvez un medecin par specialite, structure ou nom</p></div></div>
<div class="container">
    <div class="search-wrapper">
        <form class="search-bar" onsubmit="searchPrac(event)">
            <div class="search-field"><input type="text" id="pracQ" placeholder="Nom du medecin..."></div>
            <div class="search-field"><select id="pracSpec" style="border:none;font-family:Poppins,sans-serif;font-size:.85rem;width:100%;cursor:pointer;outline:none;"><option value="">Specialite</option></select></div>
            <button type="submit" class="search-btn">Rechercher</button>
        </form>
    </div>
    <div id="pracLoading" class="loading" style="display:none;">Recherche...</div>
    <div id="pracResults" class="prac-grid"></div>
    <div id="pracEmpty" style="display:none;text-align:center;padding:40px;color:#757575;">Aucun medecin trouve.</div>
</div>
@endsection

@section('scripts')
<script>
async function init() {
    const specsRes = await fetch(`${API}/referentiel/specialties`).then(r=>r.json());
    const sel = document.getElementById('pracSpec');
    specsRes.data.forEach(s => { const o=document.createElement('option'); o.value=s.code; o.textContent=s.name; sel.appendChild(o); });
    searchPrac();
}
async function searchPrac(e) {
    if(e) e.preventDefault();
    const params = new URLSearchParams();
    const q = document.getElementById('pracQ').value.trim();
    const spec = document.getElementById('pracSpec').value;
    if(q) params.set('q',q);
    if(spec) params.set('specialty',spec);
    params.set('per_page','20');
    document.getElementById('pracLoading').style.display='block';
    document.getElementById('pracResults').innerHTML='';
    document.getElementById('pracEmpty').style.display='none';
    try {
        const res = await fetch(`${API}/annuaire/practitioners?${params}`);
        const data = await res.json();
        document.getElementById('pracLoading').style.display='none';
        if(!data.data.length) { document.getElementById('pracEmpty').style.display='block'; return; }
        const grid = document.getElementById('pracResults');
        data.data.forEach(p => {
            const specs = (p.specialties||[]).map(s=>s.name).join(', ');
            const structs = (p.structures||[]).map(s=>s.name).join(', ');
            const tc = p.does_teleconsultation ? '<span style="font-size:.65rem;background:#E3F2FD;color:#1565C0;padding:2px 8px;border-radius:100px;font-weight:600;">Teleconsultation</span>' : '';
            const fee = p.consultation_fee_min ? `<span style="font-size:.78rem;color:#388E3C;font-weight:600;">${p.consultation_fee_min.toLocaleString()} - ${p.consultation_fee_max.toLocaleString()} XAF</span>` : '';
            const card = document.createElement('a');
            card.href = `/annuaire/medecins/${p.slug}`;
            card.className = 'prac-card';
            card.innerHTML = `
                <div style="display:flex;gap:12px;align-items:start;">
                    <div style="width:48px;height:48px;border-radius:12px;background:#E3F2FD;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1565C0" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:.9rem;font-weight:600;color:#1B2A1B;">${p.full_name}</div>
                        <div style="font-size:.72rem;color:#1565C0;margin-top:2px;">${specs}</div>
                        <div style="font-size:.72rem;color:#757575;margin-top:2px;">${structs}</div>
                        <div style="display:flex;gap:8px;align-items:center;margin-top:6px;flex-wrap:wrap;">${tc} ${fee}</div>
                    </div>
                </div>`;
            grid.appendChild(card);
        });
    } catch(err) { document.getElementById('pracLoading').style.display='none'; }
}
init();
</script>
@endsection
