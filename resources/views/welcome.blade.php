@extends('layouts.app')

@section('styles')
<style>
    /* Override navbar for hero: transparent on top */
    body > .navbar { position: fixed; top: 30px; background: transparent; box-shadow: none; backdrop-filter: none; }
    body > .navbar .navbar-logo { color: white; }
    body > .navbar .navbar-logo .logo-icon { background: white; }
    body > .navbar .navbar-logo .logo-icon svg { stroke: var(--green); }
    body > .navbar .navbar-nav a { color: rgba(255,255,255,.85); }
    body > .navbar .navbar-nav a:hover { color: white; background: rgba(255,255,255,.12); }
    body > .navbar .navbar-nav a.active { color: white; background: rgba(255,255,255,.15); }
    body > .navbar .navbar-nav .btn-nav { background: white; color: var(--green); }
    body > .navbar .navbar-toggle span { background: white; }
    body.scrolled > .navbar { background: rgba(255,255,255,.97); backdrop-filter: blur(20px); box-shadow: 0 1px 20px rgba(0,0,0,.06); }
    body.scrolled > .navbar .navbar-logo { color: var(--green); }
    body.scrolled > .navbar .navbar-logo .logo-icon { background: var(--green); }
    body.scrolled > .navbar .navbar-logo .logo-icon svg { stroke: white; }
    body.scrolled > .navbar .navbar-nav a { color: var(--gray-600); }
    body.scrolled > .navbar .navbar-nav a:hover { color: var(--green); background: var(--green-pale); }
    body.scrolled > .navbar .navbar-nav .btn-nav { background: var(--green); color: white; }
    body.scrolled > .navbar .navbar-toggle span { background: var(--green); }

    .section { padding: 100px 0; }
    .section-label {
        display: inline-block; font-size: .75rem; font-weight: 600; letter-spacing: 2px;
        text-transform: uppercase; color: var(--green); background: var(--green-pale);
        padding: 6px 16px; border-radius: 100px; margin-bottom: 16px;
    }
    .section-title { font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 700; color: var(--dark); margin-bottom: 16px; line-height: 1.2; }
    .section-subtitle { font-size: 1.05rem; color: var(--gray-600); max-width: 620px; line-height: 1.8; }
    .text-center { text-align: center; }
    .mx-auto { margin-left: auto; margin-right: auto; }

    .btn-outline { background: transparent; color: white; border: 2px solid rgba(255,255,255,.5); }
    .btn-outline:hover { background: white; color: var(--green); border-color: white; }
    .btn-white { background: white; color: var(--green); box-shadow: var(--shadow-md); }
    .btn-white:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }

    /* HERO */
    .hero {
        position: relative; min-height: 100vh; display: flex; align-items: center;
        background: linear-gradient(145deg, var(--green-dark) 0%, var(--green) 40%, var(--green-mid) 70%, var(--green-light) 100%);
        overflow: hidden; margin-top: -70px; padding-top: 70px;
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
    .hero .container { position: relative; z-index: 2; text-align: center; padding-top: 80px; padding-bottom: 60px; }
    .hero-content { color: white; max-width: 700px; margin: 0 auto; }
    .hero-badge {
        display: inline-flex; align-items: center; gap: 8px; padding: 6px 16px 6px 8px;
        background: rgba(255,255,255,.12); backdrop-filter: blur(10px); border-radius: 100px;
        font-size: .78rem; font-weight: 500; color: rgba(255,255,255,.9); margin-bottom: 28px;
        border: 1px solid rgba(255,255,255,.15);
    }
    .hero-badge-dot { width: 8px; height: 8px; background: #69F0AE; border-radius: 50%; animation: pulse-dot 2s ease-in-out infinite; }
    @keyframes pulse-dot { 0%,100%{opacity:1;transform:scale(1);} 50%{opacity:.5;transform:scale(1.3);} }
    .hero h1 { font-size: clamp(2.2rem, 5vw, 3.4rem); font-weight: 800; line-height: 1.1; margin-bottom: 8px; letter-spacing: -1px; }
    .hero h1 span { display: block; font-size: clamp(1rem, 2.5vw, 1.3rem); font-weight: 300; letter-spacing: 2px; opacity: .85; margin-top: 8px; }
    .hero-text { font-size: 1.05rem; line-height: 1.8; opacity: .9; margin: 24px auto 36px; max-width: 560px; }
    .hero-buttons { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }

    /* SEARCH BAR */
    .search-section { position: relative; z-index: 10; margin-top: -40px; }
    .search-bar {
        background: white; border-radius: var(--radius); padding: 12px;
        box-shadow: var(--shadow-lg); display: flex; gap: 8px; align-items: center;
        border: 1px solid var(--gray-200);
    }
    .search-field { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: var(--radius-sm); flex: 1; }
    .search-field:hover { background: var(--gray-50); }
    .search-field svg { width: 18px; height: 18px; color: var(--green); flex-shrink: 0; }
    .search-field input { border:none; outline:none; font-family:'Poppins',sans-serif; font-size:.85rem; color:var(--dark); background:transparent; width:100%; }
    .search-btn {
        padding: 12px 28px; background: var(--green); color: white; border: none;
        border-radius: var(--radius-sm); font-family: 'Poppins',sans-serif; font-size: .85rem;
        font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;
        white-space: nowrap; transition: all var(--transition);
    }
    .search-btn:hover { background: var(--green-dark); }
    .search-btn svg { width: 16px; height: 16px; }

    /* SERVICES GRID */
    .services-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 48px; }
    .service-card {
        background: white; border: 1px solid var(--gray-200); border-radius: var(--radius);
        padding: 32px 24px; text-align: center; transition: all var(--transition); cursor: pointer;
    }
    .service-card:hover { border-color: var(--green-light); transform: translateY(-6px); box-shadow: var(--shadow-md); }
    .service-icon { width: 64px; height: 64px; margin: 0 auto 16px; }
    .service-icon img { width: 100%; height: 100%; object-fit: contain; }
    .service-name { font-size: .9rem; font-weight: 600; color: var(--dark); margin-bottom: 6px; }
    .service-desc { font-size: .78rem; color: var(--gray-600); line-height: 1.5; }

    /* STATS */
    .stats { background: linear-gradient(135deg, var(--green-dark), var(--green-mid)); padding: 80px 0; }
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 32px; }
    .stat-item { text-align: center; color: white; }
    .stat-number { font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 800; line-height: 1; margin-bottom: 8px; }
    .stat-label { font-size: .85rem; opacity: .8; }
    .stat-divider { width: 40px; height: 3px; background: rgba(255,255,255,.3); margin: 12px auto 0; border-radius: 100px; }

    /* CTA */
    .cta { padding: 100px 0; }
    .cta-box {
        background: linear-gradient(135deg, var(--green), var(--green-mid)); border-radius: 24px;
        padding: 64px; text-align: center; color: white; position: relative; overflow: hidden;
    }
    .cta-box h2 { font-size: clamp(1.6rem, 3.5vw, 2.4rem); font-weight: 700; margin-bottom: 16px; position: relative; }
    .cta-box p { font-size: 1.05rem; opacity: .9; margin-bottom: 32px; max-width: 500px; margin-left: auto; margin-right: auto; position: relative; }
    .cta-buttons { display: flex; gap: 16px; justify-content: center; position: relative; flex-wrap: wrap; }

    @media (max-width: 1024px) { .services-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 768px) {
        .section { padding: 64px 0; }
        .search-bar { flex-direction: column; }
        .search-field { width: 100%; }
        .search-btn { width: 100%; justify-content: center; }
        .services-grid { grid-template-columns: repeat(2, 1fr); }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .hero { margin-top: -56px; padding-top: 56px; }
        .hero-buttons { flex-direction: column; align-items: center; }
        .hero-buttons .btn { width: 100%; justify-content: center; max-width: 300px; }
        .cta-box { padding: 40px 24px; }
    }
    @media (max-width: 480px) {
        .services-grid { grid-template-columns: 1fr; }
        .stats-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
<!-- HERO -->
<section class="hero">
    <div class="hero-pattern"></div>
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
                gerez vos ordonnances. HOSTO connecte patients, professionnels et etablissements de sante.
            </p>
            <div class="hero-buttons">
                <a href="/annuaire" class="btn btn-primary">
                    Trouver une structure
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
                <a href="#services" class="btn btn-outline">Nos services</a>
            </div>
        </div>
    </div>
</section>

<!-- SEARCH BAR (redirects to /annuaire) -->
<div class="search-section">
    <div class="container">
        <form class="search-bar" action="/annuaire" method="GET">
            <div class="search-field">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" name="q" placeholder="Rechercher un hopital, une pharmacie, un medecin...">
            </div>
            <button type="submit" class="search-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                Rechercher
            </button>
        </form>
    </div>
</div>

<!-- SERVICES -->
<section class="section" id="services">
    <div class="container">
        <div class="text-center">
            <span class="section-label">Nos services</span>
            <h2 class="section-title">Tous vos services de sante en un seul endroit</h2>
            <p class="section-subtitle mx-auto">HOSTO centralise l'ensemble des services de sante pour simplifier votre parcours medical au quotidien.</p>
        </div>
        <div class="services-grid">
            <a href="/annuaire?type=hopital" class="service-card"><div class="service-icon"><img src="{{ asset('images/icons/icon-hopitaux.png') }}" alt="Hopitaux"></div><div class="service-name">Hopitaux & Cliniques</div><div class="service-desc">Annuaire geolocalise des structures de sante</div></a>
            <a href="/annuaire" class="service-card"><div class="service-icon"><img src="{{ asset('images/icons/icon-doctor.png') }}" alt="Medecins"></div><div class="service-name">Medecins</div><div class="service-desc">Trouvez un specialiste pres de chez vous</div></a>
            <a href="/annuaire?type=pharmacie" class="service-card"><div class="service-icon"><img src="{{ asset('images/icons/icon-pharmacie.png') }}" alt="Pharmacies"></div><div class="service-name">Pharmacies</div><div class="service-desc">Disponibilite des medicaments en temps reel</div></a>
            <a href="/annuaire?type=laboratoire" class="service-card"><div class="service-icon"><img src="{{ asset('images/icons/icon-laboratoire.png') }}" alt="Laboratoires"></div><div class="service-name">Laboratoires</div><div class="service-desc">Examens, resultats et suivi biologique</div></a>
            <a href="/annuaire" class="service-card"><div class="service-icon"><img src="{{ asset('images/icons/icon-rdv.png') }}" alt="Rendez-vous"></div><div class="service-name">Rendez-vous</div><div class="service-desc">Reservez en ligne, sans file d'attente</div></a>
            <a href="/annuaire" class="service-card"><div class="service-icon"><img src="{{ asset('images/icons/icon-doctorligne.png') }}" alt="Teleconsultation"></div><div class="service-name">Teleconsultation</div><div class="service-desc">Consultez un medecin a distance en visio</div></a>
            <a href="/annuaire" class="service-card"><div class="service-icon"><img src="{{ asset('images/icons/icon-ordonances.png') }}" alt="Ordonnances"></div><div class="service-name">Ordonnances</div><div class="service-desc">Ordonnances electroniques securisees</div></a>
            <a href="/annuaire?type=pharmacie&garde=1" class="service-card"><div class="service-icon"><img src="{{ asset('images/icons/icon-ambulance.png') }}" alt="Urgences"></div><div class="service-name">Urgences & Garde</div><div class="service-desc">Pharmacies de garde et services d'urgence</div></a>
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
<section class="cta" id="ecosysteme">
    <div class="container">
        <div class="cta-box">
            <h2>Rejoignez l'ecosysteme HOSTO</h2>
            <p>Que vous soyez patient, medecin, pharmacien ou responsable d'etablissement, HOSTO a une solution pour vous.</p>
            <div class="cta-buttons">
                <a href="/annuaire" class="btn btn-white">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    Explorer l'annuaire
                </a>
                <a href="#" class="btn btn-outline">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                    Creer un compte
                </a>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
window.addEventListener('scroll', () => {
    document.body.classList.toggle('scrolled', window.scrollY > 50);
});
</script>
@endsection
