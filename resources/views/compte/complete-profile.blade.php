@extends('layouts.app')

@section('title', 'Completer mon profil — HOSTO')
@section('breadcrumb')
<li><span class="sep">/</span> <a href="/compte">Mon espace</a></li>
<li><span class="sep">/</span> <span class="current">Completer mon profil</span></li>
@endsection

@section('styles')
<style>
    .profile-container { margin:0 4%; padding:32px 0 60px; }
    .sections-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; align-items:start; }

    /* Header card */
    .profile-header { background:white; border:1px solid #EEE; border-radius:16px; padding:24px; margin-bottom:24px; display:flex; align-items:center; gap:20px; flex-wrap:wrap; }
    .profile-header-avatar { width:64px; height:64px; border-radius:50%; background:#E8F5E9; display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden; border:3px solid #C8E6C9; }
    .profile-header-avatar img { width:100%; height:100%; object-fit:cover; }
    .profile-header-info { flex:1; min-width:200px; }
    .profile-header-info h1 { font-size:1.15rem; font-weight:700; color:#1B2A1B; margin-bottom:2px; }
    .profile-header-info p { font-size:.82rem; color:#757575; }
    .progress-circle { position:relative; width:56px; height:56px; flex-shrink:0; }
    .progress-circle svg { transform:rotate(-90deg); }
    .progress-circle-text { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:700; color:#388E3C; }

    /* Progress bar (mobile) */
    .progress-bar-mobile { display:none; margin-top:12px; }
    .progress-bar-wrap { background:#EEE; border-radius:100px; height:6px; overflow:hidden; }
    .progress-bar-fill { height:100%; background:linear-gradient(90deg,#66BB6A,#388E3C); border-radius:100px; transition:width .5s; }

    /* Sections */
    .section-card { background:white; border:1px solid #EEE; border-radius:14px; overflow:hidden; transition:border-color .2s; }
    .section-card.active { border-color:#C8E6C9; }
    .section-header { padding:16px 20px; cursor:pointer; display:flex; align-items:center; gap:12px; transition:background .2s; user-select:none; }
    .section-header:hover { background:#FAFAFA; }
    .section-icon { width:32px; height:32px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .section-icon.done { background:#E8F5E9; }
    .section-icon.done svg { stroke:#388E3C; }
    .section-icon.todo { background:#F5F5F5; }
    .section-icon.todo svg { stroke:#BDBDBD; }
    .section-label { flex:1; }
    .section-label-title { font-size:.88rem; font-weight:600; color:#1B2A1B; }
    .section-label-sub { font-size:.72rem; color:#757575; margin-top:1px; }
    .section-status { display:flex; align-items:center; gap:6px; }
    .section-chevron { width:20px; height:20px; color:#BDBDBD; transition:transform .2s; }
    .section-card.active .section-chevron { transform:rotate(180deg); color:#388E3C; }
    .verify-badge { display:inline-flex; align-items:center; gap:3px; padding:3px 10px; border-radius:100px; font-size:.68rem; font-weight:600; }
    .verify-ok { background:#E8F5E9; color:#2E7D32; }
    .verify-pending { background:#FFF3E0; color:#E65100; }
    .section-body { padding:0 20px 20px; display:none; }
    .section-card.active .section-body { display:block; }

    /* Fields */
    .field { margin-bottom:14px; }
    .field label { display:block; font-size:.8rem; font-weight:500; color:#424242; margin-bottom:5px; }
    .field input, .field select, .field textarea { width:100%; padding:10px 14px; border:2px solid #EEE; border-radius:8px; font-family:Poppins,sans-serif; font-size:.85rem; outline:none; box-sizing:border-box; }
    .field input:focus, .field select:focus, .field textarea:focus { border-color:#388E3C; }
    .field-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .field-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }

    .save-btn { padding:10px 24px; background:#388E3C; color:white; border:none; border-radius:8px; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:600; cursor:pointer; transition:background .2s; }
    .save-btn:hover { background:#2E7D32; }

    .msg { padding:10px 14px; border-radius:8px; font-size:.82rem; margin-bottom:12px; display:none; }
    .msg-ok { background:#E8F5E9; color:#2E7D32; }
    .msg-err { background:#FFEBEE; color:#C62828; }

    .contact-block { background:#F5F5F5; border-radius:10px; padding:14px; margin-bottom:10px; position:relative; }
    .remove-contact { position:absolute; top:10px; right:10px; background:none; border:none; cursor:pointer; color:#E53935; font-size:.82rem; font-weight:600; }

    .photo-preview { width:80px; height:80px; border-radius:50%; object-fit:cover; border:3px solid #E8F5E9; background:#F5F5F5; }
    .photo-upload-zone { display:flex; align-items:center; gap:16px; }

    .pin-input { letter-spacing:12px; text-align:center; font-size:1.4rem; font-weight:700; max-width:200px; }

    @media(max-width:768px) {
        .profile-container { margin:0 3%; padding:20px 0 40px; }
        .sections-grid { grid-template-columns:1fr; }
        .field-row, .field-row-3 { grid-template-columns:1fr; }
        .progress-circle { display:none; }
        .progress-bar-mobile { display:block; }
        .profile-header { flex-direction:column; text-align:center; }
    }
</style>
@endsection

@section('content')
@php
    $pct = $completionPercent;
    $circumference = 2 * 3.14159 * 22;
    $dashOffset = $circumference - ($pct / 100) * $circumference;

    $sections = [
        ['id' => 'sec1', 'done' => $user->email_verified_at && $user->phone_verified_at, 'icon' => '<path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/>', 'title' => 'Verification', 'sub' => 'Email et telephone'],
        ['id' => 'sec2', 'done' => $user->nip || $user->id_document_number, 'icon' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h4M7 12h10M7 16h6"/>', 'title' => 'Identification', 'sub' => 'NIP, piece d\'identite, date naissance'],
        ['id' => 'sec3', 'done' => (bool) $user->country_of_residence, 'icon' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>', 'title' => 'Residence', 'sub' => 'Pays, ville, adresse'],
        ['id' => 'sec4', 'done' => (bool) $user->profile_photo_path, 'icon' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>', 'title' => 'Photo de profil', 'sub' => 'JPG, PNG ou WebP'],
        ['id' => 'sec5', 'done' => (bool) $user->security_question, 'icon' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>', 'title' => 'Question secrete', 'sub' => 'Securite du compte'],
        ['id' => 'sec6', 'done' => (bool) $user->medical_pin, 'icon' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>', 'title' => 'PIN dossier medical', 'sub' => 'Code secret 4-6 chiffres'],
        ['id' => 'sec7', 'done' => $user->emergencyContacts->isNotEmpty(), 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>', 'title' => 'Contacts d\'urgence', 'sub' => 'Personnes a prevenir'],
    ];
    $firstIncomplete = collect($sections)->first(fn($s) => !$s['done']);
@endphp

<div class="profile-container">
    {{-- Header --}}
    <div class="profile-header">
        <div class="profile-header-avatar">
            @if($user->profile_photo_path)
                <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="{{ $user->name }}">
            @else
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#388E3C" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            @endif
        </div>
        <div class="profile-header-info">
            <h1>{{ $user->name }}</h1>
            <p>Completez votre profil pour profiter de tous les services HOSTO.</p>
            <div class="progress-bar-mobile">
                <div class="progress-bar-wrap"><div class="progress-bar-fill" id="progressBarM" style="width:{{ $pct }}%"></div></div>
                <div style="font-size:.72rem;color:#757575;margin-top:4px;"><span id="progressPctM">{{ $pct }}</span>% complete</div>
            </div>
        </div>
        <div class="progress-circle">
            <svg width="56" height="56" viewBox="0 0 50 50">
                <circle cx="25" cy="25" r="22" fill="none" stroke="#EEE" stroke-width="4"/>
                <circle cx="25" cy="25" r="22" fill="none" stroke="#388E3C" stroke-width="4" stroke-linecap="round" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $dashOffset }}" id="progressCircle"/>
            </svg>
            <div class="progress-circle-text"><span id="progressPct">{{ $pct }}</span>%</div>
        </div>
    </div>

    <div class="sections-grid">
    {{-- ====== Section 1 : Verification ====== --}}
    <div class="section-card {{ $firstIncomplete && $firstIncomplete['id'] === 'sec1' ? 'active' : '' }}" data-section="sec1">
        <div class="section-header" onclick="toggleSection(this)">
            <div class="section-icon {{ $sections[0]['done'] ? 'done' : 'todo' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke-width="2">{!! $sections[0]['icon'] !!}</svg>
            </div>
            <div class="section-label">
                <div class="section-label-title">Verification email et telephone</div>
                <div class="section-label-sub">Obligatoire pour RDV, teleconsultation, achats</div>
            </div>
            <div class="section-status">
                @if($user->email_verified_at)<span class="verify-badge verify-ok">Email &#10003;</span>@else<span class="verify-badge verify-pending">Email</span>@endif
                @if($user->phone_verified_at)<span class="verify-badge verify-ok">Tel &#10003;</span>@else<span class="verify-badge verify-pending">Tel</span>@endif
            </div>
            <svg class="section-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="section-body" id="sec1">
            <p style="font-size:.82rem;color:#757575;margin-bottom:14px;">
                Rendez-vous sur la <a href="/verification" style="color:#388E3C;font-weight:600;">page de verification</a> pour confirmer votre email et votre telephone.
            </p>
            <a href="/verification" class="save-btn" style="display:inline-block;text-decoration:none;">Verifier mon compte</a>
        </div>
    </div>

    {{-- ====== Section 2 : Identite ====== --}}
    <div class="section-card {{ $firstIncomplete && $firstIncomplete['id'] === 'sec2' ? 'active' : '' }}" data-section="sec2">
        <div class="section-header" onclick="toggleSection(this)">
            <div class="section-icon {{ $sections[1]['done'] ? 'done' : 'todo' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke-width="2">{!! $sections[1]['icon'] !!}</svg>
            </div>
            <div class="section-label">
                <div class="section-label-title">Identification personnelle</div>
                <div class="section-label-sub">NIP, piece d'identite, date de naissance, sexe, groupe sanguin</div>
            </div>
            <svg class="section-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="section-body" id="sec2">
            <div id="msgIdentity" class="msg"></div>
            <div class="field">
                <label>NIP (Numero d'Identite Personnel) <span style="font-weight:400;color:#757575;">— format XX-BBBB-AAAAMMJJ</span></label>
                <input type="text" id="nip" value="{{ $user->nip }}" placeholder="AB-1234-20260423" maxlength="16" oninput="formatNip(this)" style="text-transform:uppercase;letter-spacing:1px;">
            </div>
            <div class="field-row">
                <div class="field">
                    <label>Type de piece d'identite</label>
                    <select id="idDocType">
                        <option value="">— Choisir —</option>
                        @foreach($idDocumentTypes as $dt)
                            <option value="{{ $dt->code }}" {{ $user->id_document_type === $dt->code ? 'selected' : '' }}>{{ $dt->label_fr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Numero de la piece</label>
                    <input type="text" id="idDocNumber" value="{{ $user->id_document_number }}" maxlength="50">
                </div>
            </div>
            <div class="field" style="margin-top:4px;">
                <label>Piece d'identite (facultatif) <span style="font-weight:400;color:#757575;">— JPG, PNG ou PDF, max 5 Mo</span></label>
                <div style="display:flex;align-items:center;gap:12px;">
                    @if($user->id_document_file_path)
                        <span style="font-size:.78rem;color:#2E7D32;display:flex;align-items:center;gap:4px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2E7D32" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                            Document joint
                        </span>
                    @endif
                    <input type="file" id="idDocFile" accept="image/jpeg,image/png,application/pdf" style="display:none;" onchange="uploadIdDocument()">
                    <button type="button" onclick="document.getElementById('idDocFile').click()" style="padding:6px 14px;border:1px solid #E0E0E0;border-radius:6px;background:white;cursor:pointer;font-family:Poppins,sans-serif;font-size:.78rem;color:#424242;">{{ $user->id_document_file_path ? 'Remplacer' : 'Joindre un fichier' }}</button>
                </div>
                <div id="msgIdDoc" class="msg" style="margin-top:6px;"></div>
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
                        @foreach($genders as $g)
                            <option value="{{ $g->code }}" {{ $user->gender === $g->code ? 'selected' : '' }}>{{ $g->label_fr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Groupe sanguin</label>
                    <select id="bloodGroup">
                        <option value="">—</option>
                        @foreach($bloodGroups as $bg)
                            <option value="{{ $bg->code }}" {{ $user->blood_group === $bg->code ? 'selected' : '' }}>{{ $bg->label_fr }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button class="save-btn" onclick="saveIdentity()">Enregistrer</button>
        </div>
    </div>

    {{-- ====== Section 3 : Residence ====== --}}
    <div class="section-card {{ $firstIncomplete && $firstIncomplete['id'] === 'sec3' ? 'active' : '' }}" data-section="sec3">
        <div class="section-header" onclick="toggleSection(this)">
            <div class="section-icon {{ $sections[2]['done'] ? 'done' : 'todo' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke-width="2">{!! $sections[2]['icon'] !!}</svg>
            </div>
            <div class="section-label">
                <div class="section-label-title">Pays de residence</div>
                <div class="section-label-sub">Pays, ville, adresse</div>
            </div>
            <svg class="section-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="section-body" id="sec3">
            <div id="msgResidence" class="msg"></div>
            <div class="field-row-3">
                <div class="field">
                    <label>Pays</label>
                    <select id="country">
                        <option value="">— Choisir —</option>
                        @foreach(['GA'=>'Gabon','CM'=>'Cameroun','CG'=>'Congo','CD'=>'RD Congo','GQ'=>'Guinee Equatoriale','TD'=>'Tchad','CF'=>'Centrafrique','SN'=>'Senegal','CI'=>"Cote d'Ivoire",'BJ'=>'Benin','TG'=>'Togo','ML'=>'Mali','BF'=>'Burkina Faso','NE'=>'Niger','MG'=>'Madagascar','FR'=>'France','XX'=>'Autre'] as $code => $name)
                            <option value="{{ $code }}" {{ $user->country_of_residence === $code ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
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

    {{-- ====== Section 4 : Photo ====== --}}
    <div class="section-card {{ $firstIncomplete && $firstIncomplete['id'] === 'sec4' ? 'active' : '' }}" data-section="sec4">
        <div class="section-header" onclick="toggleSection(this)">
            <div class="section-icon {{ $sections[3]['done'] ? 'done' : 'todo' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke-width="2">{!! $sections[3]['icon'] !!}</svg>
            </div>
            <div class="section-label">
                <div class="section-label-title">Photo de profil</div>
                <div class="section-label-sub">JPG, PNG ou WebP — max 2 Mo</div>
            </div>
            <svg class="section-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="section-body" id="sec4">
            <div id="msgPhoto" class="msg"></div>
            <div class="photo-upload-zone">
                <img id="photoPreview" class="photo-preview" src="{{ $user->profile_photo_path ? asset('storage/'.$user->profile_photo_path) : '/images/icons/icon-user.png' }}" alt="Photo">
                <div>
                    <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="uploadPhoto()">
                    <button class="save-btn" onclick="document.getElementById('photoInput').click()">Choisir une photo</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== Section 5 : Question secrete ====== --}}
    <div class="section-card {{ $firstIncomplete && $firstIncomplete['id'] === 'sec5' ? 'active' : '' }}" data-section="sec5">
        <div class="section-header" onclick="toggleSection(this)">
            <div class="section-icon {{ $sections[4]['done'] ? 'done' : 'todo' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke-width="2">{!! $sections[4]['icon'] !!}</svg>
            </div>
            <div class="section-label">
                <div class="section-label-title">Question secrete</div>
                <div class="section-label-sub">Recuperation du compte en cas de perte d'acces</div>
            </div>
            <svg class="section-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="section-body" id="sec5">
            <div id="msgSecurity" class="msg"></div>
            <div class="field">
                <label>Question</label>
                <select id="secQuestion">
                    <option value="">— Choisir une question —</option>
                    @foreach($securityQuestions as $sq)
                        <option value="{{ $sq->label_fr }}" {{ $user->security_question === $sq->label_fr ? 'selected' : '' }}>{{ $sq->label_fr }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Reponse</label>
                <input type="password" id="secAnswer" placeholder="Votre reponse (confidentielle)">
            </div>
            <button class="save-btn" onclick="saveSecurityQuestion()">Enregistrer</button>
        </div>
    </div>

    {{-- ====== Section 6 : PIN ====== --}}
    <div class="section-card {{ $firstIncomplete && $firstIncomplete['id'] === 'sec6' ? 'active' : '' }}" data-section="sec6">
        <div class="section-header" onclick="toggleSection(this)">
            <div class="section-icon {{ $sections[5]['done'] ? 'done' : 'todo' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke-width="2">{!! $sections[5]['icon'] !!}</svg>
            </div>
            <div class="section-label">
                <div class="section-label-title">PIN du dossier medical</div>
                <div class="section-label-sub">Code secret pour proteger vos donnees de sante</div>
            </div>
            <svg class="section-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="section-body" id="sec6">
            <div id="msgPin" class="msg"></div>
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
    <div class="section-card {{ $firstIncomplete && $firstIncomplete['id'] === 'sec7' ? 'active' : '' }}" data-section="sec7">
        <div class="section-header" onclick="toggleSection(this)">
            <div class="section-icon {{ $sections[6]['done'] ? 'done' : 'todo' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke-width="2">{!! $sections[6]['icon'] !!}</svg>
            </div>
            <div class="section-label">
                <div class="section-label-title">Contacts d'urgence</div>
                <div class="section-label-sub">Personnes a prevenir ou autorisees a acceder au dossier</div>
            </div>
            <svg class="section-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="section-body" id="sec7">
            <div id="msgContacts" class="msg"></div>
            <div id="contactsList">
                @forelse($user->emergencyContacts as $ec)
                <div class="contact-block">
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
                                @foreach($contactRelations as $rel)
                                    <option value="{{ $rel->code }}" {{ $ec->relation === $rel->code ? 'selected' : '' }}>{{ $rel->label_fr }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="display:flex;align-items:center;gap:8px;padding-top:20px;">
                            <label style="display:flex;align-items:center;gap:6px;margin:0;cursor:pointer;">
                                <input type="checkbox" class="ec-access" {{ $ec->can_access_medical_record ? 'checked' : '' }}>
                                Acces au dossier medical
                            </label>
                        </div>
                    </div>
                </div>
                @empty
                <div class="contact-block">
                    <button class="remove-contact" onclick="removeContact(this)" type="button">&#10005;</button>
                    <div class="field-row">
                        <div class="field"><label>Nom complet *</label><input type="text" class="ec-name"></div>
                        <div class="field"><label>Telephone *</label><input type="tel" class="ec-phone" placeholder="+241..."></div>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label>Lien</label>
                            <select class="ec-relation">
                                <option value="">—</option>
                                @foreach($contactRelations as $rel)
                                    <option value="{{ $rel->code }}">{{ $rel->label_fr }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="display:flex;align-items:center;gap:8px;padding-top:20px;">
                            <label style="display:flex;align-items:center;gap:6px;margin:0;cursor:pointer;">
                                <input type="checkbox" class="ec-access">
                                Acces au dossier medical
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

    </div>{{-- /sections-grid --}}

    <p style="text-align:center;margin-top:20px;"><a href="/compte" style="font-size:.82rem;color:#388E3C;font-weight:500;">Retour a mon espace</a></p>
</div>
@endsection

@section('scripts')
<script>
function formatNip(input) {
    let v = input.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
    let formatted = '';
    if (v.length > 0) formatted += v.substring(0, 2);
    if (v.length > 2) formatted += '-' + v.substring(2, 6);
    if (v.length > 6) formatted += '-' + v.substring(6, 14);
    input.value = formatted;
}
async function uploadIdDocument() {
    const input = document.getElementById('idDocFile');
    if (!input.files.length) return;
    const fd = new FormData();
    fd.append('id_document_file', input.files[0]);
    try {
        const res = await fetch('/compte/profil/identite/document', {
            method:'POST',
            headers:{'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','X-Requested-With':'XMLHttpRequest'},
            body: fd
        });
        const data = await res.json();
        if (res.ok) { showMsg('msgIdDoc', true, data.data?.message || 'Document enregistre.'); }
        else { showMsg('msgIdDoc', false, data.errors ? Object.values(data.errors).flat().join(' ') : 'Erreur.'); }
    } catch(e) { showMsg('msgIdDoc', false, 'Erreur de connexion.'); }
}
const CSRF = '{{ csrf_token() }}';
const RELATIONS = @json($contactRelations->map(fn($r) => ['code' => $r->code, 'label' => $r->label_fr]));
const headers = {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'};

function toggleSection(header) {
    const card = header.closest('.section-card');
    card.classList.toggle('active');
}

function showMsg(id, ok, text) {
    const el = document.getElementById(id);
    el.style.display = 'block';
    el.className = 'msg ' + (ok ? 'msg-ok' : 'msg-err');
    el.textContent = text;
    if (ok) setTimeout(() => el.style.display = 'none', 4000);
}

function updateProgress(pct) {
    document.getElementById('progressPct').textContent = pct;
    const el = document.getElementById('progressPctM');
    if (el) el.textContent = pct;
    const barM = document.getElementById('progressBarM');
    if (barM) barM.style.width = pct + '%';
    // Update circle
    const circ = document.getElementById('progressCircle');
    if (circ) {
        const circumference = 2 * Math.PI * 22;
        circ.setAttribute('stroke-dashoffset', circumference - (pct / 100) * circumference);
    }
}

async function saveSection(url, body, msgId) {
    try {
        const res = await fetch(url, { method:'PUT', headers, body:JSON.stringify(body) });
        const data = await res.json();
        if (res.ok) {
            showMsg(msgId, true, data.data?.message || 'Enregistre.');
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
    saveSection('/compte/profil/question-secrete', { security_question: q, security_answer: a }, 'msgSecurity');
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
        const res = await fetch('/compte/profil/photo', { method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'}, body: fd });
        const data = await res.json();
        if (res.ok) {
            showMsg('msgPhoto', true, 'Photo mise a jour.');
            document.getElementById('photoPreview').src = URL.createObjectURL(input.files[0]);
        } else {
            showMsg('msgPhoto', false, data.errors ? Object.values(data.errors).flat().join(' ') : 'Erreur.');
        }
    } catch(e) { showMsg('msgPhoto', false, 'Erreur de connexion.'); }
}

function addContact() {
    const list = document.getElementById('contactsList');
    if (list.children.length >= 5) { alert('Maximum 5 contacts.'); return; }
    const relOpts = RELATIONS.map(r => `<option value="${r.code}">${r.label}</option>`).join('');
    list.insertAdjacentHTML('beforeend', `<div class="contact-block">
        <button class="remove-contact" onclick="removeContact(this)" type="button">&#10005;</button>
        <div class="field-row">
            <div class="field"><label>Nom complet *</label><input type="text" class="ec-name"></div>
            <div class="field"><label>Telephone *</label><input type="tel" class="ec-phone" placeholder="+241..."></div>
        </div>
        <div class="field-row">
            <div class="field"><label>Lien</label><select class="ec-relation"><option value="">—</option>${relOpts}</select></div>
            <div class="field" style="display:flex;align-items:center;gap:8px;padding-top:20px;"><label style="display:flex;align-items:center;gap:6px;margin:0;cursor:pointer;"><input type="checkbox" class="ec-access"> Acces au dossier medical</label></div>
        </div>
    </div>`);
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
        contacts.push({ name, phone, relation: b.querySelector('.ec-relation').value || null, can_access_medical_record: b.querySelector('.ec-access').checked });
    });
    if (!valid || !contacts.length) { showMsg('msgContacts', false, 'Remplissez au moins le nom et le telephone de chaque contact.'); return; }
    saveSection('/compte/profil/contacts-urgence', { contacts }, 'msgContacts');
}
</script>
@endsection
