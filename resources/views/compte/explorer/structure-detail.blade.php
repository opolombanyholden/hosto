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
    .detail-cover { position:relative; height:200px; overflow:hidden; background:linear-gradient(135deg,#2E7D32,#43A047); border-radius:14px; margin-bottom:16px; }
    .detail-cover img { width:100%;height:100%;object-fit:cover; }
    .detail-profile { display:flex;gap:16px;align-items:flex-end;margin-top:-40px;margin-bottom:16px;position:relative;z-index:2; }
    .detail-profile img { width:80px;height:80px;border-radius:16px;border:3px solid white;object-fit:cover;background:#E8F5E9;box-shadow:0 2px 8px rgba(0,0,0,.1); }
    .detail-profile-info .types { font-size:.72rem;color:#388E3C;font-weight:600; }
    .detail-profile-info h1 { font-size:1.2rem;font-weight:700;color:#1B2A1B; }
    .detail-profile-info .location { font-size:.82rem;color:#757575; }
    .status-badges { display:flex;gap:6px;flex-wrap:wrap;margin-top:6px; }
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
    .ins-badges { display:flex;gap:3px;flex-wrap:wrap;margin-top:6px; }
    .ins-badge { padding:2px 8px;background:#E3F2FD;color:#1565C0;border-radius:100px;font-size:.62rem;font-weight:600; }
    @media(max-width:768px) { .detail-grid{grid-template-columns:1fr;} .detail-cover{height:140px;} .detail-profile img{width:64px;height:64px;} }
</style>
@endsection

@section('content')
<div class="detail-cover">
    @if($coverImg)<img src="{{ $coverImg }}" alt="">@endif
</div>

<div class="detail-profile">
    <img src="{{ $profileImg }}" alt="{{ $hosto->name }}">
    <div class="detail-profile-info">
        <div class="types">{{ $types->pluck('name_fr')->join(', ') }}</div>
        <h1>{{ $hosto->name }}</h1>
        <div class="location">{{ $hosto->city?->name_fr }} @if($hosto->address) — {{ $hosto->address }} @endif</div>
        <div class="status-badges">
            @if($hosto->is_partner)<span class="status-badge" style="background:#E3F2FD;color:#1565C0;">Partenaire</span>@endif
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
</div>

{{-- Action buttons --}}
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
    <a href="/compte/rdv/{{ $hosto->slug }}" style="padding:8px 18px;background:#388E3C;color:white;border-radius:100px;font-size:.82rem;font-weight:600;text-decoration:none;">Prendre rendez-vous</a>
    <button onclick="toggleLike()" id="btnLike" style="padding:8px 18px;border:1px solid #EEE;border-radius:100px;font-size:.82rem;cursor:pointer;background:white;font-family:Poppins,sans-serif;">{{ $userLiked ? '❤ Aime' : '♡ Aimer' }}</button>
</div>

<div class="detail-grid">
    <div>
        @if($hosto->description_fr)
        <div class="section-block"><h3>A propos</h3><p style="font-size:.85rem;color:#424242;line-height:1.7;">{{ $hosto->description_fr }}</p></div>
        @endif

        @if($specialties->isNotEmpty())
        <div class="section-block"><h3>Specialites</h3><div class="specs-list">@foreach($specialties as $spec)<span class="spec-badge">{{ $spec->name_fr }}</span>@endforeach</div></div>
        @endif

        @if($services->isNotEmpty())
        <div class="section-block">
            <h3>Services et tarifs</h3>
            @foreach($services as $category => $svcs)
                <div style="font-size:.7rem;font-weight:600;color:#388E3C;margin-top:10px;text-transform:uppercase;">{{ $catLabels[$category] ?? $category }}</div>
                @foreach($svcs as $svc)
                <div class="service-row">
                    <span>{{ $svc->name_fr }}</span>
                    <span style="color:#757575;">@if($svc->pivot->tarif_min){{ number_format($svc->pivot->tarif_min,0,',',' ') }} - {{ number_format($svc->pivot->tarif_max,0,',',' ') }} XAF @endif</span>
                </div>
                @endforeach
            @endforeach
        </div>
        @endif

        <div class="section-block">
            <h3>Medecins</h3>
            <div style="margin-bottom:10px;">
                <input type="text" id="pracSearch" placeholder="Rechercher un medecin..." oninput="debouncePrac()" style="width:100%;padding:8px 12px;border:1px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.82rem;outline:none;box-sizing:border-box;">
            </div>
            <div id="pracList"></div>
            <div id="pracEmpty" style="display:none;text-align:center;padding:16px;color:#757575;font-size:.82rem;">Aucun medecin.</div>
            <div id="pracPagination" style="display:flex;justify-content:center;gap:4px;padding:8px 0;"></div>
        </div>

        @if($recommendations->isNotEmpty())
        <div class="section-block">
            <h3>Recommandations</h3>
            @foreach($recommendations as $reco)
            <div style="padding:10px;background:#FAFAFA;border-radius:8px;margin-bottom:6px;">
                <p style="font-size:.82rem;color:#424242;">{{ $reco->content }}</p>
                <div style="font-size:.68rem;color:#757575;margin-top:4px;">{{ $reco->user->name }} — {{ $reco->approved_at?->format('d/m/Y') }}</div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <div>
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

// Medecins AJAX
let pracTimer;
function debouncePrac() { clearTimeout(pracTimer); pracTimer = setTimeout(()=>loadPrac(), 300); }
loadPrac();
async function loadPrac(page) {
    const q = document.getElementById('pracSearch').value.trim();
    const params = new URLSearchParams({per_page:'5', page: page||1});
    if (q) params.set('q', q);
    try {
        const res = await fetch(`/compte/api/structure/{{ $hosto->slug }}/medecins?${params}`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        const data = await res.json();
        const list = document.getElementById('pracList');
        const empty = document.getElementById('pracEmpty');
        list.innerHTML = '';
        if (!data.data.length) { empty.style.display='block'; } else {
            empty.style.display='none';
            data.data.forEach(p => {
                let badges = '';
                if (p.does_teleconsultation) badges += '<span style="padding:2px 6px;background:#E3F2FD;color:#1565C0;border-radius:100px;font-size:.58rem;font-weight:600;">TC</span> ';
                if (p.does_home_care) badges += '<span style="padding:2px 6px;background:#E8F5E9;color:#2E7D32;border-radius:100px;font-size:.58rem;font-weight:600;">Domicile</span>';
                list.insertAdjacentHTML('beforeend', `<a href="/compte/medecin/${p.slug}" class="prac-link"><div style="width:36px;height:36px;border-radius:8px;background:#E8F5E9;display:flex;align-items:center;justify-content:center;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#388E3C" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div style="flex:1;"><div style="font-size:.82rem;font-weight:600;">${p.full_name}</div><div style="font-size:.68rem;color:#757575;">${(p.specialties||[]).join(', ')}</div></div><div>${badges}</div></a>`);
            });
        }
        // Pagination
        const pg = document.getElementById('pracPagination'); pg.innerHTML = '';
        if (data.meta?.last_page > 1) {
            for (let i=1;i<=data.meta.last_page;i++) {
                const btn=document.createElement('button'); btn.textContent=i;
                btn.style.cssText='padding:4px 10px;border:1px solid #EEE;border-radius:6px;background:white;font-family:Poppins,sans-serif;font-size:.72rem;cursor:pointer;';
                if(i===data.meta.current_page) btn.style.cssText+='background:#388E3C;color:white;border-color:#388E3C;';
                btn.onclick=()=>loadPrac(i); pg.appendChild(btn);
            }
        }
    } catch(e) {}
}

async function toggleLike() {
    try {
        const res = await fetch('/web/like/{{ $hosto->uuid }}', {method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','X-Requested-With':'XMLHttpRequest'}});
        if (res.status===401) { window.location.href='/compte/connexion'; return; }
        const d = (await res.json()).data;
        document.getElementById('btnLike').textContent = d.liked ? '❤ Aime' : '♡ Aimer';
    } catch(e) {}
}
</script>
@endsection
