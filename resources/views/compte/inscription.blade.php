@extends('layouts.auth')

@section('color-main', '#388E3C')
@section('color-dark', '#2E7D32')
@section('logo-text', 'HOSTO')
@section('side-title', 'Rejoignez HOSTO')
@section('side-description', 'Creez votre compte patient pour acceder a tous les services : rendez-vous, teleconsultation, dossier medical, ordonnances et bien plus.')

@section('form')
<h1>Inscription</h1>
<p class="subtitle">Creez votre compte patient</p>

@if($errors->any())
<div class="auth-alert auth-alert-error">
    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
</div>
@endif

<form method="POST" action="/compte/inscription">
    @csrf
    <div class="field">
        <label for="name">Nom complet</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
    </div>
    <div class="field">
        <label for="email">Adresse email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
    </div>
    <div class="field">
        <label for="phone">Telephone (optionnel)</label>
        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="+241...">
    </div>
    <div class="field">
        <label for="password">Mot de passe</label>
        <div style="position:relative;">
            <input type="password" id="password" name="password" required oninput="checkPassword(this.value)">
            <button type="button" onclick="togglePassword('password')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#757575;font-size:.82rem;">Afficher</button>
        </div>

        {{-- Strength bar --}}
        <div style="margin-top:8px;">
            <div style="display:flex;gap:4px;margin-bottom:6px;">
                <div id="bar1" style="flex:1;height:4px;border-radius:2px;background:#EEE;transition:background .3s;"></div>
                <div id="bar2" style="flex:1;height:4px;border-radius:2px;background:#EEE;transition:background .3s;"></div>
                <div id="bar3" style="flex:1;height:4px;border-radius:2px;background:#EEE;transition:background .3s;"></div>
                <div id="bar4" style="flex:1;height:4px;border-radius:2px;background:#EEE;transition:background .3s;"></div>
            </div>
            <div id="strengthLabel" style="font-size:.72rem;color:#757575;transition:color .3s;"></div>
        </div>

        {{-- Criteria checklist --}}
        <div style="margin-top:10px;display:flex;flex-direction:column;gap:4px;">
            <div class="pw-check" id="chkLength">
                <span class="pw-icon">&#9675;</span>
                <span>12 caracteres minimum</span>
            </div>
            <div class="pw-check" id="chkUpper">
                <span class="pw-icon">&#9675;</span>
                <span>Une lettre majuscule</span>
            </div>
            <div class="pw-check" id="chkLower">
                <span class="pw-icon">&#9675;</span>
                <span>Une lettre minuscule</span>
            </div>
            <div class="pw-check" id="chkNumber">
                <span class="pw-icon">&#9675;</span>
                <span>Un chiffre</span>
            </div>
            <div class="pw-check" id="chkSpecial">
                <span class="pw-icon">&#9675;</span>
                <span>Un caractere special (@, #, !, ...)</span>
            </div>
        </div>
    </div>

    <div class="field">
        <label for="password_confirmation">Confirmer le mot de passe</label>
        <div style="position:relative;">
            <input type="password" id="password_confirmation" name="password_confirmation" required oninput="checkMatch()">
            <button type="button" onclick="togglePassword('password_confirmation')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#757575;font-size:.82rem;">Afficher</button>
        </div>
        <div id="matchMsg" style="font-size:.72rem;margin-top:4px;display:none;"></div>
    </div>

    <button type="submit" id="submitBtn" class="auth-btn">Creer mon compte</button>
</form>

<p class="auth-link">Deja inscrit ? <a href="/compte/connexion">Se connecter</a></p>

<style>
    .pw-check {
        display: flex; align-items: center; gap: 6px;
        font-size: .72rem; color: #757575; transition: color .2s;
    }
    .pw-check.valid { color: #2E7D32; }
    .pw-check.valid .pw-icon { color: #4CAF50; }
    .pw-icon { font-size: .82rem; transition: color .2s; }
</style>

<script>
function checkPassword(pw) {
    const checks = {
        length: pw.length >= 12,
        upper: /[A-Z]/.test(pw),
        lower: /[a-z]/.test(pw),
        number: /[0-9]/.test(pw),
        special: /[^A-Za-z0-9]/.test(pw),
    };

    // Update checklist
    setCriteria('chkLength', checks.length);
    setCriteria('chkUpper', checks.upper);
    setCriteria('chkLower', checks.lower);
    setCriteria('chkNumber', checks.number);
    setCriteria('chkSpecial', checks.special);

    // Score (0-4)
    const passed = Object.values(checks).filter(Boolean).length;
    const score = !checks.length ? Math.min(passed, 1) : passed;

    // Strength bar colors
    const colors = ['#E53935', '#FF9800', '#FFC107', '#8BC34A', '#4CAF50'];
    const labels = ['Tres faible', 'Faible', 'Moyen', 'Bon', 'Excellent'];
    const bars = [document.getElementById('bar1'), document.getElementById('bar2'), document.getElementById('bar3'), document.getElementById('bar4')];

    bars.forEach((bar, i) => {
        bar.style.background = i < score ? colors[score] : '#EEE';
    });

    const label = document.getElementById('strengthLabel');
    if (pw.length === 0) {
        label.textContent = '';
    } else {
        label.textContent = labels[score];
        label.style.color = colors[score];
    }

    checkMatch();
}

function setCriteria(id, valid) {
    const el = document.getElementById(id);
    el.classList.toggle('valid', valid);
    el.querySelector('.pw-icon').innerHTML = valid ? '&#10003;' : '&#9675;';
}

function checkMatch() {
    const pw = document.getElementById('password').value;
    const confirm = document.getElementById('password_confirmation').value;
    const msg = document.getElementById('matchMsg');

    if (confirm.length === 0) {
        msg.style.display = 'none';
        return;
    }

    msg.style.display = 'block';
    if (pw === confirm) {
        msg.textContent = 'Les mots de passe correspondent';
        msg.style.color = '#2E7D32';
    } else {
        msg.textContent = 'Les mots de passe ne correspondent pas';
        msg.style.color = '#E53935';
    }
}

function togglePassword(fieldId) {
    const input = document.getElementById(fieldId);
    const btn = input.nextElementSibling;
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = 'Masquer';
    } else {
        input.type = 'password';
        btn.textContent = 'Afficher';
    }
}
</script>
@endsection
