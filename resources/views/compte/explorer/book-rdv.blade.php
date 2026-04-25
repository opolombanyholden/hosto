@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Rendez-vous — ' . $hosto->name) @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'structures']) @endsection

@section('breadcrumb')
<span style="color:#BDBDBD;margin:0 6px;">/</span>
<a href="/compte/structures" style="color:#388E3C;text-decoration:none;font-weight:500;">Structures</a>
<span style="color:#BDBDBD;margin:0 6px;">/</span>
<a href="/compte/structure/{{ $hosto->slug }}" style="color:#388E3C;text-decoration:none;font-weight:500;">{{ $hosto->name }}</a>
<span style="color:#BDBDBD;margin:0 6px;">/</span>
<span style="color:#424242;">Rendez-vous</span>
@endsection

@section('styles')
<style>
    .rdv-step { background:white; border:1px solid #EEE; border-radius:14px; padding:20px; margin-bottom:14px; }
    .rdv-step-title { font-size:.85rem; font-weight:600; color:#388E3C; margin-bottom:10px; display:flex; align-items:center; gap:8px; }
    .rdv-step-num { width:24px;height:24px;background:#388E3C;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700; }
    .field { margin-bottom:14px; }
    .field label { display:block; font-size:.82rem; font-weight:500; color:#424242; margin-bottom:5px; }
    .field select, .field input, .field textarea { width:100%; padding:10px 14px; border:2px solid #EEE; border-radius:8px; font-family:Poppins,sans-serif; font-size:.85rem; outline:none; box-sizing:border-box; }
    .field select:focus, .field input:focus, .field textarea:focus { border-color:#388E3C; }
    .slots-grid { display:flex; gap:8px; flex-wrap:wrap; }
    .slot-btn { padding:8px 16px; border:2px solid #E8F5E9; border-radius:8px; background:white; cursor:pointer; font-family:Poppins,sans-serif; font-size:.78rem; font-weight:500; color:#388E3C; transition:all .2s; }
    .slot-btn:hover { background:#E8F5E9; border-color:#388E3C; }
    .slot-btn.selected { background:#388E3C; color:white; border-color:#388E3C; }
    .slot-day { margin-bottom:12px; }
    .slot-day-label { font-size:.82rem; font-weight:600; color:#1B2A1B; margin-bottom:6px; }
    .submit-btn { width:100%; padding:12px; background:#388E3C; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.88rem; font-weight:600; cursor:pointer; }
    .submit-btn:hover { background:#2E7D32; }
    .submit-btn:disabled { opacity:.5; cursor:not-allowed; }
    #rdvMessage { display:none; padding:12px; border-radius:10px; margin-bottom:14px; font-size:.85rem; }
</style>
@endsection

@section('content')
<h2 style="font-size:1.1rem;font-weight:700;color:#1B2A1B;margin-bottom:4px;">Prendre rendez-vous</h2>
<p style="font-size:.82rem;color:#757575;margin-bottom:20px;">{{ $hosto->name }}</p>

<div id="rdvMessage"></div>

<div class="rdv-step">
    <div class="rdv-step-title"><span class="rdv-step-num">1</span> Specialite</div>
    <div class="field">
        <select id="rdvSpecialty" onchange="loadSlots()">
            <option value="">Choisissez une specialite</option>
            @foreach($specialties as $spec)<option value="{{ $spec->code }}">{{ $spec->name_fr }}</option>@endforeach
        </select>
    </div>
</div>

<div class="rdv-step">
    <div class="rdv-step-title"><span class="rdv-step-num">2</span> Date et heure</div>
    <div id="slotsContainer"><p style="font-size:.82rem;color:#757575;">Selectionnez d'abord une specialite.</p></div>
    <div id="slotsLoading" style="display:none;font-size:.82rem;color:#757575;">Chargement...</div>
</div>

<div class="rdv-step">
    <div class="rdv-step-title"><span class="rdv-step-num">3</span> Motif</div>
    <div class="field"><textarea id="rdvReason" rows="3" placeholder="Motif de consultation..." maxlength="500"></textarea></div>
</div>

<div class="rdv-step">
    <div class="rdv-step-title"><span class="rdv-step-num">4</span> Beneficiaire</div>
    <div class="field">
        <label><input type="radio" name="beneficiary" value="self" checked> Pour moi-meme</label>
        <label style="margin-left:20px;"><input type="radio" name="beneficiary" value="third"> Pour un tiers</label>
    </div>
</div>

<button class="submit-btn" id="submitBtn" onclick="submitRdv()" disabled>Confirmer le rendez-vous</button>
<input type="hidden" id="selectedSlotUuid" value="">

<script>
async function loadSlots() {
    const spec = document.getElementById('rdvSpecialty').value;
    const container = document.getElementById('slotsContainer');
    if (!spec) { container.innerHTML = '<p style="font-size:.82rem;color:#757575;">Selectionnez une specialite.</p>'; return; }
    document.getElementById('slotsLoading').style.display = 'block';
    container.innerHTML = '';
    try {
        const res = await fetch(`${API}/rdv/slots?structure={{ $hosto->uuid }}&per_page=100`);
        const data = await res.json();
        document.getElementById('slotsLoading').style.display = 'none';
        if (!data.data.length) { container.innerHTML = '<p style="font-size:.82rem;color:#757575;">Aucun creneau disponible.</p>'; return; }
        const byDate = {};
        data.data.forEach(s => { if (!byDate[s.date]) byDate[s.date] = []; byDate[s.date].push(s); });
        Object.entries(byDate).forEach(([date, slots]) => {
            const day = document.createElement('div'); day.className = 'slot-day';
            const d = new Date(date);
            day.innerHTML = `<div class="slot-day-label">${d.toLocaleDateString('fr',{weekday:'long',day:'numeric',month:'long'})}</div><div class="slots-grid">${slots.map(s=>`<button class="slot-btn" onclick="selectSlot(this,'${s.uuid}')">${s.start_time.substring(0,5)}${s.is_teleconsultation?' TC':''}</button>`).join('')}</div>`;
            container.appendChild(day);
        });
    } catch(e) { document.getElementById('slotsLoading').style.display = 'none'; }
}
function selectSlot(btn,uuid) {
    document.querySelectorAll('.slot-btn').forEach(b=>b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('selectedSlotUuid').value = uuid;
    document.getElementById('submitBtn').disabled = false;
}
async function submitRdv() {
    const slotUuid = document.getElementById('selectedSlotUuid').value;
    if (!slotUuid) return;
    const body = {
        time_slot_uuid: slotUuid,
        specialty_code: document.getElementById('rdvSpecialty').value,
        reason: document.getElementById('rdvReason').value || null,
        is_for_third_party: document.querySelector('input[name=beneficiary]:checked').value === 'third',
    };
    const msg = document.getElementById('rdvMessage');
    try {
        const res = await fetch('/web/rdv/book', {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(body)});
        if (res.status===401) { window.location.href='/compte/connexion'; return; }
        const data = await res.json();
        if (res.ok) {
            msg.style.display='block';msg.style.background='#E8F5E9';msg.style.color='#2E7D32';
            msg.innerHTML='Rendez-vous pris avec succes ! <a href="/compte/rendez-vous" style="color:#2E7D32;font-weight:600;">Voir mes rendez-vous</a>';
            document.getElementById('submitBtn').disabled=true;
        } else {
            msg.style.display='block';msg.style.background='#FFEBEE';msg.style.color='#C62828';
            msg.textContent=data.error?.message||'Erreur.';
        }
    } catch(e) { msg.style.display='block';msg.style.background='#FFEBEE';msg.style.color='#C62828';msg.textContent='Erreur de connexion.'; }
}
</script>
@endsection
