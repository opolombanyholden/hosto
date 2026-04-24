@extends('layouts.app')

@section('title', 'Verification PIN — HOSTO')
@section('breadcrumb')
<li><span class="sep">/</span> <a href="/compte">Mon espace</a></li>
<li><span class="sep">/</span> <span class="current">Verification PIN</span></li>
@endsection

@section('content')
<div style="max-width:440px;margin:60px auto;padding:0 24px;">
    <div style="background:white;border:1px solid #EEE;border-radius:16px;padding:32px;text-align:center;">
        <div style="width:56px;height:56px;background:#E8F5E9;border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#388E3C" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h1 style="font-size:1.2rem;font-weight:700;color:#1B2A1B;margin-bottom:6px;">Saisissez votre PIN</h1>
        <p style="font-size:.82rem;color:#757575;margin-bottom:24px;">Pour acceder a votre profil, veuillez entrer votre code PIN a 4-6 chiffres.</p>

        @if($errors->any())
        <div style="background:#FFEBEE;color:#C62828;padding:10px;border-radius:8px;font-size:.82rem;margin-bottom:16px;">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="/compte/profil/pin-verification">
            @csrf
            <input type="password" name="pin" maxlength="6" inputmode="numeric" pattern="[0-9]*" placeholder="****" required autofocus style="width:160px;padding:14px;border:2px solid #EEE;border-radius:10px;font-family:Poppins,sans-serif;font-size:1.4rem;font-weight:700;letter-spacing:12px;text-align:center;outline:none;margin-bottom:16px;" onfocus="this.style.borderColor='#388E3C'" onblur="this.style.borderColor='#EEE'">
            <br>
            <button type="submit" style="padding:12px 32px;background:#388E3C;color:white;border:none;border-radius:10px;font-family:Poppins,sans-serif;font-size:.88rem;font-weight:600;cursor:pointer;">Valider</button>
        </form>

        <p style="margin-top:20px;"><a href="/compte" style="font-size:.82rem;color:#388E3C;">Retour a mon espace</a></p>
    </div>
</div>
@endsection
