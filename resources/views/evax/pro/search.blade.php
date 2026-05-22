@extends('layouts.dashboard')
@section('env-name', 'HOSTO Pro') @section('env-color', '#1565C0') @section('env-color-dark', '#0D47A1')
@section('title', 'Carnet de vaccination')
@section('page-title', 'Ajouter une vaccination')
@section('user-role', 'Professionnel de sante')

@section('content')
<div style="display:grid;grid-template-columns:1fr;gap:24px;max-width:760px;">

    <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:18px;">
        <h3 style="margin:0 0 10px;font-size:1rem;">1. Rechercher un patient</h3>
        <input type="text" id="proSearch" placeholder="NIP, nom ou telephone..." style="width:100%;padding:10px;border:2px solid #EEE;border-radius:8px;">
        <div id="searchResults" style="margin-top:12px;display:flex;flex-direction:column;gap:8px;"></div>
    </div>

    <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:18px;">
        <h3 style="margin:0 0 10px;font-size:1rem;">2. Ou scanner le QR identite du patient</h3>
        <label style="display:block;font-size:.82rem;color:#757575;margin-bottom:6px;">Coller l'URL scannee ou le secret 32 caracteres :</label>
        <div style="display:flex;gap:8px;">
            <input type="text" id="qrInput" placeholder="https://hosto.ga/carnet/identity/... ou xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" style="flex:1;padding:10px;border:2px solid #EEE;border-radius:8px;">
            <button onclick="resolveQr()" style="padding:10px 22px;background:#1565C0;color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;">Resoudre</button>
        </div>
        <div id="qrResult" style="margin-top:12px;"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function clearChildren(el) { while (el && el.firstChild) el.removeChild(el.firstChild); }

let searchDeb = null;
document.getElementById('proSearch').addEventListener('input', () => {
    clearTimeout(searchDeb);
    searchDeb = setTimeout(runSearch, 250);
});

async function runSearch() {
    const q = document.getElementById('proSearch').value.trim();
    const out = document.getElementById('searchResults');
    clearChildren(out);
    if (!q) return;
    const res = await fetch('/pro/evax/search?q=' + encodeURIComponent(q));
    const data = await res.json();
    data.data.forEach(p => renderPatient(out, p));
}

function renderPatient(out, p) {
    const card = document.createElement('div');
    card.style.cssText = 'border:1px solid #EEE;border-radius:10px;padding:12px;';
    const title = document.createElement('div'); title.style.fontWeight = '600';
    title.textContent = p.full_name + (p.nip ? ' — ' + p.nip : '');
    card.appendChild(title);
    const meta = document.createElement('div'); meta.style.cssText = 'font-size:.78rem;color:#757575;';
    meta.textContent = (p.date_of_birth ? 'Ne le ' + p.date_of_birth : '') + (p.phone ? ' · ' + p.phone : '');
    card.appendChild(meta);

    const link = document.createElement('a');
    link.href = '/pro/evax/vaccinations/new?target=user-' + p.uuid;
    link.textContent = '→ Ajouter une vaccination';
    link.style.cssText = 'display:inline-block;margin-top:6px;color:#1565C0;font-weight:600;text-decoration:none;font-size:.82rem;';
    card.appendChild(link);

    if (p.dependents && p.dependents.length) {
        const depTitle = document.createElement('div'); depTitle.style.cssText = 'margin-top:8px;font-size:.78rem;color:#757575;';
        depTitle.textContent = 'Dependants :';
        card.appendChild(depTitle);
        p.dependents.forEach(d => {
            const dlink = document.createElement('a');
            dlink.href = '/pro/evax/vaccinations/new?target=dep-' + d.uuid;
            dlink.textContent = '→ ' + d.full_name + ' (' + d.date_of_birth + ')';
            dlink.style.cssText = 'display:block;color:#6A1B9A;font-size:.78rem;text-decoration:none;margin-top:2px;';
            card.appendChild(dlink);
        });
    }
    out.appendChild(card);
}

async function resolveQr() {
    const qr = document.getElementById('qrInput').value.trim();
    const out = document.getElementById('qrResult');
    clearChildren(out);
    if (!qr) return;
    const res = await fetch('/pro/evax/resolve-qr', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ qr }),
    });
    if (!res.ok) {
        const m = document.createElement('div');
        m.style.cssText = 'color:#C62828;font-size:.85rem;';
        m.textContent = 'QR non resolu (' + res.status + ')';
        out.appendChild(m); return;
    }
    const data = (await res.json()).data;
    renderPatient(out, data);
}
</script>
@endsection
