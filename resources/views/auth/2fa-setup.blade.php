@extends('layouts.auth')

@section('color-main', $environment === 'pro' ? '#1565C0' : ($environment === 'admin' ? '#B71C1C' : '#388E3C'))
@section('color-dark', $environment === 'pro' ? '#0D47A1' : ($environment === 'admin' ? '#880E0E' : '#2E7D32'))
@section('robots', 'noindex')
@section('side-title', 'Securisez votre compte')
@section('side-description', 'L\'authentification a deux facteurs ajoute une couche de securite supplementaire. Scannez le QR code avec Google Authenticator, Authy ou toute application TOTP compatible.')

@section('form')
<h1>Activer la verification en 2 etapes</h1>
<p class="subtitle">Scannez le QR code avec votre application d'authentification</p>

@if($errors->any())
<div class="auth-alert auth-alert-error">{{ $errors->first() }}</div>
@endif

<div style="text-align:center;margin:24px 0;">
    <div style="background:white;display:inline-block;padding:16px;border-radius:12px;border:2px solid #EEE;">
        {!! \BaconQrCode\Renderer\Image\SvgImageBackEnd::class ? '' : '' !!}
        @php
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            );
            $writer = new \BaconQrCode\Writer($renderer);
            echo $writer->writeString($qrUri);
        @endphp
    </div>
</div>

<div style="background:#F5F5F5;padding:12px 16px;border-radius:8px;margin-bottom:20px;text-align:center;">
    <div style="font-size:.72rem;color:#757575;margin-bottom:4px;">Cle manuelle (si le QR code ne fonctionne pas)</div>
    <code style="font-size:.85rem;font-weight:600;letter-spacing:2px;color:#1B2A1B;">{{ $secret }}</code>
</div>

<form method="POST" action="{{ route('2fa.confirm') }}">
    @csrf
    <div class="field">
        <label for="code">Code a 6 chiffres de votre application</label>
        <input type="text" id="code" name="code" maxlength="6" pattern="[0-9]{6}" required autofocus
               style="text-align:center;font-size:1.5rem;letter-spacing:8px;font-weight:700;">
    </div>
    <button type="submit" class="auth-btn">Activer</button>
</form>
@endsection
