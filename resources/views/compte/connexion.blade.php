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

<form method="POST" action="/compte/connexion">
    @csrf
    <div class="field">
        <label for="email">Adresse email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
    </div>
    <div class="field">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required>
    </div>
    <div class="remember-row">
        <input type="checkbox" id="remember" name="remember">
        <label for="remember">Se souvenir de moi</label>
    </div>
    <button type="submit" class="auth-btn">Se connecter</button>
</form>

<p class="auth-link">Pas encore de compte ? <a href="/compte/inscription">Creer un compte</a></p>
@endsection
