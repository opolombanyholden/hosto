@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', 'Configuration — ' . $structure->name) @section('page-title', 'Configuration structure') @section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar') @endsection

@section('breadcrumb')
<span style="color:#BDBDBD;margin:0 6px;">/</span>
<a href="/admin/structures" style="color:#B71C1C;text-decoration:none;font-weight:500;">Structures</a>
<span style="color:#BDBDBD;margin:0 6px;">/</span>
<span style="color:#424242;">{{ $structure->name }}</span>
@endsection

@section('content')
<div id="configMsg" style="display:none;padding:12px;border-radius:10px;font-size:.82rem;margin-bottom:16px;"></div>

<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:24px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <div style="width:48px;height:48px;border-radius:12px;background:#E8F5E9;display:flex;align-items:center;justify-content:center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#388E3C" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        </div>
        <div>
            <h2 style="font-size:1.1rem;font-weight:700;color:#1B2A1B;">{{ $structure->name }}</h2>
            <p style="font-size:.78rem;color:#757575;">{{ $structure->structureTypes->pluck('name_fr')->join(', ') }} — {{ $structure->city?->name_fr }}</p>
        </div>
    </div>

    <h3 style="font-size:.88rem;font-weight:600;color:#B71C1C;margin-bottom:12px;">Sections mises en avant</h3>
    <p style="font-size:.78rem;color:#757575;margin-bottom:16px;">
        Selectionnez les sections a mettre en avant sur la fiche publique de cette structure.
        Si aucune section n'est selectionnee, le systeme detecte automatiquement les sections pertinentes selon le type de structure.
    </p>

    @php $current = $structure->featured_sections ?? []; @endphp

    <div style="display:flex;flex-direction:column;gap:10px;">
        @foreach($availableSections as $code => $label)
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px 14px;border-radius:8px;border:1px solid #EEE;transition:all .2s;" onmouseover="this.style.background='#FAFAFA'" onmouseout="this.style.background='white'">
            <input type="checkbox" class="featured-check" value="{{ $code }}" {{ in_array($code, $current) ? 'checked' : '' }} style="width:18px;height:18px;accent-color:#B71C1C;">
            <div>
                <div style="font-size:.85rem;font-weight:500;color:#1B2A1B;">{{ $label }}</div>
                <div style="font-size:.68rem;color:#757575;">Code : {{ $code }}</div>
            </div>
        </label>
        @endforeach
    </div>

    <div style="display:flex;gap:10px;margin-top:16px;">
        <button onclick="saveFeatured()" style="padding:10px 24px;background:#B71C1C;color:white;border:none;border-radius:8px;font-family:Poppins,sans-serif;font-size:.82rem;font-weight:600;cursor:pointer;">Enregistrer</button>
        <button onclick="clearFeatured()" style="padding:10px 24px;border:1px solid #EEE;border-radius:8px;background:white;cursor:pointer;font-family:Poppins,sans-serif;font-size:.82rem;color:#757575;">Reinitialiser (auto-detect)</button>
    </div>
</div>

<div style="background:#F5F5F5;border-radius:10px;padding:14px;font-size:.78rem;color:#757575;">
    <strong>Detection automatique :</strong> Si aucune section n'est selectionnee, le systeme affiche automatiquement :
    Pharmacie → Catalogue medicaments | Laboratoire → Examens | Urgence → Services d'urgence | Teleconsultation → si disponible.
</div>

<script>
const CSRF = '{{ csrf_token() }}';

function showMsg(ok, text) {
    const el = document.getElementById('configMsg');
    el.style.display = 'block';
    el.style.background = ok ? '#E8F5E9' : '#FFEBEE';
    el.style.color = ok ? '#2E7D32' : '#C62828';
    el.textContent = text;
    if (ok) setTimeout(() => el.style.display = 'none', 3000);
}

async function saveFeatured() {
    const checks = document.querySelectorAll('.featured-check');
    const sections = [];
    checks.forEach(cb => { if (cb.checked) sections.push(cb.value); });

    try {
        const res = await fetch('/admin/structures/{{ $structure->uuid }}/featured-sections', {
            method: 'PUT',
            headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({featured_sections: sections})
        });
        const data = await res.json();
        showMsg(res.ok, data.data?.message || data.error?.message || 'Erreur.');
    } catch(e) { showMsg(false, 'Erreur de connexion.'); }
}

function clearFeatured() {
    document.querySelectorAll('.featured-check').forEach(cb => cb.checked = false);
    saveFeatured();
}
</script>
@endsection
