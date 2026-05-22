<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 20mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #1B2A1B; }
    .header { display: table; width: 100%; margin-bottom: 14pt; border-bottom: 1pt solid #388E3C; padding-bottom: 8pt; }
    .header-left { display: table-cell; vertical-align: top; width: 70%; }
    .header-right { display: table-cell; vertical-align: top; text-align: right; width: 30%; }
    .header-right svg { width: 90pt; height: 90pt; }
    h1 { font-size: 18pt; color: #2E7D32; margin: 0; }
    .sub { font-size: 9pt; color: #757575; margin-top: 2pt; }
    .meta-row { font-size: 10pt; margin-top: 8pt; }
    table { width: 100%; border-collapse: collapse; margin-top: 12pt; }
    th { background: #F5F5F5; padding: 6pt; text-align: left; font-size: 10pt; }
    td { border-top: 0.5pt solid #EEE; padding: 6pt; font-size: 10pt; }
    .footer { position: fixed; bottom: 10mm; left: 20mm; right: 20mm; font-size: 8pt; color: #757575; text-align: center; }
    .badge-unstd { background: #FFF3E0; color: #E65100; padding: 1pt 4pt; border-radius: 3pt; font-size: 8pt; }
</style>
</head>
<body>
<div class="header">
    <div class="header-left">
        <h1>Carnet de vaccination</h1>
        <div class="sub">HOSTO &mdash; Plateforme de sante panafricaine &middot; Edite le {{ $generatedAt->format('d/m/Y H:i') }}</div>
        <div class="meta-row">
            <strong>{{ $subject->name ?? ($subject->first_name.' '.$subject->last_name) }}</strong>
            @if($subject->date_of_birth)
                &middot; ne(e) le {{ $subject->date_of_birth->format('d/m/Y') }}
            @endif
            @if(($subject->nip ?? null))
                &middot; NIP : {{ $subject->nip }}
            @endif
        </div>
    </div>
    <div class="header-right">
        {!! $qrSvg !!}
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Vaccin</th>
            <th>Dose</th>
            <th>Lot</th>
            <th>Lieu</th>
        </tr>
    </thead>
    <tbody>
    @forelse($records as $r)
        <tr>
            <td>{{ $r->administered_at?->format('d/m/Y') }}</td>
            <td>
                {{ $r->vaccine_name }}
                @if($r->vaccine_code) <span style="color:#757575;font-size:8pt;">({{ $r->vaccine_code }})</span> @endif
                @unless($r->is_standardized) <span class="badge-unstd">non standardise</span> @endunless
            </td>
            <td>{{ $r->dose_number }}</td>
            <td>{{ $r->batch_number ?? '&mdash;' }}</td>
            <td>{{ $r->hosto?->name ?? '&mdash;' }}</td>
        </tr>
    @empty
        <tr><td colspan="5" style="text-align:center;color:#757575;padding:20pt;">Aucune vaccination enregistree.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="footer">
    Verifier l'authenticite de ce carnet : {{ $verifyUrl }}
</div>
</body>
</html>
