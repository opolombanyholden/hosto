@extends('layouts.app')

@section('title', 'Prendre rendez-vous — ' . $hosto->name)
@section('breadcrumb')
<li><span class="sep">/</span> <a href="/annuaire">Annuaire</a></li>
<li><span class="sep">/</span> <a href="/annuaire/{{ $hosto->slug }}">{{ $hosto->name }}</a></li>
<li><span class="sep">/</span> <span class="current">Rendez-vous</span></li>
@endsection

@section('styles')
<style>
    .rdv-container { max-width:700px; margin:32px auto; padding:0 24px; }
    .rdv-step { background:white; border:1px solid #EEE; border-radius:14px; padding:24px; margin-bottom:16px; }
    .rdv-step-title { font-size:.85rem; font-weight:600; color:#388E3C; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
    .rdv-step-num { width:24px;height:24px;background:#388E3C;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700; }
    .field { margin-bottom:16px; }
    .field label { display:block; font-size:.82rem; font-weight:500; color:#424242; margin-bottom:6px; }
    .field select, .field input, .field textarea { width:100%; padding:10px 14px; border:2px solid #EEE; border-radius:8px; font-family:Poppins,sans-serif; font-size:.85rem; outline:none; }
    .field select:focus, .field input:focus, .field textarea:focus { border-color:#388E3C; }
    .slots-grid { display:flex; gap:8px; flex-wrap:wrap; }
    .slot-btn { padding:8px 16px; border:2px solid #E8F5E9; border-radius:8px; background:white; cursor:pointer; font-family:Poppins,sans-serif; font-size:.78rem; font-weight:500; color:#388E3C; transition:all .2s; }
    .slot-btn:hover { background:#E8F5E9; border-color:#388E3C; }
    .slot-btn.selected { background:#388E3C; color:white; border-color:#388E3C; }
    .slot-day { margin-bottom:12px; }
    .slot-day-label { font-size:.82rem; font-weight:600; color:#1B2A1B; margin-bottom:6px; }
    .third-party-fields { display:none; margin-top:12px; background:#F5F5F5; border-radius:10px; padding:16px; }
    .submit-btn { width:100%; padding:14px; background:#388E3C; color:white; border:none; border-radius:10px; font-family:Poppins,sans-serif; font-size:.95rem; font-weight:600; cursor:pointer; }
    .submit-btn:hover { background:#2E7D32; }
    .submit-btn:disabled { opacity:.5; cursor:not-allowed; }
    #rdvMessage { display:none; padding:12px; border-radius:10px; margin-bottom:16px; font-size:.85rem; }
    @media(max-width:768px) { .rdv-container { padding:0 16px; } }
</style>
@endsection

@section('content')
<div class="rdv-container">
    <h1 style="font-size:1.3rem;font-weight:700;color:#1B2A1B;margin-bottom:4px;">Prendre rendez-vous</h1>
    <p style="font-size:.85rem;color:#757575;margin-bottom:24px;">{{ $hosto->name }}</p>

    <div id="rdvMessage"></div>

    {{-- Step 1: Specialty --}}
    <div class="rdv-step">
        <div class="rdv-step-title"><span class="rdv-step-num">1</span> Specialite</div>
        <div class="field">
            <select id="rdvSpecialty" onchange="loadSlots()">
                <option value="">Choisissez une specialite</option>
                @foreach($specialties as $spec)
                    <option value="{{ $spec->code }}">{{ $spec->name_fr }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Step 2: Date + Time --}}
    <div class="rdv-step">
        <div class="rdv-step-title"><span class="rdv-step-num">2</span> Date et heure</div>
        <div id="slotsContainer">
            <p style="font-size:.82rem;color:#757575;">Selectionnez d'abord une specialite.</p>
        </div>
        <div id="slotsLoading" style="display:none;font-size:.82rem;color:#757575;">Chargement des creneaux...</div>
    </div>

    {{-- Step 3: Motif --}}
    <div class="rdv-step">
        <div class="rdv-step-title"><span class="rdv-step-num">3</span> Motif de consultation</div>
        <div class="field">
            <textarea id="rdvReason" rows="3" placeholder="Decrivez brievement le motif de votre consultation..." maxlength="500"></textarea>
        </div>
    </div>

    {{-- Step 4: Beneficiary --}}
    <div class="rdv-step">
        <div class="rdv-step-title"><span class="rdv-step-num">4</span> Beneficiaire</div>
        <div class="field">
            <label><input type="radio" name="beneficiary" value="self" checked onchange="toggleThirdParty(false)"> Pour moi-meme</label>
            <label style="margin-left:20px;"><input type="radio" name="beneficiary" value="third" onchange="toggleThirdParty(true)"> Pour un tiers</label>
        </div>
        <div class="third-party-fields" id="thirdPartyFields">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="field"><label>Nom complet *</label><input type="text" id="tpName"></div>
                <div class="field"><label>Age *</label><input type="number" id="tpAge" min="0" max="150"></div>
                <div class="field"><label>Sexe *</label><select id="tpGender"><option value="">—</option><option value="male">Masculin</option><option value="female">Feminin</option></select></div>
                <div class="field"><label>Lien</label><select id="tpRelation"><option value="">—</option><option value="enfant">Enfant</option><option value="parent">Parent</option><option value="conjoint">Conjoint(e)</option><option value="autre">Autre</option></select></div>
                <div class="field"><label>Ville</label><input type="text" id="tpCity"></div>
                <div class="field"><label>Telephone</label><input type="tel" id="tpPhone" placeholder="+241..."></div>
            </div>
            <div class="field"><label>Adresse</label><input type="text" id="tpAddress"></div>
            <div class="field"><label>Notes medicales</label><textarea id="tpNotes" rows="2" placeholder="Allergies, traitements en cours..."></textarea></div>
        </div>
    </div>

    {{-- Submit --}}
    <button class="submit-btn" id="submitBtn" onclick="submitRdv()" disabled>Confirmer le rendez-vous</button>
    <p style="text-align:center;margin-top:12px;"><a href="/annuaire/{{ $hosto->slug }}" style="font-size:.82rem;color:#388E3C;">Retour a la fiche</a></p>
</div>

<input type="hidden" id="selectedSlotUuid" value="">
<input type="hidden" id="hostoUuid" value="{{ $hosto->uuid }}">
@endsection

@section('scripts')
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

        // Group by date.
        const byDate = {};
        data.data.forEach(s => {
            if (!byDate[s.date]) byDate[s.date] = [];
            byDate[s.date].push(s);
        });

        Object.entries(byDate).forEach(([date, slots]) => {
            const day = document.createElement('div');
            day.className = 'slot-day';
            const d = new Date(date);
            day.innerHTML = `<div class="slot-day-label">${d.toLocaleDateString('fr', {weekday:'long', day:'numeric', month:'long'})}</div><div class="slots-grid">${
                slots.map(s => `<button class="slot-btn" onclick="selectSlot(this,'${s.uuid}')">${s.start_time.substring(0,5)}${s.is_teleconsultation ? ' TC' : ''}</button>`).join('')
            }</div>`;
            container.appendChild(day);
        });
    } catch(e) { document.getElementById('slotsLoading').style.display = 'none'; }
}

function selectSlot(btn, uuid) {
    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('selectedSlotUuid').value = uuid;
    document.getElementById('submitBtn').disabled = false;
}

function toggleThirdParty(show) {
    document.getElementById('thirdPartyFields').style.display = show ? 'block' : 'none';
}

async function submitRdv() {
    const slotUuid = document.getElementById('selectedSlotUuid').value;
    if (!slotUuid) return;

    const isThird = document.querySelector('input[name=beneficiary]:checked').value === 'third';
    const body = {
        time_slot_uuid: slotUuid,
        specialty_code: document.getElementById('rdvSpecialty').value,
        reason: document.getElementById('rdvReason').value || null,
        is_for_third_party: isThird,
    };

    if (isThird) {
        body.third_party_name = document.getElementById('tpName').value;
        body.third_party_age = parseInt(document.getElementById('tpAge').value) || null;
        body.third_party_gender = document.getElementById('tpGender').value || null;
        body.third_party_relation = document.getElementById('tpRelation').value || null;
        body.third_party_address = document.getElementById('tpAddress').value || null;
        body.third_party_city = document.getElementById('tpCity').value || null;
        body.third_party_phone = document.getElementById('tpPhone').value || null;
        body.third_party_notes = document.getElementById('tpNotes').value || null;
    }

    const msg = document.getElementById('rdvMessage');
    try {
        const res = await fetch('/web/rdv/book', {
            method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify(body),
        });
        if (res.status === 401) { window.location.href = '/compte/connexion'; return; }
        const data = await res.json();
        if (res.ok) {
            msg.style.display = 'block'; msg.style.background = '#E8F5E9'; msg.style.color = '#2E7D32';
            msg.innerHTML = 'Rendez-vous pris avec succes ! <a href="/compte/rendez-vous" style="color:#2E7D32;font-weight:600;">Voir mes rendez-vous</a>';
            document.getElementById('submitBtn').disabled = true;
        } else {
            msg.style.display = 'block'; msg.style.background = '#FFEBEE'; msg.style.color = '#C62828';
            msg.textContent = data.error?.message || 'Erreur.';
        }
    } catch(e) { msg.style.display='block'; msg.style.background='#FFEBEE'; msg.style.color='#C62828'; msg.textContent='Erreur de connexion.'; }
}
</script>
@endsection
