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
        <label for="password">Mot de passe (12 caracteres min.)</label>
        <input type="password" id="password" name="password" required>
    </div>
    <div class="field">
        <label for="password_confirmation">Confirmer le mot de passe</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required>
    </div>
    <button type="submit" class="auth-btn">Creer mon compte</button>
</form>

<p class="auth-link">Deja inscrit ? <a href="/compte/connexion">Se connecter</a></p>
@endsection
