@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Mes dépendants')
@section('page-title', 'Mes dépendants')
@section('user-role', 'Patient')

@section('content')
<div style="max-width:760px;">
    @if(session('success'))
        <div style="padding:10px 14px;background:#E8F5E9;color:#2E7D32;border-radius:8px;margin-bottom:14px;">{{ session('success') }}</div>
    @endif

    <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:18px;margin-bottom:18px;">
        <h3 style="margin:0 0 14px;font-size:1rem;">Ajouter un dépendant</h3>
        <form method="POST" action="/compte/carnet-vaccination/dependents" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            @csrf
            <input name="first_name" placeholder="Prénom" required style="padding:10px;border:2px solid #EEE;border-radius:8px;">
            <input name="last_name" placeholder="Nom" required style="padding:10px;border:2px solid #EEE;border-radius:8px;">
            <input name="date_of_birth" type="date" required style="padding:10px;border:2px solid #EEE;border-radius:8px;">
            <select name="gender" style="padding:10px;border:2px solid #EEE;border-radius:8px;">
                <option value="">Sexe — (optionnel)</option>
                <option value="male">Masculin</option>
                <option value="female">Féminin</option>
            </select>
            <input name="nip" placeholder="NIP (optionnel)" style="grid-column:1/3;padding:10px;border:2px solid #EEE;border-radius:8px;">
            <textarea name="notes" placeholder="Notes (optionnel)" rows="2" style="grid-column:1/3;padding:10px;border:2px solid #EEE;border-radius:8px;"></textarea>
            <button type="submit" style="grid-column:1/3;padding:10px;background:#388E3C;color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;">Ajouter</button>
        </form>
    </div>

    <div style="background:white;border:1px solid #EEE;border-radius:14px;padding:18px;">
        <h3 style="margin:0 0 14px;font-size:1rem;">Liste ({{ $dependents->count() }})</h3>
        @forelse($dependents as $d)
            <div style="border-top:1px solid #F5F5F5;padding:10px 0;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <strong>{{ $d->first_name }} {{ $d->last_name }}</strong>
                    <span style="color:#757575;font-size:.78rem;">né(e) le {{ $d->date_of_birth->format('d/m/Y') }}</span>
                </div>
                <div style="display:flex;gap:8px;">
                    <a href="/compte/carnet-vaccination/dependent/{{ $d->uuid }}" style="padding:6px 12px;background:#E3F2FD;color:#1565C0;border-radius:6px;text-decoration:none;font-size:.78rem;font-weight:600;">Voir le carnet</a>
                    <form method="POST" action="/compte/carnet-vaccination/dependents/{{ $d->uuid }}" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" onclick="return confirm('Supprimer ce dépendant ?')" style="padding:6px 12px;background:#FFEBEE;color:#C62828;border:none;border-radius:6px;font-size:.78rem;font-weight:600;cursor:pointer;">Supprimer</button>
                    </form>
                </div>
            </div>
        @empty
            <p style="color:#757575;font-size:.85rem;">Aucun dépendant enregistré.</p>
        @endforelse
    </div>
</div>
@endsection
