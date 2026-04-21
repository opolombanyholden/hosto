<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Teleconsultation — HOSTO</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#1B2A1B; color:white; height:100vh; display:flex; flex-direction:column; }
        .tc-header {
            background:rgba(0,0,0,.3); padding:12px 24px; display:flex; justify-content:space-between;
            align-items:center; border-bottom:1px solid rgba(255,255,255,.1); flex-shrink:0;
        }
        .tc-logo { font-size:1.1rem; font-weight:700; display:flex; align-items:center; gap:8px; }
        .tc-logo .icon { width:28px;height:28px;background:#388E3C;border-radius:6px;display:flex;align-items:center;justify-content:center; }
        .tc-logo .icon svg { width:16px;height:16px;stroke:white; }
        .tc-info { text-align:right; }
        .tc-info .name { font-size:.85rem; font-weight:600; }
        .tc-info .time { font-size:.72rem; opacity:.6; }
        .tc-body { flex:1; position:relative; }
        #jitsi-container { width:100%; height:100%; }

        .tc-waiting {
            position:absolute; inset:0; display:flex; flex-direction:column;
            align-items:center; justify-content:center; gap:20px; z-index:10;
        }
        .tc-waiting h2 { font-size:1.2rem; font-weight:600; }
        .tc-waiting p { font-size:.85rem; opacity:.7; max-width:400px; text-align:center; }
        .tc-waiting .pulse { width:80px;height:80px;border-radius:50%;background:#388E3C;animation:pulse 2s ease-in-out infinite; display:flex;align-items:center;justify-content:center; }
        .tc-waiting .pulse svg { width:40px;height:40px;stroke:white; }
        @keyframes pulse { 0%,100%{transform:scale(1);opacity:1;} 50%{transform:scale(1.1);opacity:.7;} }
        .tc-join-btn {
            padding:14px 40px; background:#388E3C; color:white; border:none; border-radius:100px;
            font-family:'Poppins',sans-serif; font-size:.95rem; font-weight:600; cursor:pointer;
            transition:background .2s;
        }
        .tc-join-btn:hover { background:#2E7D32; }
        .tc-back { font-size:.78rem; opacity:.5; margin-top:8px; }
        .tc-back a { color:white; }
        .tc-security { margin-top:16px; display:flex; align-items:center; gap:6px; font-size:.72rem; opacity:.5; }
        .tc-security svg { width:14px;height:14px;stroke:rgba(255,255,255,.5); }
    </style>
</head>
<body>
    <header class="tc-header">
        <div class="tc-logo">
            <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
            HOSTO Teleconsultation
        </div>
        <div class="tc-info">
            <div class="name">{{ $teleconsultation->practitioner->full_name }}</div>
            <div class="time">{{ $teleconsultation->scheduled_at->format('d/m/Y H:i') }} — {{ $teleconsultation->duration_minutes }} min</div>
        </div>
    </header>

    <div class="tc-body">
        <div class="tc-waiting" id="waiting">
            <div class="pulse">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
            </div>
            <h2>Pret pour la teleconsultation</h2>
            <p>Votre session avec {{ $teleconsultation->practitioner->full_name }} est prete. Cliquez pour rejoindre la salle securisee.</p>
            <button class="tc-join-btn" onclick="joinRoom()">Rejoindre la consultation</button>
            <div class="tc-back"><a href="/">Retour a l'accueil</a></div>
            <div class="tc-security">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Connexion securisee — Chiffrement de bout en bout
            </div>
        </div>
        <div id="jitsi-container"></div>
    </div>

    <script src="https://meet.jit.si/external_api.js"></script>
    <script>
    function joinRoom() {
        document.getElementById('waiting').style.display = 'none';

        const api = new JitsiMeetExternalAPI('{{ $teleconsultation->jitsi_domain }}', {
            roomName: '{{ $teleconsultation->room_name }}',
            parentNode: document.getElementById('jitsi-container'),
            width: '100%',
            height: '100%',
            userInfo: {
                displayName: '{{ addslashes(auth()->user()?->name ?? "Invité") }}',
                email: '{{ auth()->user()?->email ?? "" }}',
            },
            configOverwrite: {
                startWithAudioMuted: false,
                startWithVideoMuted: false,
                prejoinPageEnabled: false,
                disableDeepLinking: true,
                hideConferenceSubject: true,
                subject: 'HOSTO — Teleconsultation',
                // Low bandwidth optimizations
                enableLayerSuspension: true,
                channelLastN: 2,
                resolution: 480,
                constraints: { video: { height: { ideal: 480, max: 720, min: 240 } } },
                // Security
                e2eping: { enabled: true },
            },
            interfaceConfigOverwrite: {
                TOOLBAR_BUTTONS: [
                    'microphone', 'camera', 'desktop', 'chat',
                    'raisehand', 'tileview', 'hangup',
                ],
                SHOW_JITSI_WATERMARK: false,
                SHOW_WATERMARK_FOR_GUESTS: false,
                SHOW_BRAND_WATERMARK: false,
                DEFAULT_BACKGROUND: '#1B2A1B',
                DISABLE_JOIN_LEAVE_NOTIFICATIONS: false,
            },
        });

        // Track participants
        api.addListener('participantJoined', () => {
            fetch('/web/telecon/{{ $teleconsultation->uuid }}/join', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest'},
            });
        });

        api.addListener('readyToClose', () => {
            fetch('/web/telecon/{{ $teleconsultation->uuid }}/end', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest'},
            }).then(() => window.location.href = '/compte');
        });
    }
    </script>
</body>
</html>
