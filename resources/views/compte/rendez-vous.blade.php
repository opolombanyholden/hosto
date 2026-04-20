@extends('layouts.dashboard')

@section('env-name', 'HOSTO')
@section('env-color', '#388E3C')
@section('env-color-dark', '#2E7D32')
@section('title', 'Mes rendez-vous')
@section('page-title', 'Mes rendez-vous')
@section('user-role', 'Patient')

@section('sidebar-nav')
<a href="/compte">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
    Tableau de bord
</a>
<a href="/compte/rendez-vous" class="active">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
    Mes rendez-vous
</a>
<a href="/annuaire">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
    Annuaire
</a>
<a href="/compte/profil">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    Mon profil
</a>
@endsection

@section('content')
<div id="rdvLoading" style="text-align:center;padding:40px;color:#757575;">Chargement de vos rendez-vous...</div>
<div id="rdvList"></div>
<div id="rdvEmpty" style="display:none;text-align:center;padding:40px;">
    <p style="color:#757575;margin-bottom:16px;">Vous n'avez aucun rendez-vous.</p>
    <a href="/annuaire/medecins" style="padding:10px 24px;background:#388E3C;color:white;border-radius:100px;font-size:.85rem;font-weight:600;text-decoration:none;">Trouver un medecin</a>
</div>

<style>
    .rdv-card { background:white; border:1px solid #EEE; border-radius:14px; padding:20px; margin-bottom:12px; display:flex; gap:16px; align-items:start; }
    .rdv-date { width:60px; text-align:center; flex-shrink:0; }
    .rdv-date .day { font-size:1.5rem; font-weight:700; color:#388E3C; }
    .rdv-date .month { font-size:.72rem; color:#757575; text-transform:uppercase; }
    .rdv-info { flex:1; }
    .rdv-time { font-size:.9rem; font-weight:600; color:#1B2A1B; }
    .rdv-prac { font-size:.82rem; color:#757575; }
    .rdv-status { padding:4px 12px; border-radius:100px; font-size:.68rem; font-weight:600; }
    .rdv-cancel { padding:6px 14px; border:1px solid #EEE; border-radius:8px; background:white; cursor:pointer; font-family:Poppins,sans-serif; font-size:.72rem; color:#E53935; }
    @media(max-width:768px) { .rdv-card{flex-direction:column;} }
</style>
@endsection

@section('scripts')
<script>
async function loadRdv() {
    try {
        const res = await fetch('/api/v1/rdv/appointments?upcoming=1', {
            headers: {'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest'}
        });
        if (res.status === 401) { document.getElementById('rdvLoading').innerHTML = '<a href="/compte/connexion">Connectez-vous</a>'; return; }
        const data = await res.json();
        document.getElementById('rdvLoading').style.display = 'none';

        if (!data.data.length) { document.getElementById('rdvEmpty').style.display = 'block'; return; }

        const list = document.getElementById('rdvList');
        data.data.forEach(rdv => {
            const slot = rdv.time_slot || {};
            const date = slot.date ? new Date(slot.date) : null;
            const statusColors = {pending:'#FFF3E0;color:#E65100', confirmed:'#E8F5E9;color:#2E7D32', completed:'#E3F2FD;color:#1565C0', cancelled_by_patient:'#FFEBEE;color:#C62828', cancelled_by_practitioner:'#FFEBEE;color:#C62828'};
            const statusLabels = {pending:'En attente', confirmed:'Confirme', completed:'Termine', cancelled_by_patient:'Annule', cancelled_by_practitioner:'Annule par le medecin'};
            const canCancel = ['pending','confirmed'].includes(rdv.status);

            const card = document.createElement('div');
            card.className = 'rdv-card';
            card.innerHTML = `
                <div class="rdv-date">
                    <div class="day">${date ? date.getDate() : '-'}</div>
                    <div class="month">${date ? date.toLocaleDateString('fr',{month:'short'}) : ''}</div>
                </div>
                <div class="rdv-info">
                    <div class="rdv-time">${slot.start_time ? slot.start_time.substring(0,5) : ''} — ${rdv.practitioner?.full_name || ''}</div>
                    <div class="rdv-prac">${rdv.structure?.name || ''} ${rdv.is_teleconsultation ? '(Teleconsultation)' : ''}</div>
                    ${rdv.reason ? `<div style="font-size:.78rem;color:#757575;margin-top:4px;">Motif: ${rdv.reason}</div>` : ''}
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;align-items:end;">
                    <span class="rdv-status" style="background:${statusColors[rdv.status]||'#F5F5F5'}">${statusLabels[rdv.status]||rdv.status}</span>
                    ${canCancel ? `<button class="rdv-cancel" onclick="cancelRdv('${rdv.uuid}',this)">Annuler</button>` : ''}
                </div>`;
            list.appendChild(card);
        });
    } catch(e) { document.getElementById('rdvLoading').textContent = 'Erreur de chargement.'; }
}

async function cancelRdv(uuid, btn) {
    if (!confirm('Annuler ce rendez-vous ?')) return;
    try {
        const res = await fetch(`/api/v1/rdv/appointments/${uuid}/cancel`, {
            method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content||'','X-Requested-With':'XMLHttpRequest'}
        });
        if (res.ok) { btn.closest('.rdv-card').style.opacity='.4'; btn.textContent='Annule'; btn.disabled=true; }
    } catch(e) {}
}

loadRdv();
</script>
@endsection
