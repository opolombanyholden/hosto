<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>@yield('title') — @yield('env-name', 'HOSTO')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#F5F5F5; color:#2D2D2D; min-height:100vh; display:flex; }

        /* Sidebar — collapsed by default */
        .sidebar {
            width: 64px; background: var(--env-dark); color: white;
            display: flex; flex-direction: column; position: fixed;
            top: 0; bottom: 0; left: 0; z-index: 100;
            transition: width .25s ease;
            overflow: hidden;
        }
        .sidebar.expanded, .sidebar:hover { width: 260px; }

        .sidebar-header {
            padding: 14px; border-bottom: 1px solid rgba(255,255,255,.1);
            display: flex; align-items: center; gap: 10px; min-height: 56px;
        }
        .sidebar-toggle {
            width: 36px; height: 36px; background: rgba(255,255,255,.1); border: none;
            border-radius: 8px; display: flex; align-items: center; justify-content: center;
            cursor: pointer; flex-shrink: 0; transition: background .2s;
        }
        .sidebar-toggle:hover { background: rgba(255,255,255,.2); }
        .sidebar-toggle svg { width: 18px; height: 18px; stroke: white; fill: none; stroke-width: 2; }
        .sidebar-logo-text {
            font-size: 1.15rem; font-weight: 800; white-space: nowrap;
            opacity: 0; transition: opacity .2s;
        }
        .sidebar.expanded .sidebar-logo-text, .sidebar:hover .sidebar-logo-text { opacity: 1; }

        .sidebar-nav { flex: 1; padding: 10px 8px; overflow-y: auto; overflow-x: hidden; }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 10px; padding: 10px;
            color: rgba(255,255,255,.7); font-size: .82rem; font-weight: 500;
            border-radius: 8px; transition: all .2s; margin-bottom: 2px;
            white-space: nowrap; overflow: hidden;
        }
        .sidebar-nav a:hover { background: rgba(255,255,255,.1); color: white; }
        .sidebar-nav a.active { background: rgba(255,255,255,.15); color: white; font-weight: 600; }
        .sidebar-nav a svg { width: 20px; height: 20px; flex-shrink: 0; }
        .sidebar-section {
            font-size: .62rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;
            color: rgba(255,255,255,.35); padding: 14px 10px 4px; white-space: nowrap; overflow: hidden;
        }
        /* Hide text labels when collapsed */
        .sidebar:not(.expanded):not(:hover) .sidebar-nav a span,
        .sidebar:not(.expanded):not(:hover) .sidebar-section { display: none; }
        .sidebar-nav a span { flex: 1; }

        /* Footer — user + deconnexion + parametres */
        .sidebar-footer {
            border-top: 1px solid rgba(255,255,255,.1); padding: 10px 8px;
        }
        .sidebar-footer a, .sidebar-footer button {
            display: flex; align-items: center; gap: 10px; padding: 8px 10px;
            color: rgba(255,255,255,.7); font-size: .78rem; font-weight: 500;
            border-radius: 8px; transition: all .2s; margin-bottom: 2px;
            white-space: nowrap; overflow: hidden; width: 100%;
            background: none; border: none; cursor: pointer; font-family: Poppins, sans-serif;
            text-decoration: none; text-align: left;
        }
        .sidebar-footer a:hover, .sidebar-footer button:hover { background: rgba(255,255,255,.1); color: white; }
        .sidebar-footer svg { width: 20px; height: 20px; flex-shrink: 0; }
        .sidebar:not(.expanded):not(:hover) .sidebar-footer span { display: none; }

        /* Main */
        .main { flex: 1; margin-left: 64px; transition: margin-left .25s ease; }
        .sidebar.expanded ~ .main { margin-left: 260px; }
        .sidebar:not(.expanded):hover ~ .main { margin-left: 64px; }

        .topbar {
            background: white; padding: 12px 32px; display: flex;
            align-items: center; justify-content: space-between;
            border-bottom: 1px solid #EEE; position: sticky; top: 0; z-index: 50;
        }
        .topbar-title { font-size: 1rem; font-weight: 600; color: #1B2A1B; }

        .content { padding: 32px; }

        /* Mobile */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 260px; }
            .sidebar.expanded { transform: translateX(0); }
            .sidebar:not(.expanded) .sidebar-nav a span,
            .sidebar:not(.expanded) .sidebar-section { display: block; }
            .main { margin-left: 0 !important; }
            .content { padding: 20px 16px; }
            .mobile-toggle { display: flex !important; }
        }
        .mobile-toggle {
            display: none; padding: 8px; cursor: pointer; background: none; border: none;
        }
        .mobile-toggle svg { width: 24px; height: 24px; stroke: #424242; fill: none; stroke-width: 2; }
    </style>
    <style>
        :root {
            --env-main: @yield('env-color', '#388E3C');
            --env-dark: @yield('env-color-dark', '#2E7D32');
        }
    </style>
    @yield('styles')
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <button class="sidebar-toggle" onclick="toggleSidebar()" title="Menu">
                <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <span class="sidebar-logo-text">@yield('env-name', 'HOSTO')</span>
        </div>
        <nav class="sidebar-nav">
            @yield('sidebar-nav')
        </nav>
        <div class="sidebar-footer">
            <a href="/compte/profil/completer" title="Parametres">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span>Parametres</span>
            </a>
            <form method="POST" action="/deconnexion" style="margin:0;">
                @csrf
                <button type="submit" title="Deconnexion">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span>Deconnexion</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <span class="topbar-title">@yield('page-title', 'Tableau de bord')</span>
            </div>
        </header>
        <main class="content">
            @yield('content')
        </main>
    </div>

    <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('expanded');
    }
    </script>
</body>
</html>
