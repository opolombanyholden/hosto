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

{{-- Social login buttons --}}
<div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;">
    <a href="/auth/google" style="display:flex;align-items:center;justify-content:center;gap:10px;padding:11px 16px;border:2px solid #EEE;border-radius:8px;text-decoration:none;color:#424242;font-family:Poppins,sans-serif;font-size:.85rem;font-weight:500;transition:all .2s;background:white;">
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A10.96 10.96 0 0 0 1 12c0 1.77.42 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
        Continuer avec Google
    </a>
    <a href="/auth/facebook" style="display:flex;align-items:center;justify-content:center;gap:10px;padding:11px 16px;border:2px solid #EEE;border-radius:8px;text-decoration:none;color:#424242;font-family:Poppins,sans-serif;font-size:.85rem;font-weight:500;transition:all .2s;background:white;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        Continuer avec Facebook
    </a>
    <a href="/auth/yahoo" style="display:flex;align-items:center;justify-content:center;gap:10px;padding:11px 16px;border:2px solid #EEE;border-radius:8px;text-decoration:none;color:#424242;font-family:Poppins,sans-serif;font-size:.85rem;font-weight:500;transition:all .2s;background:white;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#6001D2"><path d="M14.524 6.162l-3.453 7.834-3.554-7.834H3.51l5.63 11.186L7.682 21h3.907l1.27-3.156L18.49 6.162h-3.966z"/></svg>
        Continuer avec Yahoo
    </a>
</div>

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <div style="flex:1;height:1px;background:#EEE;"></div>
    <span style="font-size:.78rem;color:#757575;font-weight:500;">ou par email</span>
    <div style="flex:1;height:1px;background:#EEE;"></div>
</div>

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
