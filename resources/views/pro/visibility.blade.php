@extends('layouts.dashboard')

@section('env-name', 'HOSTO Pro')
@section('env-color', '#1565C0')
@section('env-color-dark', '#0D47A1')
@section('title', 'Visibilite du profil')
@section('page-title', 'Parametres de visibilite')
@section('user-role', 'Professionnel')

@section('sidebar-nav')
<a href="/pro">Tableau de bord</a>
<a href="/pro/visibility" class="active">Visibilite profil</a>
<a href="/pro/publications">Mes publications</a>
<a href="/pro/consultations">Consultations</a>
@endsection

@section('content')
<div id="visMsg" style="display:none;padding:12px;border-radius:10px;font-size:.82rem;margin-bottom:16px;"></div>

{{-- Visibility settings --}}
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:24px;margin-bottom:20px;">
    <h3 style="font-size:.9rem;font-weight:600;color:#1565C0;margin-bottom:16px;">Informations publiques</h3>
    <p style="font-size:.78rem;color:#757575;margin-bottom:16px;">Choisissez les informations visibles sur votre fiche publique dans l'annuaire.</p>

    @php $vis = $practitioner->visibility_settings ?? []; @endphp

    <div style="display:flex;flex-direction:column;gap:12px;">
        @foreach([
            'phone' => 'Numero de telephone',
            'email' => 'Adresse email',
            'bio' => 'Biographie',
            'languages' => 'Langues parlees',
            'registration_number' => "Numero d'ordre",
            'fees' => 'Tarifs de consultation',
            'photo' => 'Photo de profil',
        ] as $key => $label)
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 12px;border-radius:8px;transition:background .2s;" onmouseover="this.style.background='#F5F5F5'" onmouseout="this.style.background='transparent'">
            <input type="checkbox" class="vis-toggle" data-field="{{ $key }}" {{ ($vis[$key] ?? true) ? 'checked' : '' }} style="width:18px;height:18px;accent-color:#1565C0;">
            <span style="font-size:.85rem;color:#424242;">{{ $label }}</span>
        </label>
        @endforeach
    </div>
    <button onclick="saveVisibility()" style="margin-top:16px;padding:10px 24px;background:#1565C0;color:white;border:none;border-radius:8px;font-family:Poppins,sans-serif;font-size:.82rem;font-weight:600;cursor:pointer;">Enregistrer</button>
</div>

{{-- Offered services --}}
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:24px;">
    <h3 style="font-size:.9rem;font-weight:600;color:#1565C0;margin-bottom:16px;">Services proposes</h3>
    <p style="font-size:.78rem;color:#757575;margin-bottom:16px;">Activez ou desactivez les services que vous souhaitez offrir aux patients.</p>

    @php $svc = $practitioner->offered_services ?? []; @endphp

    <div style="display:flex;flex-direction:column;gap:12px;">
        @foreach([
            'appointment' => 'Prise de rendez-vous en ligne',
            'teleconsultation' => 'Teleconsultation video',
            'chat' => 'Chat / messagerie avec les patients',
        ] as $key => $label)
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 12px;border-radius:8px;transition:background .2s;" onmouseover="this.style.background='#F5F5F5'" onmouseout="this.style.background='transparent'">
            <input type="checkbox" class="svc-toggle" data-service="{{ $key }}" {{ ($svc[$key] ?? true) ? 'checked' : '' }} style="width:18px;height:18px;accent-color:#1565C0;">
            <span style="font-size:.85rem;color:#424242;">{{ $label }}</span>
        </label>
        @endforeach
    </div>
    <button onclick="saveServices()" style="margin-top:16px;padding:10px 24px;background:#1565C0;color:white;border:none;border-radius:8px;font-family:Poppins,sans-serif;font-size:.82rem;font-weight:600;cursor:pointer;">Enregistrer</button>
</div>

<script>
const headers = {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','X-Requested-With':'XMLHttpRequest'};

function showMsg(ok, text) {
    const el = document.getElementById('visMsg');
    el.style.display = 'block';
    el.style.background = ok ? '#E3F2FD' : '#FFEBEE';
    el.style.color = ok ? '#1565C0' : '#C62828';
    el.textContent = text;
    if (ok) setTimeout(() => el.style.display = 'none', 3000);
}

async function saveVisibility() {
    const settings = {};
    document.querySelectorAll('.vis-toggle').forEach(cb => { settings[cb.dataset.field] = cb.checked; });
    try {
        const res = await fetch('/pro/visibility/settings', { method:'PUT', headers, body:JSON.stringify({visibility_settings: settings}) });
        const data = await res.json();
        showMsg(res.ok, data.data?.message || data.error?.message || 'Erreur.');
    } catch(e) { showMsg(false, 'Erreur de connexion.'); }
}

async function saveServices() {
    const services = {};
    document.querySelectorAll('.svc-toggle').forEach(cb => { services[cb.dataset.service] = cb.checked; });
    try {
        const res = await fetch('/pro/visibility/services', { method:'PUT', headers, body:JSON.stringify({offered_services: services}) });
        const data = await res.json();
        showMsg(res.ok, data.data?.message || data.error?.message || 'Erreur.');
    } catch(e) { showMsg(false, 'Erreur de connexion.'); }
}
</script>
@endsection
