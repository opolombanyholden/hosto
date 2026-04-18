@extends('layouts.auth')

@section('color-main', $environment === 'pro' ? '#1565C0' : ($environment === 'admin' ? '#B71C1C' : '#388E3C'))
@section('color-dark', $environment === 'pro' ? '#0D47A1' : ($environment === 'admin' ? '#880E0E' : '#2E7D32'))
@section('robots', 'noindex')
@section('side-title', 'Verification en 2 etapes')
@section('side-description', 'Votre compte est protege par la verification en deux etapes. Entrez le code de votre application d\'authentification ou un code de recuperation.')

@section('form')
<h1>Verification requise</h1>
<p class="subtitle">Entrez le code a 6 chiffres de votre application d'authentification</p>

@if($errors->any())
<div class="auth-alert auth-alert-error">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('2fa.verify') }}">
    @csrf
    <div class="field">
        <label for="code">Code de verification</label>
        <input type="text" id="code" name="code" maxlength="10" required autofocus
               placeholder="123456 ou ABCD-EFGH"
               style="text-align:center;font-size:1.3rem;letter-spacing:4px;font-weight:600;">
    </div>
    <button type="submit" class="auth-btn">Verifier</button>
</form>

<p class="auth-link" style="margin-top:24px;">
    <small>Vous pouvez aussi utiliser un code de recuperation</small>
</p>
@endsection
