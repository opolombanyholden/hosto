@extends('layouts.app')

@section('title', 'Catalogue des medicaments — HOSTO')
@section('breadcrumb')
<li><span class="sep">/</span> <span class="current">Medicaments</span></li>
@endsection

@section('styles')
<style>
    .med-header { background:linear-gradient(135deg,#2E7D32,#43A047); padding:56px 0 100px; color:white; text-align:center; }
    .med-header h1 { font-size:clamp(1.6rem,4vw,2.2rem); font-weight:700; margin-bottom:8px; }
    .search-wrapper { margin-top:-50px; position:relative; z-index:10; margin-bottom:32px; }
    .search-bar { background:white; border-radius:16px; padding:12px; box-shadow:0 12px 48px rgba(0,0,0,.12); display:flex; gap:8px; border:1px solid #EEE; flex-wrap:wrap; }
    .search-field { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; flex:1; min-width:200px; }
    .search-field input { border:none; outline:none; font-family:Poppins,sans-serif; font-size:.85rem; width:100%; }
    .search-btn { padding:12px 28px; background:#388E3C; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.85rem; font-weight:600; cursor:pointer; }
    .med-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; margin-bottom:40px; }
    .med-card { background:white; border:1px solid #EEE; border-radius:14px; padding:20px; }
    .med-dci { font-size:.95rem; font-weight:700; color:#1B2A1B; }
    .med-class { font-size:.72rem; color:#388E3C; margin-top:2px; }
    .med-form { font-size:.75rem; color:#757575; margin-top:2px; }
    .med-brands { margin-top:8px; display:flex; gap:6px; flex-wrap:wrap; }
    .med-brand { padding:3px 10px; background:#F5F5F5; border-radius:100px; font-size:.68rem; color:#424242; }
    .med-rx { padding:3px 10px; background:#FFEBEE; color:#C62828; border-radius:100px; font-size:.68rem; font-weight:600; }
    .loading { text-align:center; padding:40px; color:#757575; }
    @media(max-width:768px) { .search-bar{flex-direction:column;} .med-grid{grid-template-columns:1fr;} }
</style>
@endsection

@section('content')
<div class="med-header"><div class="container"><h1>Catalogue des medicaments</h1><p style="opacity:.85;">Recherchez un medicament par DCI ou nom commercial</p></div></div>
<div class="container">
    <div class="search-wrapper">
        <form class="search-bar" onsubmit="searchMed(event)">
            <div class="search-field"><input type="text" id="medQ" placeholder="Paracetamol, Doliprane, Amoxicilline..."></div>
            <button type="submit" class="search-btn">Rechercher</button>
        </form>
    </div>
    <div id="medLoading" class="loading" style="display:none;">Recherche...</div>
    <div id="medResults" class="med-grid"></div>
    <div id="medEmpty" style="display:none;text-align:center;padding:40px;color:#757575;">Aucun medicament trouve.</div>
</div>
@endsection

@section('scripts')
<script>
searchMed();
async function searchMed(e) {
    if(e) e.preventDefault();
    const q = document.getElementById('medQ').value.trim();
    const params = new URLSearchParams();
    if(q) params.set('q', q);
    params.set('per_page','30');
    document.getElementById('medLoading').style.display='block';
    document.getElementById('medResults').innerHTML='';
    document.getElementById('medEmpty').style.display='none';
    try {
        const res = await fetch(`${API}/referentiel/medications?${params}`);
        const data = await res.json();
        document.getElementById('medLoading').style.display='none';
        if(!data.data.length) { document.getElementById('medEmpty').style.display='block'; return; }
        const grid = document.getElementById('medResults');
        data.data.forEach(m => {
            const brands = (m.brands||[]).map(b => `<span class="med-brand">${b.name}${b.manufacturer ? ' ('+b.manufacturer+')' : ''}</span>`).join('');
            const rx = m.prescription_required ? '<span class="med-rx">Sur ordonnance</span>' : '';
            const card = document.createElement('div');
            card.className = 'med-card';
            card.innerHTML = `
                <div style="display:flex;justify-content:space-between;align-items:start;">
                    <div>
                        <div class="med-dci">${m.dci} ${m.strength||''}</div>
                        <div class="med-class">${m.therapeutic_class||''}</div>
                        <div class="med-form">${m.dosage_form||''}</div>
                    </div>
                    ${rx}
                </div>
                ${brands ? `<div class="med-brands">${brands}</div>` : ''}`;
            grid.appendChild(card);
        });
    } catch(err) { document.getElementById('medLoading').style.display='none'; }
}
</script>
@endsection
