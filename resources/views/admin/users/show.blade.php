@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin')
@section('env-color', '#B71C1C')
@section('env-color-dark', '#880E0E')
@section('title', 'Utilisateur')
@section('page-title', $user->name)
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar') @endsection

@section('content')

@if(session('success'))
    <div style="background:#E8F5E9;border:1px solid #C8E6C9;border-radius:8px;padding:12px 16px;margin-bottom:14px;color:#2E7D32;font-size:.85rem;">
        {{ session('success') }}
        @if(session('temp_password'))
            — Mot de passe temporaire : <code style="background:#F5F5F5;padding:2px 6px;border-radius:4px;">{{ session('temp_password') }}</code>
        @endif
    </div>
@endif

@if($errors->any())
    <div style="background:#FFEBEE;border:1px solid #FFCDD2;border-radius:8px;padding:12px 16px;margin-bottom:14px;color:#C62828;font-size:.85rem;">
        @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
@endif

<div style="display:grid;grid-template-columns:2fr 1fr;gap:18px;margin-bottom:18px;">
    <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <h3 style="margin:0;font-size:1rem;">Profil</h3>
            <a href="{{ route('admin.users.edit', $user->uuid) }}" style="font-size:.78rem;color:#B71C1C;font-weight:600;text-decoration:none;">Modifier</a>
        </div>
        <table style="width:100%;font-size:.85rem;">
            <tr><td style="padding:6px 0;color:#757575;width:140px;">UUID</td><td><code style="font-size:.75rem;">{{ $user->uuid }}</code></td></tr>
            <tr><td style="padding:6px 0;color:#757575;">Nom</td><td>{{ $user->name }}</td></tr>
            <tr><td style="padding:6px 0;color:#757575;">Email</td><td>{{ $user->email }}</td></tr>
            <tr><td style="padding:6px 0;color:#757575;">Téléphone</td><td>{{ $user->phone ?? '—' }}</td></tr>
            <tr><td style="padding:6px 0;color:#757575;">NIP</td><td>{{ $user->nip ?? '—' }}</td></tr>
            <tr><td style="padding:6px 0;color:#757575;">Inscrit le</td><td>{{ $user->created_at->format('d/m/Y H:i') }}</td></tr>
            <tr><td style="padding:6px 0;color:#757575;">Statut</td><td>
                @if($user->deleted_at)<span style="color:#C62828;font-weight:600;">Supprimé</span>
                @elseif($user->locked_until && $user->locked_until->isFuture())<span style="color:#E65100;font-weight:600;">Suspendu jusqu'au {{ $user->locked_until->format('d/m/Y') }}</span>
                @else<span style="color:#2E7D32;font-weight:600;">Actif</span>@endif
            </td></tr>
            @if($user->pro_validated_at)
            <tr><td style="padding:6px 0;color:#757575;">Pro validé</td><td style="color:#2E7D32;">{{ $user->pro_validated_at->format('d/m/Y') }}</td></tr>
            @endif
        </table>
    </div>

    <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:18px;">
        <h3 style="margin:0 0 14px;font-size:1rem;">Rôles attachés</h3>
        @forelse($user->roles as $r)
            <div style="padding:8px;background:#FAFAFA;border-radius:6px;margin-bottom:6px;font-size:.82rem;">
                <strong>{{ $r->name_fr }}</strong> <span style="color:#999;font-size:.7rem;">({{ $r->environment }})</span>
            </div>
        @empty
            <p style="color:#999;font-size:.85rem;">Aucun rôle.</p>
        @endforelse
        <div style="margin-top:12px;">
            <a href="{{ route('admin.users.sessions', $user->uuid) }}" style="font-size:.78rem;color:#1565C0;text-decoration:none;">Voir les sessions →</a>
        </div>
    </div>
</div>

<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:18px;">
    <h3 style="margin:0 0 14px;font-size:1rem;">Actions</h3>
    <div style="display:flex;flex-wrap:wrap;gap:10px;">
        @if(!$user->deleted_at && (!$user->locked_until || !$user->locked_until->isFuture()))
        <form method="POST" action="{{ route('admin.users.suspend', $user->uuid) }}" style="display:inline;">
            @csrf
            <input type="hidden" name="reason" value="Suspendu par l'administrateur">
            <button type="submit" style="padding:8px 14px;background:#E65100;color:white;border:none;border-radius:6px;font-size:.82rem;cursor:pointer;">Suspendre</button>
        </form>
        @elseif($user->locked_until && $user->locked_until->isFuture())
        <form method="POST" action="{{ route('admin.users.reactivate', $user->uuid) }}" style="display:inline;">
            @csrf
            <button type="submit" style="padding:8px 14px;background:#2E7D32;color:white;border:none;border-radius:6px;font-size:.82rem;cursor:pointer;">Réactiver</button>
        </form>
        @endif

        <form method="POST" action="{{ route('admin.users.reset_password', $user->uuid) }}" style="display:inline;">
            @csrf
            <button type="submit" onclick="return confirm('Réinitialiser le mot de passe ?')" style="padding:8px 14px;background:#1565C0;color:white;border:none;border-radius:6px;font-size:.82rem;cursor:pointer;">Réinitialiser MDP</button>
        </form>

        <form method="POST" action="{{ route('admin.users.validate_pro', $user->uuid) }}" style="display:inline;">
            @csrf
            <input type="hidden" name="action" value="approve">
            <button type="submit" style="padding:8px 14px;background:#6A1B9A;color:white;border:none;border-radius:6px;font-size:.82rem;cursor:pointer;">Valider profil pro</button>
        </form>

        @if(!$user->deleted_at)
        <form method="POST" action="{{ route('admin.users.destroy', $user->uuid) }}" style="display:inline;">
            @csrf @method('DELETE')
            <button type="submit" onclick="return confirm('Supprimer cet utilisateur ?')" style="padding:8px 14px;background:#C62828;color:white;border:none;border-radius:6px;font-size:.82rem;cursor:pointer;">Supprimer</button>
        </form>
        @else
        <form method="POST" action="{{ route('admin.users.restore', $user->uuid) }}" style="display:inline;">
            @csrf
            <button type="submit" style="padding:8px 14px;background:#558B2F;color:white;border:none;border-radius:6px;font-size:.82rem;cursor:pointer;">Restaurer</button>
        </form>
        @endif
    </div>
</div>
@endsection
