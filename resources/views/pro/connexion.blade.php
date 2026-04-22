@extends('layouts.auth')

@section('color-main', '#1565C0')
@section('color-dark', '#0D47A1')
@section('logo-text', 'HOSTO Pro')
@section('robots', 'noindex')
@section('side-title', 'Espace professionnel de sante')
@section('side-description', 'Gerez votre activite medicale, vos patients, vos rendez-vous et vos ordonnances dans un espace securise dedie aux professionnels.')

@section('form')
<h1>Connexion professionnelle</h1>
<p class="subtitle">Accedez a votre espace professionnel</p>

@if(session('success'))
<div class="auth-alert" style="background:#E3F2FD;color:#1565C0;border:1px solid #BBDEFB;">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="auth-alert auth-alert-error">{{ $errors->first() }}</div>
@endif

<div class="login-tabs">
    <button type="button" class="login-tab active" id="tabEmail" onclick="switchMode('email')">Email</button>
    <button type="button" class="login-tab" id="tabPhone" onclick="switchMode('phone')">Telephone</button>
</div>

<form method="POST" action="/pro/connexion">
    @csrf
    <input type="hidden" name="login_mode" id="loginMode" value="{{ old('login_mode', 'email') }}">

    <div class="field" id="fieldEmail">
        <label for="email">Adresse email professionnelle</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" autofocus>
    </div>

    <div id="fieldPhone" style="display:none;">
        <div class="field">
            <label>Pays</label>
            <select name="country_code" id="countryCode" style="width:100%;padding:10px 14px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;outline:none;">
                @include('partials.country-codes')
            </select>
        </div>
        <div class="field">
            <label for="phone_number">Numero de telephone</label>
            <input type="tel" id="phone_number" name="phone_number" value="{{ old('phone_number') }}" placeholder="Ex: 077123456" inputmode="tel">
        </div>
    </div>

    <div class="field">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required>
    </div>
    <div class="remember-row" style="display:flex;justify-content:space-between;align-items:center;">
        <div>
            <input type="checkbox" id="remember" name="remember">
            <label for="remember">Se souvenir de moi</label>
        </div>
        <a href="/mot-de-passe/oublie" style="font-size:.78rem;color:#1565C0;font-weight:500;">Mot de passe oublie ?</a>
    </div>
    <button type="submit" class="auth-btn">Se connecter</button>
</form>

<p class="auth-link">Pas encore inscrit ? <a href="/pro/inscription">Creer un compte professionnel</a></p>

<style>
    .login-tabs { display:flex; gap:0; margin-bottom:20px; border:2px solid #EEE; border-radius:8px; overflow:hidden; }
    .login-tab { flex:1; padding:10px; border:none; background:white; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:500; color:#757575; cursor:pointer; transition:all .2s; }
    .login-tab.active { background:#1565C0; color:white; }
    .login-tab:hover:not(.active) { background:#F5F5F5; }
</style>

<script>
function switchMode(mode) {
    document.getElementById('loginMode').value = mode;
    document.getElementById('tabEmail').classList.toggle('active', mode === 'email');
    document.getElementById('tabPhone').classList.toggle('active', mode === 'phone');
    document.getElementById('fieldEmail').style.display = mode === 'email' ? 'block' : 'none';
    document.getElementById('fieldPhone').style.display = mode === 'phone' ? 'block' : 'none';
    if (mode === 'email') { document.getElementById('email').setAttribute('required', ''); document.getElementById('phone_number').removeAttribute('required'); }
    else { document.getElementById('email').removeAttribute('required'); document.getElementById('phone_number').setAttribute('required', ''); }
}
document.addEventListener('DOMContentLoaded', function() { if (document.getElementById('loginMode').value === 'phone') switchMode('phone'); });
</script>
@endsection
