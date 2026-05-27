@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Historique partage')
@section('page-title', 'Historique des accès — ' . $grant->practitioner->full_name)
@section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'dossier']) @endsection

@section('content')
<a href="/compte/dossier/partages" style="display:inline-block;margin-bottom:14px;color:#388E3C;font-size:.82rem;">← Retour aux partages</a>

<div style="background:white;border:1px solid #EEE;border-radius:14px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
        <thead><tr style="background:#FAFAFA;">
            <th style="padding:12px 16px;text-align:left;">Date</th>
            <th style="padding:12px 16px;text-align:left;">Pro accédant</th>
            <th style="padding:12px 16px;text-align:left;">Sections</th>
            <th style="padding:12px 16px;text-align:left;">IP</th>
        </tr></thead>
        <tbody>
            @forelse($logs as $l)
                <tr style="border-top:1px solid #F5F5F5;">
                    <td style="padding:10px 16px;">{{ $l->accessed_at->format('d/m/Y H:i') }}</td>
                    <td style="padding:10px 16px;">{{ $l->practitionerUser?->name ?? '—' }}</td>
                    <td style="padding:10px 16px;color:#757575;">
                        {{ $l->sections_accessed ? implode(', ', $l->sections_accessed) : '—' }}
                    </td>
                    <td style="padding:10px 16px;font-family:monospace;font-size:.78rem;color:#757575;">{{ $l->ip_address ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="padding:30px;text-align:center;color:#999;">Aucun accès enregistré.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
