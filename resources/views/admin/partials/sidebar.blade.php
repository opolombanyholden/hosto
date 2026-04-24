<a href="/admin" class="{{ request()->is('admin') ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
    <span>Tableau de bord</span>
</a>
<a href="/admin/utilisateurs" class="{{ request()->is('admin/utilisateurs') ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    <span>Utilisateurs</span>
</a>
<a href="/admin/structures" class="{{ request()->is('admin/structures') ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
    <span>Structures</span>
</a>
<a href="/admin/demandes" class="{{ request()->is('admin/demandes') ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
    <span>Demandes</span>
</a>

<div class="sidebar-section">Referentiel</div>
<a href="/admin/structure-types" class="{{ request()->is('admin/structure-types') ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>
    <span>Types de structure</span>
</a>
<a href="/admin/specialties" class="{{ request()->is('admin/specialties') ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
    <span>Specialites</span>
</a>
<a href="/admin/services" class="{{ request()->is('admin/services') ? 'active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
    <span>Services</span>
</a>

<div class="sidebar-section">Donnees de reference</div>
@foreach(\App\Modules\Referentiel\Models\ReferenceData::categoryLabels() as $catCode => $catLabel)
<a href="/admin/references/{{ $catCode }}" class="{{ request()->is('admin/references/'.$catCode) ? 'active' : '' }}" style="font-size:.78rem;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
    <span>{{ $catLabel }}</span>
</a>
@endforeach
