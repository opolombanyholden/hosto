@extends('layouts.app')

@section('title', $practitioner->full_name . ' — HOSTO')
@section('description', ($practitioner->bio_fr ?: $practitioner->full_name . ', ' . $practitioner->specialties->pluck('name_fr')->join(', ')))

@section('breadcrumb')
<li><span class="sep">/</span> <a href="/annuaire">Annuaire</a></li>
<li><span class="sep">/</span> <a href="/annuaire/medecins">Medecins</a></li>
<li><span class="sep">/</span> <span class="current">{{ $practitioner->full_name }}</span></li>
@endsection

@section('styles')
<style>
    /* Cover */
    .prac-cover {
        position:relative; height:220px; overflow:hidden;
        background:linear-gradient(135deg, #0D47A1, #1565C0, #1E88E5);
        margin-top:0;
    }
    .prac-cover .back-btn {
        position:absolute; top:16px; left:16px; background:rgba(255,255,255,.15); color:white;
        border:none; padding:8px 16px; border-radius:100px; cursor:pointer;
        font-family:Poppins,sans-serif; font-size:.82rem; backdrop-filter:blur(4px);
        display:flex; align-items:center; gap:6px; text-decoration:none; z-index:3;
    }
    .prac-cover .back-btn:hover { background:rgba(255,255,255,.25); }
    .prac-cover-pattern {
        position:absolute; inset:0; opacity:.06;
        background-image:url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23fff'%3E%3Ccircle cx='20' cy='20' r='3'/%3E%3C/g%3E%3C/svg%3E");
    }
    .prac-cover-info {
        position:absolute; bottom:70px; left:0; right:0; text-align:center; color:white; z-index:2;
    }
    .prac-cover-info h2 { font-size:1.1rem; font-weight:600; opacity:.9; }

    /* Profile section */
    .prac-profile-wrap { max-width:900px; margin:0 auto; padding:0 24px; }
    .prac-profile {
        margin-top:-55px; display:flex; gap:20px; align-items:flex-end; flex-wrap:wrap;
        position:relative; z-index:4;
    }
    .prac-avatar {
        width:110px; height:110px; border-radius:22px; border:4px solid white;
        background:#E3F2FD; display:flex; align-items:center; justify-content:center;
        flex-shrink:0; box-shadow:0 4px 16px rgba(0,0,0,.12); overflow:hidden;
    }
    .prac-avatar img { width:100%; height:100%; object-fit:cover; }
    .prac-info { padding-bottom:8px; }
    .prac-title-label { font-size:.72rem; color:#1565C0; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
    .prac-name { font-size:1.5rem; font-weight:700; color:#1B2A1B; line-height:1.2; }
    .prac-specs { font-size:.88rem; color:#1565C0; font-weight:500; margin-top:2px; }
    .prac-meta { font-size:.78rem; color:#757575; margin-top:2px; }
    .prac-badges { display:flex; gap:8px; margin-top:10px; flex-wrap:wrap; }
    .prac-badge { padding:5px 14px; border-radius:100px; font-size:.72rem; font-weight:600; }
    .badge-telecon { background:#E3F2FD; color:#1565C0; }
    .badge-new { background:#E8F5E9; color:#2E7D32; }
    .badge-fee { background:#FFF3E0; color:#E65100; }
    .badge-reg { background:#F5F5F5; color:#757575; }

    /* Body */
    .prac-body { max-width:900px; margin:0 auto; padding:24px 24px 60px; }
    .prac-grid { display:grid; grid-template-columns:1.3fr .7fr; gap:28px; }

    .section-card { background:white; border:1px solid #EEE; border-radius:14px; padding:20px; margin-bottom:16px; }
    .section-title {
        font-size:.85rem; font-weight:600; color:#1565C0; margin-bottom:12px;
        display:flex; align-items:center; gap:8px;
    }
    .section-title svg { width:16px; height:16px; flex-shrink:0; }

    /* Contact */
    .contact-item { display:flex; align-items:center; gap:10px; padding:6px 0; font-size:.85rem; }
    .contact-item svg { width:16px; height:16px; color:#1565C0; flex-shrink:0; }
    .contact-item a { color:#1565C0; }

    /* Structures */
    .struct-link {
        display:flex; gap:12px; align-items:center; padding:10px; border-radius:10px;
        text-decoration:none; color:inherit; transition:background .2s; margin-bottom:4px;
    }
    .struct-link:hover { background:#F5F5F5; }
    .struct-icon {
        width:40px; height:40px; background:#E8F5E9; border-radius:10px;
        display:flex; align-items:center; justify-content:center; flex-shrink:0;
    }
    .struct-name { font-size:.85rem; font-weight:600; color:#1B2A1B; }
    .struct-city { font-size:.72rem; color:#757575; }
    .struct-types { font-size:.65rem; color:#388E3C; margin-top:1px; }

    /* Slots */
    .slot-day { margin-bottom:16px; }
    .slot-day-label {
        font-size:.8rem; font-weight:600; color:#1B2A1B; margin-bottom:8px;
        padding-bottom:6px; border-bottom:1px solid #F5F5F5;
    }
    .slots-row { display:flex; gap:8px; flex-wrap:wrap; }
    .slot-btn {
        padding:8px 18px; border:2px solid #E3F2FD; border-radius:8px; background:white;
        cursor:pointer; font-family:Poppins,sans-serif; font-size:.78rem; font-weight:500;
        color:#1565C0; transition:all .2s; display:inline-flex; align-items:center; gap:4px;
    }
    .slot-btn:hover { background:#E3F2FD; border-color:#1565C0; }
    .slot-btn.selected { background:#1565C0; color:white; border-color:#1565C0; }
    .slot-tc { font-size:.6rem; background:#E3F2FD; color:#1565C0; padding:1px 5px; border-radius:4px; }
    .slot-btn.selected .slot-tc { background:rgba(255,255,255,.2); color:white; }

    /* Booking form */
    .booking-form {
        display:none; margin-top:16px; padding:20px; background:#F8F9FA;
        border-radius:12px; border:1px solid #E3F2FD;
    }
    .booking-title { font-size:.88rem; font-weight:600; color:#1B2A1B; margin-bottom:12px; }
    .booking-slot-info { font-size:.82rem; color:#1565C0; font-weight:500; }
    .booking-field { margin-bottom:12px; }
    .booking-field label { display:block; font-size:.78rem; font-weight:500; color:#424242; margin-bottom:4px; }
    .booking-field input, .booking-field textarea {
        width:100%; padding:10px 14px; border:2px solid #EEE; border-radius:8px;
        font-family:Poppins,sans-serif; font-size:.85rem; outline:none;
    }
    .booking-field input:focus, .booking-field textarea:focus { border-color:#1565C0; }
    .btn-primary {
        padding:10px 24px; background:#1565C0; color:white; border:none; border-radius:8px;
        font-family:Poppins,sans-serif; font-size:.85rem; font-weight:600; cursor:pointer;
    }
    .btn-primary:hover { background:#0D47A1; }
    .btn-secondary {
        padding:10px 24px; border:1px solid #E0E0E0; border-radius:8px; background:white;
        cursor:pointer; font-family:Poppins,sans-serif; font-size:.85rem; color:#757575;
    }

    .no-slots { font-size:.85rem; color:#757575; text-align:center; padding:20px; }

    @media(max-width:768px) {
        .prac-cover { height:180px; }
        .prac-profile { flex-direction:column; align-items:center; text-align:center; }
        .prac-avatar { width:90px; height:90px; }
        .prac-badges { justify-content:center; }
        .prac-grid { grid-template-columns:1fr; }
        .prac-body { padding:20px 16px 40px; }
    }
</style>
@endsection

@section('content')
@php
    $hasSlots = $slots->isNotEmpty();
    $structTypes = fn($s) => $s->structureTypes->pluck('name_fr')->join(', ');
@endphp

<!-- Cover -->
<div class="prac-cover">
    <div class="prac-cover-pattern"></div>
    <a href="/annuaire/medecins" class="back-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Medecins
    </a>
    <div class="prac-cover-info">
        <h2>{{ $practitioner->specialties->pluck('name_fr')->join(' · ') }}</h2>
    </div>
</div>

<!-- Profile -->
<div class="prac-profile-wrap">
    <div class="prac-profile">
        <div class="prac-avatar">
            @if($practitioner->profile_image_url)
                <img src="{{ $practitioner->profile_image_url }}" alt="{{ $practitioner->full_name }}">
            @else
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#1565C0" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            @endif
        </div>
        <div class="prac-info">
            <div class="prac-title-label">{{ ucfirst($practitioner->practitioner_type) }}</div>
            <div class="prac-name">{{ $practitioner->full_name }}</div>
            <div class="prac-specs">{{ $practitioner->specialties->pluck('name_fr')->join(' · ') }}</div>
            @if($practitioner->registration_number)
                <div class="prac-meta">N° {{ $practitioner->registration_number }}</div>
            @endif
            <div class="prac-badges">
                @if($practitioner->does_teleconsultation)
                    <span class="prac-badge badge-telecon">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:3px;"><path d="M15.05 5A5 5 0 0 1 19 8.95M15.05 1A9 9 0 0 1 23 8.94"/><path d="M22 16.92v3a2 2 0 0 1-2.18 2"/></svg>
                        Teleconsultation
                    </span>
                @endif
                @if($practitioner->accepts_new_patients)
                    <span class="prac-badge badge-new">Accepte nouveaux patients</span>
                @endif
                @if($practitioner->consultation_fee_min)
                    <span class="prac-badge badge-fee">{{ number_format($practitioner->consultation_fee_min,0,',',' ') }} - {{ number_format($practitioner->consultation_fee_max,0,',',' ') }} XAF</span>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Body -->
<div class="prac-body">
    <div class="prac-grid">
        <!-- Left column -->
        <div>
            {{-- Bio --}}
            @if($practitioner->bio_fr)
            <div class="section-card">
                <div class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                    A propos
                </div>
                <p style="font-size:.88rem;color:#424242;line-height:1.7;">{{ $practitioner->bio_fr }}</p>
            </div>
            @endif

            {{-- Time slots --}}
            <div class="section-card">
                <div class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    Creneaux disponibles
                </div>

                @if($hasSlots)
                    @foreach($slots as $date => $daySlots)
                    <div class="slot-day">
                        <div class="slot-day-label">{{ \Carbon\Carbon::parse($date)->translatedFormat('l d F') }}</div>
                        <div class="slots-row">
                            @foreach($daySlots as $slot)
                            <button class="slot-btn" onclick="selectSlot(this, '{{ $slot->uuid }}', '{{ substr($slot->start_time, 0, 5) }}', '{{ addslashes($slot->structure->name) }}')" data-uuid="{{ $slot->uuid }}">
                                {{ substr($slot->start_time, 0, 5) }}
                                @if($slot->is_teleconsultation) <span class="slot-tc">TC</span> @endif
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endforeach

                    {{-- Booking form --}}
                    <div class="booking-form" id="bookingForm">
                        <div class="booking-title">Prendre rendez-vous</div>
                        <div class="booking-slot-info" id="bookingSlotInfo"></div>
                        <input type="hidden" id="bookingSlotUuid">
                        <div class="booking-field" style="margin-top:12px;">
                            <label>Motif de consultation</label>
                            <textarea id="bookingReason" rows="2" placeholder="Decrivez brievement le motif de votre consultation..." maxlength="500"></textarea>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <button onclick="submitBooking()" class="btn-primary">Confirmer</button>
                            <button onclick="cancelBooking()" class="btn-secondary">Annuler</button>
                        </div>
                        <div id="bookingMessage" style="display:none;margin-top:12px;padding:10px;border-radius:8px;font-size:.82rem;"></div>
                    </div>
                @else
                    <div class="no-slots">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#BDBDBD" stroke-width="1.5" style="margin-bottom:8px;"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        <p>Aucun creneau disponible pour le moment.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right column (sidebar) -->
        <div>
            {{-- Contact --}}
            <div class="section-card">
                <div class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/></svg>
                    Contact
                </div>
                @if($practitioner->phone)
                <div class="contact-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/></svg>
                    <a href="tel:{{ $practitioner->phone }}">{{ $practitioner->phone }}</a>
                </div>
                @endif
                @if($practitioner->email)
                <div class="contact-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <a href="mailto:{{ $practitioner->email }}">{{ $practitioner->email }}</a>
                </div>
                @endif
                @if($practitioner->languages)
                <div class="contact-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    <span style="color:#424242;">{{ implode(', ', $practitioner->languages) }}</span>
                </div>
                @endif
                @if(!$practitioner->phone && !$practitioner->email)
                <p style="font-size:.82rem;color:#757575;">Aucune information de contact disponible.</p>
                @endif
            </div>

            {{-- Structures --}}
            @if($practitioner->structures->isNotEmpty())
            <div class="section-card">
                <div class="section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Structures d'exercice
                </div>
                @foreach($practitioner->structures as $struct)
                <a href="/annuaire/{{ $struct->slug }}" class="struct-link">
                    <div class="struct-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#388E3C" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    </div>
                    <div>
                        <div class="struct-name">{{ $struct->name }}</div>
                        <div class="struct-city">{{ $struct->city?->name_fr }}</div>
                        <div class="struct-types">{{ $structTypes($struct) }}</div>
                    </div>
                </a>
                @endforeach
            </div>
            @endif

            {{-- Quick actions --}}
            <div class="section-card" style="text-align:center;">
                <a href="/annuaire/medecins" style="display:inline-block;font-size:.82rem;color:#1565C0;font-weight:500;text-decoration:none;">Voir tous les medecins</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let selectedSlot = null;

function selectSlot(btn, uuid, time, structure) {
    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    selectedSlot = uuid;
    document.getElementById('bookingSlotUuid').value = uuid;
    document.getElementById('bookingSlotInfo').textContent = time + ' — ' + structure;
    document.getElementById('bookingForm').style.display = 'block';
    document.getElementById('bookingMessage').style.display = 'none';
    document.getElementById('bookingForm').scrollIntoView({behavior:'smooth', block:'nearest'});
}

function cancelBooking() {
    document.getElementById('bookingForm').style.display = 'none';
    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
    selectedSlot = null;
}

async function submitBooking() {
    const uuid = document.getElementById('bookingSlotUuid').value;
    const reason = document.getElementById('bookingReason').value.trim();
    const msg = document.getElementById('bookingMessage');

    try {
        const res = await fetch('/web/rdv/book', {
            method:'POST',
            headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','X-Requested-With':'XMLHttpRequest'},
            body:JSON.stringify({time_slot_uuid:uuid, reason:reason||null}),
        });

        if (res.status === 401) { window.location.href = '/compte/connexion'; return; }

        const data = await res.json();
        if (res.ok) {
            msg.style.display = 'block'; msg.style.background = '#E8F5E9'; msg.style.color = '#2E7D32';
            msg.innerHTML = 'Rendez-vous confirme ! <a href="/compte/rendez-vous" style="color:#2E7D32;font-weight:600;">Voir mes rendez-vous</a>';
            const btn = document.querySelector(`[data-uuid="${uuid}"]`);
            if (btn) { btn.disabled = true; btn.style.opacity = '.4'; btn.style.cursor = 'default'; }
        } else {
            msg.style.display = 'block'; msg.style.background = '#FFEBEE'; msg.style.color = '#C62828';
            msg.textContent = data.error?.message || 'Erreur lors de la reservation.';
        }
    } catch(e) {
        msg.style.display = 'block'; msg.style.background = '#FFEBEE'; msg.style.color = '#C62828';
        msg.textContent = 'Erreur de connexion.';
    }
}
</script>
@endsection
