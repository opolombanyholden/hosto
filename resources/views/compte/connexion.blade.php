@extends('layouts.auth')

@section('color-main', '#388E3C')
@section('color-dark', '#2E7D32')
@section('logo-text', 'HOSTO')
@section('side-title', 'Votre sante, entre vos mains')
@section('side-description', 'Accedez a votre dossier medical, prenez rendez-vous, consultez en ligne et gerez vos ordonnances depuis votre espace personnel.')

@section('form')
<h1>Connexion</h1>
<p class="subtitle">Connectez-vous a votre espace patient</p>

@if($errors->any())
<div class="auth-alert auth-alert-error">{{ $errors->first() }}</div>
@endif

{{-- Login mode tabs --}}
<div class="login-tabs">
    <button type="button" class="login-tab active" id="tabEmail" onclick="switchMode('email')">Email</button>
    <button type="button" class="login-tab" id="tabPhone" onclick="switchMode('phone')">Telephone</button>
</div>

<form method="POST" action="/compte/connexion">
    @csrf
    <input type="hidden" name="login_mode" id="loginMode" value="{{ old('login_mode', 'email') }}">

    {{-- Email field --}}
    <div class="field" id="fieldEmail">
        <label for="email">Adresse email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" autofocus>
    </div>

    {{-- Phone fields --}}
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
        <div style="position:relative;">
            <input type="password" id="password" name="password" required>
            <button type="button" onclick="togglePassword()" id="togglePwBtn" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#757575;font-size:.78rem;font-family:Poppins,sans-serif;">Afficher</button>
        </div>
    </div>
    <div class="remember-row" style="display:flex;justify-content:space-between;align-items:center;">
        <div>
            <input type="checkbox" id="remember" name="remember">
            <label for="remember">Se souvenir de moi</label>
        </div>
        <a href="/mot-de-passe/oublie" style="font-size:.78rem;color:#388E3C;font-weight:500;">Mot de passe oublie ?</a>
    </div>
    <button type="submit" class="auth-btn">Se connecter</button>
</form>

<div style="display:flex;align-items:center;gap:12px;margin:20px 0;">
    <div style="flex:1;height:1px;background:#EEE;"></div>
    <span style="font-size:.78rem;color:#757575;font-weight:500;">ou</span>
    <div style="flex:1;height:1px;background:#EEE;"></div>
</div>

<div style="display:flex;flex-direction:column;gap:10px;">
    <a href="/auth/google" style="display:flex;align-items:center;justify-content:center;gap:10px;padding:11px 16px;border:2px solid #EEE;border-radius:8px;text-decoration:none;color:#424242;font-family:Poppins,sans-serif;font-size:.85rem;font-weight:500;background:white;">
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A10.96 10.96 0 0 0 1 12c0 1.77.42 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
        Google
    </a>
    <a href="/auth/facebook" style="display:flex;align-items:center;justify-content:center;gap:10px;padding:11px 16px;border:2px solid #EEE;border-radius:8px;text-decoration:none;color:#424242;font-family:Poppins,sans-serif;font-size:.85rem;font-weight:500;background:white;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        Facebook
    </a>
    <a href="/auth/yahoo" style="display:flex;align-items:center;justify-content:center;gap:10px;padding:11px 16px;border:2px solid #EEE;border-radius:8px;text-decoration:none;color:#424242;font-family:Poppins,sans-serif;font-size:.85rem;font-weight:500;background:white;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#6001D2"><path d="M14.524 6.162l-3.453 7.834-3.554-7.834H3.51l5.63 11.186L7.682 21h3.907l1.27-3.156L18.49 6.162h-3.966z"/></svg>
        Yahoo
    </a>
</div>

<p class="auth-link">Pas encore de compte ? <a href="/compte/inscription">Creer un compte</a></p>

<style>
    .login-tabs { display:flex; gap:0; margin-bottom:20px; border:2px solid #EEE; border-radius:8px; overflow:hidden; }
    .login-tab { flex:1; padding:10px; border:none; background:white; font-family:Poppins,sans-serif; font-size:.82rem; font-weight:500; color:#757575; cursor:pointer; transition:all .2s; }
    .login-tab.active { background:#388E3C; color:white; }
    .login-tab:hover:not(.active) { background:#F5F5F5; }
</style>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const btn = document.getElementById('togglePwBtn');
    if (input.type === 'password') { input.type = 'text'; btn.textContent = 'Masquer'; }
    else { input.type = 'password'; btn.textContent = 'Afficher'; }
}
function switchMode(mode) {
    document.getElementById('loginMode').value = mode;
    document.getElementById('tabEmail').classList.toggle('active', mode === 'email');
    document.getElementById('tabPhone').classList.toggle('active', mode === 'phone');
    document.getElementById('fieldEmail').style.display = mode === 'email' ? 'block' : 'none';
    document.getElementById('fieldPhone').style.display = mode === 'phone' ? 'block' : 'none';
    if (mode === 'email') {
        document.getElementById('email').setAttribute('required', '');
        document.getElementById('phone_number').removeAttribute('required');
    } else {
        document.getElementById('email').removeAttribute('required');
        document.getElementById('phone_number').setAttribute('required', '');
    }
}
// Restore mode from old input
document.addEventListener('DOMContentLoaded', function() {
    const mode = document.getElementById('loginMode').value;
    if (mode === 'phone') switchMode('phone');
});
</script>
@endsection
