@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin')
@section('env-color', '#B71C1C')
@section('env-color-dark', '#880E0E')
@section('title', 'Sessions')
@section('page-title', 'Sessions actives : '.$user->name)
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar') @endsection

@section('content')

@if(session('success'))
    <div style="background:#E8F5E9;border:1px solid #C8E6C9;border-radius:8px;padding:12px 16px;margin-bottom:14px;color:#2E7D32;font-size:.85rem;">
        {{ session('success') }}
    </div>
@endif

<div style="margin-bottom:12px;">
    <a href="{{ route('admin.users.show', $user->uuid) }}" style="font-size:.82rem;color:#B71C1C;text-decoration:none;">← Retour au profil</a>
</div>

<div style="background:white;border:1px solid #EEE;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
        <thead><tr style="background:#FAFAFA;border-bottom:1px solid #EEE;">
            <th style="padding:12px 16px;text-align:left;">Nom du device</th>
            <th style="padding:12px 16px;text-align:left;">Créé le</th>
            <th style="padding:12px 16px;text-align:left;">Dernière utilisation</th>
            <th style="padding:12px 16px;"></th>
        </tr></thead>
        <tbody>
        @forelse($tokens as $t)
        <tr style="border-top:1px solid #F5F5F5;">
            <td style="padding:12px 16px;">{{ $t->name }}</td>
            <td style="padding:12px 16px;color:#757575;">{{ $t->created_at->format('d/m/Y H:i') }}</td>
            <td style="padding:12px 16px;color:#757575;">{{ $t->last_used_at?->diffForHumans() ?? '—' }}</td>
            <td style="padding:12px 16px;text-align:right;">
                <form method="POST" action="{{ route('admin.users.sessions.revoke', [$user->uuid, $t->id]) }}" style="display:inline;">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('Révoquer cette session ?')" style="background:none;border:none;color:#C62828;cursor:pointer;font-size:.78rem;">Révoquer</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" style="padding:30px;text-align:center;color:#999;">Aucune session active</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
