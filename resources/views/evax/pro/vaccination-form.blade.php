@extends('layouts.dashboard')
@section('env-name', 'HOSTO Pro') @section('env-color', '#1565C0') @section('env-color-dark', '#0D47A1')
@section('title', 'Nouvelle vaccination')
@section('page-title', 'Nouvelle vaccination')
@section('user-role', 'Professionnel de sante')

@section('content')
<div style="max-width:680px;">
    @if($patient)
        <div style="padding:14px 18px;background:#E3F2FD;border-radius:10px;margin-bottom:18px;">
            Patient : <strong>{{ $patient->name }}</strong> &mdash;
            @if($patient->nip) NIP {{ $patient->nip }} @else <em>sans NIP</em> @endif
        </div>
    @elseif($dependent)
        <div style="padding:14px 18px;background:#F3E5F5;border-radius:10px;margin-bottom:18px;">
            Dependant : <strong>{{ $dependent->first_name }} {{ $dependent->last_name }}</strong>
            (ne le {{ $dependent->date_of_birth?->format('d/m/Y') }})
        </div>
    @endif

    <form id="vaccForm" style="display:flex;flex-direction:column;gap:14px;">
        <input type="hidden" name="patient_uuid" value="{{ $patient?->uuid }}">
        <input type="hidden" name="dependent_uuid" value="{{ $dependent?->uuid }}">

        <label>Vaccin
            <input type="text" id="vaccineQ" list="vacList" placeholder="Choisir un vaccin du PEV ou tapez un nom libre" autocomplete="off" required style="width:100%;padding:10px;border:2px solid #EEE;border-radius:8px;">
            <datalist id="vacList">
                @foreach($vaccines as $v)
                    <option value="{{ $v->name_fr }}" data-code="{{ $v->code }}"></option>
                @endforeach
            </datalist>
        </label>

        <label>Numero de dose
            <input type="number" name="dose_number" id="doseNumber" min="1" max="20" value="1" required style="width:120px;padding:10px;border:2px solid #EEE;border-radius:8px;">
        </label>

        <label>Date d'administration
            <input type="date" name="administered_at" id="adminAt" value="{{ now()->toDateString() }}" required style="width:200px;padding:10px;border:2px solid #EEE;border-radius:8px;">
        </label>

        <label>Numero de lot
            <input type="text" name="batch_number" maxlength="60" style="width:240px;padding:10px;border:2px solid #EEE;border-radius:8px;">
        </label>

        <label>Date prochaine dose (optionnel)
            <input type="date" name="next_dose_date" style="width:200px;padding:10px;border:2px solid #EEE;border-radius:8px;">
        </label>

        <label>Notes
            <textarea name="notes" rows="2" maxlength="1000" style="width:100%;padding:10px;border:2px solid #EEE;border-radius:8px;"></textarea>
        </label>

        <div id="vaccMsg" style="display:none;padding:10px;border-radius:8px;font-size:.85rem;"></div>

        <div style="display:flex;gap:10px;">
            <button type="submit" style="padding:10px 22px;background:#1565C0;color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;">Enregistrer</button>
            <a href="javascript:history.back()" style="padding:10px 22px;border:1px solid #EEE;border-radius:8px;text-decoration:none;color:#424242;">Annuler</a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
const VACCINES = @json($vaccines->map(fn($v) => ['code' => $v->code, 'name' => $v->name_fr])->values());
const NAME_TO_CODE = new Map(VACCINES.map(v => [v.name.toLowerCase(), v.code]));

document.getElementById('vaccForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const name = document.getElementById('vaccineQ').value.trim();
    const code = NAME_TO_CODE.get(name.toLowerCase()) || null;
    const fd = new FormData(e.target);
    const body = {
        patient_uuid: fd.get('patient_uuid') || null,
        dependent_uuid: fd.get('dependent_uuid') || null,
        vaccine_code: code,
        vaccine_name: code ? null : name,
        dose_number: parseInt(fd.get('dose_number'), 10),
        administered_at: fd.get('administered_at'),
        batch_number: fd.get('batch_number') || null,
        next_dose_date: fd.get('next_dose_date') || null,
        notes: fd.get('notes') || null,
    };
    const msg = document.getElementById('vaccMsg');
    try {
        const res = await fetch('/pro/evax/vaccinations', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });
        const data = await res.json();
        msg.style.display = 'block';
        if (res.ok) {
            msg.style.background = '#E8F5E9'; msg.style.color = '#2E7D32';
            msg.textContent = data.data.message + ' (revision ' + data.data.carnet_revision + ')';
            setTimeout(() => { window.location.href = '/pro/evax/'; }, 1200);
        } else {
            msg.style.background = '#FFEBEE'; msg.style.color = '#C62828';
            msg.textContent = data.message || data.error?.message || 'Erreur';
        }
    } catch (err) {
        msg.style.display = 'block';
        msg.style.background = '#FFEBEE'; msg.style.color = '#C62828';
        msg.textContent = 'Erreur de connexion.';
    }
});
</script>
@endsection
