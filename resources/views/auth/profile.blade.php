@extends('layouts.dashboard')

@section('env-name', $environment === 'pro' ? 'HOSTO Pro' : ($environment === 'admin' ? 'HOSTO Admin' : 'HOSTO'))
@section('env-color', $environment === 'pro' ? '#1565C0' : ($environment === 'admin' ? '#B71C1C' : '#388E3C'))
@section('env-color-dark', $environment === 'pro' ? '#0D47A1' : ($environment === 'admin' ? '#880E0E' : '#2E7D32'))
@section('title', 'Mon profil')
@section('page-title', 'Mon profil')
@section('user-role', $user->roles->pluck('name_fr')->join(', '))

@section('sidebar-nav')
<a href="/{{ $environment }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
    Tableau de bord
</a>
<a href="/{{ $environment }}/profil" class="active">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    Mon profil
</a>
@endsection

@section('content')
@if(session('success'))
<div style="background:#E8F5E9;color:#2E7D32;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:.85rem;">{{ session('success') }}</div>
@endif

{{-- Personal info --}}
<div style="background:white;border-radius:14px;padding:28px;border:1px solid #EEE;margin-bottom:24px;">
    <h2 style="font-size:1rem;font-weight:700;color:#1B2A1B;margin-bottom:20px;">Informations personnelles</h2>
    <form method="POST" action="/{{ $environment }}/profil/info">
        @csrf @method('PUT')
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="field"><label for="name">Nom complet</label><input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required></div>
            <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required></div>
            <div class="field"><label for="phone">Telephone</label><input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+241..."></div>
        </div>
        @if($errors->has('name') || $errors->has('email') || $errors->has('phone'))
        <div style="color:#E53935;font-size:.78rem;margin-top:8px;">{{ $errors->first('name') ?: $errors->first('email') ?: $errors->first('phone') }}</div>
        @endif
        <button type="submit" style="margin-top:16px;padding:10px 24px;background:var(--env-main);color:white;border:none;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;font-weight:600;cursor:pointer;">Enregistrer</button>
    </form>
</div>

{{-- Password --}}
<div style="background:white;border-radius:14px;padding:28px;border:1px solid #EEE;margin-bottom:24px;">
    <h2 style="font-size:1rem;font-weight:700;color:#1B2A1B;margin-bottom:20px;">Modifier le mot de passe</h2>
    <form method="POST" action="/{{ $environment }}/profil/password">
        @csrf @method('PUT')
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
            <div class="field"><label for="current_password">Mot de passe actuel</label><input type="password" id="current_password" name="current_password" required></div>
            <div class="field"><label for="password">Nouveau mot de passe</label><input type="password" id="password" name="password" required></div>
            <div class="field"><label for="password_confirmation">Confirmer</label><input type="password" id="password_confirmation" name="password_confirmation" required></div>
        </div>
        @if($errors->has('current_password') || $errors->has('password'))
        <div style="color:#E53935;font-size:.78rem;margin-top:8px;">{{ $errors->first('current_password') ?: $errors->first('password') }}</div>
        @endif
        <button type="submit" style="margin-top:16px;padding:10px 24px;background:var(--env-main);color:white;border:none;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;font-weight:600;cursor:pointer;">Modifier</button>
    </form>
</div>

{{-- 2FA --}}
<div style="background:white;border-radius:14px;padding:28px;border:1px solid #EEE;">
    <h2 style="font-size:1rem;font-weight:700;color:#1B2A1B;margin-bottom:12px;">Verification en 2 etapes (2FA)</h2>
    @if($twoFactorEnabled)
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
            <div style="width:10px;height:10px;background:#4CAF50;border-radius:50%;"></div>
            <span style="font-size:.88rem;color:#2E7D32;font-weight:600;">Active</span>
        </div>
        <p style="font-size:.82rem;color:#757575;margin-bottom:16px;">Votre compte est protege par la verification en 2 etapes via une application d'authentification (Google Authenticator, Authy, etc.).</p>
        <form method="POST" action="{{ route('2fa.disable') }}" style="display:inline;">
            @csrf @method('DELETE')
            <div class="field" style="max-width:300px;"><label for="disable_password">Mot de passe pour confirmer</label><input type="password" id="disable_password" name="password" required></div>
            @if($errors->has('password'))
            <div style="color:#E53935;font-size:.78rem;margin-bottom:8px;">{{ $errors->first('password') }}</div>
            @endif
            <button type="submit" style="padding:10px 24px;background:#E53935;color:white;border:none;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;font-weight:600;cursor:pointer;">Desactiver</button>
        </form>
    @else
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
            <div style="width:10px;height:10px;background:#E53935;border-radius:50%;"></div>
            <span style="font-size:.88rem;color:#E53935;font-weight:600;">Non active</span>
        </div>
        <p style="font-size:.82rem;color:#757575;margin-bottom:16px;">Protegez votre compte avec une couche de securite supplementaire. Vous aurez besoin d'une application comme Google Authenticator.</p>
        <a href="{{ route('2fa.setup') }}" style="display:inline-block;padding:10px 24px;background:var(--env-main);color:white;border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none;">Activer la 2FA</a>
    @endif
</div>

<style>
    .field { margin-bottom: 16px; }
    .field label { display:block; font-size:.82rem; font-weight:500; color:#424242; margin-bottom:6px; }
    .field input { width:100%; padding:10px 14px; border:2px solid #EEE; border-radius:8px; font-family:Poppins,sans-serif; font-size:.85rem; outline:none; }
    .field input:focus { border-color: var(--env-main); }
    @media (max-width: 768px) {
        div[style*="grid-template-columns: 1fr 1fr 1fr"] { grid-template-columns: 1fr !important; }
        div[style*="grid-template-columns: 1fr 1fr"] { grid-template-columns: 1fr !important; }
    }
</style>
@endsection
