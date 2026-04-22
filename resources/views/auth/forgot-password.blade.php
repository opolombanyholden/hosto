@extends('layouts.auth')

@section('color-main', '#388E3C')
@section('color-dark', '#2E7D32')
@section('logo-text', 'HOSTO')
@section('side-title', 'Mot de passe oublie ?')
@section('side-description', 'Pas de panique. Entrez votre adresse email et nous vous enverrons un lien pour reinitialiser votre mot de passe.')

@section('form')
<h1>Reinitialiser le mot de passe</h1>
<p class="subtitle">Entrez l'adresse email associee a votre compte</p>

@if(config('app.debug') && session('dev_reset_url'))
<div style="background:#FFF8E1;border:2px dashed #FFB300;border-radius:10px;padding:14px;margin-bottom:16px;">
    <div style="font-size:.75rem;font-weight:700;color:#F57F17;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Mode developpement</div>
    <div style="font-size:.82rem;color:#424242;margin-bottom:6px;">Lien de reinitialisation :</div>
    <a href="{{ session('dev_reset_url') }}" style="font-size:.78rem;color:#1565C0;word-break:break-all;">{{ session('dev_reset_url') }}</a>
    <div style="font-size:.68rem;color:#757575;margin-top:6px;">Ce bandeau n'apparait qu'en mode debug.</div>
</div>
@endif

@if($errors->any())
<div class="auth-alert auth-alert-error">{{ $errors->first() }}</div>
@endif

<form method="POST" action="/mot-de-passe/oublie">
    @csrf
    <div class="field">
        <label for="email">Adresse email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="votre@email.com">
    </div>
    <button type="submit" class="auth-btn">Envoyer le lien de reinitialisation</button>
</form>

<p class="auth-link"><a href="/compte/connexion">Retour a la connexion</a></p>
@endsection
