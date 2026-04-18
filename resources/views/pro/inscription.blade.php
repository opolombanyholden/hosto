@extends('layouts.auth')

@section('color-main', '#1565C0')
@section('color-dark', '#0D47A1')
@section('logo-text', 'HOSTO Pro')
@section('robots', 'noindex')
@section('side-title', 'Rejoignez HOSTO Pro')
@section('side-description', 'Inscrivez-vous en tant que professionnel de sante. Votre compte sera valide par nos equipes avant activation.')

@section('form')
<h1>Inscription professionnelle</h1>
<p class="subtitle">Creez votre compte professionnel</p>

@if($errors->any())
<div class="auth-alert auth-alert-error">
    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
</div>
@endif

<form method="POST" action="/pro/inscription">
    @csrf
    <div class="field">
        <label for="name">Nom complet</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
    </div>
    <div class="field">
        <label for="email">Adresse email professionnelle</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
    </div>
    <div class="field">
        <label for="phone">Telephone professionnel</label>
        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="+241..." required>
    </div>
    <div class="field">
        <label for="role">Votre profession</label>
        <select id="role" name="role" required>
            <option value="">Selectionnez votre profession</option>
            @foreach($roles as $role)
                <option value="{{ $role->slug }}" {{ old('role') === $role->slug ? 'selected' : '' }}>{{ $role->name_fr }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="password">Mot de passe (12 caracteres min.)</label>
        <input type="password" id="password" name="password" required>
    </div>
    <div class="field">
        <label for="password_confirmation">Confirmer le mot de passe</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required>
    </div>
    <button type="submit" class="auth-btn">Creer mon compte professionnel</button>
</form>

<p class="auth-link">Deja inscrit ? <a href="/pro/connexion">Se connecter</a></p>
@endsection
