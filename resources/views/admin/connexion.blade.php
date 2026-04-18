@extends('layouts.auth')

@section('color-main', '#B71C1C')
@section('color-dark', '#880E0E')
@section('logo-text', 'HOSTO Admin')
@section('robots', 'noindex')
@section('side-title', 'Administration HOSTO')
@section('side-description', 'Espace reserve aux administrateurs de la plateforme HOSTO. Acces strictement controle.')

@section('form')
<h1>Connexion administrateur</h1>
<p class="subtitle">Espace reserve — acces controle</p>

@if($errors->any())
<div class="auth-alert auth-alert-error">{{ $errors->first() }}</div>
@endif

<form method="POST" action="/admin/connexion">
    @csrf
    <div class="field">
        <label for="email">Adresse email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
    </div>
    <div class="field">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required>
    </div>
    <button type="submit" class="auth-btn">Se connecter</button>
</form>
@endsection
