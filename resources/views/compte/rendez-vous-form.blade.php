@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Nouveau RDV')
@section('page-title', 'Prendre un rendez-vous')
@section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'rdv']) @endsection

@section('styles')
<style>
    .rdv-form { background:white;border:1px solid #EEE;border-radius:14px;padding:24px;max-width:760px; }
    .rdv-form section { margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #F5F5F5; }
    .rdv-form section:last-child { border-bottom:none; }
    .rdv-form h3 { margin:0 0 10px;font-size:.95rem;color:#1B2A1B; }
    .rdv-form label { display:block;font-size:.82rem;color:#424242;margin-bottom:4px; }
    .rdv-form input[type="text"], .rdv-form input[type="email"], .rdv-form input[type="tel"],
    .rdv-form input[type="number"], .rdv-form input[type="date"], .rdv-form input[type="time"],
    .rdv-form select, .rdv-form textarea {
        width:100%;padding:10px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;
        font-size:.85rem;outline:none;box-sizing:border-box;
    }
    .rdv-form .radio-group label { display:inline-flex;align-items:center;gap:6px;margin-right:14px;cursor:pointer; }
    .rdv-form .field-row { display:grid;grid-template-columns:1fr 1fr;gap:10px; }
    .rdv-form .btn { padding:10px 22px;background:#388E3C;color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer; }
    #leafletMap { height:240px;border-radius:8px;margin-top:8px; }
    .rdv-form .identity-card { background:#F1F8E9;border:1px solid #DCEDC8;border-radius:10px;padding:14px; }
    .rdv-form .identity-card .field-row { margin-bottom:8px; }
    .rdv-form .identity-card .row-label { font-size:.72rem;color:#616161;text-transform:uppercase;letter-spacing:.4px; }
    .rdv-form .identity-card .row-value { font-size:.88rem;color:#1B2A1B;font-weight:500;margin-top:2px; }
    .rdv-form .identity-card .row-missing { font-size:.82rem;color:#B71C1C;font-style:italic;margin-top:2px; }
    .rdv-form .identity-warning { background:#FFF8E1;border:1px solid #FFECB3;color:#5D4037;padding:10px 12px;border-radius:8px;margin-top:10px;font-size:.82rem; }
    .rdv-form .identity-warning a { color:#388E3C;font-weight:600;text-decoration:none;margin-left:4px; }
    .rdv-form .identity-warning a:hover { text-decoration:underline; }
</style>
@endsection

@section('content')
<form id="rdvForm" method="POST" action="/web/rdv/book-form" enctype="multipart/form-data" class="rdv-form">
    @csrf
    <input type="hidden" name="time_slot_id" value="{{ $slot->id ?? '' }}">
    <input type="hidden" name="practitioner_id" value="{{ $practitioner->id ?? '' }}">
    <input type="hidden" name="hosto_id" value="{{ $hosto->id ?? '' }}">

    @if($errors->any())
        <div style="padding:10px;background:#FFEBEE;color:#C62828;border-radius:8px;margin-bottom:14px;">
            {{ $errors->first() }}
        </div>
    @endif

    @php
        $me = auth()->user();
        $genderLabels = ['male' => 'Masculin', 'female' => 'Féminin', 'other' => 'Autre'];
        $missingCritical = collect(['date_of_birth', 'gender', 'phone', 'city_of_residence'])
            ->filter(fn (string $k) => empty($me?->{$k}))
            ->values();
    @endphp

    <section>
        <h3>1. Vos informations</h3>
        <p style="font-size:.78rem;color:#616161;margin:0 0 10px;">Ces informations sont transmises au praticien avec votre demande. Elles proviennent de votre profil.</p>
        <div class="identity-card">
            <div class="field-row">
                <div>
                    <div class="row-label">Nom complet</div>
                    <div class="row-value">{{ $me->name }}</div>
                </div>
                <div>
                    <div class="row-label">Date de naissance</div>
                    @if($me->date_of_birth)
                        <div class="row-value">{{ \Illuminate\Support\Carbon::parse($me->date_of_birth)->format('d/m/Y') }} ({{ \Illuminate\Support\Carbon::parse($me->date_of_birth)->age }} ans)</div>
                    @else
                        <div class="row-missing">Non renseignée</div>
                    @endif
                </div>
            </div>
            <div class="field-row">
                <div>
                    <div class="row-label">Sexe</div>
                    @if($me->gender && isset($genderLabels[$me->gender]))
                        <div class="row-value">{{ $genderLabels[$me->gender] }}</div>
                    @else
                        <div class="row-missing">Non renseigné</div>
                    @endif
                </div>
                <div>
                    <div class="row-label">Téléphone</div>
                    @if($me->phone)
                        <div class="row-value">{{ $me->phone }}</div>
                    @else
                        <div class="row-missing">Non renseigné</div>
                    @endif
                </div>
            </div>
            <div class="field-row">
                <div>
                    <div class="row-label">Email</div>
                    <div class="row-value">{{ $me->email }}</div>
                </div>
                <div>
                    <div class="row-label">Ville de résidence</div>
                    @if($me->city_of_residence)
                        <div class="row-value">{{ $me->city_of_residence }}</div>
                    @else
                        <div class="row-missing">Non renseignée</div>
                    @endif
                </div>
            </div>
            @if($me->nip || $me->id_document_number || $me->blood_group)
            <div class="field-row">
                <div>
                    <div class="row-label">NIP</div>
                    <div class="row-value">{{ $me->nip ?: '—' }}</div>
                </div>
                <div>
                    <div class="row-label">Pièce d'identité</div>
                    @if($me->id_document_number)
                        <div class="row-value">{{ $me->id_document_type ? strtoupper($me->id_document_type).' · ' : '' }}{{ $me->id_document_number }}</div>
                    @else
                        <div class="row-value">—</div>
                    @endif
                </div>
            </div>
            @if($me->blood_group)
            <div class="field-row">
                <div>
                    <div class="row-label">Groupe sanguin</div>
                    <div class="row-value">{{ $me->blood_group }}</div>
                </div>
                <div></div>
            </div>
            @endif
            @endif
        </div>
        @if($missingCritical->isNotEmpty())
            <div class="identity-warning">
                <strong>Informations manquantes :</strong>
                {{ $missingCritical->map(fn ($k) => [
                    'date_of_birth' => 'date de naissance',
                    'gender' => 'sexe',
                    'phone' => 'téléphone',
                    'city_of_residence' => 'ville de résidence',
                ][$k])->join(', ') }}.
                Le praticien pourrait avoir besoin de vous joindre pour les compléter.
                <a href="/compte/mon-dossier">Compléter mon profil →</a>
            </div>
        @endif
    </section>

    <section>
        <h3>2. Type de RDV</h3>
        <div class="radio-group">
            <label><input type="radio" name="appointment_type" value="ordinaire" checked> Ordinaire</label>
            <label><input type="radio" name="appointment_type" value="urgence"> Urgence</label>
            <label><input type="radio" name="appointment_type" value="grossesse"> Grossesse</label>
            <label><input type="radio" name="appointment_type" value="natalite"> Natalité</label>
            <label><input type="radio" name="appointment_type" value="chronique"> Suivi maladie chronique</label>
        </div>
    </section>

    <section>
        <h3>3. Type de consultation</h3>
        <div class="radio-group" id="consultationModeGroup">
            <label><input type="radio" name="consultation_mode" value="in_hospital" checked> À l'hôpital</label>
            @if($practitioner->does_home_care ?? false)
                <label><input type="radio" name="consultation_mode" value="home"> À domicile</label>
            @endif
            @if($practitioner->does_teleconsultation ?? false)
                <label><input type="radio" name="consultation_mode" value="telecon"> Téléconsultation</label>
            @endif
        </div>
    </section>

    <section id="homeBlock" style="display:none;">
        <h3>Adresse de visite</h3>
        <label>Adresse <input type="text" name="visit_address" placeholder="BP 1234, Quartier Glass, Libreville"></label>
        <button type="button" onclick="useGeolocation()" style="margin-top:8px;padding:8px 14px;background:#E3F2FD;color:#1565C0;border:none;border-radius:6px;cursor:pointer;font-size:.82rem;">
            📍 Utiliser ma position actuelle
        </button>
        <input type="hidden" name="visit_lat" id="visitLat">
        <input type="hidden" name="visit_lng" id="visitLng">
        <input type="hidden" name="visit_location_accuracy_m" id="visitAcc">
        <div id="leafletMap"></div>
    </section>

    <section>
        <h3>4. Motif (facultatif)</h3>
        <input type="text" name="reason" maxlength="255" placeholder="Ex : douleur lombaire, suivi annuel...">
    </section>

    <section>
        <h3>5. Pour qui ?</h3>
        <div class="radio-group">
            <label><input type="radio" name="is_for_third_party" value="0" checked onclick="toggleThirdParty(false)"> Pour moi</label>
            <label><input type="radio" name="is_for_third_party" value="1" onclick="toggleThirdParty(true)"> Pour un tiers</label>
        </div>
        <div id="thirdPartyBlock" style="display:none;margin-top:10px;">
            <div class="field-row">
                <label>Nom complet <input type="text" name="third_party_name"></label>
                <label>Téléphone <input type="tel" name="third_party_phone" id="thirdPhone" onblur="lookupThird()"></label>
            </div>
            <div id="thirdLookupResult" style="margin-top:6px;font-size:.78rem;"></div>
            <div class="field-row">
                <label>Âge <input type="number" name="third_party_age" min="0" max="120"></label>
                <label>Sexe
                    <select name="third_party_gender"><option value="">—</option><option value="male">Masculin</option><option value="female">Féminin</option></select>
                </label>
            </div>
            <label>Ville <input type="text" name="third_party_city"></label>
            <label>Lien <input type="text" name="third_party_relation" placeholder="enfant, parent, ami..."></label>
            <label>Notes <textarea name="third_party_notes" rows="2"></textarea></label>
        </div>
    </section>

    <section>
        <h3>6. Documents (max 5, 10 MB chacun, 30 MB total)</h3>
        <input type="file" name="documents[]" multiple accept="application/pdf,image/jpeg,image/png,image/heic">
    </section>

    <section>
        <h3>7. Partager mon dossier médical</h3>
        <label style="display:flex;align-items:center;gap:8px;">
            <input type="checkbox" name="share_medical_record" value="1" id="shareDpe" onchange="togglePin()">
            Je partage mon dossier médical avec ce médecin
        </label>
        <div id="pinBlock" style="display:none;margin-top:10px;">
            <label>PIN médical (4-6 chiffres) <input type="password" name="medical_pin" maxlength="6" pattern="[0-9]{4,6}" inputmode="numeric"></label>
            <p style="font-size:.72rem;color:#757575;margin-top:4px;">Le partage reste actif jusqu'à révocation explicite depuis votre espace.</p>
        </div>
    </section>

    <div style="display:flex;gap:8px;justify-content:flex-end;">
        <a href="javascript:history.back()" style="padding:10px 22px;border:1px solid #EEE;border-radius:8px;text-decoration:none;color:#424242;">Annuler</a>
        <button type="submit" class="btn">Demander ce RDV</button>
    </div>
</form>

<script>
function toggleThirdParty(show) {
    document.getElementById('thirdPartyBlock').style.display = show ? 'block' : 'none';
}

function togglePin() {
    document.getElementById('pinBlock').style.display = document.getElementById('shareDpe').checked ? 'block' : 'none';
}

let leafletMap = null;
function showHomeBlock() {
    const homeBlock = document.getElementById('homeBlock');
    homeBlock.style.display = 'block';
    if (!leafletMap) {
        leafletMap = L.map('leafletMap').setView([0.4162, 9.4673], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM', maxZoom: 19 }).addTo(leafletMap);
    }
}

document.querySelectorAll('input[name="consultation_mode"]').forEach(r => {
    r.addEventListener('change', e => {
        if (e.target.value === 'home') showHomeBlock();
        else document.getElementById('homeBlock').style.display = 'none';
    });
});

function useGeolocation() {
    if (!navigator.geolocation) { alert('Géolocalisation non supportée par votre navigateur'); return; }
    navigator.geolocation.getCurrentPosition(pos => {
        document.getElementById('visitLat').value = pos.coords.latitude;
        document.getElementById('visitLng').value = pos.coords.longitude;
        document.getElementById('visitAcc').value = Math.round(pos.coords.accuracy);
        if (leafletMap) {
            leafletMap.setView([pos.coords.latitude, pos.coords.longitude], 16);
            L.marker([pos.coords.latitude, pos.coords.longitude]).addTo(leafletMap);
        }
    }, err => alert('Géolocalisation refusée ou indisponible'));
}

async function lookupThird() {
    const phone = document.getElementById('thirdPhone').value.trim();
    if (!phone) return;
    try {
        const res = await fetch('/api/v1/rdv/third-party/lookup?phone=' + encodeURIComponent(phone));
        const data = await res.json();
        const out = document.getElementById('thirdLookupResult');
        while (out.firstChild) out.removeChild(out.firstChild);
        if (data.matched) {
            const ok = document.createElement('span');
            ok.style.color = '#2E7D32';
            ok.textContent = '✓ Compte HOSTO trouvé. Le RDV sera lié à son dossier.';
            out.appendChild(ok);
        } else {
            const ko = document.createElement('span');
            ko.style.color = '#E65100';
            ko.textContent = '✗ Aucun compte HOSTO trouvé. Un SMS d\'invitation lui sera envoyé.';
            out.appendChild(ko);
        }
    } catch (e) { /* silent */ }
}
</script>
@endsection
