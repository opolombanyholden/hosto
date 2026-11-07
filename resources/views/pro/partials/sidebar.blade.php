@php
    $items = [
        ['dashboard', '/pro', 'Tableau de bord', '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
        ['patients', '/pro/patients', 'Mes patients', '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
        ['consultations', '/pro/consultations', 'Consultations', '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>'],
        ['nouvelle', '/pro/consultations/nouvelle', 'Nouvelle consultation', '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>'],
        ['evax', '/pro/evax', 'Vaccination', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
        ['visibility', '/pro/visibility', 'Visibilité du profil', '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'],
        ['publications', '/pro/publications', 'Mes publications', '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>'],
        ['claims', '/pro/mes-demandes', 'Mes structures', '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
        ['profil', '/pro/profil', 'Mon profil', '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
    ];
@endphp
@foreach($items as [$key, $href, $label, $icon])
<a href="{{ $href }}" @class(['active' => ($active ?? '') === $key])>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icon !!}</svg>
    {{ $label }}
</a>
@endforeach
