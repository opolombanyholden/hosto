@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', $hosto->name) @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'structures']) @endsection

@section('breadcrumb')
<span style="color:#BDBDBD;margin:0 6px;">/</span>
<a href="/compte/structures" style="color:#388E3C;text-decoration:none;font-weight:500;">Structures</a>
<span style="color:#BDBDBD;margin:0 6px;">/</span>
<span style="color:#424242;">{{ $hosto->name }}</span>
@endsection

@section('styles')
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
<style>
    .detail-wrap { margin-bottom:20px; }
    .detail-cover { height:240px; overflow:hidden; background:linear-gradient(135deg,#2E7D32,#43A047); border-radius:14px 14px 0 0; }
    .detail-cover img { width:100%;height:100%;object-fit:cover; }
    .detail-profile-bar { background:white; border:1px solid #EEE; border-top:none; border-radius:0 0 14px 14px; padding:16px 20px; display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap; }
    .detail-profile-img { width:110px; height:110px; border-radius:50%; border:4px solid white; object-fit:cover; background:#E8F5E9; box-shadow:0 2px 12px rgba(0,0,0,.15); margin-top:-55px; flex-shrink:0; }
    .detail-profile-info { flex:1; min-width:200px; padding-top:4px; }
    .detail-profile-info .types { font-size:.72rem;color:#388E3C;font-weight:600; }
    .detail-profile-info h1 { font-size:1.3rem;font-weight:700;color:#1B2A1B;line-height:1.2; }
    .detail-profile-info .location { font-size:.82rem;color:#757575; }
    .detail-profile-actions { display:flex;gap:8px;align-items:center;padding-bottom:8px; }
    .status-badges { display:flex;gap:5px;flex-wrap:wrap;margin-top:6px; }
    .status-badge { padding:3px 10px;border-radius:100px;font-size:.68rem;font-weight:600; }
    .detail-grid { display:grid;grid-template-columns:1.2fr .8fr;gap:24px; }
    .section-block { background:white;border:1px solid #EEE;border-radius:14px;padding:18px;margin-bottom:14px; }
    .section-block h3 { font-size:.85rem;font-weight:600;color:#388E3C;margin-bottom:10px; }
    .specs-list { display:flex;flex-wrap:wrap;gap:4px; }
    .spec-badge { padding:3px 10px;background:#E8F5E9;color:#388E3C;border-radius:100px;font-size:.68rem;font-weight:500; }
    .service-row { display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #F5F5F5;font-size:.82rem; }
    .service-row:last-child { border-bottom:none; }
    .contact-row { display:flex;align-items:center;gap:8px;padding:4px 0;font-size:.82rem; }
    .contact-row a { color:#388E3C; }
    .prac-link { display:flex;gap:10px;align-items:center;padding:8px;border-radius:8px;text-decoration:none;color:inherit;transition:background .2s; }
    .prac-link:hover { background:#F5F5F5; }
    .map-container { border-radius:14px;overflow:hidden;height:220px; }
    .filter-input { width:100%;padding:8px 12px;border:1px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.82rem;outline:none;box-sizing:border-box;margin-bottom:10px; }
    .filter-input:focus { border-color:#388E3C; }
    .section-empty { text-align:center;padding:12px;color:#757575;font-size:.82rem; }
    .ins-badges { display:flex;gap:3px;flex-wrap:wrap;margin-top:6px; }
    .ins-badge { padding:2px 8px;background:#E3F2FD;color:#1565C0;border-radius:100px;font-size:.62rem;font-weight:600; }
    .gallery-scroll { display:flex;gap:8px;overflow-x:auto;padding-bottom:6px;-webkit-overflow-scrolling:touch; }
    .gallery-item { width:140px;height:105px;border-radius:10px;overflow:hidden;flex-shrink:0;cursor:pointer;position:relative; }
    .gallery-item img { width:100%;height:100%;object-fit:cover;transition:transform .3s ease; }
    .gallery-item:hover img { transform:scale(1.15); }
    .gallery-item::after { content:'';position:absolute;inset:0;background:rgba(0,0,0,0);transition:background .3s;border-radius:10px; }
    .gallery-item:hover::after { background:rgba(0,0,0,.1); }

    /* Lightbox zoom */
    .lightbox { position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:1000;display:none;align-items:center;justify-content:center;cursor:pointer; }
    .lightbox.open { display:flex; }
    .lightbox img { max-width:90vw;max-height:90vh;border-radius:12px;box-shadow:0 8px 40px rgba(0,0,0,.5);animation:lbZoom .25s ease; }
    .lightbox-close { position:absolute;top:20px;right:24px;width:40px;height:40px;background:rgba(255,255,255,.15);border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center; }
    .lightbox-close svg { width:20px;height:20px;stroke:white; }
    .lightbox-nav { position:absolute;top:50%;transform:translateY(-50%);width:44px;height:44px;background:rgba(255,255,255,.15);border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center; }
    .lightbox-nav:hover { background:rgba(255,255,255,.3); }
    .lightbox-nav svg { width:20px;height:20px;stroke:white; }
    .lightbox-prev { left:16px; }
    .lightbox-next { right:16px; }
    @keyframes lbZoom { from{transform:scale(.8);opacity:0} to{transform:scale(1);opacity:1} }
    @media(max-width:768px) { .detail-grid{grid-template-columns:1fr;} .detail-cover{height:160px;} .detail-profile-bar{flex-direction:column;align-items:center;text-align:center;} .detail-profile-img{width:90px;height:90px;margin-top:-45px;} .detail-profile-actions{justify-content:center;} .status-badges{justify-content:center;} .ins-badges{justify-content:center;} }
</style>
@endsection

@section('content')
{{-- Cover + Profile bar (style Facebook) --}}
<div class="detail-wrap">
<div class="detail-cover">
    @if($coverImg)<img src="{{ $coverImg }}" alt="">@endif
</div>
<div class="detail-profile-bar">
    <img src="{{ $profileImg }}" alt="{{ $hosto->name }}" class="detail-profile-img">
    <div class="detail-profile-info">
        <div class="types">{{ $types->pluck('name_fr')->join(', ') }}</div>
        <h1>{{ $hosto->name }}</h1>
        <div class="location">{{ $hosto->city?->name_fr }} @if($hosto->address) — {{ $hosto->address }} @endif @if($hosto->quarter) ({{ $hosto->quarter }}) @endif</div>
        <div class="status-badges">
            @if($hosto->is_partner)<span class="status-badge" style="background:#E3F2FD;color:#1565C0;">Partenaire HOSTO</span>@endif
            @if($hosto->is_guard_service)<span class="status-badge" style="background:#FFF3E0;color:#E65100;">Garde</span>@endif
            @if($hosto->is_emergency_service)<span class="status-badge" style="background:#FFEBEE;color:#C62828;">Urgence</span>@endif
            @if($hosto->is_evacuation_service)<span class="status-badge" style="background:#F3E5F5;color:#6A1B9A;">Evacuation</span>@endif
            @if($hosto->is_home_care_service)<span class="status-badge" style="background:#E8F5E9;color:#2E7D32;">Domicile</span>@endif
            <span class="status-badge" style="background:{{ $hosto->is_public ? '#E3F2FD;color:#1565C0' : '#F3E5F5;color:#6A1B9A' }};">{{ $hosto->is_public ? 'Public' : 'Prive' }}</span>
        </div>
        @if($hosto->accepted_insurances)
        <div class="ins-badges">
            @foreach($hosto->accepted_insurances as $ins)<span class="ins-badge">{{ $ins }}</span>@endforeach
        </div>
        @endif
    </div>
    <div class="detail-profile-actions">
        <a href="/compte/rdv/{{ $hosto->slug }}" style="padding:8px 20px;background:#388E3C;color:white;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            Rendez-vous
        </a>
        <button onclick="toggleLike()" id="btnLike" style="padding:8px 16px;border:1px solid #EEE;border-radius:8px;font-size:.82rem;cursor:pointer;background:white;font-family:Poppins,sans-serif;display:flex;align-items:center;gap:4px;">
            <span id="likeIcon">{{ $userLiked ? '❤' : '♡' }}</span> {{ $userLiked ? 'Aime' : 'Aimer' }}
        </button>
    </div>
</div>
</div>{{-- /detail-wrap --}}

{{-- Description --}}
@if($hosto->description_fr)
<div class="section-block"><h3>A propos</h3><p style="font-size:.85rem;color:#424242;line-height:1.7;">{{ $hosto->description_fr }}</p></div>
@endif

<div class="detail-grid">
    <div>
        {{-- 1. Specialites --}}
        <div class="section-block">
            <h3>Specialites</h3>
            @if($specialties->isNotEmpty())
            <div class="specs-list">@foreach($specialties as $spec)<span class="spec-badge">{{ $spec->name_fr }}</span>@endforeach</div>
            @else
            <p style="font-size:.82rem;color:#757575;">Aucune specialite renseignee.</p>
            @endif
        </div>

        {{-- 2. Prestations --}}
        <div class="section-block">
            <h3 style="color:#388E3C;">Prestations</h3>
            <input type="text" placeholder="Filtrer les prestations..." oninput="filterList(this,'prestations')" class="filter-input">
            <div id="prestations">
                @forelse($services->get('prestation', collect()) as $svc)
                <div class="service-row filterable" data-text="{{ mb_strtolower($svc->name_fr) }}"><span>{{ $svc->name_fr }}</span><span style="color:#757575;font-size:.78rem;">@if($svc->pivot->tarif_min){{ number_format($svc->pivot->tarif_min,0,',',' ') }} - {{ number_format($svc->pivot->tarif_max,0,',',' ') }} XAF @endif</span></div>
                @empty
                <div class="section-empty">Aucune prestation.</div>
                @endforelse
            </div>
        </div>

        {{-- 3. Examens --}}
        <div class="section-block">
            <h3 style="color:#1565C0;">Examens</h3>
            <input type="text" placeholder="Filtrer les examens..." oninput="filterList(this,'examens')" class="filter-input">
            <div id="examens">
                @forelse($services->get('examen', collect()) as $svc)
                <div class="service-row filterable" data-text="{{ mb_strtolower($svc->name_fr) }}"><span>{{ $svc->name_fr }}</span><span style="color:#757575;font-size:.78rem;">@if($svc->pivot->tarif_min){{ number_format($svc->pivot->tarif_min,0,',',' ') }} - {{ number_format($svc->pivot->tarif_max,0,',',' ') }} XAF @endif</span></div>
                @empty
                <div class="section-empty">Aucun examen.</div>
                @endforelse
            </div>
        </div>

        {{-- 4. Soins --}}
        <div class="section-block">
            <h3 style="color:#E65100;">Soins</h3>
            <input type="text" placeholder="Filtrer les soins..." oninput="filterList(this,'soins')" class="filter-input">
            <div id="soins">
                @forelse($services->get('soin', collect()) as $svc)
                <div class="service-row filterable" data-text="{{ mb_strtolower($svc->name_fr) }}"><span>{{ $svc->name_fr }}</span><span style="color:#757575;font-size:.78rem;">@if($svc->pivot->tarif_min){{ number_format($svc->pivot->tarif_min,0,',',' ') }} - {{ number_format($svc->pivot->tarif_max,0,',',' ') }} XAF @endif</span></div>
                @empty
                <div class="section-empty">Aucun soin.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div>
        {{-- 5. Medecins --}}
        <div class="section-block">
            <h3>Medecins ({{ $practitioners->count() }})</h3>
            <input type="text" placeholder="Filtrer les medecins..." oninput="filterList(this,'medecins')" class="filter-input">
            <div id="medecins">
                @forelse($practitioners as $prac)
                <a href="/compte/medecin/{{ $prac->slug }}" class="prac-link filterable" data-text="{{ mb_strtolower($prac->full_name.' '.$prac->specialties->pluck('name_fr')->join(' ')) }}">
                    <div style="width:36px;height:36px;border-radius:8px;background:#E8F5E9;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#388E3C" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:.82rem;font-weight:600;">{{ $prac->full_name }}</div>
                        <div style="font-size:.68rem;color:#757575;">{{ $prac->specialties->pluck('name_fr')->join(', ') }}</div>
                    </div>
                    <div>
                        @if($prac->does_teleconsultation)<span style="padding:2px 6px;background:#E3F2FD;color:#1565C0;border-radius:100px;font-size:.58rem;font-weight:600;">TC</span>@endif
                        @if($prac->does_home_care)<span style="padding:2px 6px;background:#E8F5E9;color:#2E7D32;border-radius:100px;font-size:.58rem;font-weight:600;">Domicile</span>@endif
                    </div>
                </a>
                @empty
                <div class="section-empty">Aucun medecin.</div>
                @endforelse
            </div>
        </div>

        {{-- 6. Medicaments --}}
        @if($types->pluck('slug')->contains('pharmacie'))
        <div class="section-block">
            <h3>Medicaments</h3>
            <p style="font-size:.82rem;color:#757575;margin-bottom:8px;">Cette structure est une pharmacie.</p>
            <a href="/compte/medicaments" style="font-size:.82rem;color:#388E3C;font-weight:500;">Rechercher un medicament &rarr;</a>
        </div>
        @endif

        {{-- Mediatheque --}}
        @if($gallery->isNotEmpty())
        <div class="section-block">
            <h3>Mediatheque ({{ $gallery->count() }})</h3>
            <div class="gallery-scroll">
                @foreach($gallery as $idx => $media)
                <div class="gallery-item" onclick="openLightbox({{ $idx }})">
                    <img src="{{ $media->url }}" alt="{{ $media->alt_text ?: $hosto->name }}">
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="section-block">
            <h3>Contact</h3>
            @if($hosto->phone)<div class="contact-row"><a href="tel:{{ $hosto->phone }}">{{ $hosto->phone }}</a></div>@endif
            @if($hosto->phone2)<div class="contact-row"><a href="tel:{{ $hosto->phone2 }}">{{ $hosto->phone2 }}</a></div>@endif
            @if($hosto->email)<div class="contact-row"><a href="mailto:{{ $hosto->email }}">{{ $hosto->email }}</a></div>@endif
            @if($hosto->emergency_phone)<div class="contact-row" style="color:#E53935;font-weight:600;">Urgences : {{ $hosto->emergency_phone }}</div>@endif
        </div>

        @if($hours)
        <div class="section-block">
            <h3>Horaires</h3>
            @php $dayLabels = ['lun'=>'Lundi','mar'=>'Mardi','mer'=>'Mercredi','jeu'=>'Jeudi','ven'=>'Vendredi','sam'=>'Samedi','dim'=>'Dimanche']; @endphp
            @foreach($dayLabels as $key => $label)
            <div style="display:flex;justify-content:space-between;padding:3px 0;font-size:.82rem;">
                <span>{{ $label }}</span>
                @if(isset($hours[$key]))<span style="color:#757575;">{{ $hours[$key]['open'] }} - {{ $hours[$key]['close'] }}</span>@else<span style="color:#E53935;">Ferme</span>@endif
            </div>
            @endforeach
        </div>
        @endif

        @if($coords)
        <div class="section-block">
            <h3>Localisation</h3>
            <div id="structureMap" class="map-container"></div>
        </div>
        @endif
    </div>
</div>

<script>
@if($coords)
document.addEventListener('DOMContentLoaded', function() {
    const map = L.map('structureMap').setView([{{ $coords[0] }}, {{ $coords[1] }}], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OpenStreetMap',maxZoom:19}).addTo(map);
    L.marker([{{ $coords[0] }}, {{ $coords[1] }}]).addTo(map).bindPopup('<strong>{{ addslashes($hosto->name) }}</strong>').openPopup();
    setTimeout(()=>map.invalidateSize(),200);
});
@endif

// --- Filtre client simple (pas d'AJAX) ---
function filterList(input, containerId) {
    const q = input.value.trim().toLowerCase();
    const container = document.getElementById(containerId);
    const items = container.querySelectorAll('.filterable');
    let visible = 0;
    items.forEach(item => {
        const text = item.getAttribute('data-text') || '';
        const show = !q || text.includes(q);
        item.style.display = show ? '' : 'none';
        if (show) visible++;
    });
}

async function toggleLike() {
    try {
        const res = await fetch('/web/like/{{ $hosto->uuid }}', {method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','X-Requested-With':'XMLHttpRequest'}});
        if (res.status===401) { window.location.href='/compte/connexion'; return; }
        const d = (await res.json()).data;
        document.getElementById('btnLike').textContent = d.liked ? '❤ Aime' : '♡ Aimer';
    } catch(e) {}
}

// --- Lightbox ---
@if($gallery->isNotEmpty())
const galleryUrls = @json($gallery->pluck('url')->values());
let lbIndex = 0;

function openLightbox(idx) {
    lbIndex = idx;
    const lb = document.getElementById('lightbox');
    document.getElementById('lbImg').src = galleryUrls[lbIndex];
    lb.classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
    document.body.style.overflow = '';
}
function lbPrev(e) { e.stopPropagation(); lbIndex = (lbIndex - 1 + galleryUrls.length) % galleryUrls.length; document.getElementById('lbImg').src = galleryUrls[lbIndex]; }
function lbNext(e) { e.stopPropagation(); lbIndex = (lbIndex + 1) % galleryUrls.length; document.getElementById('lbImg').src = galleryUrls[lbIndex]; }
document.addEventListener('keydown', e => {
    if (!document.getElementById('lightbox').classList.contains('open')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') { lbIndex = (lbIndex - 1 + galleryUrls.length) % galleryUrls.length; document.getElementById('lbImg').src = galleryUrls[lbIndex]; }
    if (e.key === 'ArrowRight') { lbIndex = (lbIndex + 1) % galleryUrls.length; document.getElementById('lbImg').src = galleryUrls[lbIndex]; }
});
@endif
</script>

{{-- Lightbox overlay --}}
@if($gallery->isNotEmpty())
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()"><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    <button class="lightbox-nav lightbox-prev" onclick="lbPrev(event)"><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg></button>
    <img id="lbImg" src="" alt="" onclick="event.stopPropagation()">
    <button class="lightbox-nav lightbox-next" onclick="lbNext(event)"><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg></button>
</div>
@endif
@endsection
