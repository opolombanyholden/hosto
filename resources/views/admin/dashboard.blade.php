@extends('layouts.dashboard')

@section('env-name', 'HOSTO Admin')
@section('env-color', '#B71C1C')
@section('env-color-dark', '#880E0E')
@section('title', 'Administration')
@section('page-title', 'Administration HOSTO')
@section('user-role', 'Administrateur')

@section('sidebar-nav')
@include('admin.partials.sidebar')
@endsection

@section('content')
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:32px;">
    <div style="background:white;border-radius:14px;padding:24px;border:1px solid #EEE;">
        <div style="font-size:.78rem;color:#757575;">Structures</div>
        <div style="font-size:1.5rem;font-weight:700;color:#B71C1C;">{{ \App\Modules\Annuaire\Models\Hosto::count() }}</div>
    </div>
    <div style="background:white;border-radius:14px;padding:24px;border:1px solid #EEE;">
        <div style="font-size:.78rem;color:#757575;">Utilisateurs</div>
        <div style="font-size:1.5rem;font-weight:700;color:#B71C1C;">{{ \App\Models\User::count() }}</div>
    </div>
    <div style="background:white;border-radius:14px;padding:24px;border:1px solid #EEE;">
        <div style="font-size:.78rem;color:#757575;">Roles</div>
        <div style="font-size:1.5rem;font-weight:700;color:#B71C1C;">{{ \App\Modules\Core\Models\Role::count() }}</div>
    </div>
</div>
@endsection
