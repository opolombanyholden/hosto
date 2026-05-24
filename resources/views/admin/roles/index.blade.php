@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', 'Rôles')
@section('page-title', 'Rôles &amp; permissions')
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar', ['active' => 'roles']) @endsection

@section('content')
<div style="background:white;border:1px solid #EEE;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
        <thead><tr style="background:#FAFAFA;">
            <th style="padding:12px 16px;text-align:left;">Slug</th>
            <th style="padding:12px 16px;text-align:left;">Nom</th>
            <th style="padding:12px 16px;text-align:left;">Env</th>
            <th style="padding:12px 16px;text-align:left;">Users</th>
            <th style="padding:12px 16px;text-align:left;">Perms</th>
            <th style="padding:12px 16px;"></th>
        </tr></thead>
        <tbody>
        @foreach($roles as $r)
        <tr style="border-top:1px solid #F5F5F5;">
            <td style="padding:12px 16px;"><code>{{ $r->slug }}</code> @if($r->is_system)<span style="background:#FFCDD2;color:#B71C1C;padding:1px 6px;border-radius:6px;font-size:.65rem;">system</span>@endif</td>
            <td style="padding:12px 16px;">{{ $r->name_fr }}</td>
            <td style="padding:12px 16px;">{{ $r->environment }}</td>
            <td style="padding:12px 16px;">{{ $r->users_count }}</td>
            <td style="padding:12px 16px;">{{ $r->permissions_count }}</td>
            <td style="padding:12px 16px;text-align:right;">
                <a href="/admin/roles/{{ $r->slug }}/permissions" style="color:#B71C1C;font-weight:600;text-decoration:none;font-size:.78rem;">Permissions →</a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
