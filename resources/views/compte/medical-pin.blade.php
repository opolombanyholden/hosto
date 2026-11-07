@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'PIN médical')
@section('page-title', 'Accès à vos données médicales')
@section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'dossier']) @endsection

@section('content')
<div style="max-width:420px;margin:40px auto;background:white;border:1px solid #EEE;border-radius:16px;padding:28px;text-align:center;">
    <h1 style="font-size:1.1rem;font-weight:700;color:#1B2A1B;margin:0 0 6px;">Saisissez votre PIN médical</h1>
    <p style="font-size:.82rem;color:#757575;margin:0 0 20px;">Il protège vos données médicales. Il reste valable 15 minutes.</p>
    @if($errors->any())
    <div style="background:#FFEBEE;color:#C62828;padding:10px;border-radius:8px;font-size:.82rem;margin-bottom:14px;">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="/compte/pin-medical">
        @csrf
        <input type="password" name="pin" maxlength="6" inputmode="numeric" pattern="[0-9]*" autocomplete="off" required autofocus aria-label="PIN médical" style="width:160px;padding:12px;border:2px solid #EEE;border-radius:10px;font-size:1.2rem;text-align:center;letter-spacing:.3em;">
        <br>
        <button type="submit" style="margin-top:16px;padding:11px 30px;background:#388E3C;color:white;border:none;border-radius:10px;font-family:Poppins,sans-serif;font-size:.85rem;font-weight:600;cursor:pointer;">Continuer</button>
    </form>
</div>
@endsection
