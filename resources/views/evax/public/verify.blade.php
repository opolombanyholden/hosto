<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Carnet de vaccination — Vérification HOSTO</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
    body { font-family: -apple-system, system-ui, sans-serif; max-width: 720px; margin: 0 auto; padding: 24px; color: #1B2A1B; }
    .badge { background:#E8F5E9;color:#2E7D32;padding:4px 12px;border-radius:100px;font-size:.78rem;font-weight:600;display:inline-block;margin-bottom:14px; }
    h1 { font-size: 1.3rem; margin: 0 0 4px; }
    .meta { color:#757575;font-size:.85rem;margin-bottom:18px; }
    table { width:100%;border-collapse:collapse;margin-top:14px;font-size:.85rem; }
    th { background:#F5F5F5;padding:8px;text-align:left; }
    td { border-top:1px solid #EEE;padding:8px; }
</style>
</head>
<body>
<div class="badge">&#x2713; Vérifié par HOSTO le {{ $verifiedAt->format('d/m/Y à H:i') }}</div>
<h1>{{ $subject->name ?? ($subject->first_name.' '.$subject->last_name) }}</h1>
<div class="meta">
    @if(($subject->date_of_birth ?? null))
        Né(e) le {{ $subject->date_of_birth->format('d/m/Y') }}
    @endif
    @if(($subject->nip ?? null))
        · NIP : {{ $subject->nip }}
    @endif
</div>

@if($records->isEmpty())
    <p style="color:#757575;">Aucune vaccination enregistrée à ce jour.</p>
@else
<table>
    <thead><tr><th>Date</th><th>Vaccin</th><th>Dose</th><th>Lot</th></tr></thead>
    <tbody>
    @foreach($records as $r)
        <tr>
            <td>{{ $r->administered_at?->format('d/m/Y') }}</td>
            <td>{{ $r->vaccine_name }} @if(!$r->is_standardized)<small style="color:#E65100;">(non standardisé)</small>@endif</td>
            <td>{{ $r->dose_number }}</td>
            <td>{{ $r->batch_number ?? '—' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@endif

<p style="margin-top:30px;font-size:.78rem;color:#757575;">
Ce carnet est servi par <strong>HOSTO</strong> ({{ url('/') }}). La signature numérique est vérifiable hors-ligne via la clé publique disponible sur <a href="/.well-known/hosto/carnet-keys.json">/.well-known/hosto/carnet-keys.json</a>.
</p>
</body>
</html>
