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

        .sidebar {
            width: 260px; background: var(--env-dark); color: white;
            display: flex; flex-direction: column; position: fixed;
            top: 0; bottom: 0; left: 0; z-index: 100;
            transition: transform .3s;
        }
        .sidebar-header { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,.1); }
        .sidebar-logo { font-size: 1.2rem; font-weight: 800; display: flex; align-items: center; gap: 8px; }
        .sidebar-logo .icon { width:32px;height:32px;background:rgba(255,255,255,.15);border-radius:8px;display:flex;align-items:center;justify-content:center; }
        .sidebar-logo .icon svg { width:18px;height:18px;stroke:white; }
        .sidebar-nav { flex:1; padding:16px 12px; overflow-y:auto; }
        .sidebar-nav a {
            display:flex; align-items:center; gap:10px; padding:10px 14px;
            color:rgba(255,255,255,.7); font-size:.85rem; font-weight:500;
            border-radius:8px; transition:all .2s; margin-bottom:2px;
        }
        .sidebar-nav a:hover { background:rgba(255,255,255,.1); color:white; }
        .sidebar-nav a.active { background:rgba(255,255,255,.15); color:white; font-weight:600; }
        .sidebar-nav a svg { width:18px; height:18px; flex-shrink:0; }
        .sidebar-section { font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:1px; color:rgba(255,255,255,.4); padding:16px 14px 6px; }
        .sidebar-footer { padding:16px 24px; border-top:1px solid rgba(255,255,255,.1); }
        .sidebar-user { font-size:.82rem; }
        .sidebar-user .name { font-weight:600; }
        .sidebar-user .role { font-size:.72rem; opacity:.6; }

        .main { flex:1; margin-left:260px; }
        .topbar {
            background:white; padding:12px 32px; display:flex;
            align-items:center; justify-content:space-between;
            border-bottom:1px solid #EEE; position:sticky; top:0; z-index:50;
        }
        .topbar-title { font-size:1rem; font-weight:600; color:#1B2A1B; }
        .topbar-actions { display:flex; align-items:center; gap:12px; }
        .topbar-actions .logout-btn {
            padding:6px 16px; border:1px solid #EEE; border-radius:8px;
            font-family:'Poppins',sans-serif; font-size:.78rem; color:#757575;
            background:white; cursor:pointer; transition:all .2s;
        }
        .topbar-actions .logout-btn:hover { border-color:var(--env-main); color:var(--env-main); }

        .content { padding:32px; }

        .toggle-sidebar { display:none; padding:8px; cursor:pointer; background:none; border:none; }
        .toggle-sidebar svg { width:24px; height:24px; stroke:#424242; }

        @media (max-width: 768px) {
            .sidebar { transform:translateX(-100%); }
            .sidebar.open { transform:translateX(0); }
            .main { margin-left:0; }
            .toggle-sidebar { display:block; }
            .content { padding:20px 16px; }
        }
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
            <div class="sidebar-logo">
                <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
                @yield('env-name', 'HOSTO')
            </div>
        </div>
        <nav class="sidebar-nav">
            @yield('sidebar-nav')
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="name">{{ auth()->user()?->name }}</div>
                <div class="role">@yield('user-role')</div>
            </div>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="toggle-sidebar" onclick="document.getElementById('sidebar').classList.toggle('open')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <span class="topbar-title">@yield('page-title', 'Tableau de bord')</span>
            </div>
            <div class="topbar-actions">
                <form method="POST" action="/deconnexion" style="margin:0;">
                    @csrf
                    <button type="submit" class="logout-btn">Deconnexion</button>
                </form>
            </div>
        </header>
        <main class="content">
            @yield('content')
        </main>
    </div>
</body>
</html>
