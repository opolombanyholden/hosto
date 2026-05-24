@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin')
@section('env-color', '#B71C1C')
@section('env-color-dark', '#880E0E')
@section('title', 'Éditer utilisateur')
@section('page-title', 'Éditer : '.$user->name)
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar') @endsection

@section('content')
<form method="POST" action="{{ route('admin.users.update', $user->uuid) }}" style="max-width:560px;background:white;border:1px solid #EEE;border-radius:14px;padding:18px;display:flex;flex-direction:column;gap:12px;">
    @csrf @method('PUT')
    @if($errors->any())
        <div style="background:#FFEBEE;border:1px solid #FFCDD2;border-radius:8px;padding:12px;color:#C62828;font-size:.85rem;">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif
    <div>
        <label style="font-size:.78rem;color:#757575;display:block;margin-bottom:4px;">Nom *</label>
        <input type="text" name="name" required value="{{ old('name', $user->name) }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">
    </div>
    <div>
        <label style="font-size:.78rem;color:#757575;display:block;margin-bottom:4px;">Email *</label>
        <input type="email" name="email" required value="{{ old('email', $user->email) }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">
    </div>
    <div>
        <label style="font-size:.78rem;color:#757575;display:block;margin-bottom:4px;">Téléphone</label>
        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">
    </div>
    <div>
        <label style="font-size:.78rem;color:#757575;display:block;margin-bottom:4px;">NIP</label>
        <input type="text" name="nip" value="{{ old('nip', $user->nip) }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">
    </div>
    <div style="display:flex;gap:10px;">
        <button type="submit" style="padding:10px 20px;background:#B71C1C;color:white;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Enregistrer</button>
        <a href="{{ route('admin.users.show', $user->uuid) }}" style="padding:10px 20px;background:#F5F5F5;color:#333;border-radius:6px;font-size:.85rem;text-decoration:none;">Annuler</a>
    </div>
</form>
@endsection
