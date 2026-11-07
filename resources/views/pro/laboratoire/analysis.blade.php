@extends('layouts.dashboard')
@section('env-name', 'HOSTO Pro')
@section('env-color', '#1565C0')
@section('env-color-dark', '#0D47A1')
@section('user-role', 'Professionnel de santé')
@section('title', $analysis->reference)
@section('page-title', 'Analyse '.$analysis->reference)

@section('sidebar-nav')
@include('pro.partials.sidebar', ['active' => 'laboratoire'])
@endsection

@section('content')
<a href="/pro/laboratoire" style="display:inline-block;margin-bottom:14px;color:#1565C0;font-size:.82rem;text-decoration:none;">Retour au laboratoire</a>
@include('pro.partials.flash')
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:16px 20px;margin-bottom:16px;">
    <div style="font-size:1rem;font-weight:700;color:#1B2A1B;">{{ $analysis->patient?->name }}</div>
    <div style="font-size:.82rem;color:#1565C0;font-weight:600;margin-top:2px;">{{ $analysis->examRequest?->exam_type }}</div>
    @if($analysis->examRequest?->clinical_info)<div style="font-size:.78rem;color:#757575;margin-top:2px;">Contexte : {{ $analysis->examRequest->clinical_info }}</div>@endif
    <div style="font-size:.78rem;color:#757575;">Prescrit par {{ $analysis->examRequest?->practitioner?->full_name }}</div>
</div>

@if($analysis->status === 'received')
<form method="POST" action="/pro/laboratoire/analyses/{{ $analysis->uuid }}/prelevement">
    @csrf
    <button type="submit" style="padding:9px 20px;border:none;border-radius:8px;background:#1565C0;color:white;font-size:.8rem;font-weight:600;cursor:pointer;">Enregistrer le prélèvement</button>
</form>
@elseif(in_array($analysis->status, ['sample_collected', 'completed'], true))
<form method="POST" action="/pro/laboratoire/analyses/{{ $analysis->uuid }}/resultats" style="background:white;border:1px solid #EEE;border-radius:14px;padding:16px 20px;margin-bottom:12px;">
    @csrf
    <h2 style="font-size:.95rem;font-weight:600;color:#1565C0;margin:0 0 10px;">Résultats</h2>
    @php $lines = $analysis->items->isNotEmpty() ? $analysis->items : collect(range(1, 3))->map(fn () => null); @endphp
    @foreach($lines->values() as $i => $line)
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
        <input name="items[{{ $i }}][test_name]" value="{{ $line?->test_name }}" placeholder="Paramètre" aria-label="Paramètre" style="flex:2;min-width:150px;padding:7px;border:1px solid #DDD;border-radius:6px;">
        <input name="items[{{ $i }}][value]" value="{{ $line?->value }}" placeholder="Valeur" aria-label="Valeur" style="width:90px;padding:7px;border:1px solid #DDD;border-radius:6px;">
        <input name="items[{{ $i }}][unit]" value="{{ $line?->unit }}" placeholder="Unité" aria-label="Unité" style="width:80px;padding:7px;border:1px solid #DDD;border-radius:6px;">
        <input name="items[{{ $i }}][reference_range]" value="{{ $line?->reference_range }}" placeholder="Valeurs de référence" aria-label="Valeurs de référence" style="width:150px;padding:7px;border:1px solid #DDD;border-radius:6px;">
        <select name="items[{{ $i }}][flag]" aria-label="Interprétation" style="padding:7px;border:1px solid #DDD;border-radius:6px;">
            @foreach(['' => 'Interprétation', 'N' => 'Normal', 'H' => 'Élevé', 'L' => 'Bas', 'C' => 'Critique'] as $code => $label)<option value="{{ $code }}" @selected($line?->flag === $code)>{{ $label }}</option>@endforeach
        </select>
    </div>
    @endforeach
    <label style="display:block;font-size:.74rem;color:#757575;margin-top:8px;">Conclusion
        <textarea name="conclusion" rows="2" style="display:block;width:100%;padding:8px;border:1px solid #DDD;border-radius:6px;font-family:Poppins,sans-serif;">{{ $analysis->conclusion }}</textarea>
    </label>
    <button type="submit" style="margin-top:10px;padding:9px 20px;border:none;border-radius:8px;background:#1565C0;color:white;font-size:.8rem;font-weight:600;cursor:pointer;">Enregistrer les résultats</button>
</form>
@if($analysis->status === 'completed')
<form method="POST" action="/pro/laboratoire/analyses/{{ $analysis->uuid }}/valider" onsubmit="return confirm('Valider et transmettre ? Les résultats ne pourront plus être modifiés.')">
    @csrf
    <button type="submit" style="padding:9px 20px;border:none;border-radius:8px;background:#2E7D32;color:white;font-size:.8rem;font-weight:600;cursor:pointer;">Valider et transmettre</button>
</form>
@endif
@else
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:16px 20px;">
    @foreach($analysis->items as $line)
    <div style="font-size:.85rem;color:#1B2A1B;padding:4px 0;">{{ $line->test_name }} : <strong>{{ $line->value }}</strong> {{ $line->unit }}</div>
    @endforeach
    @if($analysis->conclusion)<p style="font-size:.82rem;color:#424242;margin:8px 0 0;">Conclusion : {{ $analysis->conclusion }}</p>@endif
    <p style="font-size:.74rem;color:#757575;margin:8px 0 0;">Transmis le {{ $analysis->validated_at?->format('d/m/Y H:i') }}.</p>
</div>
@endif
@endsection
