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

@if($errors->any())
<div class="auth-alert auth-alert-error">{{ $errors->first() }}</div>
@endif

<form method="POST" action="/pro/connexion">
    @csrf
    <div class="field">
        <label for="email">Adresse email professionnelle</label>
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

<p class="auth-link">Pas encore inscrit ? <a href="/pro/inscription">Creer un compte professionnel</a></p>
@endsection
