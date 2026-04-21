@extends('layouts.dashboard')

@section('env-name', 'HOSTO Admin')
@section('env-color', '#B71C1C')
@section('env-color-dark', '#880E0E')
@section('title', 'Utilisateurs')
@section('page-title', 'Gestion des utilisateurs')
@section('user-role', 'Administrateur')

@section('sidebar-nav')
<a href="/admin"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg> Tableau de bord</a>
<a href="/admin/utilisateurs" class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg> Utilisateurs</a>
<a href="/admin/structures"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> Structures</a>
<a href="/admin/demandes"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/></svg> Demandes</a>
<a href="/admin/profil"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Mon profil</a>
@endsection

@section('content')
<div style="font-size:.85rem;color:#757575;margin-bottom:16px;">{{ $users->total() }} utilisateur(s)</div>
<div style="background:white;border:1px solid #EEE;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
        <thead><tr style="background:#FAFAFA;border-bottom:1px solid #EEE;">
            <th style="padding:12px 16px;text-align:left;font-weight:600;color:#424242;">Nom</th>
            <th style="padding:12px 16px;text-align:left;font-weight:600;color:#424242;">Email</th>
            <th style="padding:12px 16px;text-align:left;font-weight:600;color:#424242;">Roles</th>
            <th style="padding:12px 16px;text-align:left;font-weight:600;color:#424242;">Inscrit le</th>
        </tr></thead>
        <tbody>
        @foreach($users as $u)
        <tr style="border-bottom:1px solid #F5F5F5;">
            <td style="padding:12px 16px;font-weight:500;">{{ $u->name }}</td>
            <td style="padding:12px 16px;color:#757575;">{{ $u->email }}</td>
            <td style="padding:12px 16px;">@foreach($u->roles as $r)<span style="padding:2px 8px;background:#F5F5F5;border-radius:100px;font-size:.68rem;margin-right:4px;">{{ $r->name_fr }}</span>@endforeach</td>
            <td style="padding:12px 16px;color:#757575;font-size:.78rem;">{{ $u->created_at->format('d/m/Y') }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div style="margin-top:16px;">{{ $users->links() }}</div>
@endsection
