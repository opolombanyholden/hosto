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

@if($errors->any())
<div class="auth-alert auth-alert-error">
    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
</div>
@endif

@php $user = auth()->user(); @endphp

{{-- ===== Dev mode banner ===== --}}
@if(config('app.debug') && (session('dev_email_otp') || session('dev_phone_otp')))
<div style="background:#FFF8E1;border:2px dashed #FFB300;border-radius:10px;padding:14px;margin-bottom:16px;">
    <div style="font-size:.75rem;font-weight:700;color:#F57F17;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Mode developpement</div>
    @if(session('dev_email_otp'))
    <div style="font-size:.85rem;color:#424242;">Code email : <strong style="font-size:1.1rem;letter-spacing:4px;color:#F57F17;">{{ session('dev_email_otp') }}</strong></div>
    @endif
    @if(session('dev_phone_otp'))
    <div style="font-size:.85rem;color:#424242;">Code telephone : <strong style="font-size:1.1rem;letter-spacing:4px;color:#F57F17;">{{ session('dev_phone_otp') }}</strong></div>
    @endif
    <div style="font-size:.68rem;color:#757575;margin-top:4px;">Ce bandeau n'apparait qu'en mode debug. En production, les codes sont envoyes par email/SMS.</div>
</div>
@endif

{{-- ===== Email verification ===== --}}
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
        {{-- Send OTP --}}
        <form method="POST" action="{{ route('verification.send.email') }}" style="margin-top:12px;">
            @csrf
            <button type="submit" class="auth-btn" style="padding:10px 20px;font-size:.82rem;">Envoyer le code</button>
        </form>

        {{-- Verify OTP --}}
        <form method="POST" action="{{ route('verification.verify.email') }}" style="margin-top:12px;display:flex;gap:8px;align-items:end;">
            @csrf
            <div style="flex:1;">
                <label style="font-size:.75rem;font-weight:500;color:#424242;display:block;margin-bottom:4px;">Code a 6 chiffres</label>
                <input type="text" name="email_otp" maxlength="6" inputmode="numeric" pattern="[0-9]*" placeholder="000000" style="width:100%;padding:10px 14px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:1rem;letter-spacing:6px;text-align:center;outline:none;" required>
            </div>
            <button type="submit" class="auth-btn" style="padding:10px 20px;font-size:.82rem;white-space:nowrap;">Verifier</button>
        </form>
    @endunless
</div>

{{-- ===== Phone verification ===== --}}
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

    @if(!$user->phone_verified_at && $user->phone)
        {{-- Send OTP --}}
        <form method="POST" action="{{ route('verification.send.phone') }}" style="margin-top:12px;">
            @csrf
            <button type="submit" class="auth-btn" style="padding:10px 20px;font-size:.82rem;">Envoyer le code SMS</button>
        </form>

        {{-- Verify OTP --}}
        <form method="POST" action="{{ route('verification.verify.phone') }}" style="margin-top:12px;display:flex;gap:8px;align-items:end;">
            @csrf
            <div style="flex:1;">
                <label style="font-size:.75rem;font-weight:500;color:#424242;display:block;margin-bottom:4px;">Code a 6 chiffres</label>
                <input type="text" name="phone_otp" maxlength="6" inputmode="numeric" pattern="[0-9]*" placeholder="000000" style="width:100%;padding:10px 14px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:1rem;letter-spacing:6px;text-align:center;outline:none;" required>
            </div>
            <button type="submit" class="auth-btn" style="padding:10px 20px;font-size:.82rem;white-space:nowrap;">Verifier</button>
        </form>
    @elseif(!$user->phone)
        <p style="font-size:.78rem;color:#E65100;margin-top:8px;">Ajoutez votre numero dans <a href="{{ route('compte.complete-profile') }}" style="color:#388E3C;font-weight:500;">votre profil</a> d'abord.</p>
    @endif
</div>

@if($user->email_verified_at && $user->phone_verified_at)
    <div style="text-align:center;margin-top:24px;">
        <a href="/compte" class="auth-btn" style="display:inline-block;text-decoration:none;">Acceder a mon espace</a>
    </div>
@endif

<p class="auth-link" style="margin-top:24px;"><a href="/compte">Retour au tableau de bord</a></p>
@endsection
