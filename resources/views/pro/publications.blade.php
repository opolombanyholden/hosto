@extends('layouts.dashboard')

@section('env-name', 'HOSTO Pro')
@section('env-color', '#1565C0')
@section('env-color-dark', '#0D47A1')
@section('title', 'Mes publications')
@section('page-title', 'Mes publications')
@section('user-role', 'Professionnel')

@section('sidebar-nav')
<a href="/pro">Tableau de bord</a>
<a href="/pro/visibility">Visibilite profil</a>
<a href="/pro/publications" class="active">Mes publications</a>
<a href="/pro/consultations">Consultations</a>
@endsection

@section('content')
<div id="pubMsg" style="display:none;padding:12px;border-radius:10px;font-size:.82rem;margin-bottom:16px;"></div>

{{-- New publication form --}}
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:24px;margin-bottom:20px;">
    <h3 style="font-size:.9rem;font-weight:600;color:#1565C0;margin-bottom:16px;">Nouvelle publication</h3>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
        <div>
            <label style="font-size:.78rem;font-weight:500;color:#424242;display:block;margin-bottom:4px;">Type</label>
            <select id="pubType" style="width:100%;padding:10px 14px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;">
                <option value="activity">Activite</option>
                <option value="research">Travaux / Recherche</option>
                <option value="tip">Conseil sante</option>
                <option value="video">Video</option>
            </select>
        </div>
        <div>
            <label style="font-size:.78rem;font-weight:500;color:#424242;display:block;margin-bottom:4px;">Titre (optionnel)</label>
            <input type="text" id="pubTitle" placeholder="Titre de la publication..." style="width:100%;padding:10px 14px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;outline:none;box-sizing:border-box;">
        </div>
    </div>

    <div style="margin-bottom:12px;">
        <label style="font-size:.78rem;font-weight:500;color:#424242;display:block;margin-bottom:4px;">Contenu *</label>
        <textarea id="pubContent" rows="4" maxlength="5000" placeholder="Partagez votre activite, vos travaux, un conseil sante..." style="width:100%;padding:10px 14px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;outline:none;resize:vertical;box-sizing:border-box;"></textarea>
    </div>

    <div style="margin-bottom:12px;">
        <label style="font-size:.78rem;font-weight:500;color:#424242;display:block;margin-bottom:4px;">URL video (YouTube, Vimeo...)</label>
        <input type="url" id="pubVideo" placeholder="https://..." style="width:100%;padding:10px 14px;border:2px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;outline:none;box-sizing:border-box;">
    </div>

    <div style="display:flex;align-items:center;gap:16px;margin-bottom:16px;">
        <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;color:#424242;cursor:pointer;">
            <input type="checkbox" id="pubComments" checked style="accent-color:#1565C0;">
            Autoriser les commentaires
        </label>
    </div>

    <button onclick="createPublication()" style="padding:10px 24px;background:#1565C0;color:white;border:none;border-radius:8px;font-family:Poppins,sans-serif;font-size:.85rem;font-weight:600;cursor:pointer;">Publier</button>
</div>

{{-- Publications list --}}
<h3 style="font-size:.9rem;font-weight:600;color:#1B2A1B;margin-bottom:12px;">Publications ({{ $publications->total() }})</h3>

