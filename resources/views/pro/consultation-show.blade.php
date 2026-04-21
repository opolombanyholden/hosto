@extends('layouts.dashboard')

@section('env-name', 'HOSTO Pro')
@section('env-color', '#1565C0')
@section('env-color-dark', '#0D47A1')
@section('title', 'Consultation — ' . $consultation->patient?->name)
@section('page-title', 'Consultation')
@section('user-role', 'Professionnel')

@section('sidebar-nav')
<a href="/pro"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg> Tableau de bord</a>
<a href="/pro/consultations" class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> Consultations</a>
<a href="/pro/consultations/nouvelle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> Nouvelle consultation</a>
@endsection

@section('content')
@if(session('success'))
<div style="background:#E8F5E9;color:#2E7D32;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:.85rem;">{{ session('success') }}</div>
@endif

<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
    {{-- Left: consultation content --}}
    <div>
        <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:24px;margin-bottom:20px;">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
                <div>
                    <div style="font-size:1.1rem;font-weight:700;color:#1B2A1B;">{{ $consultation->patient?->name }}</div>
                    <div style="font-size:.78rem;color:#757575;">{{ $consultation->structure?->name }} — {{ $consultation->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <span style="padding:4px 14px;border-radius:100px;font-size:.72rem;font-weight:600;background:#E8F5E9;color:#2E7D32;">{{ ucfirst($consultation->status) }}</span>
            </div>

            @if($consultation->motif)<div style="margin-bottom:16px;"><div style="font-size:.75rem;font-weight:600;color:#1565C0;margin-bottom:4px;">MOTIF</div><p style="font-size:.88rem;color:#424242;">{{ $consultation->motif }}</p></div>@endif
            @if($consultation->anamnesis)<div style="margin-bottom:16px;"><div style="font-size:.75rem;font-weight:600;color:#1565C0;margin-bottom:4px;">ANAMNESE</div><p style="font-size:.85rem;color:#424242;white-space:pre-line;">{{ $consultation->anamnesis }}</p></div>@endif
            @if($consultation->examen_clinique)<div style="margin-bottom:16px;"><div style="font-size:.75rem;font-weight:600;color:#1565C0;margin-bottom:4px;">EXAMEN CLINIQUE</div><p style="font-size:.85rem;color:#424242;white-space:pre-line;">{{ $consultation->examen_clinique }}</p></div>@endif
            @if($consultation->diagnostic)<div style="margin-bottom:16px;"><div style="font-size:.75rem;font-weight:600;color:#1565C0;margin-bottom:4px;">DIAGNOSTIC @if($consultation->diagnostic_code)<span style="background:#E3F2FD;padding:2px 8px;border-radius:4px;font-size:.7rem;">{{ $consultation->diagnostic_code }}</span>@endif</div><p style="font-size:.85rem;color:#424242;">{{ $consultation->diagnostic }}</p></div>@endif
            @if($consultation->conduite_a_tenir)<div style="margin-bottom:16px;"><div style="font-size:.75rem;font-weight:600;color:#1565C0;margin-bottom:4px;">CONDUITE A TENIR</div><p style="font-size:.85rem;color:#424242;white-space:pre-line;">{{ $consultation->conduite_a_tenir }}</p></div>@endif
            @if($consultation->notes_internes)<div style="background:#FFF8E1;border-radius:8px;padding:12px;"><div style="font-size:.72rem;font-weight:600;color:#F57F17;margin-bottom:4px;">NOTES INTERNES</div><p style="font-size:.82rem;color:#424242;">{{ $consultation->notes_internes }}</p></div>@endif
        </div>

        {{-- Prescriptions --}}
        @foreach($consultation->prescriptions as $rx)
        <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:24px;margin-bottom:20px;">
            <div style="font-size:.85rem;font-weight:600;color:#1565C0;margin-bottom:12px;">Ordonnance {{ $rx->reference }}</div>
            @foreach($rx->items as $item)
            <div style="padding:10px 0;border-bottom:1px solid #F5F5F5;">
                <div style="font-size:.88rem;font-weight:600;color:#1B2A1B;">{{ $item->medication_name }} @if($item->dosage)<span style="font-weight:400;color:#757575;">{{ $item->dosage }}</span>@endif</div>
                @if($item->posology)<div style="font-size:.78rem;color:#424242;">{{ $item->posology }}</div>@endif
                @if($item->duration)<div style="font-size:.72rem;color:#757575;">Duree : {{ $item->duration }}</div>@endif
                @if($item->instructions)<div style="font-size:.72rem;color:#757575;font-style:italic;">{{ $item->instructions }}</div>@endif
            </div>
            @endforeach
        </div>
        @endforeach
    </div>

    {{-- Right: actions --}}
    <div>
        <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:20px;">
            <h3 style="font-size:.85rem;font-weight:600;color:#1565C0;margin-bottom:12px;">Actions</h3>
            <a href="/pro/consultations/nouvelle" style="display:block;padding:10px;border-radius:8px;font-size:.82rem;color:#424242;text-decoration:none;margin-bottom:4px;">+ Nouvelle consultation</a>

            <div style="border-top:1px solid #EEE;padding-top:12px;margin-top:12px;">
                <h4 style="font-size:.78rem;font-weight:600;color:#1565C0;margin-bottom:8px;">Ajouter ordonnance</h4>
                <form method="POST" action="/pro/consultations/{{ $consultation->uuid }}/ordonnance">
                    @csrf
                    <div id="rxItems">
                        <div class="rx-item" style="background:#F5F5F5;border-radius:8px;padding:12px;margin-bottom:8px;">
                            <input type="text" name="items[0][medication_name]" placeholder="Medicament *" required style="width:100%;padding:8px;border:1px solid #EEE;border-radius:6px;font-family:Poppins,sans-serif;font-size:.78rem;margin-bottom:4px;">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;">
                                <input type="text" name="items[0][dosage]" placeholder="Dosage" style="padding:8px;border:1px solid #EEE;border-radius:6px;font-family:Poppins,sans-serif;font-size:.78rem;">
                                <input type="text" name="items[0][posology]" placeholder="Posologie" style="padding:8px;border:1px solid #EEE;border-radius:6px;font-family:Poppins,sans-serif;font-size:.78rem;">
                            </div>
                            <input type="text" name="items[0][duration]" placeholder="Duree (ex: 7 jours)" style="width:100%;padding:8px;border:1px solid #EEE;border-radius:6px;font-family:Poppins,sans-serif;font-size:.78rem;margin-top:4px;">
                        </div>
                    </div>
                    <button type="button" onclick="addRxItem()" style="font-size:.72rem;color:#1565C0;background:none;border:none;cursor:pointer;margin-bottom:8px;">+ Ajouter un medicament</button>
                    <br>
                    <button type="submit" style="padding:8px 20px;background:#1565C0;color:white;border:none;border-radius:8px;font-family:Poppins,sans-serif;font-size:.78rem;font-weight:600;cursor:pointer;">Creer l'ordonnance</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>@media(max-width:768px) { div[style*="grid-template-columns: 2fr"] { grid-template-columns:1fr !important; } }</style>

<script>
let rxCount = 1;
function addRxItem() {
    const container = document.getElementById('rxItems');
    const div = document.createElement('div');
    div.className = 'rx-item';
    div.style.cssText = 'background:#F5F5F5;border-radius:8px;padding:12px;margin-bottom:8px;';
    div.innerHTML = `
        <input type="text" name="items[${rxCount}][medication_name]" placeholder="Medicament *" required style="width:100%;padding:8px;border:1px solid #EEE;border-radius:6px;font-family:Poppins,sans-serif;font-size:.78rem;margin-bottom:4px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;">
            <input type="text" name="items[${rxCount}][dosage]" placeholder="Dosage" style="padding:8px;border:1px solid #EEE;border-radius:6px;font-family:Poppins,sans-serif;font-size:.78rem;">
            <input type="text" name="items[${rxCount}][posology]" placeholder="Posologie" style="padding:8px;border:1px solid #EEE;border-radius:6px;font-family:Poppins,sans-serif;font-size:.78rem;">
        </div>
        <input type="text" name="items[${rxCount}][duration]" placeholder="Duree" style="width:100%;padding:8px;border:1px solid #EEE;border-radius:6px;font-family:Poppins,sans-serif;font-size:.78rem;margin-top:4px;">`;
    container.appendChild(div);
    rxCount++;
}
</script>
@endsection
