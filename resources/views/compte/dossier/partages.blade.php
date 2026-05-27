@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Mes partages')
@section('page-title', 'Partages de mon dossier médical')
@section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'dossier']) @endsection

@section('content')
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:18px;">
    @if($grants->isEmpty())
        <p style="color:#757575;">Vous n'avez partagé votre dossier avec personne pour le moment.</p>
    @else
        @foreach($grants as $g)
            <div style="padding:14px;border-bottom:1px solid #F5F5F5;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <strong>{{ $g->practitioner->full_name }}</strong>
                    <div style="font-size:.78rem;color:#757575;">
                        Partagé depuis le {{ $g->granted_at->format('d/m/Y') }}
                        @if($g->revoked_at)
                            <span style="color:#C62828;"> — Révoqué le {{ $g->revoked_at->format('d/m/Y') }}</span>
                        @else
                            — {{ $g->access_count }} accès au total
                            @if($g->last_accessed_at)
                                · dernier le {{ $g->last_accessed_at->format('d/m/Y H:i') }}
                            @endif
                        @endif
                    </div>
                </div>
                <div style="display:flex;gap:8px;">
                    <a href="/compte/dossier/partages/{{ $g->uuid }}/historique"
                       style="padding:6px 12px;background:#E3F2FD;color:#1565C0;border-radius:6px;text-decoration:none;font-size:.78rem;font-weight:600;">
                        Historique
                    </a>
                    @if(!$g->revoked_at)
                        <form method="POST" action="/compte/dossier/partages/{{ $g->uuid }}/revoke" style="display:inline;">
                            @csrf
                            <button type="submit" onclick="return confirm('Révoquer ce partage ?')"
                                    style="padding:6px 12px;background:#FFEBEE;color:#C62828;border:none;border-radius:6px;cursor:pointer;font-size:.78rem;font-weight:600;">
                                Révoquer
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
