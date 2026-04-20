@extends('layouts.app')

@section('title', $practitioner->full_name . ' — HOSTO')
@section('breadcrumb')
<li><span class="sep">/</span> <a href="/annuaire">Annuaire</a></li>
<li><span class="sep">/</span> <a href="/annuaire/medecins">Medecins</a></li>
<li><span class="sep">/</span> <span class="current">{{ $practitioner->full_name }}</span></li>
@endsection

@section('styles')
<style>
    .prac-detail { max-width:900px; margin:0 auto; padding:32px 24px 60px; }
    .prac-hero { display:flex; gap:24px; align-items:start; margin-bottom:32px; flex-wrap:wrap; }
    .prac-avatar { width:100px; height:100px; border-radius:20px; background:#E3F2FD; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .prac-name { font-size:1.5rem; font-weight:700; color:#1B2A1B; }
    .prac-specs { font-size:.85rem; color:#1565C0; margin-top:4px; }
    .prac-meta { font-size:.82rem; color:#757575; margin-top:4px; }
    .prac-badges { display:flex; gap:8px; margin-top:8px; flex-wrap:wrap; }
    .prac-badge { padding:4px 12px; border-radius:100px; font-size:.72rem; font-weight:600; }
    .section-block { background:white; border:1px solid #EEE; border-radius:14px; padding:24px; margin-bottom:20px; }
    .section-block h3 { font-size:.9rem; font-weight:600; color:#1565C0; margin-bottom:12px; }
    .slot-day { margin-bottom:16px; }
    .slot-day-label { font-size:.82rem; font-weight:600; color:#1B2A1B; margin-bottom:8px; }
    .slots-row { display:flex; gap:8px; flex-wrap:wrap; }
    .slot-btn { padding:8px 16px; border:2px solid #E3F2FD; border-radius:8px; background:white; cursor:pointer; font-family:Poppins,sans-serif; font-size:.78rem; font-weight:500; color:#1565C0; transition:all .2s; }
    .slot-btn:hover { background:#E3F2FD; border-color:#1565C0; }
    .slot-btn.selected { background:#1565C0; color:white; border-color:#1565C0; }
    .booking-form { display:none; margin-top:16px; padding:20px; background:#F5F5F5; border-radius:12px; }
    @media(max-width:768px) { .prac-hero{flex-direction:column;align-items:center;text-align:center;} .prac-avatar{width:80px;height:80px;} .prac-detail{padding:20px 16px 40px;} }
</style>
@endsection

@section('content')
<div class="prac-detail">
    <div class="prac-hero">
        <div class="prac-avatar">
            @if($practitioner->profile_image_url)
                <img src="{{ $practitioner->profile_image_url }}" alt="{{ $practitioner->full_name }}" style="width:100%;height:100%;object-fit:cover;border-radius:20px;">
            @else
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#1565C0" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            @endif
        </div>
        <div>
            <div class="prac-name">{{ $practitioner->full_name }}</div>
            <div class="prac-specs">{{ $practitioner->specialties->pluck('name_fr')->join(', ') }}</div>
            <div class="prac-meta">{{ ucfirst($practitioner->practitioner_type) }}</div>
            <div class="prac-badges">
                @if($practitioner->does_teleconsultation) <span class="prac-badge" style="background:#E3F2FD;color:#1565C0;">Teleconsultation</span> @endif
                @if($practitioner->accepts_new_patients) <span class="prac-badge" style="background:#E8F5E9;color:#2E7D32;">Accepte nouveaux patients</span> @endif
                @if($practitioner->consultation_fee_min) <span class="prac-badge" style="background:#FFF3E0;color:#E65100;">{{ number_format($practitioner->consultation_fee_min,0,',',' ') }} - {{ number_format($practitioner->consultation_fee_max,0,',',' ') }} XAF</span> @endif
            </div>
        </div>
    </div>

    {{-- Bio --}}
    @if($practitioner->bio_fr)
    <div class="section-block"><h3>A propos</h3><p style="font-size:.88rem;color:#424242;line-height:1.7;">{{ $practitioner->bio_fr }}</p></div>
    @endif

    {{-- Contact --}}
    <div class="section-block">
        <h3>Contact</h3>
        @if($practitioner->phone) <div style="font-size:.85rem;margin-bottom:4px;"><a href="tel:{{ $practitioner->phone }}" style="color:#1565C0;">{{ $practitioner->phone }}</a></div> @endif
        @if($practitioner->email) <div style="font-size:.85rem;"><a href="mailto:{{ $practitioner->email }}" style="color:#1565C0;">{{ $practitioner->email }}</a></div> @endif
        @if($practitioner->languages) <div style="font-size:.78rem;color:#757575;margin-top:8px;">Langues : {{ implode(', ', $practitioner->languages) }}</div> @endif
    </div>

    {{-- Structures --}}
    <div class="section-block">
        <h3>Structures d'exercice</h3>
        @foreach($practitioner->structures as $struct)
        <a href="/annuaire/{{ $struct->slug }}" style="display:flex;gap:10px;align-items:center;padding:8px;border-radius:8px;transition:background .2s;text-decoration:none;color:inherit;margin-bottom:4px;">
            <div style="width:36px;height:36px;background:#E8F5E9;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#388E3C" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            </div>
            <div>
                <div style="font-size:.85rem;font-weight:600;">{{ $struct->name }}</div>
                <div style="font-size:.72rem;color:#757575;">{{ $struct->city?->name_fr }}</div>
            </div>
        </a>
        @endforeach
    </div>

    {{-- Available time slots --}}
    @if($slots->isNotEmpty())
    <div class="section-block">
        <h3>Creneaux disponibles</h3>
        @foreach($slots as $date => $daySlots)
        <div class="slot-day">
            <div class="slot-day-label">{{ \Carbon\Carbon::parse($date)->translatedFormat('l d F Y') }}</div>
            <div class="slots-row">
                @foreach($daySlots as $slot)
                <button class="slot-btn" onclick="selectSlot(this, '{{ $slot->uuid }}', '{{ $slot->start_time }}', '{{ $slot->structure->name }}')" data-uuid="{{ $slot->uuid }}">
                    {{ substr($slot->start_time, 0, 5) }}
                    @if($slot->is_teleconsultation) <span style="font-size:.6rem;">TC</span> @endif
                </button>
                @endforeach
            </div>
        </div>
        @endforeach

        {{-- Booking form --}}
        <div class="booking-form" id="bookingForm">
            <div style="font-size:.85rem;font-weight:600;margin-bottom:12px;">Prendre rendez-vous — <span id="bookingSlotInfo"></span></div>
            <input type="hidden" id="bookingSlotUuid">
            <div style="margin-bottom:12px;">
                <label style="font-size:.78rem;font-weight:500;color:#424242;display:block;margin-bottom:4px;">Motif de consultation</label>
                <input type="text" id="bookingReason" placeholder="Ex: douleur thoracique, bilan annuel..." style="width:100%;padding:10px 14px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;outline:none;">
            </div>
            <div style="display:flex;gap:8px;">
                <button onclick="submitBooking()" style="padding:10px 24px;background:#1565C0;color:white;border:none;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;font-weight:600;cursor:pointer;">Confirmer le rendez-vous</button>
                <button onclick="cancelBooking()" style="padding:10px 24px;border:1px solid #EEE;border-radius:8px;background:white;cursor:pointer;font-family:Poppins,sans-serif;font-size:.85rem;">Annuler</button>
            </div>
            <div id="bookingMessage" style="display:none;margin-top:12px;padding:10px;border-radius:8px;font-size:.82rem;"></div>
        </div>
    </div>
    @else
    <div class="section-block"><h3>Creneaux</h3><p style="font-size:.85rem;color:#757575;">Aucun creneau disponible pour le moment.</p></div>
    @endif
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
    document.getElementById('bookingSlotInfo').textContent = `${time} — ${structure}`;
    document.getElementById('bookingForm').style.display = 'block';
    document.getElementById('bookingMessage').style.display = 'none';
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
        const res = await fetch(`${API}/rdv/appointments`, {
            method: 'POST',
            headers: {'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({time_slot_uuid: uuid, reason: reason || null}),
        });

        if (res.status === 401) { window.location.href = '/compte/connexion'; return; }

        const data = await res.json();
        if (res.ok) {
            msg.style.display = 'block'; msg.style.background = '#E8F5E9'; msg.style.color = '#2E7D32';
            msg.textContent = 'Rendez-vous pris avec succes ! Consultez vos rendez-vous dans votre espace.';
            // Disable the selected slot button.
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
