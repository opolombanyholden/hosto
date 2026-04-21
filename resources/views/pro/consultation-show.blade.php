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
<a href="/pro/consultations/nouvelle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> Nouvelle</a>
@endsection

@section('styles')
<style>
    .tabs { display:flex; border-bottom:2px solid #EEE; margin-bottom:20px; gap:4px; flex-wrap:wrap; }
    .tab { padding:10px 20px; font-size:.82rem; font-weight:500; color:#757575; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all .2s; background:none; border-top:none; border-left:none; border-right:none; font-family:Poppins,sans-serif; }
    .tab:hover { color:#1565C0; }
    .tab.active { color:#1565C0; border-bottom-color:#1565C0; font-weight:600; }
    .tab-badge { background:#E3F2FD; color:#1565C0; font-size:.65rem; padding:1px 6px; border-radius:100px; margin-left:4px; }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }
    .mini-form { background:#F5F5F5; border-radius:10px; padding:16px; margin-top:16px; }
    .mini-form label { display:block; font-size:.78rem; font-weight:500; color:#424242; margin-bottom:4px; }
    .mini-form input, .mini-form textarea, .mini-form select { width:100%; padding:8px 12px; border:1px solid #EEE; border-radius:6px; font-family:Poppins,sans-serif; font-size:.82rem; outline:none; margin-bottom:8px; }
    .mini-form input:focus, .mini-form textarea:focus, .mini-form select:focus { border-color:#1565C0; }
    .mini-btn { padding:8px 20px; background:#1565C0; color:white; border:none; border-radius:8px; font-family:Poppins,sans-serif; font-size:.78rem; font-weight:600; cursor:pointer; }
    .item-card { background:white; border:1px solid #EEE; border-radius:10px; padding:14px; margin-bottom:8px; }
    @media(max-width:768px) { .tabs { gap:0; } .tab { padding:8px 12px; font-size:.75rem; } }
</style>
@endsection

@section('content')
@if(session('success'))
<div style="background:#E8F5E9;color:#2E7D32;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:.85rem;">{{ session('success') }}</div>
@endif

{{-- Patient header --}}
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:12px;">
    <div>
        <div style="font-size:1.1rem;font-weight:700;color:#1B2A1B;">{{ $consultation->patient?->name }}</div>
        <div style="font-size:.78rem;color:#757575;">{{ $consultation->structure?->name }} — {{ $consultation->created_at->format('d/m/Y H:i') }}</div>
    </div>
    <span style="padding:4px 14px;border-radius:100px;font-size:.72rem;font-weight:600;background:#E8F5E9;color:#2E7D32;">{{ ucfirst($consultation->status) }}</span>
</div>

{{-- Tabs --}}
<div class="tabs">
    <button class="tab active" onclick="showTab('consultation',this)">Consultation</button>
    <button class="tab" onclick="showTab('examens',this)">Examens <span class="tab-badge">{{ $consultation->examRequests->count() }}</span></button>
    <button class="tab" onclick="showTab('soins',this)">Soins <span class="tab-badge">{{ $consultation->careActs->count() }}</span></button>
    <button class="tab" onclick="showTab('traitements',this)">Traitements <span class="tab-badge">{{ $consultation->treatments->count() }}</span></button>
    <button class="tab" onclick="showTab('ordonnance',this)">Ordonnance <span class="tab-badge">{{ $consultation->prescriptions->count() }}</span></button>
</div>

{{-- TAB 1: Consultation --}}
<div class="tab-panel active" id="tab-consultation">
    <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:24px;">
        @if($consultation->motif)<div style="margin-bottom:16px;"><div style="font-size:.72rem;font-weight:600;color:#1565C0;margin-bottom:4px;">MOTIF</div><p style="font-size:.88rem;color:#424242;">{{ $consultation->motif }}</p></div>@endif
        @if($consultation->anamnesis)<div style="margin-bottom:16px;"><div style="font-size:.72rem;font-weight:600;color:#1565C0;margin-bottom:4px;">ANAMNESE</div><p style="font-size:.85rem;color:#424242;white-space:pre-line;">{{ $consultation->anamnesis }}</p></div>@endif
        @if($consultation->examen_clinique)<div style="margin-bottom:16px;"><div style="font-size:.72rem;font-weight:600;color:#1565C0;margin-bottom:4px;">EXAMEN CLINIQUE</div><p style="font-size:.85rem;color:#424242;white-space:pre-line;">{{ $consultation->examen_clinique }}</p></div>@endif
        @if($consultation->diagnostic)<div style="margin-bottom:16px;"><div style="font-size:.72rem;font-weight:600;color:#1565C0;margin-bottom:4px;">DIAGNOSTIC @if($consultation->diagnostic_code)<span style="background:#E3F2FD;padding:2px 8px;border-radius:4px;font-size:.68rem;">{{ $consultation->diagnostic_code }}</span>@endif</div><p style="font-size:.85rem;color:#424242;">{{ $consultation->diagnostic }}</p></div>@endif
        @if($consultation->conduite_a_tenir)<div style="margin-bottom:16px;"><div style="font-size:.72rem;font-weight:600;color:#1565C0;margin-bottom:4px;">CONDUITE A TENIR</div><p style="font-size:.85rem;color:#424242;white-space:pre-line;">{{ $consultation->conduite_a_tenir }}</p></div>@endif
        @if($consultation->notes_internes)<div style="background:#FFF8E1;border-radius:8px;padding:12px;"><div style="font-size:.68rem;font-weight:600;color:#F57F17;margin-bottom:4px;">NOTES INTERNES</div><p style="font-size:.82rem;color:#424242;">{{ $consultation->notes_internes }}</p></div>@endif
    </div>
</div>

{{-- TAB 2: Examens --}}
<div class="tab-panel" id="tab-examens">
    @foreach($consultation->examRequests as $ex)
    <div class="item-card">
        <div style="display:flex;justify-content:space-between;"><div style="font-size:.82rem;font-weight:600;color:#1565C0;">{{ $ex->exam_type }}</div><span style="padding:2px 10px;border-radius:100px;font-size:.65rem;font-weight:600;background:{{ $ex->status==='completed' ? '#E8F5E9;color:#2E7D32' : '#FFF3E0;color:#E65100' }};">{{ $ex->status }}</span></div>
        @if($ex->clinical_info)<div style="font-size:.78rem;color:#757575;margin-top:4px;">{{ $ex->clinical_info }}</div>@endif
        @if($ex->urgency==='urgent')<div style="font-size:.7rem;color:#E53935;font-weight:600;margin-top:4px;">URGENT</div>@endif
    </div>
    @endforeach
    <div class="mini-form"><form method="POST" action="/pro/consultations/{{ $consultation->uuid }}/examen">@csrf
        <label>Type d'examen *</label><input type="text" name="exam_type" placeholder="Bilan sanguin, Echographie..." required>
        <label>Contexte clinique</label><textarea name="clinical_info" rows="2"></textarea>
        <label>Urgence</label><select name="urgency"><option value="normal">Normal</option><option value="urgent">Urgent</option></select>
        <button type="submit" class="mini-btn">Prescrire l'examen</button>
    </form></div>
</div>

{{-- TAB 3: Soins --}}
<div class="tab-panel" id="tab-soins">
    @foreach($consultation->careActs as $care)
    <div class="item-card">
        <div style="display:flex;justify-content:space-between;"><div style="font-size:.82rem;font-weight:600;color:#388E3C;">{{ ucfirst($care->care_type) }}</div><span style="padding:2px 10px;border-radius:100px;font-size:.65rem;font-weight:600;background:{{ $care->status==='performed' ? '#E8F5E9;color:#2E7D32' : '#FFF3E0;color:#E65100' }};">{{ $care->status }}</span></div>
        <div style="font-size:.85rem;color:#424242;margin-top:4px;">{{ $care->description }}</div>
        @if($care->instructions)<div style="font-size:.72rem;color:#757575;margin-top:4px;">Instructions : {{ $care->instructions }}</div>@endif
    </div>
    @endforeach
    <div class="mini-form"><form method="POST" action="/pro/consultations/{{ $consultation->uuid }}/soin">@csrf
        <label>Type de soin *</label><select name="care_type" required><option value="">Selectionnez</option><option value="injection">Injection</option><option value="perfusion">Perfusion</option><option value="pansement">Pansement</option><option value="suture">Suture</option><option value="kine">Kinesitherapie</option><option value="dialyse">Dialyse</option><option value="autre">Autre</option></select>
        <label>Description *</label><textarea name="description" rows="2" required></textarea>
        <label>Instructions</label><textarea name="instructions" rows="2"></textarea>
        <button type="submit" class="mini-btn">Prescrire le soin</button>
    </form></div>
</div>

{{-- TAB 4: Traitements --}}
<div class="tab-panel" id="tab-traitements">
    @foreach($consultation->treatments as $tx)
    <div class="item-card">
        <div style="display:flex;justify-content:space-between;"><div style="font-size:.82rem;font-weight:600;color:#E65100;">{{ ucfirst($tx->type) }}</div><span style="padding:2px 10px;border-radius:100px;font-size:.65rem;font-weight:600;background:#E3F2FD;color:#1565C0;">{{ $tx->status }}</span></div>
        <div style="font-size:.85rem;color:#424242;margin-top:4px;">{{ $tx->description }}</div>
        @if($tx->frequency)<div style="font-size:.72rem;color:#757575;margin-top:4px;">Frequence : {{ $tx->frequency }}</div>@endif
        @if($tx->duration)<div style="font-size:.72rem;color:#757575;">Duree : {{ $tx->duration }}</div>@endif
    </div>
    @endforeach
    <div class="mini-form"><form method="POST" action="/pro/consultations/{{ $consultation->uuid }}/traitement">@csrf
        <label>Type *</label><select name="type" required><option value="">Selectionnez</option><option value="medication">Medicamenteux</option><option value="diet">Regime alimentaire</option><option value="rest">Repos / Arret</option><option value="rehabilitation">Reeducation</option><option value="follow_up">Suivi periodique</option><option value="lifestyle">Hygiene de vie</option><option value="other">Autre</option></select>
        <label>Description *</label><textarea name="description" rows="2" required></textarea>
        <label>Frequence</label><input type="text" name="frequency" placeholder="Ex: 3 fois/jour">
        <label>Duree</label><input type="text" name="duration" placeholder="Ex: 7 jours, 3 mois">
        <button type="submit" class="mini-btn">Prescrire le traitement</button>
    </form></div>
</div>

{{-- TAB 5: Ordonnance --}}
<div class="tab-panel" id="tab-ordonnance">
    @foreach($consultation->prescriptions as $rx)
    <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:12px;">
        <div style="font-size:.85rem;font-weight:600;color:#1565C0;margin-bottom:12px;">{{ $rx->reference }} <span style="font-size:.72rem;color:#757575;font-weight:400;">— valide jusqu'au {{ $rx->valid_until?->format('d/m/Y') }}</span></div>
        @foreach($rx->items as $item)
        <div style="padding:8px 0;border-bottom:1px solid #F5F5F5;">
            <div style="font-size:.88rem;font-weight:600;color:#1B2A1B;">{{ $item->medication_name }} @if($item->dosage)<span style="font-weight:400;color:#757575;">{{ $item->dosage }}</span>@endif</div>
            @if($item->posology)<div style="font-size:.78rem;color:#424242;">{{ $item->posology }}</div>@endif
            @if($item->duration)<div style="font-size:.72rem;color:#757575;">Duree : {{ $item->duration }}</div>@endif
        </div>
        @endforeach
    </div>
    @endforeach

    <div class="mini-form">
        <div style="font-size:.82rem;font-weight:600;color:#1565C0;margin-bottom:12px;">Nouvelle ordonnance</div>
        <form method="POST" action="/pro/consultations/{{ $consultation->uuid }}/ordonnance">@csrf
            <div id="rxItems">
                <div style="background:white;border:1px solid #EEE;border-radius:8px;padding:12px;margin-bottom:8px;">
                    <input type="text" name="items[0][medication_name]" placeholder="Medicament *" required>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;"><input type="text" name="items[0][dosage]" placeholder="Dosage"><input type="text" name="items[0][posology]" placeholder="Posologie"></div>
                    <input type="text" name="items[0][duration]" placeholder="Duree">
                </div>
            </div>
            <button type="button" onclick="addRxItem()" style="font-size:.72rem;color:#1565C0;background:none;border:none;cursor:pointer;margin-bottom:8px;">+ Ajouter un medicament</button><br>
            <button type="submit" class="mini-btn">Creer l'ordonnance</button>
        </form>
    </div>
</div>

<script>
function showTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}
let rxCount = 1;
function addRxItem() {
    const c = document.getElementById('rxItems'), d = document.createElement('div');
    d.style.cssText = 'background:white;border:1px solid #EEE;border-radius:8px;padding:12px;margin-bottom:8px;';
    d.innerHTML = `<input type="text" name="items[${rxCount}][medication_name]" placeholder="Medicament *" required style="width:100%;padding:8px 12px;border:1px solid #EEE;border-radius:6px;font-family:Poppins,sans-serif;font-size:.82rem;margin-bottom:4px;"><div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;"><input type="text" name="items[${rxCount}][dosage]" placeholder="Dosage" style="padding:8px 12px;border:1px solid #EEE;border-radius:6px;font-family:Poppins,sans-serif;font-size:.82rem;"><input type="text" name="items[${rxCount}][posology]" placeholder="Posologie" style="padding:8px 12px;border:1px solid #EEE;border-radius:6px;font-family:Poppins,sans-serif;font-size:.82rem;"></div><input type="text" name="items[${rxCount}][duration]" placeholder="Duree" style="width:100%;padding:8px 12px;border:1px solid #EEE;border-radius:6px;font-family:Poppins,sans-serif;font-size:.82rem;margin-top:4px;">`;
    c.appendChild(d); rxCount++;
}
</script>
@endsection
