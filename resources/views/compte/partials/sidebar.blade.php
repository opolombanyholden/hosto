<a href="/compte" class="{{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
    <span>Tableau de bord</span>
</a>
<a href="/compte/dossier-medical" class="{{ ($active ?? '') === 'dossier' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 18v-6M9 15h6"/></svg>
    <span>Mon dossier</span>
</a>
<a href="/compte/rendez-vous" class="{{ ($active ?? '') === 'rdv' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
    <span>Mes rendez-vous</span>
</a>
<a href="/compte/carnet-vaccination" class="{{ ($active ?? '') === 'carnet-vaccination' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7z"/></svg>
    <span>Carnet vaccination</span>
</a>
<div class="sidebar-section">Explorer</div>
<a href="/compte/structures" class="{{ ($active ?? '') === 'structures' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
    <span>Structures</span>
</a>
<a href="/compte/medecins" class="{{ ($active ?? '') === 'medecins' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    <span>Medecins</span>
</a>
<a href="/compte/medicaments" class="{{ ($active ?? '') === 'medicaments' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M2 12h20"/></svg>
    <span>Medicaments</span>
</a>
<a href="/compte/examens" class="{{ ($active ?? '') === 'examens' ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
    <span>Examens</span>
</a>
