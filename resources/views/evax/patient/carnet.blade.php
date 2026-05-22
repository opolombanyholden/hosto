@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Mon carnet de vaccination')
@section('page-title', 'Carnet de vaccination')
@section('user-role', 'Patient')

@section('content')
<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;align-items:start;">

    <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:24px;">
        <h2 style="margin:0 0 12px;font-size:1.2rem;">{{ $subject->name ?? ($subject->first_name.' '.$subject->last_name) }}</h2>
        @if(($subject->nip ?? null))
            <div style="font-size:.82rem;color:#757575;">NIP : {{ $subject->nip }}</div>
        @endif
        @if(($subject->date_of_birth ?? null))
            <div style="font-size:.82rem;color:#757575;">Né(e) le : {{ $subject->date_of_birth->format('d/m/Y') }}</div>
        @endif

        <h3 style="margin:24px 0 10px;font-size:1rem;">Vaccinations</h3>
        @if($records->isEmpty())
            <p style="color:#757575;font-size:.85rem;">Aucune vaccination enregistrée à ce jour.</p>
        @else
        <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
            <thead style="background:#F5F5F5;">
                <tr><th style="text-align:left;padding:8px;">Date</th><th style="text-align:left;padding:8px;">Vaccin</th><th style="text-align:left;padding:8px;">Dose</th><th style="text-align:left;padding:8px;">Lot</th><th style="text-align:left;padding:8px;">Structure</th></tr>
            </thead>
            <tbody>
                @foreach($records as $r)
                <tr style="border-top:1px solid #EEE;">
                    <td style="padding:8px;">{{ $r->administered_at?->format('d/m/Y') }}</td>
                    <td style="padding:8px;">
                        {{ $r->vaccine_name }}
                        @unless($r->is_standardized)<span style="font-size:.65rem;background:#FFF3E0;color:#E65100;padding:1px 6px;border-radius:100px;margin-left:4px;">non standardisé</span>@endunless
                    </td>
                    <td style="padding:8px;">{{ $r->dose_number }}</td>
                    <td style="padding:8px;">{{ $r->batch_number ?? '—' }}</td>
                    <td style="padding:8px;">{{ $r->hosto?->name ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if(count($pevSchedule))
        <h3 style="margin:24px 0 10px;font-size:1rem;">Calendrier PEV</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;">
            @foreach($pevSchedule as $entry)
                @php
                    $bg = match($entry['status']) { 'overdue' => '#FFEBEE', 'due_now' => '#FFF3E0', default => '#E8F5E9' };
                    $color = match($entry['status']) { 'overdue' => '#C62828', 'due_now' => '#E65100', default => '#2E7D32' };
                @endphp
                <div style="padding:10px;background:{{ $bg }};color:{{ $color }};border-radius:8px;font-size:.78rem;">
                    <strong>{{ $entry['vaccine']->code }}</strong><br>
                    {{ $entry['vaccine']->name_fr }}<br>
                    Prévu : {{ $entry['due_at']->format('d/m/Y') }}
                </div>
            @endforeach
        </div>
        @endif
    </div>

    <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:24px;text-align:center;">
        <h3 style="margin:0 0 8px;font-size:.95rem;">QR de vérification</h3>
        <p style="font-size:.72rem;color:#757575;margin-bottom:12px;">Scannez ce QR pour authentifier le carnet hors-ligne ou en ligne.</p>
        <div style="margin:0 auto;width:220px;">{!! $qrSvg !!}</div>
        <a href="{{ $pdfUrl }}" style="display:inline-block;margin-top:14px;padding:10px 20px;background:#388E3C;color:white;border-radius:8px;text-decoration:none;font-weight:600;font-size:.85rem;">Télécharger le PDF</a>
        @if($subjectType === 'user')
            <a href="/compte/carnet-vaccination/dependents" style="display:block;margin-top:10px;font-size:.78rem;color:#388E3C;">Gérer mes dépendants</a>
        @else
            <a href="/compte/carnet-vaccination" style="display:block;margin-top:10px;font-size:.78rem;color:#388E3C;">← Retour à mon carnet</a>
        @endif
    </div>
</div>
@endsection
