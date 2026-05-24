@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin')
@section('env-color', '#B71C1C')
@section('env-color-dark', '#880E0E')
@section('title', 'Utilisateurs')
@section('page-title', 'Gestion des utilisateurs')
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar') @endsection

@section('content')
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:18px;margin-bottom:18px;">
    <form method="GET" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px;align-items:end;">
        <div>
            <label style="font-size:.72rem;color:#757575;display:block;margin-bottom:4px;">Recherche</label>
            <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Nom, email, NIP, téléphone..." style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">
        </div>
        <div>
            <label style="font-size:.72rem;color:#757575;display:block;margin-bottom:4px;">Rôle</label>
            <select name="role" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">
                <option value="">Tous</option>
                @foreach($roles as $r)
                    <option value="{{ $r->slug }}" @if($roleSlug===$r->slug) selected @endif>{{ $r->name_fr }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-size:.72rem;color:#757575;display:block;margin-bottom:4px;">Environnement</label>
            <select name="env" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">
                <option value="">Tous</option>
                <option value="admin" @if($env==='admin') selected @endif>Admin</option>
                <option value="pro" @if($env==='pro') selected @endif>Pro</option>
                <option value="usager" @if($env==='usager') selected @endif>Usager</option>
            </select>
        </div>
        <div>
            <label style="font-size:.72rem;color:#757575;display:block;margin-bottom:4px;">Statut</label>
            <select name="status" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">
                <option value="active" @if($status==='active') selected @endif>Actifs</option>
                <option value="locked" @if($status==='locked') selected @endif>Suspendus</option>
                <option value="deleted" @if($status==='deleted') selected @endif>Supprimés</option>
            </select>
        </div>
        <button type="submit" style="padding:8px 16px;background:#B71C1C;color:white;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Filtrer</button>
    </form>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div style="font-size:.85rem;color:#757575;">{{ $users->total() }} utilisateur(s)</div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('admin.users.create') }}" style="padding:7px 14px;background:#B71C1C;color:white;border-radius:6px;font-size:.82rem;font-weight:600;text-decoration:none;">+ Nouvel utilisateur</a>
        <a href="{{ route('admin.users.export') }}" style="padding:7px 14px;background:#F5F5F5;color:#333;border-radius:6px;font-size:.82rem;font-weight:600;text-decoration:none;">Export CSV</a>
    </div>
</div>

@if(session('success'))
    <div style="background:#E8F5E9;border:1px solid #C8E6C9;border-radius:8px;padding:12px 16px;margin-bottom:14px;color:#2E7D32;font-size:.85rem;">
        {{ session('success') }}
    </div>
@endif

<div style="background:white;border:1px solid #EEE;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
        <thead><tr style="background:#FAFAFA;border-bottom:1px solid #EEE;">
            <th style="padding:12px 16px;text-align:left;">Nom</th>
            <th style="padding:12px 16px;text-align:left;">Email</th>
            <th style="padding:12px 16px;text-align:left;">Rôles</th>
            <th style="padding:12px 16px;text-align:left;">Inscrit</th>
            <th style="padding:12px 16px;text-align:left;">Statut</th>
            <th style="padding:12px 16px;"></th>
        </tr></thead>
        <tbody>
        @foreach($users as $u)
        <tr style="border-bottom:1px solid #F5F5F5;">
            <td style="padding:12px 16px;font-weight:500;">{{ $u->name }}</td>
            <td style="padding:12px 16px;color:#757575;">{{ $u->email }}</td>
            <td style="padding:12px 16px;">
                @foreach($u->roles as $r)
                    <span style="padding:2px 8px;background:#F5F5F5;border-radius:100px;font-size:.68rem;margin-right:4px;">{{ $r->name_fr }}</span>
                @endforeach
            </td>
            <td style="padding:12px 16px;color:#757575;font-size:.78rem;">{{ $u->created_at->format('d/m/Y') }}</td>
            <td style="padding:12px 16px;">
                @if($u->deleted_at)<span style="color:#C62828;">Supprimé</span>
                @elseif($u->locked_until && $u->locked_until->isFuture())<span style="color:#E65100;">Suspendu</span>
                @else<span style="color:#2E7D32;">Actif</span>@endif
            </td>
            <td style="padding:12px 16px;text-align:right;">
                <a href="{{ route('admin.users.show', $u->uuid) }}" style="color:#B71C1C;font-weight:600;text-decoration:none;font-size:.78rem;">Détail →</a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top:16px;">{{ $users->links() }}</div>
@endsection
