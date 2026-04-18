@extends('layouts.auth')

@section('color-main', $environment === 'pro' ? '#1565C0' : ($environment === 'admin' ? '#B71C1C' : '#388E3C'))
@section('color-dark', $environment === 'pro' ? '#0D47A1' : ($environment === 'admin' ? '#880E0E' : '#2E7D32'))
@section('robots', 'noindex')
@section('side-title', 'Codes de recuperation')
@section('side-description', 'Conservez ces codes dans un endroit sur. Ils vous permettront de vous connecter si vous perdez l\'acces a votre application d\'authentification. Chaque code ne peut etre utilise qu\'une seule fois.')

@section('form')
<h1>Codes de recuperation</h1>
<p class="subtitle">Sauvegardez ces codes maintenant. Ils ne seront plus affiches.</p>

<div style="background:#FFF3E0;border:2px solid #FF9800;border-radius:10px;padding:16px;margin-bottom:24px;">
    <div style="font-size:.82rem;color:#E65100;font-weight:600;margin-bottom:8px;">Important</div>
    <div style="font-size:.78rem;color:#BF360C;">Ces codes sont a usage unique. Notez-les dans un endroit securise (gestionnaire de mots de passe, coffre-fort, papier).</div>
</div>

<div style="background:#F5F5F5;border-radius:10px;padding:20px;margin-bottom:24px;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        @foreach($codes as $code)
            <code style="font-size:.9rem;font-weight:600;padding:8px 12px;background:white;border-radius:6px;text-align:center;border:1px solid #EEE;">{{ $code }}</code>
        @endforeach
    </div>
</div>

<a href="/{{ $environment }}" class="auth-btn" style="display:block;text-align:center;text-decoration:none;">
    J'ai sauvegarde mes codes — Continuer
</a>
@endsection
