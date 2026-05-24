@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', 'Matrice permissions')
@section('page-title', 'Permissions : '.$role->name_fr)
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar', ['active' => 'roles']) @endsection

@section('content')
@if($role->is_system)
<div style="padding:12px;background:#FFF3E0;color:#E65100;border-radius:8px;margin-bottom:16px;font-size:.85rem;">
    Ce rôle système a toutes les permissions (court-circuit) et ne peut pas être modifié.
</div>
@endif
<form method="POST" action="/admin/roles/{{ $role->slug }}/permissions">
    @csrf @method('PUT')
    @foreach($permissions as $scope => $perms)
    <div style="background:white;border:1px solid #EEE;border-radius:10px;margin-bottom:14px;">
        <div style="padding:10px 16px;background:#FAFAFA;font-weight:600;text-transform:uppercase;font-size:.72rem;color:#757575;letter-spacing:.05em;">{{ $scope }}</div>
        <div style="padding:14px 16px;display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px;">
            @foreach($perms as $p)
            <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;">
                <input type="checkbox" name="permission_ids[]" value="{{ $p->id }}"
                    @if(in_array($p->id, $assigned)) checked @endif
                    @if($role->is_system) disabled @endif>
                <span>{{ $p->name_fr }} <code style="color:#999;font-size:.7rem;">{{ $p->slug }}</code></span>
            </label>
            @endforeach
        </div>
    </div>
    @endforeach
    @if(!$role->is_system)
    <button type="submit" style="padding:10px 22px;background:#B71C1C;color:white;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Enregistrer</button>
    @endif
</form>
@endsection
