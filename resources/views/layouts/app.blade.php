<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#388E3C">
    <title>@yield('title', 'HOSTO — La Sante au bout du Click')</title>
    <meta name="description" content="@yield('description', 'HOSTO - Plateforme panafricaine de e-sante. Trouvez un hopital, un medecin, une pharmacie.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; font-size: 16px; }
        body { font-family: 'Poppins', sans-serif; color: #2D2D2D; background: #fff; line-height: 1.7; overflow-x: hidden; }
        a { text-decoration: none; color: inherit; transition: color .3s; }
        ul { list-style: none; }
        img { max-width: 100%; height: auto; display: block; }

        :root {
            --green-dark: #2E7D32; --green: #388E3C; --green-mid: #43A047;
            --green-light: #4CAF50; --green-pale: #E8F5E9; --green-glow: #66BB6A;
            --white: #FFFFFF; --gray-50: #FAFAFA; --gray-100: #F5F5F5;
            --gray-200: #EEEEEE; --gray-600: #757575; --gray-800: #424242;
            --dark: #1B2A1B;
            --shadow-sm: 0 2px 8px rgba(0,0,0,.06); --shadow-md: 0 4px 24px rgba(0,0,0,.08);
            --shadow-lg: 0 12px 48px rgba(0,0,0,.12); --shadow-green: 0 8px 32px rgba(56,142,60,.25);
            --radius: 16px; --radius-sm: 10px;
            --transition: .3s cubic-bezier(.4,0,.2,1);
        }

        .container { width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 24px; }
        .btn {
            display: inline-flex; align-items: center; gap: 10px; padding: 14px 32px;
            font-family: 'Poppins', sans-serif; font-size: .95rem; font-weight: 600;
            border-radius: 100px; border: none; cursor: pointer; transition: all var(--transition); text-decoration: none;
        }
        .btn-primary { background: var(--green); color: var(--white); box-shadow: var(--shadow-green); }
        .btn-primary:hover { background: var(--green-dark); transform: translateY(-2px); }
        .btn-outline-green { background: transparent; color: var(--green); border: 2px solid var(--green); }
        .btn-outline-green:hover { background: var(--green); color: white; }
        .btn-sm { padding: 10px 20px; font-size: .82rem; }

        /* Country bar */
        .country-bar {
            background: var(--dark); color: rgba(255,255,255,.8); font-size: .75rem;
            padding: 6px 0; position: sticky; top: 0; z-index: 1001;
        }
        .country-bar .container { display: flex; justify-content: flex-end; align-items: center; gap: 8px; }
        .country-bar select {
            background: rgba(255,255,255,.1); color: white; border: 1px solid rgba(255,255,255,.2);
            border-radius: 6px; padding: 3px 8px; font-family: 'Poppins',sans-serif; font-size: .72rem;
            cursor: pointer; outline: none;
        }
        .country-bar select option { color: var(--dark); background: white; }

        /* Navbar */
        .navbar {
            position: sticky; top: 30px; z-index: 1000; padding: 12px 0;
            background: rgba(255,255,255,.97); backdrop-filter: blur(20px);
            box-shadow: 0 1px 20px rgba(0,0,0,.06); transition: all var(--transition);
        }
        .navbar .container { display: flex; align-items: center; justify-content: space-between; }
        .navbar-logo {
            display: flex; align-items: center; gap: 4px; font-size: 1.5rem; font-weight: 800;
            color: var(--green); letter-spacing: -0.5px;
        }
        .navbar-logo .logo-icon {
            width: 36px; height: 36px; background: var(--green); border-radius: 10px;
            display: flex; align-items: center; justify-content: center; margin-right: 4px;
        }
        .navbar-logo .logo-icon svg { width: 20px; height: 20px; stroke: white; }
        .navbar-nav { display: flex; align-items: center; gap: 4px; }
        .navbar-nav a {
            padding: 8px 16px; font-size: .85rem; font-weight: 500; color: var(--gray-600);
            border-radius: 100px; transition: all var(--transition);
        }
        .navbar-nav a:hover { color: var(--green); background: var(--green-pale); }
        .navbar-nav a.active { color: var(--green); background: var(--green-pale); font-weight: 600; }
        .navbar-nav .btn-nav {
            background: var(--green); color: var(--white); font-weight: 600; margin-left: 8px;
        }
        .navbar-toggle { display: none; flex-direction: column; gap: 5px; cursor: pointer; padding: 8px; }
        .navbar-toggle span { width: 24px; height: 2.5px; background: var(--green); border-radius: 4px; transition: all var(--transition); }

        /* Footer */
        .footer { background: var(--dark); color: rgba(255,255,255,.7); padding: 48px 0 0; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 48px; padding-bottom: 48px; border-bottom: 1px solid rgba(255,255,255,.08); }
        .footer-brand p { font-size: .85rem; line-height: 1.8; max-width: 300px; margin-top: 12px; }
        .footer h4 { color: var(--white); font-size: .85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; }
        .footer ul li { margin-bottom: 10px; }
        .footer ul li a { font-size: .85rem; transition: color var(--transition); }
        .footer ul li a:hover { color: var(--green-glow); }
        .footer-bottom { display: flex; justify-content: space-between; align-items: center; padding: 24px 0; font-size: .78rem; flex-wrap: wrap; gap: 16px; }

        @media (max-width: 768px) {
            .container { padding: 0 16px; }
            .navbar-nav { display: none; }
            .navbar-nav.open {
                display: flex; flex-direction: column; position: absolute; top: 100%;
                left: 16px; right: 16px; background: var(--white); border-radius: var(--radius);
                padding: 16px; box-shadow: var(--shadow-lg);
            }
            .navbar-nav.open a { color: var(--gray-800); padding: 12px 16px; }
            .navbar-nav.open .btn-nav { background: var(--green); color: var(--white); text-align: center; justify-content: center; }
            .navbar-toggle { display: flex; }
            .footer-grid { grid-template-columns: 1fr; }
            .country-bar .container { justify-content: center; }
        }

        @yield('styles')
    </style>
</head>
<body>

<!-- COUNTRY BAR -->
<div class="country-bar">
    <div class="container">
        <span>Pays :</span>
        <select id="countrySelect" onchange="changeCountry(this.value)">
            <option value="">Chargement...</option>
        </select>
    </div>
</div>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="container">
        <a href="/" class="navbar-logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            </div>
            HOSTO
        </a>
        <div class="navbar-nav" id="navMenu">
            <a href="/" class="{{ request()->is('/') ? 'active' : '' }}">Accueil</a>
            <a href="/annuaire" class="{{ request()->is('annuaire*') ? 'active' : '' }}">Annuaire</a>
            <a href="/#services">Services</a>
            <a href="/#ecosysteme">Ecosysteme</a>
            <a href="#" class="btn-nav">Connexion</a>
        </div>
        <div class="navbar-toggle" onclick="document.getElementById('navMenu').classList.toggle('open')">
            <span></span><span></span><span></span>
        </div>
    </div>
</nav>

@yield('content')

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="navbar-logo" style="font-size:1.3rem;">
                    <div class="logo-icon"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
                    HOSTO
                </div>
                <p>Plateforme panafricaine de e-sante. La sante au bout du click, pour tous, partout en Afrique.</p>
            </div>
            <div>
                <h4>Plateforme</h4>
                <ul>
                    <li><a href="/annuaire">Annuaire</a></li>
                    <li><a href="#">HOSTO Pro</a></li>
                    <li><a href="#">HOSTO Pharma</a></li>
                    <li><a href="#">HOSTO Lab</a></li>
                </ul>
            </div>
            <div>
                <h4>Services</h4>
                <ul>
                    <li><a href="/annuaire?type=hopital">Hopitaux</a></li>
                    <li><a href="/annuaire?type=pharmacie&garde=1">Pharmacies de garde</a></li>
                    <li><a href="/annuaire?type=laboratoire">Laboratoires</a></li>
                    <li><a href="/annuaire">Rendez-vous</a></li>
                </ul>
            </div>
            <div>
                <h4>A propos</h4>
                <ul>
                    <li><a href="#">Yubile Technologie</a></li>
                    <li><a href="#">Mentions legales</a></li>
                    <li><a href="#">Confidentialite</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>HOSTO &copy; {{ date('Y') }} Yubile Technologie. Tous droits reserves.</span>
        </div>
    </div>
</footer>

<script>
const API = '/api/v1';
let currentCountryIso = localStorage.getItem('hosto_country') || 'GA';

async function loadCountries() {
    try {
        const res = await fetch(`${API}/referentiel/countries`);
        const countries = (await res.json()).data;
        const sel = document.getElementById('countrySelect');
        sel.innerHTML = '';
        countries.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.iso2;
            opt.textContent = c.name;
            if (c.iso2 === currentCountryIso) opt.selected = true;
            sel.appendChild(opt);
        });
    } catch (e) { console.error('Countries load failed', e); }
}

function changeCountry(iso2) {
    currentCountryIso = iso2;
    localStorage.setItem('hosto_country', iso2);
    if (typeof onCountryChanged === 'function') onCountryChanged(iso2);
}

loadCountries();
</script>

@yield('scripts')

</body>
</html>
