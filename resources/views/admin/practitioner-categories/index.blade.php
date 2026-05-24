@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', 'Catégories pro')
@section('page-title', 'Catégories de professionnels')
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar', ['active' => 'pro-cats']) @endsection

@section('content')
<a href="/admin/practitioner-categories/create" style="display:inline-block;margin-bottom:14px;padding:8px 16px;background:#B71C1C;color:white;border-radius:6px;text-decoration:none;font-weight:600;font-size:.82rem;">+ Nouvelle catégorie</a>

<div style="background:white;border:1px solid #EEE;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
        <thead><tr style="background:#FAFAFA;">
            <th style="padding:12px 16px;text-align:left;">Code</th>
            <th style="padding:12px 16px;text-align:left;">Nom</th>
            <th style="padding:12px 16px;text-align:left;">Parent</th>
            <th style="padding:12px 16px;text-align:left;">Couleur</th>
            <th style="padding:12px 16px;text-align:left;">Praticiens</th>
            <th style="padding:12px 16px;"></th>
        </tr></thead>
        <tbody>
        @foreach($categories as $c)
        <tr style="border-top:1px solid #F5F5F5;">
            <td style="padding:12px 16px;"><code>{{ $c->code }}</code></td>
            <td style="padding:12px 16px;">{{ $c->name_fr }}</td>
            <td style="padding:12px 16px;color:#757575;">{{ $c->parent?->name_fr ?? '—' }}</td>
            <td style="padding:12px 16px;">
                @if($c->color_hex)<span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:{{ $c->color_hex }};border:1px solid #DDD;vertical-align:middle;"></span> {{ $c->color_hex }}@else—@endif
            </td>
            <td style="padding:12px 16px;">{{ $c->practitioners_count }}</td>
            <td style="padding:12px 16px;text-align:right;">
                <a href="/admin/practitioner-categories/{{ $c->uuid }}/edit" style="color:#B71C1C;text-decoration:none;font-size:.78rem;margin-right:8px;">Éditer</a>
                <form method="POST" action="/admin/practitioner-categories/{{ $c->uuid }}" style="display:inline;">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('Supprimer ?')" style="background:none;border:none;color:#C62828;cursor:pointer;font-size:.78rem;">Supprimer</button>
                </form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
