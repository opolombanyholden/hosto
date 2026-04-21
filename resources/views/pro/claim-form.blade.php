@extends('layouts.dashboard')

@section('env-name', 'HOSTO Pro')
@section('env-color', '#1565C0')
@section('env-color-dark', '#0D47A1')
@section('title', 'Enregistrer une structure')
@section('page-title', 'Enregistrer une structure de sante')
@section('user-role', 'Professionnel')

@section('sidebar-nav')
<a href="/pro"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg> Tableau de bord</a>
<a href="/pro/enregistrer-structure" class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> Enregistrer structure</a>
<a href="/pro/mes-demandes"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/></svg> Mes demandes</a>
@endsection

@section('content')
@if($errors->any())
<div style="background:#FFEBEE;color:#C62828;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:.85rem;">
    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
</div>
@endif

<div style="background:white;border-radius:14px;padding:28px;border:1px solid #EEE;max-width:700px;">
    <p style="font-size:.85rem;color:#757575;margin-bottom:24px;">Remplissez les informations de votre structure de sante. Votre demande sera examinee par nos equipes. Vous devrez egalement fournir les documents legaux justifiant votre droit d'exercer.</p>

    <form method="POST" action="/pro/enregistrer-structure">
        @csrf
        <h3 style="font-size:.9rem;font-weight:600;color:#1565C0;margin-bottom:16px;">Informations de la structure</h3>
        <div class="field"><label>Nom de la structure *</label><input type="text" name="structure_name" value="{{ old('structure_name') }}" required></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="field"><label>Type de structure</label><input type="text" name="structure_type" value="{{ old('structure_type') }}" placeholder="Hopital, Clinique, Pharmacie..."></div>
            <div class="field"><label>Ville</label><input type="text" name="structure_city" value="{{ old('structure_city') }}"></div>
        </div>
        <div class="field"><label>Adresse</label><input type="text" name="structure_address" value="{{ old('structure_address') }}"></div>
        <div class="field"><label>Telephone</label><input type="tel" name="structure_phone" value="{{ old('structure_phone') }}" placeholder="+241..."></div>

        <h3 style="font-size:.9rem;font-weight:600;color:#1565C0;margin:24px 0 16px;">Representant legal</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="field"><label>Nom complet *</label><input type="text" name="representative_name" value="{{ old('representative_name') }}" required></div>
            <div class="field"><label>Fonction</label><input type="text" name="representative_role" value="{{ old('representative_role') }}" placeholder="Directeur, Gerant..."></div>
        </div>
        <div class="field"><label>Numero RCCM / Autorisation</label><input type="text" name="registration_number" value="{{ old('registration_number') }}"></div>

        <div style="background:#E3F2FD;border-radius:10px;padding:16px;margin:20px 0;font-size:.82rem;color:#0D47A1;">
            <strong>Documents requis</strong> (a fournir apres soumission) :
            <ul style="margin-top:8px;padding-left:20px;">
                <li>Registre de commerce ou autorisation d'exercer</li>
                <li>Piece d'identite du representant legal</li>
                <li>Diplome ou attestation professionnelle</li>
            </ul>
        </div>

        <button type="submit" style="padding:12px 32px;background:#1565C0;color:white;border:none;border-radius:10px;font-family:Poppins,sans-serif;font-size:.9rem;font-weight:600;cursor:pointer;">Soumettre la demande</button>
    </form>
</div>

<style>
    .field { margin-bottom:16px; }
    .field label { display:block; font-size:.82rem; font-weight:500; color:#424242; margin-bottom:6px; }
    .field input { width:100%; padding:10px 14px; border:2px solid #EEE; border-radius:8px; font-family:Poppins,sans-serif; font-size:.85rem; outline:none; }
    .field input:focus { border-color:#1565C0; }
    @media(max-width:768px) { div[style*="grid-template-columns: 1fr 1fr"] { grid-template-columns:1fr !important; } }
</style>
@endsection
