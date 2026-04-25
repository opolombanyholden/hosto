@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', $practitioner->full_name) @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'medecins']) @endsection

@section('breadcrumb')
<span style="color:#BDBDBD;margin:0 6px;">/</span>
<a href="/compte/medecins" style="color:#388E3C;text-decoration:none;font-weight:500;">Medecins</a>
<span style="color:#BDBDBD;margin:0 6px;">/</span>
<span style="color:#424242;">{{ $practitioner->full_name }}</span>
@endsection

@section('styles')
@php $hasSlots = $slots->isNotEmpty(); @endphp
<style>
    .prac-cover { height:160px;background:linear-gradient(135deg,#0D47A1,#1E88E5);border-radius:14px;margin-bottom:12px;position:relative;overflow:hidden; }
    .prac-profile { display:flex;gap:16px;align-items:flex-end;margin-top:-40px;margin-bottom:16px;position:relative;z-index:2; }
    .prac-avatar { width:80px;height:80px;border-radius:50%;border:3px solid white;background:#E3F2FD;display:flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1); }
    .prac-avatar img { width:100%;height:100%;object-fit:cover; }
    .prac-name { font-size:1.15rem;font-weight:700;color:#1B2A1B; }
    .prac-specs { font-size:.85rem;color:#1565C0;font-weight:500; }
    .prac-meta { font-size:.75rem;color:#757575; }
    .prac-badges { display:flex;gap:6px;margin-top:6px;flex-wrap:wrap; }
    .prac-badge { padding:4px 12px;border-radius:100px;font-size:.68rem;font-weight:600; }
    .detail-grid { display:grid;grid-template-columns:1.3fr .7fr;gap:20px; }
    .section-block { background:white;border:1px solid #EEE;border-radius:14px;padding:18px;margin-bottom:14px; }
    .section-block h3 { font-size:.85rem;font-weight:600;color:#1565C0;margin-bottom:10px; }
    .slot-day { margin-bottom:12px; }
    .slot-day-label { font-size:.78rem;font-weight:600;color:#1B2A1B;margin-bottom:6px;padding-bottom:4px;border-bottom:1px solid #F5F5F5; }
    .slots-row { display:flex;gap:6px;flex-wrap:wrap; }
    .slot-btn { padding:6px 14px;border:2px solid #E3F2FD;border-radius:6px;background:white;cursor:pointer;font-family:Poppins,sans-serif;font-size:.75rem;font-weight:500;color:#1565C0;transition:all .2s; }
    .slot-btn:hover { background:#E3F2FD;border-color:#1565C0; }
    .slot-btn.selected { background:#1565C0;color:white;border-color:#1565C0; }
    .booking-form { display:none;margin-top:14px;padding:16px;background:#F8F9FA;border-radius:10px;border:1px solid #E3F2FD; }
    .contact-row { display:flex;align-items:center;gap:8px;padding:4px 0;font-size:.82rem; }
    .contact-row a { color:#1565C0; }
    .struct-link { display:flex;gap:10px;align-items:center;padding:8px;border-radius:8px;text-decoration:none;color:inherit;transition:background .2s; }
    .struct-link:hover { background:#F5F5F5; }
    .pub-item { padding:14px 0;border-bottom:1px solid #F5F5F5; }
    .pub-item:last-child { border-bottom:none; }
    .pub-type { padding:2px 8px;border-radius:100px;font-size:.62rem;font-weight:600; }
    @media(max-width:768px) { .detail-grid{grid-template-columns:1fr;} .prac-cover{height:120px;} .prac-avatar{width:64px;height:64px;} }
</style>
@endsection

@section('content')
<div class="prac-cover"></div>

<div class="prac-profile">
    <div class="prac-avatar">
        @if($practitioner->profile_image_url)<img src="{{ $practitioner->profile_image_url }}" alt="">@else
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#1565C0" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>@endif
    </div>
    <div>
        <div class="prac-name">{{ $practitioner->full_name }}</div>
        <div class="prac-specs">{{ $practitioner->specialties->pluck('name_fr')->join(' · ') }}</div>
        <div class="prac-meta">{{ ucfirst($practitioner->practitioner_type) }}@if($practitioner->registration_number) — N° {{ $practitioner->registration_number }}@endif</div>
        <div class="prac-badges">
            @if($practitioner->does_teleconsultation)<span class="prac-badge" style="background:#E3F2FD;color:#1565C0;">Teleconsultation</span>@endif
            @if($practitioner->does_home_care)<span class="prac-badge" style="background:#E8F5E9;color:#2E7D32;">Soins a domicile</span>@endif
            @if($practitioner->accepts_new_patients)<span class="prac-badge" style="background:#E8F5E9;color:#2E7D32;">Nouveaux patients</span>@endif
            @if($practitioner->consultation_fee_min)<span class="prac-badge" style="background:#FFF3E0;color:#E65100;">{{ number_format($practitioner->consultation_fee_min,0,',',' ') }} - {{ number_format($practitioner->consultation_fee_max,0,',',' ') }} XAF</span>@endif
        </div>
    </div>
</div>

<div class="detail-grid">
    <div>
        @if($practitioner->bio_fr)
        <div class="section-block"><h3>A propos</h3><p style="font-size:.85rem;color:#424242;line-height:1.7;">{{ $practitioner->bio_fr }}</p></div>
        @endif

        <div class="section-block">
            <h3>Creneaux disponibles</h3>
            @if($hasSlots)
                @foreach($slots as $date => $daySlots)
                <div class="slot-day">
                    <div class="slot-day-label">{{ \Carbon\Carbon::parse($date)->translatedFormat('l d F') }}</div>
                    <div class="slots-row">
                        @foreach($daySlots as $slot)
                        <button class="slot-btn" onclick="selectSlot(this,'{{ $slot->uuid }}','{{ substr($slot->start_time,0,5) }}','{{ addslashes($slot->structure->name) }}')" data-uuid="{{ $slot->uuid }}">{{ substr($slot->start_time,0,5) }}@if($slot->is_teleconsultation) <span style="font-size:.6rem;">TC</span>@endif</button>
                        @endforeach
                    </div>
                </div>
                @endforeach
                <div class="booking-form" id="bookingForm">
                    <div style="font-size:.85rem;font-weight:600;margin-bottom:8px;">Prendre rendez-vous</div>
                    <div style="font-size:.82rem;color:#1565C0;" id="bookingSlotInfo"></div>
                    <input type="hidden" id="bookingSlotUuid">
                    <div style="margin-top:10px;"><textarea id="bookingReason" rows="2" placeholder="Motif..." maxlength="500" style="width:100%;padding:8px 12px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.82rem;outline:none;box-sizing:border-box;"></textarea></div>
                    <div style="display:flex;gap:8px;margin-top:8px;">
                        <button onclick="submitBooking()" style="padding:8px 18px;background:#1565C0;color:white;border:none;border-radius:8px;font-family:Poppins,sans-serif;font-size:.82rem;font-weight:600;cursor:pointer;">Confirmer</button>
                        <button onclick="cancelBooking()" style="padding:8px 18px;border:1px solid #EEE;border-radius:8px;background:white;cursor:pointer;font-family:Poppins,sans-serif;font-size:.82rem;">Annuler</button>
                    </div>
                    <div id="bookingMessage" style="display:none;margin-top:10px;padding:8px;border-radius:8px;font-size:.82rem;"></div>
                </div>
            @else
                <p style="text-align:center;color:#757575;font-size:.82rem;padding:16px;">Aucun creneau disponible.</p>
            @endif
        </div>

        @if($publications->isNotEmpty())
        <div class="section-block">
            <h3>Publications</h3>
            @foreach($publications as $pub)
            @php
                $tLabels = ['activity'=>'Activite','research'=>'Travaux','tip'=>'Conseil','video'=>'Video'];
                $tBg = ['activity'=>'#E8F5E9;color:#2E7D32','research'=>'#E3F2FD;color:#1565C0','tip'=>'#FFF3E0;color:#E65100','video'=>'#F3E5F5;color:#6A1B9A'];
            @endphp
            <div class="pub-item">
                <span class="pub-type" style="background:{{ $tBg[$pub->type] ?? '#F5F5F5;color:#757575' }};">{{ $tLabels[$pub->type] ?? $pub->type }}</span>
                @if($pub->title)<div style="font-size:.85rem;font-weight:600;margin-top:4px;">{{ $pub->title }}</div>@endif
                <p style="font-size:.82rem;color:#424242;margin-top:4px;line-height:1.6;">{{ Str::limit($pub->content, 200) }}</p>
                <div style="font-size:.68rem;color:#BDBDBD;margin-top:4px;">{{ $pub->published_at?->format('d/m/Y') }}</div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <div>
        <div class="section-block">
            <h3>Contact</h3>
            @if($practitioner->phone)<div class="contact-row"><a href="tel:{{ $practitioner->phone }}">{{ $practitioner->phone }}</a></div>@endif
            @if($practitioner->email)<div class="contact-row"><a href="mailto:{{ $practitioner->email }}">{{ $practitioner->email }}</a></div>@endif
            @if($practitioner->languages)<div class="contact-row" style="color:#424242;">{{ implode(', ', $practitioner->languages) }}</div>@endif
        </div>

        @if($practitioner->structures->isNotEmpty())
        <div class="section-block">
            <h3>Structures</h3>
            @foreach($practitioner->structures as $struct)
            <a href="/compte/structure/{{ $struct->slug }}" class="struct-link">
                <div style="width:32px;height:32px;border-radius:8px;background:#E8F5E9;display:flex;align-items:center;justify-content:center;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#388E3C" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                </div>
                <div>
                    <div style="font-size:.82rem;font-weight:600;">{{ $struct->name }}</div>
                    <div style="font-size:.68rem;color:#757575;">{{ $struct->city?->name_fr }}</div>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>
</div>

<script>
let selectedSlot = null;
function selectSlot(btn,uuid,time,structure) {
    document.querySelectorAll('.slot-btn').forEach(b=>b.classList.remove('selected'));
    btn.classList.add('selected'); selectedSlot=uuid;
    document.getElementById('bookingSlotUuid').value=uuid;
    document.getElementById('bookingSlotInfo').textContent=time+' — '+structure;
    document.getElementById('bookingForm').style.display='block';
    document.getElementById('bookingMessage').style.display='none';
}
function cancelBooking() { document.getElementById('bookingForm').style.display='none'; document.querySelectorAll('.slot-btn').forEach(b=>b.classList.remove('selected')); selectedSlot=null; }
async function submitBooking() {
    const uuid=document.getElementById('bookingSlotUuid').value;
    const reason=document.getElementById('bookingReason').value.trim();
    const msg=document.getElementById('bookingMessage');
    try {
        const res=await fetch('/web/rdv/book',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({time_slot_uuid:uuid,reason:reason||null})});
        if(res.status===401){window.location.href='/compte/connexion';return;}
        const data=await res.json();
        if(res.ok){msg.style.display='block';msg.style.background='#E8F5E9';msg.style.color='#2E7D32';msg.innerHTML='Rendez-vous confirme ! <a href="/compte/rendez-vous" style="color:#2E7D32;font-weight:600;">Voir mes RDV</a>';const btn=document.querySelector(`[data-uuid="${uuid}"]`);if(btn){btn.disabled=true;btn.style.opacity='.4';}}
        else{msg.style.display='block';msg.style.background='#FFEBEE';msg.style.color='#C62828';msg.textContent=data.error?.message||'Erreur.';}
    }catch(e){msg.style.display='block';msg.style.background='#FFEBEE';msg.style.color='#C62828';msg.textContent='Erreur de connexion.';}
}
</script>
@endsection
