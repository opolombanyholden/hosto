@extends('layouts.dashboard')

@section('env-name', 'HOSTO Pro')
@section('env-color', '#1565C0')
@section('env-color-dark', '#0D47A1')
@section('title', 'Nouvelle consultation')
@section('page-title', 'Nouvelle consultation')
@section('user-role', 'Professionnel')

@section('sidebar-nav')
<a href="/pro"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg> Tableau de bord</a>
<a href="/pro/consultations"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> Consultations</a>
<a href="/pro/consultations/nouvelle" class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> Nouvelle consultation</a>
@endsection

@section('content')
@if($errors->any())
<div style="background:#FFEBEE;color:#C62828;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:.85rem;">
    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
</div>
@endif

@if(!$practitioner)
<div style="background:#FFF3E0;border:1px solid #FFB74D;border-radius:12px;padding:20px;color:#E65100;font-size:.85rem;">Profil praticien introuvable.</div>
@else
<div style="background:white;border-radius:14px;padding:28px;border:1px solid #EEE;max-width:800px;">
    <form method="POST" action="/pro/consultations">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="field"><label>Email du patient *</label><input type="email" name="patient_email" value="{{ old('patient_email') }}" required placeholder="patient@email.com"></div>
            <div class="field">
                <label>Structure *</label>
                <select name="hosto_id" required style="width:100%;padding:10px 14px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;outline:none;">
                    @foreach($structures as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="field"><label>Motif de consultation *</label><input type="text" name="motif" value="{{ old('motif') }}" required placeholder="Douleur thoracique, bilan annuel..."></div>
        <div class="field"><label>Anamnese</label><textarea name="anamnesis" rows="3" style="width:100%;padding:10px 14px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;resize:vertical;outline:none;">{{ old('anamnesis') }}</textarea></div>
        <div class="field"><label>Examen clinique</label><textarea name="examen_clinique" rows="3" style="width:100%;padding:10px 14px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;resize:vertical;outline:none;">{{ old('examen_clinique') }}</textarea></div>

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;">
            <div class="field"><label>Diagnostic</label><textarea name="diagnostic" rows="2" style="width:100%;padding:10px 14px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;resize:vertical;outline:none;">{{ old('diagnostic') }}</textarea></div>
            <div class="field"><label>Code CIM-10</label><input type="text" name="diagnostic_code" value="{{ old('diagnostic_code') }}" placeholder="J06.9"></div>
        </div>

        <div class="field"><label>Conduite a tenir</label><textarea name="conduite_a_tenir" rows="3" style="width:100%;padding:10px 14px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;resize:vertical;outline:none;">{{ old('conduite_a_tenir') }}</textarea></div>
        <div class="field"><label>Notes internes (non visibles par le patient)</label><textarea name="notes_internes" rows="2" style="width:100%;padding:10px 14px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;resize:vertical;outline:none;background:#FFF8E1;">{{ old('notes_internes') }}</textarea></div>

        <button type="submit" style="margin-top:16px;padding:12px 32px;background:#1565C0;color:white;border:none;border-radius:10px;font-family:Poppins,sans-serif;font-size:.9rem;font-weight:600;cursor:pointer;">Enregistrer la consultation</button>
    </form>
</div>

<style>
    .field { margin-bottom:16px; }
    .field label { display:block; font-size:.82rem; font-weight:500; color:#424242; margin-bottom:6px; }
    .field input { width:100%; padding:10px 14px; border:2px solid #EEE; border-radius:8px; font-family:Poppins,sans-serif; font-size:.85rem; outline:none; }
    .field input:focus, .field textarea:focus, .field select:focus { border-color:#1565C0; }
    @media(max-width:768px) { div[style*="grid-template-columns"] { grid-template-columns:1fr !important; } }
</style>
@endif
@endsection
