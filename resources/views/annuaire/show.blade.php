@extends('layouts.app')

@section('title', $hosto->name . ' — HOSTO')
@section('description', ($hosto->description_fr ?: 'Fiche detaillee de ' . $hosto->name . ' sur HOSTO'))

@section('styles')
<style>
    .detail-cover {
        position: relative; height: 260px; overflow: hidden;
        background: linear-gradient(135deg, var(--green-dark), var(--green-mid));
    }
    .detail-cover img { width: 100%; height: 100%; object-fit: cover; }
    .detail-cover .back-btn {
        position: absolute; top: 16px; left: 16px; background: rgba(0,0,0,.4); color: white;
        border: none; padding: 8px 16px; border-radius: 100px; cursor: pointer;
        font-family: 'Poppins',sans-serif; font-size: .82rem; backdrop-filter: blur(4px);
        display: flex; align-items: center; gap: 6px;
    }
    .detail-cover .back-btn:hover { background: rgba(0,0,0,.6); }

    .detail-profile {
        margin-top: -50px; padding: 0 24px; display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;
    }
    .detail-profile img {
        width: 100px; height: 100px; border-radius: 20px; border: 4px solid white;
        object-fit: cover; background: var(--green-pale); box-shadow: var(--shadow-md);
    }
    .detail-profile-info { padding-bottom: 8px; }
    .detail-profile-info .types { font-size: .75rem; color: var(--green); font-weight: 600; }
    .detail-profile-info h1 { font-size: 1.5rem; font-weight: 700; color: var(--dark); line-height: 1.2; }
    .detail-profile-info .location { font-size: .85rem; color: var(--gray-600); }
    .detail-profile-info .dist { color: var(--green); font-weight: 600; }

    .detail-body { max-width: 900px; margin: 0 auto; padding: 24px 24px 60px; }
    .detail-grid { display: grid; grid-template-columns: 1.2fr .8fr; gap: 32px; }

    .detail-section { margin-bottom: 28px; }
    .detail-section-title { font-size: .85rem; font-weight: 600; color: var(--green); margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
    .detail-section-title svg { width: 16px; height: 16px; }

    .contact-row { display: flex; align-items: center; gap: 10px; padding: 6px 0; font-size: .85rem; }
    .contact-row svg { width: 16px; height: 16px; color: var(--green); flex-shrink: 0; }
    .contact-row a { color: var(--green); }

    .specs-list { display: flex; flex-wrap: wrap; gap: 6px; }
    .spec-badge { padding: 4px 12px; background: var(--green-pale); color: var(--green); border-radius: 100px; font-size: .72rem; font-weight: 500; }

    .service-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--gray-200); font-size: .82rem; }
    .service-row:last-child { border-bottom: none; }
    .service-name { color: var(--gray-800); }
    .service-price { color: var(--gray-600); white-space: nowrap; }
    .service-cat { font-size: .72rem; font-weight: 600; color: var(--green); margin-top: 12px; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 1px; }

    .hours-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: .82rem; }
    .hours-day { color: var(--gray-800); }
    .hours-time { color: var(--gray-600); }
    .hours-closed { color: #E53935; }

    .gallery-scroll { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 8px; -webkit-overflow-scrolling: touch; }
    .gallery-scroll img { width: 140px; height: 110px; border-radius: 12px; object-fit: cover; flex-shrink: 0; background: var(--green-pale); cursor: pointer; }
    .gallery-scroll img:hover { opacity: .8; }

    .map-container { border-radius: 14px; overflow: hidden; height: 280px; }
    .map-link { display: inline-block; margin-top: 10px; font-size: .82rem; color: var(--green); font-weight: 500; }

    .status-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
    .status-badge { padding: 4px 12px; border-radius: 100px; font-size: .72rem; font-weight: 600; }
    .status-open { background: #E8F5E9; color: #2E7D32; }
    .status-closed { background: #FFEBEE; color: #C62828; }
    .status-guard { background: #FFF3E0; color: #E65100; }
    .status-public { background: #E3F2FD; color: #1565C0; }
    .status-private { background: #F3E5F5; color: #6A1B9A; }

    @media (max-width: 768px) {
        .detail-cover { height: 180px; }
        .detail-profile { padding: 0 16px; }
        .detail-profile img { width: 80px; height: 80px; }
        .detail-profile-info h1 { font-size: 1.2rem; }
        .detail-grid { grid-template-columns: 1fr; }
        .detail-body { padding: 20px 16px 40px; }
        .gallery-scroll img { width: 120px; height: 90px; }
        .map-container { height: 220px; }
    }
</style>
@endsection

@section('breadcrumb')
<li><span class="sep">/</span> <a href="/annuaire">Annuaire</a></li>
<li><span class="sep">/</span> <span class="current">{{ $hosto->name }}</span></li>
@endsection

@section('content')
@php
    $profileImg = $hosto->profileImageUrl() ?: '/images/icons/icon-hopitaux.png';
    $coverImg = $hosto->coverImageUrl();
    $coords = $hosto->coordinates();
    $types = $hosto->structureTypes;
    $specialties = $hosto->specialties;
    $services = $hosto->services->groupBy('category');
    $gallery = $hosto->galleryImages();
    $hours = $hosto->opening_hours;

    $catLabels = ['prestation' => 'Prestations', 'soin' => 'Soins', 'examen' => 'Examens'];
@endphp

<!-- Cover -->
<div class="detail-cover">
    @if($coverImg)
        <img src="{{ $coverImg }}" alt="Couverture {{ $hosto->name }}">
    @endif
    <a href="/annuaire" class="back-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour
    </a>
</div>

<!-- Profile -->
<div class="container">
    <div class="detail-profile">
        <img src="{{ $profileImg }}" alt="{{ $hosto->name }}">
        <div class="detail-profile-info">
            <div class="types">{{ $types->pluck('name_fr')->join(', ') }}</div>
            <h1>{{ $hosto->name }}</h1>
            <div class="location">
                {{ $hosto->city?->name_fr }}
                @if($hosto->address) &mdash; {{ $hosto->address }} @endif
                @if($hosto->quarter) ({{ $hosto->quarter }}) @endif
            </div>
            <div class="status-badges">
                @if($hosto->is_guard_service) <span class="status-badge status-guard">Service de garde</span> @endif
                <span class="status-badge {{ $hosto->is_public ? 'status-public' : 'status-private' }}">{{ $hosto->is_public ? 'Public' : 'Prive' }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Body -->
<div class="detail-body">
    <div class="detail-grid">
        <!-- Left column -->
        <div>
            {{-- Description --}}
            @if($hosto->description_fr)
            <div class="detail-section">
                <div class="detail-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                    A propos
                </div>
                <p style="font-size:.88rem;color:var(--gray-800);line-height:1.7;">{{ $hosto->description_fr }}</p>
            </div>
            @endif

            {{-- Specialties --}}
            @if($specialties->isNotEmpty())
            <div class="detail-section">
                <div class="detail-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    Specialites
                </div>
                <div class="specs-list">
                    @foreach($specialties as $spec)
                        <span class="spec-badge">{{ $spec->name_fr }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Services --}}
            @if($services->isNotEmpty())
            <div class="detail-section">
                <div class="detail-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                    Services et tarifs
                </div>
                @foreach($services as $category => $svcs)
                    <div class="service-cat">{{ $catLabels[$category] ?? $category }}</div>
                    @foreach($svcs as $svc)
                    <div class="service-row">
                        <span class="service-name">{{ $svc->name_fr }}</span>
                        <span class="service-price">
                            @if($svc->pivot->tarif_min)
                                {{ number_format($svc->pivot->tarif_min, 0, ',', ' ') }} - {{ number_format($svc->pivot->tarif_max, 0, ',', ' ') }} XAF
                            @endif
                        </span>
                    </div>
                    @endforeach
                @endforeach
            </div>
            @endif

            {{-- Gallery --}}
            @if($gallery->isNotEmpty())
            <div class="detail-section">
                <div class="detail-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    Galerie
                </div>
                <div class="gallery-scroll">
                    @foreach($gallery as $media)
                        <img src="{{ $media->url }}" alt="{{ $media->alt_text ?: $hosto->name }}">
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Right column (sidebar) -->
        <div>
            {{-- Contact --}}
            <div class="detail-section">
                <div class="detail-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    Contact
                </div>
                @if($hosto->phone)
                <div class="contact-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/></svg>
                    <a href="tel:{{ $hosto->phone }}">{{ $hosto->phone }}</a>
                </div>
                @endif
                @if($hosto->phone2)
                <div class="contact-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/></svg>
                    <a href="tel:{{ $hosto->phone2 }}">{{ $hosto->phone2 }}</a>
                </div>
                @endif
                @if($hosto->email)
                <div class="contact-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <a href="mailto:{{ $hosto->email }}">{{ $hosto->email }}</a>
                </div>
                @endif
                @if($hosto->website)
                <div class="contact-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    <a href="{{ $hosto->website }}" target="_blank">{{ $hosto->website }}</a>
                </div>
                @endif
                @if($hosto->emergency_phone)
                <div class="contact-row" style="color:#E53935;font-weight:600;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#E53935" stroke-width="2"><path d="M15.05 5A5 5 0 0 1 19 8.95M15.05 1A9 9 0 0 1 23 8.94m-1 7.98v3a2 2 0 0 1-2.18 2"/></svg>
                    <a href="tel:{{ $hosto->emergency_phone }}" style="color:#E53935;">Urgences : {{ $hosto->emergency_phone }}</a>
                </div>
                @endif
            </div>

            {{-- Hours --}}
            @if($hours)
            <div class="detail-section">
                <div class="detail-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Horaires
                </div>
                @php
                    $dayLabels = ['lun'=>'Lundi','mar'=>'Mardi','mer'=>'Mercredi','jeu'=>'Jeudi','ven'=>'Vendredi','sam'=>'Samedi','dim'=>'Dimanche'];
                @endphp
                @foreach($dayLabels as $key => $label)
                    <div class="hours-row">
                        <span class="hours-day">{{ $label }}</span>
                        @if(isset($hours[$key]))
                            <span class="hours-time">{{ $hours[$key]['open'] }} - {{ $hours[$key]['close'] }}</span>
                        @else
                            <span class="hours-closed">Ferme</span>
                        @endif
                    </div>
                @endforeach
            </div>
            @endif

            {{-- Map --}}
            @if($coords)
            <div class="detail-section">
                <div class="detail-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Localisation
                </div>
                <div id="structureMap" class="map-container"></div>
                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $coords[0] }},{{ $coords[1] }}" target="_blank" class="map-link">Itineraire Google Maps &rarr;</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
@if($coords)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const map = L.map('structureMap').setView([{{ $coords[0] }}, {{ $coords[1] }}], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap', maxZoom: 19,
    }).addTo(map);
    L.marker([{{ $coords[0] }}, {{ $coords[1] }}])
        .addTo(map)
        .bindPopup('<strong>{{ addslashes($hosto->name) }}</strong><br>{{ addslashes($types->pluck("name_fr")->join(", ")) }}')
        .openPopup();
});
</script>
@endif
@endsection
