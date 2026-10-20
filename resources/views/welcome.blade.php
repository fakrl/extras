<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <script>try { document.documentElement.setAttribute('data-theme', localStorage.getItem('jbtb-theme-v2') || 'light'); } catch (e) {}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <meta name="theme-color" content="#f1f0e9">
    <link rel="icon" href="{{ asset('images/logo-jbtb.jpg') }}" type="image/jpeg">
    <title>JBTB Casting — Agensi Extras & Talent Jakarta</title>
    <link rel="canonical" href="{{ url('/') }}">
    @include('partials.og-meta', [
        'noindex' => false,
        'ogTitle' => 'JBTB Casting — Agensi Extras & Talent Jakarta',
        'ogDesc' => 'Daftar jadi extras, lamar casting yang sedang buka, kontrak dan honor tercatat jelas di satu tempat di JBTB Casting.',
        'ogImage' => asset('images/hero-clapboard.jpg'),
    ])

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@@tabler/icons-webfont@3.48.0/dist/tabler-icons.min.css">
    @include('partials.theme-style')
    <style>
        /* ── Palet Warna Homepage (AK.7) ── */
        :root[data-theme="dark"] {
            --hp-bg:       #111311;
            --hp-fg:       #f2f1eb;
            --hp-muted:    rgba(242, 241, 235, 0.55);
            --hp-accent:   #b7ff3c;
            --hp-accent-on:#111311;
            --hp-line:     rgba(255, 255, 255, 0.13);
            --hp-card:     #1a1c1a;
        }
        :root[data-theme="light"] {
            --hp-bg:       #f1f0e9;
            --hp-fg:       #171a16;
            --hp-muted:    #52564e;
            --hp-accent:   #486610;
            --hp-accent-on:#ffffff;
            --hp-line:     rgba(23, 26, 22, 0.16);
            --hp-card:     #e6e4da;
        }

        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', sans-serif; background: var(--hp-bg); color: var(--hp-fg); }

        a:focus-visible, button:focus-visible, summary:focus-visible { outline: 2px solid var(--hp-accent); outline-offset: 2px; }

        .film-strip-divider {
            height: 18px;
            background: repeating-linear-gradient(90deg, var(--hp-card) 0 14px, transparent 14px 28px), var(--hp-line);
            background-position: center;
            background-size: 28px 10px, 100% 2px;
            background-repeat: repeat-x, no-repeat;
            opacity: 0.6;
        }

        .section-eyebrow {
            display: block; font-size: var(--fs-xs); font-weight: 700; letter-spacing: 3px;
            text-transform: uppercase; color: var(--hp-accent); margin-bottom: 10px;
        }

        /* ── Navbar ── */
        .hp-nav {
            position: sticky; top: 0; z-index: 100;
            background: var(--hp-bg);
            border-bottom: 1px solid var(--hp-line);
            padding: 0 32px;
            display: flex; align-items: center; justify-content: space-between; height: 56px;
        }
        .hp-nav-brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 15px; color: var(--hp-fg); }
        .hp-logo { width: 34px; height: 34px; border-radius: 8px; object-fit: contain; display: block; flex-shrink: 0; }
        .hp-nav-menu { list-style: none; margin: 0; padding: 0; display: flex; gap: 2px; align-items: center; flex: 1; justify-content: center; }
        .hp-nav-menu a { display: flex; align-items: center; min-height: 44px; font-size: 13.5px; color: var(--hp-muted); text-decoration: none; padding: 0 11px; border-radius: 7px; }
        .hp-nav-menu a:hover { color: var(--hp-fg); background: var(--hp-card); }
        .hp-menu-toggle { display: none; width: 44px; height: 44px; border-radius: 8px; border: none; cursor: pointer; background: var(--hp-card); color: var(--hp-fg); align-items: center; justify-content: center; font-size: 18px; }
        .hp-nav-actions { display: flex; gap: 8px; align-items: center; }
        .theme-toggle-btn { width: 44px; height: 44px; border-radius: 50%; border: none; cursor: pointer; background: var(--hp-card); color: var(--hp-muted); display: flex; align-items: center; justify-content: center; font-size: 15px; }
        .avatar-badge { width: 44px; height: 44px; border-radius: 50%; border: none; cursor: pointer; background: var(--hp-accent); color: var(--hp-accent-on); font-size: var(--fs-xs); font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .avatar-badge-img { object-fit: cover; padding: 0; }
        .navbar-user-menu { position: relative; list-style: none; }
        .navbar-user-menu > summary { list-style: none; cursor: pointer; }
        .navbar-user-menu > summary::-webkit-details-marker { display: none; }
        .navbar-user-menu-dropdown { display: none; position: absolute; right: 0; top: calc(100% + 8px); z-index: 200; background: var(--hp-card); border: 1px solid var(--hp-line); border-radius: 10px; min-width: 160px; padding: 6px; box-shadow: 0 4px 16px rgba(0,0,0,0.25); }
        .navbar-user-menu[open] .navbar-user-menu-dropdown { display: block; }
        .navbar-user-menu-dropdown a, .navbar-user-menu-dropdown button { display: flex; align-items: center; gap: 8px; width: 100%; padding: 0 12px; min-height: 44px; border-radius: 7px; font-size: 13.5px; font-weight: 500; color: var(--hp-fg); text-decoration: none; background: none; border: none; cursor: pointer; }
        .navbar-user-menu-dropdown a:hover, .navbar-user-menu-dropdown button:hover { background: var(--hp-bg); }
        .btn-brand { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 20px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; background: var(--hp-accent); color: var(--hp-accent-on); border: none; }
        .btn-brand:hover { filter: brightness(1.08); }
        .btn-outline { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 20px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; background: transparent; color: var(--hp-fg); border: 1px solid var(--hp-line); }
        .btn-outline:hover { background: var(--hp-card); }

        /* ── Hero (AK.1 + AK.6) ── */
        .hero {
            position: relative; overflow: hidden;
            border-top: 1px solid var(--hp-accent);
            min-height: 560px;
            display: flex; align-items: center;
            background-image:
                linear-gradient(90deg,
                    var(--hp-bg) 0%,
                    color-mix(in srgb, var(--hp-bg) 82%, transparent) 52%,
                    color-mix(in srgb, var(--hp-bg) 50%, transparent) 100%),
                url('/images/homepage-hero-bg.webp');
            background-size: cover;
            background-position: center;
        }
        .hero-inner { position: relative; z-index: 2; max-width: 1100px; margin: 0 auto; width: 100%; padding: 96px 32px 88px; }
        .hero-headline {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: clamp(3rem, 11vw, 9rem);
            line-height: 0.85;
            letter-spacing: -0.04em;
            font-weight: 400;
            margin: 10px 0 40px;
            color: var(--hp-fg);
        }
        .hero-h-indent { display: inline-block; margin-left: 10vw; }
        .hero-h-italic { font-style: italic; color: var(--hp-muted); }
        .hero-h-accent { color: var(--hp-accent); }
        @media (max-width: 767px) {
            .hero { min-height: 480px; background-position: 68% center; }
            .hero-inner { padding: 64px 20px 56px; }
        }

        /* ── Carousel (BH.2/BH.3): geser manual (drag mouse/trackpad/swipe), bukan auto-animasi ── */
        .marquee { position: relative; max-width: var(--hp-reel, 1440px); margin-inline: auto; overflow-x: auto; scroll-snap-type: x proximity; scrollbar-width: none; cursor: grab; }
        .marquee::-webkit-scrollbar { display: none; }
        .marquee.is-dragging { cursor: grabbing; scroll-snap-type: none; user-select: none; }
        .marquee::before, .marquee::after { content: ''; position: absolute; top: 0; bottom: 0; width: 80px; z-index: 2; pointer-events: none; }
        .marquee::before { left: 0; background: linear-gradient(to right, var(--hp-bg), transparent); }
        .marquee::after { right: 0; background: linear-gradient(to left, var(--hp-bg), transparent); }
        .marquee-track { display: flex; width: max-content; gap: 20px; padding: 4px 32px; }
        .marquee-track > * { scroll-snap-align: start; }
        .marquee-wrap { position: relative; }
        .marquee-nav { display: none; }
        @media (min-width: 861px) {
            .marquee-nav { display: flex; position: absolute; top: 50%; transform: translateY(-50%); z-index: 3; width: 36px; height: 36px; border-radius: 50%; align-items: center; justify-content: center; background: var(--hp-card); border: 1px solid var(--hp-line); color: var(--hp-fg); font-size: 18px; line-height: 1; cursor: pointer; }
            .marquee-nav.prev { left: 8px; }
            .marquee-nav.next { right: 8px; }
            .marquee-nav:hover { background: var(--hp-accent); color: var(--hp-accent-on); }
        }
        .hp-sec-head { max-width: 1100px; margin: 0 auto 2.5rem; padding: 0 32px; display: flex; justify-content: space-between; align-items: flex-end; gap: 16px 24px; flex-wrap: wrap; }
        .hp-sec-title { font-family: Georgia, 'Times New Roman', serif; font-size: clamp(2.2rem, 5.5vw, 3.75rem); font-weight: 400; line-height: 1; letter-spacing: -0.04em; margin: 0; color: var(--hp-fg); }
        .hp-sec-note { font-size: 12px; letter-spacing: 0.16em; text-transform: uppercase; color: var(--hp-muted); }

        /* ── Cast (BH.2) ── */
        .cast-section { padding-block: 7rem; border-top: 1px solid var(--hp-line); overflow: hidden; }
        .cast-card { flex: 0 0 210px; display: block; color: inherit; text-decoration: none; }
        .cast-card:focus-visible { outline: 2px solid var(--hp-accent); outline-offset: 4px; }
        .cast-photo-wrap { aspect-ratio: 0.78; overflow: hidden; background: var(--hp-card); position: relative; border-radius: 4px; }
        .cast-photo { width: 100%; height: 100%; object-fit: cover; display: block; filter: grayscale(1); transition: filter .5s ease, transform .5s ease; }
        .cast-card:hover .cast-photo, .cast-card:focus-visible .cast-photo { filter: grayscale(0); transform: scale(1.05); }
        .cast-no { position: absolute; left: 12px; top: 10px; font-size: 12px; color: rgba(255, 255, 255, 0.8); text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6); }
        .cast-view { position: absolute; right: 12px; bottom: 12px; border-radius: 999px; background: var(--hp-accent); color: var(--hp-accent-on); font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; padding: 4px 10px; opacity: 0; transition: opacity .3s; }
        .cast-card:hover .cast-view, .cast-card:focus-visible .cast-view { opacity: 1; }
        .cast-name { font-family: Georgia, 'Times New Roman', serif; font-size: 1.3rem; letter-spacing: -0.03em; margin-top: 14px; color: var(--hp-fg); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cast-tag { margin-top: 4px; font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; color: var(--hp-muted); }
        @media (prefers-reduced-motion: reduce) { .cast-photo, .cast-view { transition: none; } .cast-card:hover .cast-photo { transform: none; } }
        @media (max-width: 767px) { .cast-card { flex-basis: 160px; } }

        .section-title { font-size: 18px; font-weight: 700; margin: 0 0 16px; color: var(--hp-fg); }
        .section-body { font-size: 14px; color: var(--hp-muted); line-height: 1.75; margin: 0; }

        /* ── Cara Kerja (CK.2.3) ── */
        .services-section { padding-block: 7rem; border-top: 1px solid var(--hp-line); }
        .services-inner { max-width: 1100px; margin: 0 auto; padding: 0 32px; display: grid; grid-template-columns: 1fr 2fr; gap: 56px; align-items: start; }
        .services-left-title { font-family: Georgia, 'Times New Roman', serif; font-size: clamp(1.8rem, 4vw, 3rem); font-weight: 400; line-height: 1.1; letter-spacing: -0.02em; margin: 8px 0 0; color: var(--hp-fg); }
        .services-list { border-top: 1px solid var(--hp-line); }
        .services-item { display: flex; gap: 20px; align-items: flex-start; padding: 20px 0; border-bottom: 1px solid var(--hp-line); }
        .services-num { font-family: Georgia, 'Times New Roman', serif; font-size: 13px; color: var(--hp-muted); flex-shrink: 0; padding-top: 2px; letter-spacing: 1px; min-width: 28px; }
        .services-body p { font-size: 13.5px; color: var(--hp-muted); margin: 0; line-height: 1.65; }

        /* ── Lowongan list ala loker (AK.9) ── */
        .lowongan-section { padding-block: 7rem; border-top: 1px solid var(--hp-line); }
        .lowongan-inner { max-width: 1100px; margin: 0 auto; padding: 0 32px; }
        .lowongan-list { display: flex; flex-direction: column; border-top: 1px solid var(--hp-line); margin-top: 2.5rem; }
        .lowongan-item { border-bottom: 1px solid var(--hp-line); }
        .lowongan-summary {
            display: flex; align-items: center; gap: 16px;
            padding: 18px 0; cursor: pointer; list-style: none;
        }
        .lowongan-summary::-webkit-details-marker { display: none; }
        .lowongan-summary::marker { display: none; }
        .lowongan-thumb {
            width: 56px; height: 56px; border-radius: 10px; overflow: hidden;
            flex-shrink: 0; display: flex; align-items: center; justify-content: center;
            background: color-mix(in srgb, var(--hp-accent) 18%, var(--hp-card));
        }
        .lowongan-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .lowongan-thumb-mono { font-size: 22px; font-weight: 700; color: var(--hp-accent); line-height: 1; }
        .lowongan-summary-info { flex: 1; min-width: 0; }
        .lowongan-summary-title { font-size: 15px; font-weight: 600; color: var(--hp-fg); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .lowongan-summary-meta { font-size: 12.5px; color: var(--hp-muted); margin-top: 3px; }
        .lowongan-summary-badges { display: flex; gap: 6px; align-items: center; flex-shrink: 0; }
        .badge-dibuka { display: inline-block; font-size: var(--fs-xs); font-weight: 700; letter-spacing: 0.5px; background: var(--hp-accent); color: var(--hp-accent-on); padding: 2px 8px; border-radius: 3px; }
        .lowongan-summary-badges .badge-urgent { padding: 2px 8px; border-radius: 3px; letter-spacing: 0.5px; }
        .lowongan-chevron { font-size: 18px; color: var(--hp-muted); flex-shrink: 0; transition: transform .2s; line-height: 1; }
        .lowongan-item[open] { border-left: 3px solid var(--hp-accent); background: color-mix(in srgb, var(--hp-accent) 8%, transparent); padding-left: 10px; margin-left: -13px; }
        .lowongan-item[open] .lowongan-chevron { transform: rotate(45deg); }
        .lowongan-item[open] .lowongan-summary-title { font-weight: 700; }
        @media (prefers-reduced-motion: reduce) { .lowongan-chevron { transition: none; } }
        .lowongan-detail { padding: 4px 0 20px 72px; display: flex; flex-direction: column; gap: 12px; }
        .lowongan-roles { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 6px; }
        .lowongan-roles li { font-size: 13px; color: var(--hp-muted); display: flex; justify-content: space-between; gap: 8px; }
        .lowongan-empty { text-align: center; color: var(--hp-muted); font-size: 14px; padding: 40px; border: 1px dashed var(--hp-line); border-radius: 12px; margin-top: 2.5rem; }

        /* ── Kontak (AK.10) ── */
        .contact-section { padding-block: 7rem; border-top: 1px solid var(--hp-line); }
        .contact-inner { max-width: 1100px; margin: 0 auto; padding: 0 32px; display: grid; grid-template-columns: 1fr 2fr; gap: 56px; align-items: start; }
        .contact-left-title { font-family: Georgia, 'Times New Roman', serif; font-size: clamp(1.8rem, 4vw, 3rem); font-weight: 400; line-height: 1.1; letter-spacing: -0.02em; margin: 8px 0 0; color: var(--hp-fg); }
        .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px 40px; }
        .contact-block strong { display: block; font-size: var(--fs-xs); font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--hp-muted); margin-bottom: 8px; }
        .contact-block p { font-size: 14px; color: var(--hp-fg); margin: 0; line-height: 1.6; }
        .contact-block a { font-size: 14px; color: var(--hp-fg); text-decoration: none; border-bottom: 1px solid var(--hp-line); }
        .contact-block a:hover { color: var(--hp-accent); border-color: var(--hp-accent); }

        /* ── Portofolio (BH.3) ── */
        .porto-section { padding-block: 7rem; border-top: 1px solid var(--hp-line); overflow: hidden; }
        .porto-card { flex: 0 0 380px; margin: 0; }
        .porto-img { aspect-ratio: 1.28; overflow: hidden; border-radius: 4px; position: relative; background: linear-gradient(135deg, color-mix(in srgb, var(--hp-accent) 35%, var(--hp-card)), var(--hp-card) 70%); }
        .porto-img img { width: 100%; height: 100%; object-fit: cover; display: block; filter: grayscale(1); transition: filter .5s ease, transform .5s ease; }
        .porto-card:hover .porto-img img { filter: grayscale(0); transform: scale(1.05); }
        .porto-img .ti { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 44px; color: var(--hp-muted); }
        .porto-title { font-family: Georgia, 'Times New Roman', serif; font-size: 1.5rem; font-weight: 400; letter-spacing: -0.03em; line-height: 1.15; margin: 16px 0 0; color: var(--hp-fg); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .porto-sub { margin-top: 6px; font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; color: var(--hp-muted); }
        @media (prefers-reduced-motion: reduce) { .porto-img img { transition: none; } .porto-card:hover .porto-img img { transform: none; } }
        @media (max-width: 767px) { .porto-card { flex-basis: 280px; } }

        /* ── Footer (AK.10 enhanced) ── */
        footer { border-top: 1px solid var(--hp-line); padding: 24px 32px; }
        .footer-inner { max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: 1fr auto auto; gap: 24px; align-items: center; }
        .footer-brand { display: flex; align-items: center; gap: 9px; font-size: 13px; font-weight: 600; color: var(--hp-fg); text-decoration: none; }
        .footer-brand img { width: 26px; height: 26px; border-radius: 6px; object-fit: contain; }
        .footer-nav { display: flex; gap: 20px; }
        .footer-nav a { display: inline-flex; align-items: center; min-height: 44px; font-size: 13px; color: var(--hp-muted); text-decoration: none; white-space: nowrap; }
        .footer-nav a:hover { color: var(--hp-fg); }
        .footer-legal { display: flex; gap: 16px; align-items: center; font-size: 13px; color: var(--hp-muted); }
        .footer-legal a { display: inline-flex; align-items: center; min-height: 44px; color: var(--hp-muted); text-decoration: none; }
        .footer-legal a:hover { color: var(--hp-fg); }

        .cta-row { display: flex; gap: 12px; flex-wrap: wrap; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .hp-nav { padding: 0 16px; position: relative; }
            .hp-nav-menu { display: none; position: absolute; top: 56px; left: 0; right: 0; flex-direction: column; align-items: flex-start; background: var(--hp-bg); border-bottom: 1px solid var(--hp-line); padding: 8px 0; z-index: 99; }
            .hp-nav-menu.open { display: flex; }
            .hp-nav-menu a { padding: 10px 20px; border-radius: 0; width: 100%; }
            .hp-menu-toggle { display: flex; }
            .services-inner,
            .lowongan-inner,
            .hp-sec-head,
            .footer-inner { padding-left: 20px; padding-right: 20px; }
            .cast-section, .services-section, .lowongan-section, .porto-section { padding-block: 5rem; }
            .services-inner { grid-template-columns: 1fr; gap: 28px; }
            .contact-inner { grid-template-columns: 1fr; gap: 32px; }
            .contact-grid { grid-template-columns: 1fr; gap: 24px; }
            .lowongan-detail { padding-left: 0; }
            .footer-inner { grid-template-columns: 1fr; gap: 16px; }
            .footer-nav { flex-wrap: wrap; gap: 12px; }
            footer { padding: 20px; }
        }
    </style>
</head>
<body>
    {{-- Navbar --}}
    <nav class="hp-nav" aria-label="Menu utama">
        <div class="hp-nav-brand">
            <img src="{{ asset('images/logo-jbtb.jpg') }}" alt="Logo JBTB" class="hp-logo">
            JBTB Casting
        </div>
        <ul class="hp-nav-menu" id="hp-nav-menu">
            <li><a href="{{ route('home') }}">Beranda</a></li>
            <li><a href="#lowongan">Lowongan</a></li>
            <li><a href="#cara-kerja">Cara Kerja</a></li>
            <li><a href="#faq">FAQ</a></li>
        </ul>
        <div class="hp-nav-actions">
            <button type="button" class="hp-menu-toggle" id="menu-toggle" aria-label="Buka menu" aria-controls="hp-nav-menu" aria-expanded="false">
                <i class="ti ti-menu-2"></i>
            </button>
            <button type="button" class="theme-toggle-btn" id="theme-toggle" aria-label="Ganti tema" aria-pressed="false">
                <i class="ti ti-moon" id="theme-icon"></i>
            </button>
            @auth
                <details class="navbar-user-menu" id="navbar-user-menu">
                    <summary class="avatar-badge-summary" aria-label="Menu akun">
                        @if (auth()->user()->extrasProfile?->foto_profil_path)
                            <img src="{{ route('extras.media.foto', auth()->user()->extrasProfile) }}" class="avatar-badge avatar-badge-img" alt="Foto profil">
                        @else
                            <div class="avatar-badge">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                        @endif
                    </summary>
                    <div class="navbar-user-menu-dropdown">
                        <a href="/dashboard"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"><i class="ti ti-logout"></i> Keluar</button>
                        </form>
                    </div>
                </details>
            @else
                <a href="{{ route('login') }}" class="btn-brand" style="font-size: 13px; padding: 0 16px;">Masuk</a>
            @endauth
        </div>
    </nav>

    <main>
    {{-- Hero (AK.1 + AK.6) --}}
    <div class="hero">
        <div class="hero-inner">
            <span class="section-eyebrow">Sistem Manajemen Casting Extras</span>
            <h1 class="hero-headline">
                Casting extras<br>
                <span class="hero-h-indent hero-h-italic">film &amp; iklan</span><br>
                <span class="hero-h-accent">yang dipercaya.</span>
            </h1>
            <p class="section-body" style="max-width: 440px; margin: -16px 0 32px;">Daftar jadi extras, lamar casting yang sedang buka, kontrak dan honor tercatat jelas di satu tempat.</p>
            <div class="cta-row">
                @guest
                    <a href="{{ route('register') }}" class="btn-brand">Daftar jadi Extras</a>
                    <a href="#lowongan" class="btn-outline">Lihat lowongan</a>
                @else
                    @if (auth()->user()->role === 'extras')
                        <a href="#lowongan" class="btn-brand">Lihat lowongan</a>
                    @else
                        <a href="/dashboard" class="btn-brand">Buka Dashboard</a>
                    @endif
                @endguest
            </div>
        </div>
    </div>

    {{-- Cara Kerja (CK.2.3): 3 langkah ringkas, menggantikan Services --}}
    <div class="services-section" id="cara-kerja">
        <div class="services-inner">
            <div>
                <span class="section-eyebrow">Cara Kerja</span>
                <div class="services-left-title">3 Langkah<br>Mudah</div>
            </div>
            <div class="services-list">
                <div class="services-item">
                    <div class="services-num"><i class="ti ti-user-plus" aria-hidden="true" style="font-size: 20px; color: var(--hp-accent);"></i></div>
                    <div class="services-body"><p>Daftar &amp; lengkapi profil: foto, tinggi badan, kategori.</p></div>
                </div>
                <div class="services-item">
                    <div class="services-num"><i class="ti ti-camera" aria-hidden="true" style="font-size: 20px; color: var(--hp-accent);"></i></div>
                    <div class="services-body"><p>Lamar casting yang sedang buka.</p></div>
                </div>
                <div class="services-item">
                    <div class="services-num"><i class="ti ti-signature" aria-hidden="true" style="font-size: 20px; color: var(--hp-accent);"></i></div>
                    <div class="services-body"><p>Terpilih? Tanda tangan kontrak dan pantau honormu.</p></div>
                </div>
            </div>
        </div>
    </div>

    <div class="film-strip-divider" aria-hidden="true"></div>

    {{-- Lowongan Casting (AK.9: list ringkas ala loker) --}}
    <div class="lowongan-section" id="lowongan">
        <div class="lowongan-inner">
            <span class="section-eyebrow">Sedang Tayang</span>
            <div class="section-title">Lowongan Casting Terbuka</div>
            @if ($proyekTerbuka->isEmpty())
                <div class="lowongan-empty">Belum ada lowongan casting yang terbuka saat ini.</div>
            @else
                <div class="lowongan-list">
                    @foreach ($proyekTerbuka as $proyek)
                        <details class="lowongan-item">
                            <summary class="lowongan-summary">
                                {{-- Thumbnail: poster → monogram PH --}}
                                <div class="lowongan-thumb">
                                    @if ($proyek->poster_path)
                                        <img src="{{ Storage::url($proyek->poster_path) }}" alt="">
                                    @else
                                        <div class="lowongan-thumb-mono">{{ strtoupper(mb_substr($proyek->tampilkan_nama_client && $proyek->client ? $proyek->namaClient() : $proyek->nama_produksi, 0, 1)) }}</div>
                                    @endif
                                </div>
                                <div class="lowongan-summary-info">
                                    <div class="lowongan-summary-title">{{ $proyek->nama_produksi }}</div>
                                    <div class="lowongan-summary-meta">Deadline {{ $proyek->deadline->translatedFormat('d M Y') }}</div>
                                </div>
                                <div class="lowongan-summary-badges">
                                    <span class="badge-dibuka">Dibuka</span>
                                    @if ($proyek->isUrgent())
                                        <span class="badge badge-urgent">Butuh Dadakan</span>
                                    @endif
                                </div>
                                <span class="lowongan-chevron" aria-hidden="true">+</span>
                            </summary>
                            <div class="lowongan-detail">
                                @if ($proyek->classes->isNotEmpty())
                                    <ul class="lowongan-roles">
                                        @foreach ($proyek->classes as $kelas)
                                            <li>
                                                <span>{{ $kelas->nama_kelas }}</span>
                                                <span style="color: var(--hp-muted); flex-shrink:0;">{{ $kelas->kuota_kelas }} orang</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                @guest
                                    <a href="{{ route('register') }}" class="btn-brand" style="font-size: 13px; min-height: 38px; align-self: flex-start;">Daftar untuk Apply</a>
                                @endguest
                                @auth
                                    @if (auth()->user()->role === 'extras')
                                        <a href="{{ route('extras.projects.show', $proyek) }}" class="btn-brand" style="font-size: 13px; min-height: 38px; align-self: flex-start;">Lihat &amp; Apply</a>
                                    @endif
                                @endauth
                            </div>
                        </details>
                    @endforeach
                </div>
                @if ($adaLebih)
                    <div style="text-align: center; margin-top: 24px;">
                        @guest
                            <a href="{{ route('register') }}" class="btn-outline" style="font-size: 13px;">Daftar untuk lihat semua lowongan</a>
                        @endguest
                        @auth
                            @if (auth()->user()->role === 'extras')
                                <a href="{{ route('extras.projects.index') }}" class="btn-outline" style="font-size: 13px;">Lihat semua lowongan</a>
                            @endif
                        @endauth
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- Cast (BH.2): cuma Extras yang izin + disetujui Admin, minimal 4. Carousel geser manual (drag/scroll/swipe), bukan auto-animasi. --}}
    @if ($castExtras->isNotEmpty())
    <div class="film-strip-divider" aria-hidden="true"></div>
    <section class="cast-section" id="cast" aria-labelledby="cast-judul">
        <div class="hp-sec-head">
            <div>
                <span class="section-eyebrow">Cast</span>
                <h2 class="hp-sec-title" id="cast-judul">Kenalan dengan cast</h2>
            </div>
            <span class="hp-sec-note">Geser untuk lihat semua &#x2194;</span>
        </div>
        <div class="marquee-wrap">
            <button type="button" class="marquee-nav prev" data-dir="-1" aria-label="Sebelumnya">&#8249;</button>
            <button type="button" class="marquee-nav next" data-dir="1" aria-label="Berikutnya">&#8250;</button>
            <div class="marquee" data-carousel tabindex="0" aria-label="Daftar cast, geser untuk lihat semua">
                <div class="marquee-track">
                    @foreach ($castExtras as $i => $ex)
                        <a href="{{ route('public.extras.profile', $ex->share_token) }}" class="cast-card">
                            <div class="cast-photo-wrap">
                                <img src="{{ route('public.extras.foto', $ex->share_token) }}" alt="Foto {{ '@'.$ex->user->username }}" class="cast-photo" loading="lazy">
                                <span class="cast-no" aria-hidden="true">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="cast-view" aria-hidden="true">Lihat &#x2197;</span>
                            </div>
                            <div class="cast-name">{{ '@'.$ex->user->username }}</div>
                            <div class="cast-tag">{{ $ex->categories->first()?->nama ?? 'Extras' }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- Portofolio (BH.3): cuma proyek selesai yang dicentang Admin/SA. Carousel geser manual, sama seperti Cast. --}}
    @if ($portofolio->isNotEmpty())
    <div class="film-strip-divider" aria-hidden="true"></div>
    <section class="porto-section" id="portofolio" aria-labelledby="porto-judul">
        <div class="hp-sec-head">
            <div>
                <span class="section-eyebrow">Portofolio</span>
                <h2 class="hp-sec-title" id="porto-judul">Pernah dikerjakan</h2>
            </div>
            <span class="hp-sec-note">Geser untuk lihat semua &#x2194;</span>
        </div>
        <div class="marquee-wrap">
            <button type="button" class="marquee-nav prev" data-dir="-1" aria-label="Sebelumnya">&#8249;</button>
            <button type="button" class="marquee-nav next" data-dir="1" aria-label="Berikutnya">&#8250;</button>
            <div class="marquee" data-carousel tabindex="0" aria-label="Daftar portofolio, geser untuk lihat semua">
                <div class="marquee-track">
                    @foreach ($portofolio as $p)
                        @php $gambar = $p->poster_path; @endphp
                        <article class="porto-card">
                            <div class="porto-img">
                                @if ($gambar)
                                    <img src="{{ asset('storage/'.$gambar) }}" alt="" loading="lazy">
                                @else
                                    <i class="ti ti-movie" aria-hidden="true"></i>
                                @endif
                            </div>
                            <h3 class="porto-title">{{ $p->portofolio_judul ?: $p->nama_produksi }}</h3>
                            <div class="porto-sub">{{ implode(' · ', array_filter([$p->portofolio_jenis, $p->portofolio_tahun, $p->tampilkan_nama_client ? $p->namaClient() : null])) }}</div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- FAQ (CK.2.6): reuse struktur accordion lowongan-item/lowongan-summary --}}
    <div class="lowongan-section" id="faq" aria-label="Pertanyaan umum">
        <div class="lowongan-inner">
            <span class="section-eyebrow">Sering Ditanyakan</span>
            <div class="section-title">FAQ</div>
            <div class="lowongan-list">
                <details class="lowongan-item">
                    <summary class="lowongan-summary">
                        <span style="flex: 1; font-size: 15px; font-weight: 600; color: var(--hp-fg);">Daftarnya bayar?</span>
                        <span class="lowongan-chevron" aria-hidden="true">+</span>
                    </summary>
                    <div class="lowongan-detail" style="padding-left: 0;">
                        <p class="section-body" style="margin: 0;">Gratis.</p>
                    </div>
                </details>
                <details class="lowongan-item">
                    <summary class="lowongan-summary">
                        <span style="flex: 1; font-size: 15px; font-weight: 600; color: var(--hp-fg);">Kapan honor dibayar?</span>
                        <span class="lowongan-chevron" aria-hidden="true">+</span>
                    </summary>
                    <div class="lowongan-detail" style="padding-left: 0;">
                        <p class="section-body" style="margin: 0;">Setelah kontrak ditandatangani dan produksi berjalan; status transfer bisa kamu cek dan konfirmasi di aplikasi.</p>
                    </div>
                </details>
                <details class="lowongan-item">
                    <summary class="lowongan-summary">
                        <span style="flex: 1; font-size: 15px; font-weight: 600; color: var(--hp-fg);">Kalau batal ikut gimana?</span>
                        <span class="lowongan-chevron" aria-hidden="true">+</span>
                    </summary>
                    <div class="lowongan-detail" style="padding-left: 0;">
                        <p class="section-body" style="margin: 0;">Batal kurang dari 2 hari sebelum shooting dihitung batal mendadak; 3 kali batal mendadak membuat akun dinonaktifkan.</p>
                    </div>
                </details>
                <details class="lowongan-item">
                    <summary class="lowongan-summary">
                        <span style="flex: 1; font-size: 15px; font-weight: 600; color: var(--hp-fg);">Data KTP dan rekening aman?</span>
                        <span class="lowongan-chevron" aria-hidden="true">+</span>
                    </summary>
                    <div class="lowongan-detail" style="padding-left: 0;">
                        <p class="section-body" style="margin: 0;">Disimpan terenkripsi dan hanya dipakai untuk kontrak dan pembayaran; tidak terlihat oleh Client.</p>
                    </div>
                </details>
            </div>
        </div>
    </div>

    {{-- Contact Section (AK.10): WA/email tampil hanya bila JBTB_WA / JBTB_EMAIL diisi --}}
    @php
        $kontakWa = config('app.kontak.wa');
        $kontakEmail = config('app.kontak.email');
    @endphp
    <section class="contact-section" aria-label="Hubungi kami">
        <div class="contact-inner">
            <div>
                <span class="section-eyebrow">Hubungi Kami</span>
                <div class="contact-left-title">Production house?<br>Hubungi kami</div>
            </div>
            <div class="contact-grid">
                <div class="contact-block">
                    <strong>Alamat</strong>
                    <p>Pamulang, Tangerang Selatan,<br>Banten, Indonesia</p>
                </div>
                @if ($kontakWa)
                <div class="contact-block">
                    <strong>WhatsApp</strong>
                    <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $kontakWa)) }}" target="_blank" rel="noopener">{{ $kontakWa }}</a>
                </div>
                @endif
                <div class="contact-block">
                    <strong>Instagram</strong>
                    <a href="https://instagram.com/jbtb.casting" target="_blank" rel="noopener">@jbtb.casting</a>
                </div>
                @if ($kontakEmail)
                <div class="contact-block">
                    <strong>Email</strong>
                    <a href="mailto:{{ $kontakEmail }}">{{ $kontakEmail }}</a>
                </div>
                @endif
            </div>
        </div>
    </section>
    </main>

    <footer>
        <div class="footer-inner">
            <a href="{{ route('home') }}" class="footer-brand">
                <img src="{{ asset('images/logo-jbtb.jpg') }}" alt="" width="26" height="26">
                JBTB Casting
            </a>
            <nav class="footer-nav" aria-label="Tautan footer">
                <a href="#lowongan">Lowongan</a>
                <a href="#cara-kerja">Cara Kerja</a>
                <a href="#faq">FAQ</a>
                <a href="{{ route('privacy-policy') }}">Privasi</a>
            </nav>
            <div class="footer-legal">
                <span>&copy; {{ date('Y') }} JBTB Casting</span>
            </div>
        </div>
        <div style="max-width: 1100px; margin: 12px auto 0; padding: 0 32px; font-size: 12px; color: var(--hp-muted);">PT. JBTB Casting Creative Group &middot; NIB terdaftar OSS</div>
    </footer>

    <script>
    // Carousel geser manual: drag mouse/trackpad, swipe native di touch.
    document.querySelectorAll('[data-carousel]').forEach(function (el) {
        var dragging = false, moved = false, startX, startScroll, pointerId, captured = false;
        el.addEventListener('pointerdown', function (e) {
            dragging = true; moved = false; captured = false;
            startX = e.clientX; startScroll = el.scrollLeft;
            pointerId = e.pointerId;
        });
        el.addEventListener('pointermove', function (e) {
            if (!dragging) return;
            var dx = e.clientX - startX;
            if (!moved && Math.abs(dx) > 3) {
                moved = true;
                el.classList.add('is-dragging');
                // Capture baru dipasang setelah benar-benar drag, dan cuma untuk mouse -
                // biar klik polos (tanpa drag) tetap membuka tautan kartu, dan swipe
                // sentuh/pen tetap pakai scroll native.
                if (e.pointerType === 'mouse') { el.setPointerCapture(pointerId); captured = true; }
            }
            if (moved) el.scrollLeft = startScroll - dx;
        });
        function stop(e) {
            dragging = false;
            el.classList.remove('is-dragging');
            if (captured) { try { el.releasePointerCapture(pointerId); } catch (err) {} captured = false; }
        }
        el.addEventListener('pointerup', stop);
        el.addEventListener('pointercancel', stop);
        el.addEventListener('click', function (e) { if (moved) { e.preventDefault(); e.stopPropagation(); } }, true);

        var wrap = el.closest('.marquee-wrap');
        if (wrap) {
            wrap.querySelectorAll('.marquee-nav').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    el.scrollBy({ left: Number(btn.dataset.dir) * el.clientWidth * 0.8, behavior: 'smooth' });
                });
            });
        }
    });
    </script>
    <script>
    (function () {
        var icon = document.getElementById('theme-icon');
        var tema = document.getElementById('theme-toggle');
        function ikon(t) { icon.className = t === 'dark' ? 'ti ti-sun' : 'ti ti-moon'; tema.setAttribute('aria-pressed', t === 'dark'); }
        ikon(document.documentElement.getAttribute('data-theme'));

        tema.addEventListener('click', function () {
            var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('jbtb-theme-v2', next); } catch (e) {}
            ikon(next);
        });

        var menuToggle = document.getElementById('menu-toggle');
        var navMenu = document.getElementById('hp-nav-menu');
        if (menuToggle && navMenu) {
            function menu(buka) { navMenu.classList.toggle('open', buka); menuToggle.setAttribute('aria-expanded', buka); }
            menuToggle.addEventListener('click', function () { menu(!navMenu.classList.contains('open')); });
            navMenu.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () { menu(false); }); });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && navMenu.classList.contains('open')) { menu(false); menuToggle.focus(); }
            });
        }
        var userMenu = document.getElementById('navbar-user-menu');
        if (userMenu) {
            document.addEventListener('click', function (e) { if (!userMenu.contains(e.target)) userMenu.removeAttribute('open'); });
        }
    })();
    </script>
</body>
</html>
