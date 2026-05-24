@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', 'Permissions')
@section('page-title', 'Catalogue des permissions')
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar', ['active' => 'roles']) @endsection

@section('content')
@foreach($permissions as $scope => $perms)
<div style="background:white;border:1px solid #EEE;border-radius:10px;margin-bottom:14px;">
    <div style="padding:10px 16px;background:#FAFAFA;font-weight:600;text-transform:uppercase;font-size:.72rem;color:#757575;letter-spacing:.05em;">{{ $scope }}</div>
    <table style="width:100%;font-size:.82rem;">
        @foreach($perms as $p)
        <tr style="border-top:1px solid #F5F5F5;">
            <td style="padding:8px 16px;font-weight:500;"><code>{{ $p->slug }}</code></td>
            <td style="padding:8px 16px;color:#757575;">{{ $p->name_fr }}</td>
        </tr>
        @endforeach
    </table>
</div>
@endforeach
@endsection
