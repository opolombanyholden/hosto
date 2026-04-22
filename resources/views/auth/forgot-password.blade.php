@extends('layouts.auth')

@section('color-main', '#388E3C')
@section('color-dark', '#2E7D32')
@section('logo-text', 'HOSTO')
@section('side-title', 'Mot de passe oublie ?')
@section('side-description', 'Pas de panique. Entrez votre adresse email et nous vous enverrons un lien pour reinitialiser votre mot de passe.')

@section('form')
<h1>Reinitialiser le mot de passe</h1>
<p class="subtitle">Entrez l'adresse email associee a votre compte</p>

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