@forelse($publications as $pub)
<div class="pub-card" id="pub-{{ $pub->uuid }}" style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:12px;">
    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px;">
        <div>
            @php
                $typeLabels = ['activity' => 'Activite', 'research' => 'Travaux', 'tip' => 'Conseil', 'video' => 'Video'];
                $typeColors = ['activity' => '#E8F5E9;color:#2E7D32', 'research' => '#E3F2FD;color:#1565C0', 'tip' => '#FFF3E0;color:#E65100', 'video' => '#F3E5F5;color:#6A1B9A'];
            @endphp
            <span style="padding:3px 10px;border-radius:100px;font-size:.68rem;font-weight:600;background:{{ $typeColors[$pub->type] ?? '#F5F5F5;color:#757575' }};">{{ $typeLabels[$pub->type] ?? $pub->type }}</span>
            @if(!$pub->is_published) <span style="padding:3px 10px;border-radius:100px;font-size:.68rem;font-weight:600;background:#FFEBEE;color:#C62828;">Brouillon</span> @endif
        </div>
        <div style="display:flex;gap:8px;">
            <button onclick="deletePublication('{{ $pub->uuid }}')" style="background:none;border:none;cursor:pointer;font-size:.78rem;color:#E53935;">Supprimer</button>
        </div>
    </div>
    @if($pub->title) <div style="font-size:.88rem;font-weight:600;color:#1B2A1B;margin-bottom:4px;">{{ $pub->title }}</div> @endif
    <p style="font-size:.82rem;color:#424242;line-height:1.6;white-space:pre-line;">{{ Str::limit($pub->content, 300) }}</p>
    @if($pub->video_url) <div style="margin-top:8px;font-size:.78rem;color:#1565C0;">Video : {{ $pub->video_url }}</div> @endif
    <div style="display:flex;gap:16px;margin-top:12px;font-size:.75rem;color:#757575;">
        <span>{{ $pub->likes_count }} like{{ $pub->likes_count > 1 ? 's' : '' }}</span>
        <span>{{ $pub->comments_count }} commentaire{{ $pub->comments_count > 1 ? 's' : '' }}</span>
        <span>{{ $pub->allow_comments ? 'Commentaires actives' : 'Commentaires desactives' }}</span>
        <span>{{ $pub->published_at?->format('d/m/Y H:i') }}</span>
    </div>
</div>
@empty
<div style="text-align:center;padding:40px;color:#757575;font-size:.85rem;">Aucune publication pour le moment.</div>
@endforelse

{{ $publications->links() }}

<script>
const headers = {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','X-Requested-With':'XMLHttpRequest'};

function showMsg(ok, text) {
    const el = document.getElementById('pubMsg');
    el.style.display = 'block';
    el.style.background = ok ? '#E3F2FD' : '#FFEBEE';
    el.style.color = ok ? '#1565C0' : '#C62828';
    el.textContent = text;
    if (ok) setTimeout(() => el.style.display = 'none', 3000);
}

async function createPublication() {
    const body = {
        type: document.getElementById('pubType').value,
        title: document.getElementById('pubTitle').value || null,
        content: document.getElementById('pubContent').value,
        video_url: document.getElementById('pubVideo').value || null,
        allow_comments: document.getElementById('pubComments').checked,
    };
    if (!body.content) { showMsg(false, 'Le contenu est obligatoire.'); return; }
    try {
        const res = await fetch('/pro/publications', { method:'POST', headers, body:JSON.stringify(body) });
        const data = await res.json();
        if (res.ok) {
            showMsg(true, data.data?.message || 'Publie.');
            document.getElementById('pubTitle').value = '';
            document.getElementById('pubContent').value = '';
            document.getElementById('pubVideo').value = '';
            setTimeout(() => location.reload(), 1000);
        } else {
            const errors = data.errors ? Object.values(data.errors).flat().join(' ') : (data.error?.message || 'Erreur.');
            showMsg(false, errors);
        }
    } catch(e) { showMsg(false, 'Erreur de connexion.'); }
}

async function deletePublication(uuid) {
    if (!confirm('Supprimer cette publication ?')) return;
    try {
        const res = await fetch(`/pro/publications/${uuid}`, { method:'DELETE', headers });
        if (res.ok) {
            document.getElementById(`pub-${uuid}`).remove();
            showMsg(true, 'Publication supprimee.');
        } else { showMsg(false, 'Erreur.'); }
    } catch(e) { showMsg(false, 'Erreur de connexion.'); }
}
</script>
@endsection
