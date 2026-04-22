@extends('layouts.app')

@section('title', 'Completer mon profil — HOSTO')
@section('breadcrumb')
<li><span class="sep">/</span> <a href="/compte">Mon espace</a></li>
<li><span class="sep">/</span> <span class="current">Completer mon profil</span></li>
@endsection

@section('styles')
<style>
    .profile-container { max-width:740px; margin:32px auto; padding:0 24px; }
    .progress-bar-wrap { background:#EEE; border-radius:100px; height:8px; margin-bottom:24px; overflow:hidden; }
    .progress-bar-fill { height:100%; background:linear-gradient(90deg,#66BB6A,#388E3C); border-radius:100px; transition:width .5s; }
    .progress-label { font-size:.78rem; color:#757575; margin-bottom:6px; }

    .section-card { background:white; border:1px solid #EEE; border-radius:14px; margin-bottom:16px; overflow:hidden; }
    .section-header { padding:16px 20px; cursor:pointer; display:flex; align-items:center; justify-content:space-between; }
    .section-header:hover { background:#FAFAFA; }
    .section-title { display:flex; align-items:center; gap:10px; font-size:.88rem; font-weight:600; color:#1B2A1B; }
    .section-num { width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:white; }
    .section-num.done { background:#388E3C; }
    .section-num.todo { background:#BDBDBD; }
    .section-check { font-size:1rem; }
    .section-body { padding:0 20px 20px; display:none; }
    .section-body.open { display:block; }

    .field { margin-bottom:14px; }
    .field label { display:block; font-size:.8rem; font-weight:500; color:#424242; margin-bottom:5px; }
    .field input, .field select, .field textarea { width:100%; padding:10px 14px; border:2px solid #EEE; border-radius:8px; font-family:Poppins,sans-serif; font-size:.85rem; outline:none; box-sizing:border-box; }
    .field input:focus, .field select:focus, .field textarea:focus { border-color:#388E3C; }
    .field-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .field-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }

    .save-btn { padding:10px 24px; background:#388E3C; color:white; border:none; border-radius:8px; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:600; cursor:pointer; }
    .save-btn:hover { background:#2E7D32; }
    .save-btn:disabled { opacity:.5; cursor:not-allowed; }

    .msg { padding:10px 14px; border-radius:8px; font-size:.82rem; margin-bottom:12px; display:none; }
    .msg-ok { background:#E8F5E9; color:#2E7D32; }
    .msg-err { background:#FFEBEE; color:#C62828; }

    .verify-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:100px; font-size:.7rem; font-weight:600; }
    .verify-ok { background:#E8F5E9; color:#2E7D32; }
    .verify-pending { background:#FFF3E0; color:#E65100; }

    .contact-block { background:#F5F5F5; border-radius:10px; padding:14px; margin-bottom:10px; position:relative; }
    .remove-contact { position:absolute; top:10px; right:10px; background:none; border:none; cursor:pointer; color:#E53935; font-size:.82rem; font-weight:600; }

    .photo-preview { width:80px; height:80px; border-radius:50%; object-fit:cover; border:3px solid #E8F5E9; background:#F5F5F5; }
    .photo-upload-zone { display:flex; align-items:center; gap:16px; }

    .pin-input { letter-spacing:12px; text-align:center; font-size:1.4rem; font-weight:700; max-width:200px; }

    @media(max-width:768px) {
        .profile-container { padding:0 16px; }
        .field-row, .field-row-3 { grid-template-columns:1fr; }
    }
</style>
@endsection

@section('content')
<div class="profile-container">
    <h1 style="font-size:1.3rem;font-weight:700;color:#1B2A1B;margin-bottom:4px;">Completer mon profil</h1>
    <p style="font-size:.85rem;color:#757575;margin-bottom:16px;">Remplissez ces informations pour profiter de tous les services HOSTO.</p>

    {{-- Progress bar --}}
    <div class="progress-label"><span id="progressPct">{{ $completionPercent }}</span>% complete</div>
    <div class="progress-bar-wrap"><div class="progress-bar-fill" id="progressBar" style="width:{{ $completionPercent }}%"></div></div>

    {{-- ====== Section 1 : Verification ====== --}}
    <div class="section-card">
        <div class="section-header" onclick="toggleSection('sec1')">
            <div class="section-title">
                <span class="section-num {{ $user->email_verified_at && $user->phone_verified_at ? 'done' : 'todo' }}">1</span>
                Verification email et telephone
            </div>
            <div>
                @if($user->email_verified_at)<span class="verify-badge verify-ok">Email &#10003;</span>@else<span class="verify-badge verify-pending">Email &#9888;</span>@endif
                @if($user->phone_verified_at)<span class="verify-badge verify-ok">Tel &#10003;</span>@else<span class="verify-badge verify-pending">Tel &#9888;</span>@endif
            </div>
        </div>
        <div class="section-body" id="sec1">
            <p style="font-size:.82rem;color:#757575;margin-bottom:12px;">
                La verification de votre telephone est obligatoire pour prendre un rendez-vous, utiliser la teleconsultation ou acheter des medicaments en ligne.
            </p>
            <div class="field-row">
                <div>
                    <div style="font-size:.82rem;font-weight:600;margin-bottom:4px;">Email : {{ $user->email }}</div>
                    @if($user->email_verified_at)
                        <span class="verify-badge verify-ok">Verifie le {{ $user->email_verified_at->format('d/m/Y') }}</span>
                    @else
                        <form method="POST" action="/verification/email" style="display:inline;">@csrf
                            <button type="submit" class="save-btn" style="padding:6px 14px;font-size:.78rem;">Envoyer le code</button>
                        </form>
                    @endif
                </div>
                <div>
                    <div style="font-size:.82rem;font-weight:600;margin-bottom:4px;">Telephone : {{ $user->phone ?: 'Non renseigne' }}</div>
                    @if($user->phone_verified_at)
                        <span class="verify-badge verify-ok">Verifie le {{ $user->phone_verified_at->format('d/m/Y') }}</span>
                    @elseif($user->phone)
                        <button class="save-btn" style="padding:6px 14px;font-size:.78rem;" onclick="alert('Verification SMS bientot disponible.')">Verifier</button>
                    @else
                        <p style="font-size:.78rem;color:#E65100;">Ajoutez votre numero dans votre profil d'abord.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ====== Section 2 : Identite ====== --}}
    <div class="section-card">
        <div class="section-header" onclick="toggleSection('sec2')">
            <div class="section-title">
                <span class="section-num {{ ($user->nip || $user->id_document_number) ? 'done' : 'todo' }}">2</span>
                Identification personnelle
            </div>
            <span class="section-check">{{ ($user->nip || $user->id_document_number) ? '&#10003;' : '' }}</span>
        </div>
        <div class="section-body" id="sec2">
            <div id="msgIdentity" class="msg"></div>
            <div class="field">
                <label>NIP (Numero d'Identite Personnel)</label>
                <input type="text" id="nip" value="{{ $user->nip }}" placeholder="Votre NIP" maxlength="30">
            </div>
            <div class="field-row">
                <div class="field">
                    <label>Type de piece d'identite</label>
                    <select id="idDocType">
                        <option value="">— Choisir —</option>
                        <option value="cni" {{ $user->id_document_type === 'cni' ? 'selected' : '' }}>Carte Nationale d'Identite</option>
                        <option value="passeport" {{ $user->id_document_type === 'passeport' ? 'selected' : '' }}>Passeport</option>
                        <option value="carte_sejour" {{ $user->id_document_type === 'carte_sejour' ? 'selected' : '' }}>Carte de sejour</option>
                        <option value="permis_conduire" {{ $user->id_document_type === 'permis_conduire' ? 'selected' : '' }}>Permis de conduire</option>
                    </select>
                </div>
                <div class="field">
                    <label>Numero de la piece</label>
                    <input type="text" id="idDocNumber" value="{{ $user->id_document_number }}" maxlength="50">
                </div>
            </div>
            <div class="field-row-3">
                <div class="field">
                    <label>Date de naissance</label>
                    <input type="date" id="dob" value="{{ $user->date_of_birth?->format('Y-m-d') }}">
                </div>
                <div class="field">
                    <label>Sexe</label>
                    <select id="gender">
                        <option value="">—</option>
                        <option value="male" {{ $user->gender === 'male' ? 'selected' : '' }}>Masculin</option>
                        <option value="female" {{ $user->gender === 'female' ? 'selected' : '' }}>Feminin</option>
                    </select>
                </div>
                <div class="field">
                    <label>Groupe sanguin</label>
                    <select id="bloodGroup">
                        <option value="">—</option>
                        @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                            <option value="{{ $bg }}" {{ $user->blood_group === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button class="save-btn" onclick="saveIdentity()">Enregistrer</button>
        </div>
    </div>

    {{-- ====== Section 3 : Residence ====== --}}
    <div class="section-card">
        <div class="section-header" onclick="toggleSection('sec3')">
            <div class="section-title">
                <span class="section-num {{ $user->country_of_residence ? 'done' : 'todo' }}">3</span>
                Pays de residence
            </div>
            <span class="section-check">{{ $user->country_of_residence ? '&#10003;' : '' }}</span>
        </div>
        <div class="section-body" id="sec3">
            <div id="msgResidence" class="msg"></div>
            <div class="field-row-3">
                <div class="field">
                    <label>Pays</label>
                    <select id="country">
                        <option value="">— Choisir —</option>
                        <option value="GA" {{ $user->country_of_residence === 'GA' ? 'selected' : '' }}>Gabon</option>
                        <option value="CM" {{ $user->country_of_residence === 'CM' ? 'selected' : '' }}>Cameroun</option>
                        <option value="CG" {{ $user->country_of_residence === 'CG' ? 'selected' : '' }}>Congo</option>
                        <option value="CD" {{ $user->country_of_residence === 'CD' ? 'selected' : '' }}>RD Congo</option>
                        <option value="GQ" {{ $user->country_of_residence === 'GQ' ? 'selected' : '' }}>Guinee Equatoriale</option>
                        <option value="TD" {{ $user->country_of_residence === 'TD' ? 'selected' : '' }}>Tchad</option>
                        <option value="CF" {{ $user->country_of_residence === 'CF' ? 'selected' : '' }}>Centrafrique</option>
                        <option value="SN" {{ $user->country_of_residence === 'SN' ? 'selected' : '' }}>Senegal</option>
                        <option value="CI" {{ $user->country_of_residence === 'CI' ? 'selected' : '' }}>Cote d'Ivoire</option>
                        <option value="BJ" {{ $user->country_of_residence === 'BJ' ? 'selected' : '' }}>Benin</option>
                        <option value="TG" {{ $user->country_of_residence === 'TG' ? 'selected' : '' }}>Togo</option>
                        <option value="ML" {{ $user->country_of_residence === 'ML' ? 'selected' : '' }}>Mali</option>
                        <option value="BF" {{ $user->country_of_residence === 'BF' ? 'selected' : '' }}>Burkina Faso</option>
                        <option value="NE" {{ $user->country_of_residence === 'NE' ? 'selected' : '' }}>Niger</option>
                        <option value="MG" {{ $user->country_of_residence === 'MG' ? 'selected' : '' }}>Madagascar</option>
                        <option value="FR" {{ $user->country_of_residence === 'FR' ? 'selected' : '' }}>France</option>
                        <option value="XX" {{ $user->country_of_residence === 'XX' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>
                <div class="field">
                    <label>Ville</label>
                    <input type="text" id="city" value="{{ $user->city_of_residence }}">
                </div>
                <div class="field">
                    <label>Adresse</label>
                    <input type="text" id="address" value="{{ $user->address_of_residence }}">
                </div>
            </div>
            <button class="save-btn" onclick="saveResidence()">Enregistrer</button>
        </div>
    </div>

    {{-- ====== Section 4 : Photo de profil ====== --}}
    <div class="section-card">
        <div class="section-header" onclick="toggleSection('sec4')">
            <div class="section-title">
                <span class="section-num {{ $user->profile_photo_path ? 'done' : 'todo' }}">4</span>
                Photo de profil
            </div>
            <span class="section-check">{{ $user->profile_photo_path ? '&#10003;' : '' }}</span>
        </div>
        <div class="section-body" id="sec4">
            <div id="msgPhoto" class="msg"></div>
            <div class="photo-upload-zone">
                <img id="photoPreview" class="photo-preview" src="{{ $user->profile_photo_path ? asset('storage/'.$user->profile_photo_path) : '/images/icons/icon-user.png' }}" alt="Photo">
                <div>
                    <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="uploadPhoto()">
                    <button class="save-btn" onclick="document.getElementById('photoInput').click()">Choisir une photo</button>
                    <p style="font-size:.72rem;color:#757575;margin-top:6px;">JPG, PNG ou WebP. Max 2 Mo.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== Section 5 : Question secrete ====== --}}
    <div class="section-card">
        <div class="section-header" onclick="toggleSection('sec5')">
            <div class="section-title">
                <span class="section-num {{ $user->security_question ? 'done' : 'todo' }}">5</span>
                Question secrete
            </div>
            <span class="section-check">{{ $user->security_question ? '&#10003;' : '' }}</span>
        </div>
        <div class="section-body" id="sec5">
            <div id="msgSecurity" class="msg"></div>
            <p style="font-size:.82rem;color:#757575;margin-bottom:12px;">Cette question sera utilisee pour verifier votre identite en cas de perte d'acces a votre compte.</p>
            <div class="field">
                <label>Question</label>
                <select id="secQuestion">
                    <option value="">— Choisir une question —</option>
                    <option value="Quel est le nom de votre premier animal de compagnie ?" {{ $user->security_question === 'Quel est le nom de votre premier animal de compagnie ?' ? 'selected' : '' }}>Quel est le nom de votre premier animal de compagnie ?</option>
                    <option value="Quel est le nom de jeune fille de votre mere ?" {{ $user->security_question === 'Quel est le nom de jeune fille de votre mere ?' ? 'selected' : '' }}>Quel est le nom de jeune fille de votre mere ?</option>
                    <option value="Dans quelle ville etes-vous ne(e) ?" {{ $user->security_question === 'Dans quelle ville etes-vous ne(e) ?' ? 'selected' : '' }}>Dans quelle ville etes-vous ne(e) ?</option>
                    <option value="Quel est le nom de votre meilleur ami d'enfance ?" {{ $user->security_question === 'Quel est le nom de votre meilleur ami d\'enfance ?' ? 'selected' : '' }}>Quel est le nom de votre meilleur ami d'enfance ?</option>
                    <option value="Quel est le nom de votre premiere ecole ?" {{ $user->security_question === 'Quel est le nom de votre premiere ecole ?' ? 'selected' : '' }}>Quel est le nom de votre premiere ecole ?</option>
                    <option value="Quel est votre plat prefere ?" {{ $user->security_question === 'Quel est votre plat prefere ?' ? 'selected' : '' }}>Quel est votre plat prefere ?</option>
                </select>
            </div>
            <div class="field">
                <label>Reponse</label>
                <input type="password" id="secAnswer" placeholder="Votre reponse (confidentielle)">
            </div>
            <button class="save-btn" onclick="saveSecurityQuestion()">Enregistrer</button>
        </div>
    </div>

    {{-- ====== Section 6 : PIN medical ====== --}}
    <div class="section-card">
        <div class="section-header" onclick="toggleSection('sec6')">
            <div class="section-title">
                <span class="section-num {{ $user->medical_pin ? 'done' : 'todo' }}">6</span>
                PIN du dossier medical
            </div>
            <span class="section-check">{{ $user->medical_pin ? '&#10003;' : '' }}</span>
        </div>
        <div class="section-body" id="sec6">
            <div id="msgPin" class="msg"></div>
            <p style="font-size:.82rem;color:#757575;margin-bottom:12px;">
                Ce code secret (4 a 6 chiffres) sera demande chaque fois que vous souhaitez consulter votre dossier medical. Il protege vos donnees de sante meme si quelqu'un accede a votre compte.
            </p>
            @if($user->medical_pin)
            <div class="field">
                <label>PIN actuel</label>
                <input type="password" id="currentPin" class="pin-input" maxlength="6" placeholder="****" inputmode="numeric" pattern="[0-9]*">
            </div>
            @endif
            <div class="field-row">
                <div class="field">
                    <label>{{ $user->medical_pin ? 'Nouveau PIN' : 'PIN' }} (4-6 chiffres)</label>
                    <input type="password" id="newPin" class="pin-input" maxlength="6" placeholder="****" inputmode="numeric" pattern="[0-9]*">
                </div>
                <div class="field">
                    <label>Confirmer le PIN</label>
                    <input type="password" id="confirmPin" class="pin-input" maxlength="6" placeholder="****" inputmode="numeric" pattern="[0-9]*">
                </div>
            </div>
            <button class="save-btn" onclick="saveMedicalPin()">Enregistrer le PIN</button>
        </div>
    </div>

    {{-- ====== Section 7 : Contacts d'urgence ====== --}}
    <div class="section-card">
        <div class="section-header" onclick="toggleSection('sec7')">
            <div class="section-title">
                <span class="section-num {{ $user->emergencyContacts->isNotEmpty() ? 'done' : 'todo' }}">7</span>
                Contacts d'urgence
            </div>
            <span class="section-check">{{ $user->emergencyContacts->isNotEmpty() ? '&#10003;' : '' }}</span>
        </div>
        <div class="section-body" id="sec7">
            <div id="msgContacts" class="msg"></div>
            <p style="font-size:.82rem;color:#757575;margin-bottom:12px;">
                Personnes a contacter en cas d'urgence ou autorisees a consulter votre dossier medical en cas d'incapacite.
            </p>
            <div id="contactsList">
                @forelse($user->emergencyContacts as $ec)
                <div class="contact-block" data-idx="{{ $loop->index }}">
                    <button class="remove-contact" onclick="removeContact(this)" type="button">&#10005;</button>
                    <div class="field-row">
                        <div class="field"><label>Nom complet *</label><input type="text" class="ec-name" value="{{ $ec->name }}"></div>
                        <div class="field"><label>Telephone *</label><input type="tel" class="ec-phone" value="{{ $ec->phone }}" placeholder="+241..."></div>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label>Lien</label>
                            <select class="ec-relation">
                                <option value="">—</option>
                                <option value="enfant" {{ $ec->relation === 'enfant' ? 'selected' : '' }}>Enfant</option>
                                <option value="parent" {{ $ec->relation === 'parent' ? 'selected' : '' }}>Parent</option>
                                <option value="conjoint" {{ $ec->relation === 'conjoint' ? 'selected' : '' }}>Conjoint(e)</option>
                                <option value="frere_soeur" {{ $ec->relation === 'frere_soeur' ? 'selected' : '' }}>Frere / Soeur</option>
                                <option value="ami" {{ $ec->relation === 'ami' ? 'selected' : '' }}>Ami(e)</option>
                                <option value="autre" {{ $ec->relation === 'autre' ? 'selected' : '' }}>Autre</option>
                            </select>
                        </div>
                        <div class="field" style="display:flex;align-items:center;gap:8px;padding-top:20px;">
                            <label style="display:flex;align-items:center;gap:6px;margin:0;cursor:pointer;">
                                <input type="checkbox" class="ec-access" {{ $ec->can_access_medical_record ? 'checked' : '' }}>
                                Peut acceder au dossier medical
                            </label>
                        </div>
                    </div>
                </div>
                @empty
                <div class="contact-block" data-idx="0">
                    <button class="remove-contact" onclick="removeContact(this)" type="button">&#10005;</button>
                    <div class="field-row">
                        <div class="field"><label>Nom complet *</label><input type="text" class="ec-name" value=""></div>
                        <div class="field"><label>Telephone *</label><input type="tel" class="ec-phone" value="" placeholder="+241..."></div>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label>Lien</label>
                            <select class="ec-relation">
                                <option value="">—</option>
                                <option value="enfant">Enfant</option>
                                <option value="parent">Parent</option>
                                <option value="conjoint">Conjoint(e)</option>
                                <option value="frere_soeur">Frere / Soeur</option>
                                <option value="ami">Ami(e)</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div class="field" style="display:flex;align-items:center;gap:8px;padding-top:20px;">
                            <label style="display:flex;align-items:center;gap:6px;margin:0;cursor:pointer;">
                                <input type="checkbox" class="ec-access">
                                Peut acceder au dossier medical
                            </label>
                        </div>
                    </div>
                </div>
                @endforelse
            </div>
            <div style="display:flex;gap:10px;margin-top:8px;">
                <button type="button" onclick="addContact()" style="padding:8px 16px;border:2px dashed #BDBDBD;border-radius:8px;background:white;cursor:pointer;font-family:Poppins,sans-serif;font-size:.78rem;color:#757575;">+ Ajouter un contact</button>
                <button class="save-btn" onclick="saveEmergencyContacts()">Enregistrer</button>
            </div>
        </div>
    </div>

    <p style="text-align:center;margin-top:16px;"><a href="/compte" style="font-size:.82rem;color:#388E3C;font-weight:500;">Retour a mon espace</a></p>
</div>
@endsection

@section('scripts')
<script>
const CSRF = '{{ csrf_token() }}';
const headers = {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'};

function toggleSection(id) {
    const el = document.getElementById(id);
    el.classList.toggle('open');
}

function showMsg(id, ok, text) {
    const el = document.getElementById(id);
    el.style.display = 'block';
    el.className = 'msg ' + (ok ? 'msg-ok' : 'msg-err');
    el.textContent = text;
    if (ok) setTimeout(() => { el.style.display = 'none'; }, 4000);
}

function updateProgress(pct) {
    document.getElementById('progressPct').textContent = pct;
    document.getElementById('progressBar').style.width = pct + '%';
}

async function saveSection(url, body, msgId) {
    try {
        const res = await fetch(url, { method:'PUT', headers, body:JSON.stringify(body) });
        const data = await res.json();
        if (res.ok) {
            showMsg(msgId, true, data.data?.message || 'Enregistre.');
            // Refresh completion
            const r2 = await fetch('/compte/profil/completer', {headers:{'Accept':'text/html'}});
            const html = await r2.text();
            const match = html.match(/id="progressPct">(\d+)/);
            if (match) updateProgress(match[1]);
        } else {
            const errors = data.errors ? Object.values(data.errors).flat().join(' ') : (data.error?.message || 'Erreur.');
            showMsg(msgId, false, errors);
        }
    } catch(e) { showMsg(msgId, false, 'Erreur de connexion.'); }
}

function saveIdentity() {
    saveSection('/compte/profil/identite', {
        nip: document.getElementById('nip').value || null,
        id_document_type: document.getElementById('idDocType').value || null,
        id_document_number: document.getElementById('idDocNumber').value || null,
        date_of_birth: document.getElementById('dob').value || null,
        gender: document.getElementById('gender').value || null,
        blood_group: document.getElementById('bloodGroup').value || null,
    }, 'msgIdentity');
}

function saveResidence() {
    saveSection('/compte/profil/residence', {
        country_of_residence: document.getElementById('country').value || null,
        city_of_residence: document.getElementById('city').value || null,
        address_of_residence: document.getElementById('address').value || null,
    }, 'msgResidence');
}

function saveSecurityQuestion() {
    const q = document.getElementById('secQuestion').value;
    const a = document.getElementById('secAnswer').value;
    if (!q || !a) { showMsg('msgSecurity', false, 'Veuillez remplir la question et la reponse.'); return; }
    saveSection('/compte/profil/question-secrete', {
        security_question: q,
        security_answer: a,
    }, 'msgSecurity');
}

async function saveMedicalPin() {
    const pin = document.getElementById('newPin').value;
    const confirm = document.getElementById('confirmPin').value;
    const currentEl = document.getElementById('currentPin');
    const body = { pin, pin_confirmation: confirm };
    if (currentEl) body.current_pin = currentEl.value;

    try {
        const res = await fetch('/compte/profil/pin-medical', { method:'PUT', headers, body:JSON.stringify(body) });
        const data = await res.json();
        if (res.ok) {
            showMsg('msgPin', true, data.data?.message || 'PIN enregistre.');
            document.getElementById('newPin').value = '';
            document.getElementById('confirmPin').value = '';
            if (currentEl) currentEl.value = '';
        } else {
            const errors = data.errors ? Object.values(data.errors).flat().join(' ') : (data.error?.message || 'Erreur.');
            showMsg('msgPin', false, errors);
        }
    } catch(e) { showMsg('msgPin', false, 'Erreur de connexion.'); }
}

async function uploadPhoto() {
    const input = document.getElementById('photoInput');
    if (!input.files.length) return;
    const fd = new FormData();
    fd.append('photo', input.files[0]);
    try {
        const res = await fetch('/compte/profil/photo', {
            method:'POST',
            headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'},
            body: fd
        });
        const data = await res.json();
        if (res.ok) {
            showMsg('msgPhoto', true, 'Photo mise a jour.');
            document.getElementById('photoPreview').src = URL.createObjectURL(input.files[0]);
        } else {
            const errors = data.errors ? Object.values(data.errors).flat().join(' ') : 'Erreur.';
            showMsg('msgPhoto', false, errors);
        }
    } catch(e) { showMsg('msgPhoto', false, 'Erreur de connexion.'); }
}

// --- Emergency contacts ---
function addContact() {
    const list = document.getElementById('contactsList');
    if (list.children.length >= 5) { alert('Maximum 5 contacts.'); return; }
    const idx = list.children.length;
    const tpl = `<div class="contact-block" data-idx="${idx}">
        <button class="remove-contact" onclick="removeContact(this)" type="button">&#10005;</button>
        <div class="field-row">
            <div class="field"><label>Nom complet *</label><input type="text" class="ec-name" value=""></div>
            <div class="field"><label>Telephone *</label><input type="tel" class="ec-phone" value="" placeholder="+241..."></div>
        </div>
        <div class="field-row">
            <div class="field"><label>Lien</label><select class="ec-relation"><option value="">—</option><option value="enfant">Enfant</option><option value="parent">Parent</option><option value="conjoint">Conjoint(e)</option><option value="frere_soeur">Frere / Soeur</option><option value="ami">Ami(e)</option><option value="autre">Autre</option></select></div>
            <div class="field" style="display:flex;align-items:center;gap:8px;padding-top:20px;"><label style="display:flex;align-items:center;gap:6px;margin:0;cursor:pointer;"><input type="checkbox" class="ec-access"> Peut acceder au dossier medical</label></div>
        </div>
    </div>`;
    list.insertAdjacentHTML('beforeend', tpl);
}

function removeContact(btn) {
    const list = document.getElementById('contactsList');
    if (list.children.length <= 1) { alert('Au moins un contact est requis.'); return; }
    btn.closest('.contact-block').remove();
}

function saveEmergencyContacts() {
    const blocks = document.querySelectorAll('.contact-block');
    const contacts = [];
    let valid = true;
    blocks.forEach(b => {
        const name = b.querySelector('.ec-name').value.trim();
        const phone = b.querySelector('.ec-phone').value.trim();
        if (!name || !phone) { valid = false; return; }
        contacts.push({
            name,
            phone,
            relation: b.querySelector('.ec-relation').value || null,
            can_access_medical_record: b.querySelector('.ec-access').checked,
        });
    });
    if (!valid || !contacts.length) { showMsg('msgContacts', false, 'Remplissez au moins le nom et le telephone de chaque contact.'); return; }
    saveSection('/compte/profil/contacts-urgence', { contacts }, 'msgContacts');
}
</script>
@endsection
