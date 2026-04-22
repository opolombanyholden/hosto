<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="@yield('robots', 'index')">
    <title>@yield('title') — HOSTO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; display:flex; }

        .auth-side {
            flex: 1; display:flex; align-items:center; justify-content:center;
            background: linear-gradient(145deg, var(--color-dark), var(--color-main));
            color: white; padding: 48px; position: relative; overflow: hidden;
        }
        .auth-side::before {
            content:''; position:absolute; inset:0; opacity:.05;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .auth-side-content { position:relative; z-index:1; max-width:400px; }
        .auth-side .logo { font-size:2rem; font-weight:800; margin-bottom:24px; display:flex; align-items:center; gap:8px; }
        .auth-side .logo-icon { width:44px;height:44px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center; }
        .auth-side .logo-icon svg { width:24px; height:24px; stroke:white; }
        .auth-side h2 { font-size:1.5rem; font-weight:700; margin-bottom:12px; }
        .auth-side p { font-size:.9rem; opacity:.8; line-height:1.7; }

        .auth-form-side {
            flex: 1; display:flex; align-items:center; justify-content:center;
            padding: 48px; background: #FAFAFA;
        }
        .auth-form { width:100%; max-width:420px; }
        .auth-form h1 { font-size:1.5rem; font-weight:700; color:#1B2A1B; margin-bottom:8px; }
        .auth-form .subtitle { font-size:.88rem; color:#757575; margin-bottom:32px; }

        .field { margin-bottom:20px; }
        .field label { display:block; font-size:.82rem; font-weight:500; color:#424242; margin-bottom:6px; }
        .field input, .field select {
            width:100%; padding:12px 16px; border:2px solid #EEEEEE; border-radius:10px;
            font-family:'Poppins',sans-serif; font-size:.88rem; color:#1B2A1B;
            outline:none; transition:border-color .2s;
        }
        .field input:focus, .field select:focus { border-color: var(--color-main); }
        .field select { cursor: pointer; background: white; }

        .field-error { color:#E53935; font-size:.75rem; margin-top:4px; }

        .auth-btn {
            width:100%; padding:14px; border:none; border-radius:10px;
            font-family:'Poppins',sans-serif; font-size:.95rem; font-weight:600;
            color:white; background:var(--color-main); cursor:pointer;
            transition:background .2s;
        }
        .auth-btn:hover { background:var(--color-dark); }

        .auth-link { text-align:center; margin-top:20px; font-size:.82rem; color:#757575; }
        .auth-link a { color:var(--color-main); font-weight:500; }

        .auth-alert {
            padding:12px 16px; border-radius:10px; font-size:.82rem; margin-bottom:20px;
        }
        .auth-alert-success { background:#E8F5E9; color:#2E7D32; }
        .auth-alert-error { background:#FFEBEE; color:#C62828; }

        .remember-row { display:flex; align-items:center; gap:8px; margin-bottom:20px; font-size:.82rem; color:#757575; }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .auth-side { padding: 32px; min-height: auto; }
            .auth-side-content { text-align: center; }
            .auth-side h2 { font-size: 1.2rem; }
            .auth-form-side { padding: 32px 24px; }
        }
    </style>
    <style>
        :root {
            --color-main: @yield('color-main', '#388E3C');
            --color-dark: @yield('color-dark', '#2E7D32');
        }
    </style>
</head>
<body>
    <div class="auth-side">
        <div class="auth-side-content">
            <div class="logo">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </div>
                @yield('logo-text', 'HOSTO')
            </div>
            <h2>@yield('side-title')</h2>
            <p>@yield('side-description')</p>
        </div>
    </div>
    <div class="auth-form-side">
        <div class="auth-form">
            <a href="/" style="display:inline-flex;align-items:center;gap:6px;font-size:.78rem;color:#757575;text-decoration:none;margin-bottom:20px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Retour a l'accueil
            </a>
            @if(session('success'))
                <div class="auth-alert auth-alert-success">{{ session('success') }}</div>
            @endif
            @yield('form')
        </div>
    </div>
</body>
</html>
