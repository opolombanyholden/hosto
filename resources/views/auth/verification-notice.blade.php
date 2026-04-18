@extends('layouts.auth')

@section('color-main', '#388E3C')
@section('color-dark', '#2E7D32')
@section('robots', 'noindex')
@section('side-title', 'Verification de votre compte')
@section('side-description', 'Pour acceder a toutes les fonctionnalites de HOSTO, veuillez verifier votre adresse email et votre numero de telephone.')

@section('form')
<h1>Verification requise</h1>

@if(session('warning'))
<div class="auth-alert" style="background:#FFF3E0;color:#E65100;">{{ session('warning') }}</div>
@endif
@if(session('success'))
<div class="auth-alert auth-alert-success">{{ session('success') }}</div>
@endif

@php $user = auth()->user(); @endphp

{{-- Email verification --}}
<div style="background:white;border:2px solid {{ $user->email_verified_at ? '#4CAF50' : '#FF9800' }};border-radius:12px;padding:20px;margin-bottom:16px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
        @if($user->email_verified_at)
            <div style="width:24px;height:24px;background:#4CAF50;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
            </div>
            <span style="font-weight:600;color:#2E7D32;">Email verifie</span>
        @else
            <div style="width:24px;height:24px;background:#FF9800;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <span style="color:white;font-weight:700;font-size:.82rem;">!</span>
            </div>
            <span style="font-weight:600;color:#E65100;">Email non verifie</span>
        @endif
    </div>
    <p style="font-size:.82rem;color:#757575;">{{ $user->email }}</p>
    @unless($user->email_verified_at)
        <form method="POST" action="{{ route('verification.send.email') }}" style="margin-top:12px;">
            @csrf
            <button type="submit" class="auth-btn" style="padding:10px 20px;font-size:.82rem;">Envoyer le code de verification</button>
        </form>
    @endunless
</div>

{{-- Phone verification --}}
<div style="background:white;border:2px solid {{ $user->phone_verified_at ? '#4CAF50' : '#FF9800' }};border-radius:12px;padding:20px;margin-bottom:16px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
        @if($user->phone_verified_at)
            <div style="width:24px;height:24px;background:#4CAF50;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
            </div>
            <span style="font-weight:600;color:#2E7D32;">Telephone verifie</span>
        @else
            <div style="width:24px;height:24px;background:#FF9800;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <span style="color:white;font-weight:700;font-size:.82rem;">!</span>
            </div>
            <span style="font-weight:600;color:#E65100;">Telephone non verifie</span>
        @endif
    </div>
    <p style="font-size:.82rem;color:#757575;">{{ $user->phone ?: 'Non renseigne' }}</p>
    @unless($user->phone_verified_at)
        <p style="font-size:.78rem;color:#757575;margin-top:8px;">La verification par SMS sera disponible prochainement.</p>
    @endunless
</div>

@if($user->email_verified_at && $user->phone_verified_at)
    <div style="text-align:center;margin-top:24px;">
        <a href="/compte" class="auth-btn" style="display:inline-block;text-decoration:none;">Acceder a mon espace</a>
    </div>
@endif

<p class="auth-link" style="margin-top:24px;"><a href="/compte">Retour au tableau de bord</a></p>
@endsection
