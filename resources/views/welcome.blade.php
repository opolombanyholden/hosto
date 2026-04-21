<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HOSTO - La plateforme panafricaine de e-sant&eacute;. Trouvez un h&ocirc;pital, un m&eacute;decin, une pharmacie. Prenez rendez-vous en ligne.">
    <meta name="theme-color" content="#388E3C">
    <title>HOSTO &mdash; La Sant&eacute; au bout du Click</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; font-size: 16px; }
        body {
            font-family: 'Poppins', sans-serif;
            color: #2D2D2D;
            background: #fff;
            line-height: 1.7;
            overflow-x: hidden;
        }
        a { text-decoration: none; color: inherit; transition: color .3s; }
        ul { list-style: none; }
        img { max-width: 100%; height: auto; display: block; }

        :root {
            --green-dark: #2E7D32;
            --green: #388E3C;
            --green-mid: #43A047;
            --green-light: #4CAF50;
            --green-pale: #E8F5E9;
            --green-glow: #66BB6A;
            --white: #FFFFFF;
            --gray-50: #FAFAFA;
            --gray-100: #F5F5F5;
            --gray-200: #EEEEEE;
            --gray-600: #757575;
            --gray-800: #424242;
            --dark: #1B2A1B;
            --shadow-sm: 0 2px 8px rgba(0,0,0,.06);
            --shadow-md: 0 4px 24px rgba(0,0,0,.08);
            --shadow-lg: 0 12px 48px rgba(0,0,0,.12);
            --shadow-green: 0 8px 32px rgba(56,142,60,.25);
            --radius: 16px;
            --radius-sm: 10px;
            --transition: .3s cubic-bezier(.4,0,.2,1);
        }

        .container { width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 24px; }
        .section { padding: 100px 0; }
        .section-label {
            display: inline-block; font-size: .75rem; font-weight: 600;
            letter-spacing: 2px; text-transform: uppercase; color: var(--green);
            background: var(--green-pale); padding: 6px 16px; border-radius: 100px; margin-bottom: 16px;
        }
        .section-title {
            font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 700;
            color: var(--dark); margin-bottom: 16px; line-height: 1.2;
        }
        .section-subtitle { font-size: 1.05rem; color: var(--gray-600); max-width: 620px; line-height: 1.8; }
        .text-center { text-align: center; }
        .mx-auto { margin-left: auto; margin-right: auto; }

        .btn {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 14px 32px; font-family: 'Poppins', sans-serif;
            font-size: .95rem; font-weight: 600; border-radius: 100px;
            border: none; cursor: pointer; transition: all var(--transition); text-decoration: none;
        }
        .btn-primary { background: var(--green); color: var(--white); box-shadow: var(--shadow-green); }
        .btn-primary:hover { background: var(--green-dark); transform: translateY(-2px); box-shadow: 0 12px 40px rgba(56,142,60,.35); }
        .btn-outline { background: transparent; color: var(--white); border: 2px solid rgba(255,255,255,.5); }
        .btn-outline:hover { background: var(--white); color: var(--green); border-color: var(--white); }
        .btn-white { background: var(--white); color: var(--green); box-shadow: var(--shadow-md); }
        .btn-white:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }

        /* NAVBAR */
        .navbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            padding: 16px 0; transition: all var(--transition);
        }
        .navbar.scrolled {
            background: rgba(255,255,255,.97); backdrop-filter: blur(20px);
            padding: 10px 0; box-shadow: 0 1px 20px rgba(0,0,0,.06);
        }
        .navbar .container { display: flex; align-items: center; justify-content: space-between; }
        .navbar-logo {
            display: flex; align-items: center; gap: 4px;
            font-size: 1.6rem; font-weight: 800; color: var(--white); letter-spacing: -0.5px;
        }
        .navbar.scrolled .navbar-logo { color: var(--green); }
        .navbar-logo .logo-icon {
            width: 38px; height: 38px; background: var(--white); border-radius: 10px;
            display: flex; align-items: center; justify-content: center; margin-right: 4px;
        }
        .navbar.scrolled .logo-icon { background: var(--green); }
        .navbar.scrolled .logo-icon svg { stroke: white; }
        .navbar-logo .logo-icon svg { width: 22px; height: 22px; }
        .navbar-nav { display: flex; align-items: center; gap: 8px; }
        .navbar-nav a {
            padding: 8px 18px; font-size: .88rem; font-weight: 500;
            color: rgba(255,255,255,.85); border-radius: 100px; transition: all var(--transition);
        }
        .navbar.scrolled .navbar-nav a { color: var(--gray-600); }
        .navbar-nav a:hover { color: var(--white); background: rgba(255,255,255,.12); }
        .navbar.scrolled .navbar-nav a:hover { color: var(--green); background: var(--green-pale); }
        .navbar-nav .btn-nav { background: var(--white); color: var(--green); font-weight: 600; margin-left: 8px; }
        .navbar.scrolled .navbar-nav .btn-nav { background: var(--green); color: var(--white); }
        .navbar-toggle { display: none; flex-direction: column; gap: 5px; cursor: pointer; padding: 8px; }
        .navbar-toggle span { width: 24px; height: 2.5px; background: var(--white); border-radius: 4px; transition: all var(--transition); }
        .navbar.scrolled .navbar-toggle span { background: var(--green); }

        /* HERO */
        .hero {
            position: relative; min-height: 100vh; display: flex; align-items: center;
            background: linear-gradient(145deg, var(--green-dark) 0%, var(--green) 40%, var(--green-mid) 70%, var(--green-light) 100%);
            overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(ellipse 600px 600px at 80% 20%, rgba(255,255,255,.07), transparent),
                        radial-gradient(ellipse 400px 400px at 20% 80%, rgba(255,255,255,.05), transparent);
        }
        .hero-pattern {
            position: absolute; inset: 0; opacity: .04;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .hero-blob { position: absolute; border-radius: 50%; filter: blur(80px); opacity: .15; }
        .hero-blob-1 { width: 500px; height: 500px; background: #81C784; top: -150px; right: -100px; }
        .hero-blob-2 { width: 300px; height: 300px; background: #A5D6A7; bottom: -80px; left: -50px; }
        .hero .container {
            position: relative; z-index: 2; display: grid; grid-template-columns: 1fr 1fr;
            gap: 60px; align-items: center; padding-top: 100px; padding-bottom: 60px;
        }
        .hero-content { color: var(--white); }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px; padding: 6px 16px 6px 8px;
            background: rgba(255,255,255,.12); backdrop-filter: blur(10px); border-radius: 100px;
            font-size: .78rem; font-weight: 500; color: rgba(255,255,255,.9); margin-bottom: 28px;
            border: 1px solid rgba(255,255,255,.15);
        }
        .hero-badge-dot {
            width: 8px; height: 8px; background: #69F0AE; border-radius: 50%;
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .5; transform: scale(1.3); }
        }
        .hero h1 { font-size: clamp(2.4rem, 5vw, 3.6rem); font-weight: 800; line-height: 1.1; margin-bottom: 8px; letter-spacing: -1px; }
        .hero h1 span { display: block; font-size: clamp(1.1rem, 2.5vw, 1.5rem); font-weight: 300; letter-spacing: 2px; opacity: .85; margin-top: 8px; }
        .hero-text { font-size: 1.1rem; line-height: 1.8; opacity: .9; margin: 24px 0 36px; max-width: 500px; }
        .hero-buttons { display: flex; gap: 16px; flex-wrap: wrap; }
        .hero-stats { display: flex; gap: 40px; margin-top: 48px; padding-top: 32px; border-top: 1px solid rgba(255,255,255,.15); }
        .hero-stat-value { font-size: 1.8rem; font-weight: 700; line-height: 1; }
        .hero-stat-label { font-size: .78rem; opacity: .7; margin-top: 4px; }

        .hero-visual { display: flex; justify-content: center; align-items: center; }
        .hero-card-stack { position: relative; width: 380px; height: 480px; }
        .hero-card {
            position: absolute; background: var(--white); border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,.15); padding: 28px; transition: all .5s cubic-bezier(.4,0,.2,1);
        }
        .hero-card-main { width: 320px; top: 30px; left: 30px; z-index: 3; }
        .hero-card-main:hover { transform: translateY(-8px); }
        .hero-card-back { width: 280px; height: 200px; top: 0; left: 60px; z-index: 1; background: rgba(255,255,255,.6); backdrop-filter: blur(10px); }
        .hero-card-side { width: 160px; bottom: 40px; right: 0; z-index: 4; padding: 20px; background: var(--green-dark); color: var(--white); }
        .hero-card-side:hover { transform: translate(4px, -4px); }
        .card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .card-avatar { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: .85rem; font-weight: 700; }
        .card-avatar.green { background: var(--green-pale); color: var(--green); }
        .card-name { font-weight: 600; font-size: .9rem; color: var(--dark); }
        .card-role { font-size: .75rem; color: var(--gray-600); }
        .card-row { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--gray-200); }
        .card-row:last-child { border-bottom: none; }
        .card-row-icon { width: 36px; height: 36px; border-radius: 10px; background: var(--green-pale); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .card-row-icon svg { width: 18px; height: 18px; color: var(--green); }
        .card-row-text { font-size: .82rem; color: var(--gray-800); font-weight: 500; }
        .card-row-sub { font-size: .72rem; color: var(--gray-600); }
        .card-row-badge { margin-left: auto; font-size: .68rem; font-weight: 600; padding: 3px 10px; border-radius: 100px; background: var(--green-pale); color: var(--green); white-space: nowrap; }
        .card-side-icon { width: 40px; height: 40px; background: rgba(255,255,255,.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
        .card-side-icon svg { width: 20px; height: 20px; }
        .card-side-value { font-size: 1.6rem; font-weight: 700; }
        .card-side-label { font-size: .72rem; opacity: .8; }

        /* SEARCH */
        .search-section { position: relative; z-index: 10; margin-top: -40px; }
        .search-bar {
            background: var(--white); border-radius: var(--radius); padding: 12px;
            box-shadow: var(--shadow-lg); display: grid; grid-template-columns: 1fr 1fr 1fr auto;
            gap: 8px; align-items: center; border: 1px solid var(--gray-200);
        }
        .search-field { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: var(--radius-sm); transition: background var(--transition); }
        .search-field:hover { background: var(--gray-50); }
        .search-field svg { width: 20px; height: 20px; color: var(--green); flex-shrink: 0; }
        .search-field input, .search-field select { border: none; outline: none; font-family: 'Poppins', sans-serif; font-size: .88rem; color: var(--dark); background: transparent; width: 100%; }
        .search-field select { cursor: pointer; }
        .search-btn {
            padding: 14px 36px; background: var(--green); color: var(--white); border: none;
            border-radius: var(--radius-sm); font-family: 'Poppins', sans-serif; font-size: .9rem;
            font-weight: 600; cursor: pointer; transition: all var(--transition);
            display: flex; align-items: center; gap: 8px; white-space: nowrap;
        }
        .search-btn:hover { background: var(--green-dark); transform: translateY(-1px); box-shadow: var(--shadow-green); }
        .search-btn svg { width: 18px; height: 18px; }

        /* SERVICES */
        .services { background: var(--white); }
        .services-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 48px; }
        .service-card {
            background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius);
            padding: 32px 24px; text-align: center; transition: all var(--transition); cursor: pointer;
        }
        .service-card:hover { border-color: var(--green-light); transform: translateY(-6px); box-shadow: var(--shadow-md); }
        .service-icon { width: 64px; height: 64px; margin: 0 auto 16px; }
        .service-icon img { width: 100%; height: 100%; object-fit: contain; }
        .service-name { font-size: .9rem; font-weight: 600; color: var(--dark); margin-bottom: 6px; }
        .service-desc { font-size: .78rem; color: var(--gray-600); line-height: 1.5; }

        /* FEATURES */
        .features { background: var(--gray-50); }
        .features-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; margin-top: 48px; }
        .features-list { display: flex; flex-direction: column; gap: 24px; }
        .feature-item { display: flex; gap: 16px; padding: 20px; border-radius: var(--radius); transition: all var(--transition); cursor: default; }
        .feature-item:hover { background: var(--white); box-shadow: var(--shadow-sm); }
        .feature-icon-wrap {
            width: 48px; height: 48px; border-radius: 14px; background: var(--green-pale);
            display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all var(--transition);
        }
        .feature-item:hover .feature-icon-wrap { background: var(--green); }
        .feature-icon-wrap svg { width: 22px; height: 22px; color: var(--green); transition: color var(--transition); }
        .feature-item:hover .feature-icon-wrap svg { color: var(--white); }
        .feature-title { font-size: .95rem; font-weight: 600; color: var(--dark); margin-bottom: 4px; }
        .feature-desc { font-size: .82rem; color: var(--gray-600); line-height: 1.6; }

        .features-phone {
            width: 300px; margin: 0 auto; background: var(--dark);
            border-radius: 40px; padding: 12px; box-shadow: var(--shadow-lg); position: relative;
        }
        .features-phone-screen { background: var(--white); border-radius: 30px; overflow: hidden; aspect-ratio: 9/17; display: flex; flex-direction: column; }
        .phone-header { background: var(--green); padding: 40px 20px 20px; color: var(--white); }
        .phone-header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .phone-logo { font-size: 1.1rem; font-weight: 700; }
        .phone-avatar { width: 32px; height: 32px; background: rgba(255,255,255,.2); border-radius: 50%; }
        .phone-greeting { font-size: .82rem; opacity: .9; }
        .phone-greeting strong { display: block; font-size: 1rem; }
        .phone-body { padding: 20px; flex: 1; }
        .phone-search { background: var(--gray-100); border-radius: 12px; padding: 12px 16px; font-size: .75rem; color: var(--gray-600); display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
        .phone-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .phone-service { text-align: center; padding: 12px 4px; }
        .phone-service-icon { width: 40px; height: 40px; margin: 0 auto 6px; background: var(--green-pale); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .phone-service-icon svg { width: 18px; height: 18px; color: var(--green); }
        .phone-service-label { font-size: .6rem; color: var(--gray-800); font-weight: 500; }
        .phone-notch { position: absolute; top: 18px; left: 50%; transform: translateX(-50%); width: 100px; height: 24px; background: var(--dark); border-radius: 100px; z-index: 5; }

        /* ECOSYSTEM */
        .ecosystem { background: var(--white); }
        .eco-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 48px; }
        .eco-card {
            background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius);
            padding: 32px; transition: all var(--transition); position: relative; overflow: hidden;
        }
        .eco-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--green); transform: scaleX(0); transition: transform var(--transition); }
        .eco-card:hover::before { transform: scaleX(1); }
        .eco-card:hover { box-shadow: var(--shadow-md); transform: translateY(-4px); }
        .eco-icon { width: 52px; height: 52px; border-radius: 14px; background: var(--green-pale); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
        .eco-icon svg { width: 24px; height: 24px; color: var(--green); }
        .eco-name { font-size: 1rem; font-weight: 700; color: var(--dark); margin-bottom: 4px; }
        .eco-name span { color: var(--green); }
        .eco-role { font-size: .78rem; color: var(--gray-600); line-height: 1.6; }
        .eco-tag { display: inline-block; margin-top: 12px; font-size: .68rem; font-weight: 600; padding: 4px 12px; border-radius: 100px; background: var(--green-pale); color: var(--green); }

        /* AUDIENCE */
        .audience { background: var(--gray-50); }
        .audience-tabs { display: flex; gap: 8px; justify-content: center; margin-top: 32px; margin-bottom: 40px; flex-wrap: wrap; }
        .audience-tab {
            padding: 10px 24px; border-radius: 100px; border: 2px solid var(--gray-200);
            background: var(--white); font-family: 'Poppins', sans-serif; font-size: .85rem;
            font-weight: 500; color: var(--gray-600); cursor: pointer; transition: all var(--transition);
        }
        .audience-tab:hover, .audience-tab.active { border-color: var(--green); color: var(--green); background: var(--green-pale); }
        .audience-panel { display: none; animation: fadeIn .4s ease; }
        .audience-panel.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .audience-content { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: center; }
        .audience-content h3 { font-size: 1.5rem; font-weight: 700; color: var(--dark); margin-bottom: 16px; }
        .audience-content p { color: var(--gray-600); margin-bottom: 24px; line-height: 1.8; }
        .audience-checklist { display: flex; flex-direction: column; gap: 12px; }
        .audience-check { display: flex; align-items: center; gap: 12px; font-size: .88rem; color: var(--gray-800); }
        .audience-check svg { width: 20px; height: 20px; color: var(--green); flex-shrink: 0; }
        .audience-illustration {
            background: linear-gradient(135deg, var(--green-pale), #C8E6C9); border-radius: 24px;
            padding: 48px; display: flex; align-items: center; justify-content: center; min-height: 360px;
        }
        .audience-illustration svg { width: 200px; height: 200px; color: var(--green); opacity: .3; }

        /* STATS */
        .stats { background: linear-gradient(135deg, var(--green-dark), var(--green-mid)); padding: 80px 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 32px; }
        .stat-item { text-align: center; color: var(--white); }
        .stat-number { font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 800; line-height: 1; margin-bottom: 8px; }
        .stat-label { font-size: .85rem; opacity: .8; }
        .stat-divider { width: 40px; height: 3px; background: rgba(255,255,255,.3); margin: 12px auto 0; border-radius: 100px; }

        /* CTA */
        .cta { background: var(--white); padding: 100px 0; }
        .cta-box {
            background: linear-gradient(135deg, var(--green), var(--green-mid)); border-radius: 24px;
            padding: 64px; text-align: center; color: var(--white); position: relative; overflow: hidden;
        }
        .cta-box::before {
            content: ''; position: absolute; inset: 0; opacity: .05;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .cta-box h2 { font-size: clamp(1.6rem, 3.5vw, 2.4rem); font-weight: 700; margin-bottom: 16px; position: relative; }
        .cta-box p { font-size: 1.05rem; opacity: .9; margin-bottom: 32px; max-width: 500px; margin-left: auto; margin-right: auto; position: relative; }
        .cta-buttons { display: flex; gap: 16px; justify-content: center; position: relative; flex-wrap: wrap; }

        /* FOOTER */
        .footer { background: var(--dark); color: rgba(255,255,255,.7); padding: 64px 0 0; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 48px; padding-bottom: 48px; border-bottom: 1px solid rgba(255,255,255,.08); }
        .footer-brand .navbar-logo { margin-bottom: 16px; font-size: 1.4rem; }
        .footer-brand .navbar-logo .logo-icon { background: var(--green); }
        .footer-brand p { font-size: .85rem; line-height: 1.8; max-width: 300px; }
        .footer h4 { color: var(--white); font-size: .85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; }
        .footer ul li { margin-bottom: 10px; }
        .footer ul li a { font-size: .85rem; transition: color var(--transition); }
        .footer ul li a:hover { color: var(--green-glow); }
        .footer-bottom { display: flex; justify-content: space-between; align-items: center; padding: 24px 0; font-size: .78rem; flex-wrap: wrap; gap: 16px; }
        .footer-social { display: flex; gap: 12px; }
        .footer-social a {
            width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,.06);
            display: flex; align-items: center; justify-content: center; transition: all var(--transition);
        }
        .footer-social a:hover { background: var(--green); transform: translateY(-2px); }
        .footer-social a svg { width: 16px; height: 16px; color: rgba(255,255,255,.7); }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .hero .container { grid-template-columns: 1fr; text-align: center; }
            .hero-text { margin-left: auto; margin-right: auto; }
            .hero-buttons { justify-content: center; }
            .hero-stats { justify-content: center; }
            .hero-visual { display: none; }
            .services-grid { grid-template-columns: repeat(3, 1fr); }
            .features-grid { grid-template-columns: 1fr; }
            .features-visual { order: -1; }
            .eco-grid { grid-template-columns: repeat(2, 1fr); }
            .audience-content { grid-template-columns: 1fr; }
            .audience-illustration { order: -1; min-height: 240px; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .section { padding: 64px 0; }
            .navbar-nav { display: none; }
            .navbar-nav.open {
                display: flex; flex-direction: column; position: absolute; top: 100%;
                left: 16px; right: 16px; background: var(--white); border-radius: var(--radius);
                padding: 16px; box-shadow: var(--shadow-lg);
            }
            .navbar-nav.open a { color: var(--gray-800); padding: 12px 16px; }
            .navbar-nav.open .btn-nav { background: var(--green); color: var(--white); text-align: center; justify-content: center; }
            .navbar-toggle { display: flex; }
            .search-bar { grid-template-columns: 1fr; }
            .services-grid { grid-template-columns: repeat(2, 1fr); }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 32px; }
            .eco-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; }
            .cta-box { padding: 40px 24px; }
            .hero-stats { gap: 24px; }
        }
        @media (max-width: 480px) {
            .services-grid { grid-template-columns: 1fr 1fr; }
            .hero-stats { flex-direction: column; align-items: center; gap: 16px; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="container">
        <a href="#" class="navbar-logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            </div>
            HOSTO
        </a>
        <div class="navbar-nav" id="navMenu">
            <a href="#services">Services</a>
            <a href="#fonctionnalites">Fonctionnalites</a>
            <a href="#ecosysteme">Ecosysteme</a>
            <a href="/annuaire">Annuaire</a>
            <a href="/annuaire/medecins">Medecins</a>
            <a href="/medicaments">Medicaments</a>
            <a href="/compte/connexion" class="btn-nav">Connexion</a>
        </div>
        <div class="navbar-toggle" id="navToggle" onclick="document.getElementById('navMenu').classList.toggle('open')">
            <span></span><span></span><span></span>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero" id="hero">
    <div class="hero-pattern"></div>
    <div class="hero-blob hero-blob-1"></div>
    <div class="hero-blob hero-blob-2"></div>
    <div class="container">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="hero-badge-dot"></span>
                Plateforme panafricaine de e-sante
            </div>
            <h1>
                La Sante au bout<br>du Click
                <span>Votre parcours de sante, simplifie et connecte</span>
            </h1>
            <p class="hero-text">
                Trouvez un hopital, un medecin, une pharmacie. Prenez rendez-vous, consultez en ligne,
                gerez vos ordonnances. HOSTO connecte patients, professionnels et etablissements de sante
                dans un ecosysteme unique.
            </p>
            <div class="hero-buttons">
                <a href="#services" class="btn btn-primary">
                    Decouvrir les services
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
                <a href="#ecosysteme" class="btn btn-outline">En savoir plus</a>
            </div>
            <div class="hero-stats">
                <div>
                    <div class="hero-stat-value">14</div>
                    <div class="hero-stat-label">Modules integres</div>
                </div>
                <div>
                    <div class="hero-stat-value">3</div>
                    <div class="hero-stat-label">Plateformes (Web, Mobile, Desktop)</div>
                </div>
                <div>
                    <div class="hero-stat-value">100%</div>
                    <div class="hero-stat-label">Mode offline disponible</div>
                </div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-card-stack">
                <div class="hero-card hero-card-back"></div>
                <div class="hero-card hero-card-main">
                    <div class="card-header">
                        <div class="card-avatar green">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div>
                            <div class="card-name">Dr. Ndong A.</div>
                            <div class="card-role">Cardiologue — CHU Libreville</div>
                        </div>
                    </div>
                    <div class="card-row">
                        <div class="card-row-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        </div>
                        <div>
                            <div class="card-row-text">Rendez-vous aujourd'hui</div>
                            <div class="card-row-sub">14h30 — Consultation</div>
                        </div>
                        <span class="card-row-badge">Confirme</span>
                    </div>
                    <div class="card-row">
                        <div class="card-row-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                        </div>
                        <div>
                            <div class="card-row-text">Ordonnance #2847</div>
                            <div class="card-row-sub">Paracetamol, Amoxicilline</div>
                        </div>
                        <span class="card-row-badge">Envoyee</span>
                    </div>
                    <div class="card-row">
                        <div class="card-row-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        </div>
                        <div>
                            <div class="card-row-text">Resultats labo</div>
                            <div class="card-row-sub">Bilan sanguin complet</div>
                        </div>
                        <span class="card-row-badge">Nouveau</span>
                    </div>
                </div>
                <div class="hero-card hero-card-side">
                    <div class="card-side-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="card-side-value">1 247</div>
                    <div class="card-side-label">Structures referencees</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SEARCH (redirects to /annuaire) -->
<div class="search-section">
    <div class="container">
        <form class="search-bar" action="/annuaire" method="GET">
            <div class="search-field">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <input type="text" name="q" placeholder="Rechercher un hopital, une pharmacie, un medecin...">
            </div>
            <div class="search-field">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <select name="type">
                    <option value="">Type de structure</option>
                    <option value="hopital">Hopital</option>
                    <option value="clinique">Clinique</option>
                    <option value="pharmacie">Pharmacie</option>
                    <option value="laboratoire">Laboratoire</option>
                    <option value="cabinet-medical">Cabinet medical</option>
                </select>
            </div>
            <button type="submit" class="search-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                Rechercher
            </button>
        </form>
    </div>
</div>

<!-- SERVICES -->
<section class="section services" id="services">
    <div class="container">
        <div class="text-center">
            <span class="section-label">Nos services</span>
            <h2 class="section-title">Tous vos services de sante en un seul endroit</h2>
            <p class="section-subtitle mx-auto">HOSTO centralise l'ensemble des services de sante pour simplifier votre parcours medical au quotidien.</p>
        </div>
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon"><img src="{{ asset('images/icons/icon-hopitaux.png') }}" alt="Hopitaux"></div>
                <div class="service-name">Hopitaux & Cliniques</div>
                <div class="service-desc">Annuaire geolocalise des structures de sante</div>
            </div>
            <div class="service-card">
                <div class="service-icon"><img src="{{ asset('images/icons/icon-doctor.png') }}" alt="Medecins"></div>
                <div class="service-name">Medecins</div>
                <div class="service-desc">Trouvez un specialiste pres de chez vous</div>
            </div>
            <div class="service-card">
                <div class="service-icon"><img src="{{ asset('images/icons/icon-pharmacie.png') }}" alt="Pharmacies"></div>
                <div class="service-name">Pharmacies</div>
                <div class="service-desc">Disponibilite des medicaments en temps reel</div>
            </div>
            <div class="service-card">
                <div class="service-icon"><img src="{{ asset('images/icons/icon-laboratoire.png') }}" alt="Laboratoires"></div>
                <div class="service-name">Laboratoires</div>
                <div class="service-desc">Examens, resultats et suivi biologique</div>
            </div>
            <div class="service-card">
                <div class="service-icon"><img src="{{ asset('images/icons/icon-rdv.png') }}" alt="Rendez-vous"></div>
                <div class="service-name">Rendez-vous</div>
                <div class="service-desc">Reservez en ligne, sans file d'attente</div>
            </div>
            <div class="service-card">
                <div class="service-icon"><img src="{{ asset('images/icons/icon-doctorligne.png') }}" alt="Teleconsultation"></div>
                <div class="service-name">Teleconsultation</div>
                <div class="service-desc">Consultez un medecin a distance en visio</div>
            </div>
            <div class="service-card">
                <div class="service-icon"><img src="{{ asset('images/icons/icon-ordonances.png') }}" alt="Ordonnances"></div>
                <div class="service-name">Ordonnances</div>
                <div class="service-desc">Ordonnances electroniques securisees</div>
            </div>
            <div class="service-card">
                <div class="service-icon"><img src="{{ asset('images/icons/icon-ambulance.png') }}" alt="Urgences"></div>
                <div class="service-name">Urgences</div>
                <div class="service-desc">Localisation et appel ambulance rapide</div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="section features" id="fonctionnalites">
    <div class="container">
        <div class="text-center">
            <span class="section-label">Fonctionnalites</span>
            <h2 class="section-title">Une plateforme pensee pour l'Afrique</h2>
            <p class="section-subtitle mx-auto">Concue pour fonctionner meme dans les zones a faible connectivite, HOSTO s'adapte aux realites du continent.</p>
        </div>
        <div class="features-grid">
            <div class="features-list">
                <div class="feature-item">
                    <div class="feature-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div>
                        <div class="feature-title">Geolocalisation intelligente</div>
                        <div class="feature-desc">Trouvez les structures de sante, pharmacies et medecins les plus proches avec horaires, specialites et disponibilite.</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                    </div>
                    <div>
                        <div class="feature-title">Mode offline complet</div>
                        <div class="feature-desc">Dossiers patients, ordonnances et agenda accessibles sans connexion Internet. Synchronisation automatique au retour du reseau.</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div>
                        <div class="feature-title">Securite et consentement</div>
                        <div class="feature-desc">Chiffrement AES-256, authentification 2FA et controle total du patient sur l'acces a son dossier medical.</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <div>
                        <div class="feature-title">Multilingue & commande vocale</div>
                        <div class="feature-desc">Interface en francais, anglais, portugais et langues locales. Navigation vocale integree pour une accessibilite maximale.</div>
                    </div>
                </div>
            </div>
            <div class="features-visual">
                <div class="features-phone">
                    <div class="phone-notch"></div>
                    <div class="features-phone-screen">
                        <div class="phone-header">
                            <div class="phone-header-top">
                                <div class="phone-logo">HOSTO</div>
                                <div class="phone-avatar"></div>
                            </div>
                            <div class="phone-greeting">Bonjour,<strong>Marie N.</strong></div>
                        </div>
                        <div class="phone-body">
                            <div class="phone-search">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                                Rechercher un service...
                            </div>
                            <div class="phone-grid">
                                <div class="phone-service"><div class="phone-service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div><div class="phone-service-label">Hopitaux</div></div>
                                <div class="phone-service"><div class="phone-service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div class="phone-service-label">Medecins</div></div>
                                <div class="phone-service"><div class="phone-service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div><div class="phone-service-label">RDV</div></div>
                                <div class="phone-service"><div class="phone-service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></div><div class="phone-service-label">Pharmacies</div></div>
                                <div class="phone-service"><div class="phone-service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></div><div class="phone-service-label">Ordonnances</div></div>
                                <div class="phone-service"><div class="phone-service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div><div class="phone-service-label">Examens</div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ECOSYSTEM -->
<section class="section ecosystem" id="ecosysteme">
    <div class="container">
        <div class="text-center">
            <span class="section-label">Ecosysteme</span>
            <h2 class="section-title">Un ecosysteme complet pour la sante</h2>
            <p class="section-subtitle mx-auto">14 modules specialises, un noyau commun. Chaque acteur de la sante dispose de son espace dedie.</p>
        </div>
        <div class="eco-grid">
            <div class="eco-card">
                <div class="eco-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                <div class="eco-name">HOSTO <span>Usager</span></div>
                <div class="eco-role">Portail patient : dossier medical, rendez-vous, annuaire, teleconsultation, suivi des traitements.</div>
                <span class="eco-tag">Patient-centric</span>
            </div>
            <div class="eco-card">
                <div class="eco-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
                <div class="eco-name">HOSTO <span>Pro</span></div>
                <div class="eco-role">Gestion complete de l'activite medicale : consultations, dossiers, ordonnances, multi-structures.</div>
                <span class="eco-tag">Professionnels</span>
            </div>
            <div class="eco-card">
                <div class="eco-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><circle cx="12" cy="17" r=".5"/></svg></div>
                <div class="eco-name">HOSTO <span>Pharma</span></div>
                <div class="eco-role">Pharmacies commerciales : stocks, ventes, ordonnances electroniques, commandes et livraisons.</div>
                <span class="eco-tag">Pharmacies</span>
            </div>
            <div class="eco-card">
                <div class="eco-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
                <div class="eco-name">HOSTO <span>Lab</span></div>
                <div class="eco-role">Gestion des laboratoires : catalogue des examens, resultats, prescriptions et tracabilite.</div>
                <span class="eco-tag">Laboratoires</span>
            </div>
            <div class="eco-card">
                <div class="eco-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                <div class="eco-name">HOSTO <span>Assur</span></div>
                <div class="eco-role">Interface assurances : feuilles de soins, prises en charge, remboursements CNAMGS et prives.</div>
                <span class="eco-tag">Assurances</span>
            </div>
            <div class="eco-card">
                <div class="eco-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg></div>
                <div class="eco-name">HOSTO <span>Analytic</span></div>
                <div class="eco-role">Veille sanitaire, tableaux de bord, statistiques avancees et aide a la decision pour les gouvernants.</div>
                <span class="eco-tag">Gouvernement</span>
            </div>
        </div>
    </div>
</section>

<!-- AUDIENCE -->
<section class="section audience" id="solutions">
    <div class="container">
        <div class="text-center">
            <span class="section-label">Solutions</span>
            <h2 class="section-title">Pour chaque acteur de la sante</h2>
            <p class="section-subtitle mx-auto">HOSTO s'adapte a vos besoins, que vous soyez patient, professionnel ou decideur.</p>
        </div>
        <div class="audience-tabs">
            <button class="audience-tab active" onclick="switchTab(0)">Patients</button>
            <button class="audience-tab" onclick="switchTab(1)">Professionnels</button>
            <button class="audience-tab" onclick="switchTab(2)">Hopitaux</button>
            <button class="audience-tab" onclick="switchTab(3)">Gouvernement</button>
        </div>

        <div class="audience-panel active" id="panel-0">
            <div class="audience-content">
                <div>
                    <h3>Votre sante, entre vos mains</h3>
                    <p>Accedez a l'ensemble de vos services de sante depuis votre telephone ou votre ordinateur, ou que vous soyez.</p>
                    <div class="audience-checklist">
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Trouvez un medecin ou une pharmacie a proximite</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Prenez rendez-vous en quelques clics</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Consultez un medecin en video</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Gerez vos ordonnances et dossier medical</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Controlez qui accede a vos donnees</div>
                    </div>
                </div>
                <div class="audience-illustration"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            </div>
        </div>

        <div class="audience-panel" id="panel-1">
            <div class="audience-content">
                <div>
                    <h3>Optimisez votre pratique medicale</h3>
                    <p>HOSTO Pro vous offre les outils pour gerer efficacement votre activite medicale au quotidien.</p>
                    <div class="audience-checklist">
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Gestion multi-structures et multi-specialites</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Dossier patient electronique complet</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Teleconsultation et messagerie securisee</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Ordonnances electroniques et prescriptions labo</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Intranet medical entre confreres</div>
                    </div>
                </div>
                <div class="audience-illustration"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
            </div>
        </div>

        <div class="audience-panel" id="panel-2">
            <div class="audience-content">
                <div>
                    <h3>Digitalisez votre etablissement</h3>
                    <p>HOSTO transforme la gestion de votre structure de sante avec des outils modernes et interoperables.</p>
                    <div class="audience-checklist">
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Gestion complete des patients et du personnel</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Pharmacie interne, stocks et approvisionnement</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Facturation, paiement et rapprochement assurance</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Statistiques et tableaux de bord en temps reel</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Mode local autonome avec synchronisation cloud</div>
                    </div>
                </div>
                <div class="audience-illustration"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
            </div>
        </div>

        <div class="audience-panel" id="panel-3">
            <div class="audience-content">
                <div>
                    <h3>Pilotez la sante publique</h3>
                    <p>HOSTO Analytic fournit aux decideurs les donnees necessaires pour orienter les politiques de sante.</p>
                    <div class="audience-checklist">
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Veille sanitaire et surveillance epidemiologique</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Cartographie dynamique des maladies</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Suivi de la couverture vaccinale</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Gestion des ressources et optimisation des couts</div>
                        <div class="audience-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg> Donnees anonymisees et conformes RGPD</div>
                    </div>
                </div>
                <div class="audience-illustration"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M18 20V10M12 20V4M6 20v-6"/></svg></div>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="stats">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item"><div class="stat-number">14</div><div class="stat-label">Modules specialises</div><div class="stat-divider"></div></div>
            <div class="stat-item"><div class="stat-number">3</div><div class="stat-label">Plateformes (Web, Mobile, Desktop)</div><div class="stat-divider"></div></div>
            <div class="stat-item"><div class="stat-number">HL7</div><div class="stat-label">Interoperabilite FHIR</div><div class="stat-divider"></div></div>
            <div class="stat-item"><div class="stat-number">AES-256</div><div class="stat-label">Chiffrement des donnees</div><div class="stat-divider"></div></div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta">
    <div class="container">
        <div class="cta-box">
            <h2>Rejoignez l'ecosysteme HOSTO</h2>
            <p>Que vous soyez patient, medecin, pharmacien ou responsable d'etablissement, HOSTO a une solution pour vous.</p>
            <div class="cta-buttons">
                <a href="#" class="btn btn-white">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                    Creer un compte
                </a>
                <a href="#" class="btn btn-outline">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
                    Voir la demo
                </a>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="navbar-logo">
                    <div class="logo-icon"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
                    HOSTO
                </div>
                <p>Plateforme panafricaine de e-sante. La sante au bout du click, pour tous, partout en Afrique.</p>
            </div>
            <div>
                <h4>Plateforme</h4>
                <ul>
                    <li><a href="#">HOSTO Pro</a></li>
                    <li><a href="#">HOSTO Pharma</a></li>
                    <li><a href="#">HOSTO Lab</a></li>
                    <li><a href="#">HOSTO Assur</a></li>
                    <li><a href="#">HOSTO Analytic</a></li>
                </ul>
            </div>
            <div>
                <h4>Services</h4>
                <ul>
                    <li><a href="#">Annuaire des hopitaux</a></li>
                    <li><a href="#">Teleconsultation</a></li>
                    <li><a href="#">Pharmacies de garde</a></li>
                    <li><a href="#">Rendez-vous en ligne</a></li>
                    <li><a href="#">SOS Medicale</a></li>
                </ul>
            </div>
            <div>
                <h4>A propos</h4>
                <ul>
                    <li><a href="#">Yubile Technologie</a></li>
                    <li><a href="#">Mentions legales</a></li>
                    <li><a href="#">Confidentialite</a></li>
                    <li><a href="#">Conditions d'utilisation</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>HOSTO &copy; {{ date('Y') }} Yubile Technologie. Tous droits reserves.</span>
            <div class="footer-social">
                <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
                <a href="#" aria-label="Twitter"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg></a>
                <a href="#" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg></a>
            </div>
        </div>
    </div>
</footer>

<script>
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 50);
});

function switchTab(index) {
    document.querySelectorAll('.audience-tab').forEach((tab, i) => {
        tab.classList.toggle('active', i === index);
    });
    document.querySelectorAll('.audience-panel').forEach((panel, i) => {
        panel.classList.toggle('active', i === index);
    });
}

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            document.getElementById('navMenu').classList.remove('open');
        }
    });
});


document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            document.getElementById('navMenu').classList.remove('open');
        }
    });
});
</script>

</body>
</html>
