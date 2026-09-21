<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('jbtb-theme-v2') || 'light');</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title>SIM Casting JBTB</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
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
            --hp-muted:    rgba(23, 26, 22, 0.55);
            --hp-accent:   #76a51b;
            --hp-accent-on:#ffffff;
            --hp-line:     rgba(23, 26, 22, 0.16);
            --hp-card:     #e6e4da;
        }

        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', sans-serif; background: var(--hp-bg); color: var(--hp-fg); }

        @property --spot-size { syntax: "<length-percentage>"; inherits: true; initial-value: 0%; }

        .film-grain {
            position: fixed; inset: 0; z-index: 1; pointer-events: none;
            opacity: 0.04; mix-blend-mode: overlay;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }

        .film-strip-divider {
            height: 18px;
            background: repeating-linear-gradient(90deg, var(--hp-card) 0 14px, transparent 14px 28px), var(--hp-line);
            background-position: center;
            background-size: 28px 10px, 100% 2px;
            background-repeat: repeat-x, no-repeat;
            opacity: 0.6;
        }

        .section-eyebrow {
            display: block; font-size: 11px; font-weight: 700; letter-spacing: 3px;
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
        .hp-nav-menu a { display: block; font-size: 13.5px; color: var(--hp-muted); text-decoration: none; padding: 6px 11px; border-radius: 7px; }
        .hp-nav-menu a:hover { color: var(--hp-fg); background: var(--hp-card); }
        .hp-menu-toggle { display: none; width: 34px; height: 34px; border-radius: 8px; border: none; cursor: pointer; background: var(--hp-card); color: var(--hp-fg); align-items: center; justify-content: center; font-size: 18px; }
        .hp-nav-actions { display: flex; gap: 8px; align-items: center; }
        .theme-toggle-btn { width: 34px; height: 34px; border-radius: 50%; border: none; cursor: pointer; background: var(--hp-card); color: var(--hp-muted); display: flex; align-items: center; justify-content: center; font-size: 15px; }
        .avatar-badge { width: 34px; height: 34px; border-radius: 50%; border: none; cursor: pointer; background: var(--hp-accent); color: var(--hp-accent-on); font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .avatar-badge-img { object-fit: cover; padding: 0; }
        .navbar-user-menu { position: relative; list-style: none; }
        .navbar-user-menu > summary { list-style: none; cursor: pointer; }
        .navbar-user-menu > summary::-webkit-details-marker { display: none; }
        .navbar-user-menu-dropdown { display: none; position: absolute; right: 0; top: calc(100% + 8px); z-index: 200; background: var(--hp-card); border: 1px solid var(--hp-line); border-radius: 10px; min-width: 160px; padding: 6px; box-shadow: 0 4px 16px rgba(0,0,0,0.25); }
        .navbar-user-menu[open] .navbar-user-menu-dropdown { display: block; }
        .navbar-user-menu-dropdown a, .navbar-user-menu-dropdown button { display: flex; align-items: center; gap: 8px; width: 100%; padding: 9px 12px; border-radius: 7px; font-size: 13.5px; font-weight: 500; color: var(--hp-fg); text-decoration: none; background: none; border: none; cursor: pointer; }
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
                url('/images/homepage-hero-bg.png');
            background-size: cover;
            background-position: center;
        }
        .hero-spotlight {
            position: absolute; inset: 0; pointer-events: none;
            transition: --spot-size 0.3s ease-out;
            mask-image: radial-gradient(circle at var(--spot-x,50%) var(--spot-y,50%), black var(--spot-size,0%), transparent calc(var(--spot-size,0%) + 15%));
            background: radial-gradient(circle at var(--spot-x,50%) var(--spot-y,50%), rgba(183,255,60,0.07), transparent 60%);
        }
        .hero:hover .hero-spotlight { --spot-size: 35%; }
        @media (prefers-reduced-motion: reduce) { .hero-spotlight { transition: none; } }
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
        .hero-h-italic { font-style: italic; opacity: 0.45; }
        .hero-h-accent { color: var(--hp-accent); }
        .hero-cta { font-size: 14px; font-weight: 600; color: var(--hp-muted); text-decoration: none; border-bottom: 1px solid var(--hp-line); padding-bottom: 3px; transition: color .2s, border-color .2s; }
        .hero-cta:hover { color: var(--hp-accent); border-color: var(--hp-accent); }
        @media (max-width: 767px) {
            .hero { min-height: 480px; background-position: 68% center; }
            .hero-inner { padding: 64px 20px 56px; }
        }

        /* ── Cast Marquee (AK.5) ── */
        .cast-section { padding-block: 7rem; border-top: 1px solid var(--hp-line); overflow: hidden; }
        .cast-section-header { max-width: 1100px; margin: 0 auto 2.5rem; padding: 0 32px; }
        .cast-section-title { font-family: Georgia, 'Times New Roman', serif; font-size: clamp(1.6rem, 3.5vw, 2.4rem); font-weight: 400; letter-spacing: -0.02em; margin: 6px 0 0; color: var(--hp-fg); }

        .cast-track-wrap { position: relative; }
        .cast-track-wrap::before,
        .cast-track-wrap::after {
            content: ''; position: absolute; top: 0; bottom: 0; width: 80px; z-index: 2; pointer-events: none;
        }
        .cast-track-wrap::before { left: 0; background: linear-gradient(to right, var(--hp-bg), transparent); }
        .cast-track-wrap::after  { right: 0; background: linear-gradient(to left,  var(--hp-bg), transparent); }

        .cast-track { display: flex; gap: 16px; width: max-content; }
        .cast-track-wrap:hover .cast-track { animation-play-state: paused; }

        @media (prefers-reduced-motion: no-preference) {
            @keyframes marquee-scroll { from { transform: translateX(0); } to { transform: translateX(-50%); } }
            .cast-track { animation: marquee-scroll 32s linear infinite; }
        }

        .cast-card { flex: 0 0 160px; display: flex; flex-direction: column; gap: 8px; }
        .cast-photo-wrap { width: 160px; aspect-ratio: 2/3; border-radius: 10px; overflow: hidden; background: var(--hp-card); position: relative; }
        .cast-photo { width: 100%; height: 100%; object-fit: cover; display: block; filter: grayscale(1); transition: filter .5s ease; }
        .cast-card:hover .cast-photo { filter: grayscale(0); }
        @media (prefers-reduced-motion: reduce) { .cast-photo { transition: none; } }
        .cast-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: 700; color: var(--hp-muted); letter-spacing: -1px; }
        .cast-name { font-size: 12.5px; font-weight: 600; color: var(--hp-fg); text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .cast-fallback { max-width: 1100px; margin: 0 auto; padding: 0 32px; display: flex; gap: 16px; flex-wrap: wrap; }

        /* ── About ── */
        .about-section { padding-block: 7rem; border-top: 1px solid var(--hp-line); }
        .about-inner { max-width: 1100px; margin: 0 auto; padding: 0 32px; display: flex; flex-direction: column; gap: 40px; }
        .history-facts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: start; }
        .section-title { font-size: 18px; font-weight: 700; margin: 0 0 16px; color: var(--hp-fg); }
        .section-body { font-size: 14px; color: var(--hp-muted); line-height: 1.75; margin: 0; }
        .quick-facts { background: var(--hp-card); border: 1px solid var(--hp-line); border-radius: 12px; padding: 18px 22px; }
        .quick-facts dl { display: grid; grid-template-columns: auto 1fr; gap: 6px 16px; margin: 0; }
        .quick-facts dt { font-size: 12px; color: var(--hp-muted); font-weight: 600; white-space: nowrap; padding-top: 2px; }
        .quick-facts dd { font-size: 13px; color: var(--hp-fg); margin: 0; }
        .vm-section { display: flex; gap: 32px; }
        .vm-block { flex: 1; }
        .vm-title { font-size: 17px; font-weight: 700; margin: 0 0 12px; color: var(--hp-fg); }
        .vm-body { font-size: 14px; color: var(--hp-muted); margin: 0; line-height: 1.8; }
        .vm-list { margin: 0; padding-left: 20px; font-size: 14px; color: var(--hp-muted); line-height: 2.2; }

        /* ── Services (AK.3) ── */
        .services-section { padding-block: 7rem; border-top: 1px solid var(--hp-line); }
        .services-inner { max-width: 1100px; margin: 0 auto; padding: 0 32px; display: grid; grid-template-columns: 1fr 2fr; gap: 56px; align-items: start; }
        .services-left-title { font-family: Georgia, 'Times New Roman', serif; font-size: clamp(1.8rem, 4vw, 3rem); font-weight: 400; line-height: 1.1; letter-spacing: -0.02em; margin: 8px 0 0; color: var(--hp-fg); }
        .services-list { border-top: 1px solid var(--hp-line); }
        .services-item { display: flex; gap: 20px; align-items: flex-start; padding: 20px 0; border-bottom: 1px solid var(--hp-line); }
        .services-num { font-family: Georgia, 'Times New Roman', serif; font-size: 13px; color: var(--hp-muted); flex-shrink: 0; padding-top: 2px; letter-spacing: 1px; min-width: 28px; }
        .services-body strong { font-size: 14px; font-weight: 600; color: var(--hp-fg); display: block; margin-bottom: 4px; }
        .services-body p { font-size: 13.5px; color: var(--hp-muted); margin: 0; line-height: 1.65; }

        /* ── Lowongan (AK.2) ── */
        .lowongan-section { padding-block: 7rem; border-top: 1px solid var(--hp-line); }
        .lowongan-inner { max-width: 1100px; margin: 0 auto; padding: 0 32px; }
        .lowongan-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1px; background: var(--hp-line); margin-top: 2.5rem; border: 1px solid var(--hp-line); border-radius: 4px; overflow: hidden; }
        .lowongan-card { position: relative; background: var(--hp-bg); display: flex; flex-direction: column; }
        .lowongan-poster-wrap { position: relative; overflow: hidden; }
        .lowongan-poster { width: 100%; aspect-ratio: 2/3; object-fit: cover; display: block; filter: grayscale(1); transition: filter .5s ease; }
        .lowongan-card:hover .lowongan-poster,
        .lowongan-card:focus-within .lowongan-poster { filter: grayscale(0); }
        @media (prefers-reduced-motion: reduce) { .lowongan-poster { transition: none; } }
        .lowongan-poster-placeholder { width: 100%; aspect-ratio: 2/3; background: var(--hp-card); display: flex; align-items: center; justify-content: center; color: var(--hp-muted); font-size: 36px; }
        .lowongan-badge-num { position: absolute; top: 10px; left: 10px; font-size: 10px; font-weight: 700; letter-spacing: 1px; background: rgba(0,0,0,0.55); color: #fff; padding: 2px 7px; border-radius: 3px; }
        .lowongan-badge-urgent { position: absolute; top: 10px; right: 10px; font-size: 10px; font-weight: 700; background: #ef4444; color: #fff; padding: 2px 7px; border-radius: 3px; }
        .lowongan-badge-lihat { position: absolute; bottom: 10px; right: 10px; font-size: 11px; font-weight: 600; background: rgba(0,0,0,0.65); color: #fff; padding: 4px 10px; border-radius: 3px; opacity: 0; transition: opacity .25s; }
        .lowongan-card:hover .lowongan-badge-lihat { opacity: 1; }
        @media (prefers-reduced-motion: reduce) { .lowongan-badge-lihat { transition: none; } }
        .lowongan-card-info { padding: 14px 16px; flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .lowongan-card-title { font-size: 14px; font-weight: 600; margin: 0; color: var(--hp-fg); }
        .lowongan-card-meta { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
        .badge-dibuka { display: inline-block; font-size: 10px; font-weight: 700; letter-spacing: 0.5px; background: var(--hp-accent); color: var(--hp-accent-on); padding: 2px 7px; border-radius: 3px; }
        .lowongan-card-deadline { font-size: 12px; color: var(--hp-muted); }
        .lowongan-roles { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 4px; }
        .lowongan-roles li { font-size: 12.5px; color: var(--hp-muted); display: flex; justify-content: space-between; }
        .lowongan-card-cta { margin-top: auto; padding-top: 8px; }
        .lowongan-empty { text-align: center; color: var(--hp-muted); font-size: 14px; padding: 40px; border: 1px dashed var(--hp-line); border-radius: 12px; margin-top: 2.5rem; }

        /* ── Produksi ── */
        .produksi-section { padding-block: 7rem; border-top: 1px solid var(--hp-line); }
        .produksi-inner { max-width: 1100px; margin: 0 auto; padding: 0 32px; }
        .produksi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; margin-top: 2.5rem; }
        .produksi-grid-reel { display: flex; gap: 20px; overflow-x: auto; scroll-snap-type: x proximity; padding: 24px 8px 32px; margin-top: 2rem; }
        .produksi-grid-reel .produksi-card { flex: 0 0 160px; scroll-snap-align: center; }
        .produksi-card { display: flex; flex-direction: column; gap: 10px; }
        .produksi-poster { width: 100%; aspect-ratio: 2/3; object-fit: cover; border-radius: 10px; display: block; filter: grayscale(100%); transition: filter 0.4s ease; }
        .produksi-card:hover .produksi-poster,
        .produksi-card:focus-within .produksi-poster { filter: grayscale(0%); }
        @media (prefers-reduced-motion: reduce) { .produksi-poster { transition: none; } }
        @media (prefers-reduced-motion: no-preference) {
            @supports ((animation-timeline: view()) and (animation-range: entry)) {
                @keyframes reel-scale { 0% { scale: 0.82; opacity: 0.6; } 50% { scale: 1; opacity: 1; } 100% { scale: 0.82; opacity: 0.6; } }
                .produksi-grid-reel .produksi-card { animation: reel-scale auto linear both; animation-timeline: view(inline); }
            }
        }
        .produksi-placeholder { width: 100%; aspect-ratio: 2/3; border-radius: 10px; background: var(--hp-card); border: 1px solid var(--hp-line); display: flex; align-items: center; justify-content: center; font-size: 32px; color: var(--hp-muted); }
        .produksi-name { font-size: 12.5px; color: var(--hp-muted); text-align: center; font-weight: 500; }

        /* ── Footer CTA (AK.4) ── */
        .footer-cta { padding-block: 8rem; border-top: 1px solid var(--hp-line); }
        .footer-cta-inner { max-width: 1100px; margin: 0 auto; padding: 0 32px; display: grid; grid-template-columns: 1fr 1fr; gap: 56px; align-items: end; }
        .footer-cta-headline { font-family: Georgia, 'Times New Roman', serif; font-size: clamp(2.2rem, 7vw, 6rem); line-height: 0.9; letter-spacing: -0.03em; font-weight: 400; margin: 10px 0 32px; color: var(--hp-fg); }
        .footer-cta-accent { color: var(--hp-accent); }
        .footer-cta-contact { font-size: 13.5px; color: var(--hp-muted); line-height: 2.2; }
        .footer-cta-contact strong { display: block; font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--hp-muted); margin-bottom: 4px; opacity: 0.7; }

        /* ── Footer ── */
        footer { border-top: 1px solid var(--hp-line); padding: 20px 32px; font-size: 13px; color: var(--hp-muted); }
        .footer-inner { max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
        footer a { color: var(--hp-muted); text-decoration: none; }
        footer a:hover { color: var(--hp-fg); }

        /* ── Dialog / Modal ── */
        dialog { border: none; border-radius: 16px; padding: 0; max-width: 360px; width: 90%; background: var(--hp-card); color: var(--hp-fg); opacity: 0; transform: translateY(8px); transition: opacity .25s ease, transform .25s ease; }
        dialog.is-open { opacity: 1; transform: none; }
        dialog.is-closing { opacity: 0; transform: translateY(-140px) scale(0.75); transition: opacity .25s ease, transform .25s ease; }
        dialog::backdrop { background: rgba(0,0,0,0.55); }
        .modal-body { padding: 24px; }
        .modal-title { font-size: 16px; font-weight: 600; margin-bottom: 8px; }
        .modal-text { font-size: 13.5px; color: var(--hp-muted); margin: 0 0 18px; line-height: 1.6; }
        .cta-row { display: flex; gap: 12px; flex-wrap: wrap; }
        .modal-dismiss { display: block; width: 100%; text-align: center; margin-top: 12px; background: none; border: none; color: var(--hp-muted); font-size: 13px; cursor: pointer; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .hp-nav { padding: 0 16px; position: relative; }
            .hp-nav-menu { display: none; position: absolute; top: 56px; left: 0; right: 0; flex-direction: column; align-items: flex-start; background: var(--hp-bg); border-bottom: 1px solid var(--hp-line); padding: 8px 0; z-index: 99; }
            .hp-nav-menu.open { display: flex; }
            .hp-nav-menu a { padding: 10px 20px; border-radius: 0; width: 100%; }
            .hp-menu-toggle { display: flex; }
            .about-section .about-inner,
            .services-inner,
            .lowongan-inner,
            .produksi-inner,
            .footer-cta-inner,
            .cast-section-header,
            .cast-fallback,
            .footer-inner { padding-left: 20px; padding-right: 20px; }
            .about-section, .cast-section, .services-section, .lowongan-section, .produksi-section, .footer-cta { padding-block: 5rem; }
            .history-facts-row { grid-template-columns: 1fr; }
            .vm-section { flex-direction: column; gap: 20px; }
            .services-inner { grid-template-columns: 1fr; gap: 28px; }
            .footer-cta-inner { grid-template-columns: 1fr; gap: 40px; }
            .lowongan-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
            footer { padding: 16px 20px; }
        }
    </style>
</head>
<body>
    <div class="film-grain" aria-hidden="true"></div>

    {{-- Navbar --}}
    <nav class="hp-nav">
        <div class="hp-nav-brand">
            <img src="{{ asset('images/logo-jbtb.jpg') }}" alt="Logo JBTB" class="hp-logo">
            SIM Casting JBTB
        </div>
        <ul class="hp-nav-menu" id="hp-nav-menu">
            <li><a href="#">Beranda</a></li>
            <li><a href="#tentang">Tentang Kami</a></li>
            <li><a href="#lowongan">Lowongan</a></li>
        </ul>
        <div class="hp-nav-actions">
            <button type="button" class="hp-menu-toggle" id="menu-toggle" aria-label="Buka menu">
                <i class="ti ti-menu-2"></i>
            </button>
            <button type="button" class="theme-toggle-btn" id="theme-toggle" aria-label="Ganti tema">
                <i class="ti ti-moon" id="theme-icon"></i>
            </button>
            @auth
                <details class="navbar-user-menu" id="navbar-user-menu">
                    <summary class="avatar-badge-summary">
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
                <a href="{{ route('login') }}" class="btn-brand" style="min-height: 36px; font-size: 13px; padding: 0 16px;">Masuk / Daftar</a>
            @endauth
        </div>
    </nav>

    {{-- Hero (AK.1 + AK.6) --}}
    <div class="hero">
        <div class="hero-spotlight" aria-hidden="true"></div>
        <div class="hero-inner">
            <span class="section-eyebrow">Sistem Manajemen Casting Extras</span>
            <h1 class="hero-headline">
                Talenta<br>
                <span class="hero-h-indent hero-h-italic">yang</span><br>
                <span class="hero-h-accent">dipercaya.</span>
            </h1>
            <a href="#lowongan" class="hero-cta">Lihat proyek terbuka &#x2197;</a>
        </div>
    </div>

    {{-- Cast Section (AK.5) --}}
    <div class="cast-section">
        <div class="cast-section-header">
            <span class="section-eyebrow">Talenta Kami</span>
            <div class="cast-section-title">Extras terdaftar &amp; siap produksi</div>
        </div>

        @if ($castExtras->count() >= 4)
            {{-- Marquee: duplikat list 2x biar loop mulus --}}
            <div class="cast-track-wrap">
                <div class="cast-track">
                    @foreach ($castExtras as $extras)
                        <div class="cast-card">
                            <div class="cast-photo-wrap">
                                <img src="{{ route('public.extras.foto', $extras->extrasProfile->share_token) }}"
                                     alt="{{ $extras->username ?? $extras->name }}"
                                     class="cast-photo"
                                     loading="lazy">
                            </div>
                            <div class="cast-name">{{ $extras->username ?? $extras->name }}</div>
                        </div>
                    @endforeach
                    {{-- duplikat untuk seamless loop --}}
                    @foreach ($castExtras as $extras)
                        <div class="cast-card" aria-hidden="true">
                            <div class="cast-photo-wrap">
                                <img src="{{ route('public.extras.foto', $extras->extrasProfile->share_token) }}"
                                     alt=""
                                     class="cast-photo"
                                     loading="lazy">
                            </div>
                            <div class="cast-name">{{ $extras->username ?? $extras->name }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            {{-- Fallback: kurang dari 4 extras opt-in, tampil static grid --}}
            <div class="cast-fallback">
                @foreach ($castExtras as $extras)
                    <div class="cast-card">
                        <div class="cast-photo-wrap">
                            <img src="{{ route('public.extras.foto', $extras->extrasProfile->share_token) }}"
                                 alt="{{ $extras->username ?? $extras->name }}"
                                 class="cast-photo">
                        </div>
                        <div class="cast-name">{{ $extras->username ?? $extras->name }}</div>
                    </div>
                @endforeach
                @for ($i = $castExtras->count(); $i < 4; $i++)
                    <div class="cast-card">
                        <div class="cast-photo-wrap">
                            <div class="cast-placeholder">?</div>
                        </div>
                        <div class="cast-name" style="color: var(--hp-muted);">—</div>
                    </div>
                @endfor
            </div>
        @endif
    </div>

    <div class="film-strip-divider" aria-hidden="true"></div>

    {{-- About --}}
    <div class="about-section" id="tentang">
        <div class="about-inner">
            <div class="history-facts-row">
                <div>
                    <div class="section-title">Tentang PT. JBTB Casting Creative Group</div>
                    <p class="section-body">PT. JBTB Casting Creative Group adalah perusahaan talent agency dan casting management yang berfokus pada penyediaan extras/pemeran figuran untuk kebutuhan produksi film, iklan, dan konten kreatif di Indonesia. Resmi berdiri sejak 2020 dengan badan hukum PT dan NIB terdaftar OSS. Berkantor pusat di Pamulang, Tangerang Selatan, dengan tim inti 5 orang profesional, JBTB aktif mengelola 50–80 extras dan menangani 4–5 proyek per bulan.</p>
                </div>
                <div class="quick-facts">
                    <dl>
                        <dt>Nama Perusahaan</dt><dd>PT. JBTB Casting Creative Group</dd>
                        <dt>Tahun Berdiri</dt><dd>2020</dd>
                        <dt>Bidang Usaha</dt><dd>Talent Agency &amp; Casting Management</dd>
                        <dt>Domisili</dt><dd>Pamulang, Tangerang Selatan, Banten</dd>
                        <dt>Legalitas</dt><dd>PT &amp; NIB terdaftar OSS Kemenves RI</dd>
                        <dt>Karyawan</dt><dd>5 orang (tim inti)</dd>
                        <dt>Skala Operasional</dt><dd>50–80 extras aktif, 4–5 proyek/bulan</dd>
                    </dl>
                </div>
            </div>
            <div class="vm-section">
                <div class="vm-block">
                    <div class="vm-title">Visi</div>
                    <p class="vm-body">Menjadi platform casting digital terdepan di Indonesia yang transparan, profesional, dan terintegrasi bagi seluruh ekosistem film, periklanan, dan segala yang berhubungan dengan manajemen talent di industri entertainment.</p>
                </div>
                <div class="vm-block">
                    <div class="vm-title">Misi</div>
                    <ol class="vm-list">
                        <li>Menyediakan sistem manajemen talent yang rapi, aman, dan mudah digunakan.</li>
                        <li>Menjadi sarana resmi open casting yang kredibel bagi seluruh pelaku industri kreatif.</li>
                        <li>Mempercepat proses seleksi melalui fitur filter dan rekomendasi otomatis.</li>
                        <li>Membantu talent mengembangkan karir profesional di industri hiburan Indonesia.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Services (AK.3) --}}
    <div class="services-section">
        <div class="services-inner">
            <div>
                <span class="section-eyebrow">Yang Kami Lakukan</span>
                <div class="services-left-title">Kenapa<br>JBTB?</div>
            </div>
            <div class="services-list">
                <div class="services-item">
                    <div class="services-num">01</div>
                    <div class="services-body">
                        <strong>Seleksi Visual Terstruktur</strong>
                        <p>Profil lengkap extras disajikan dengan foto, video, dan riwayat proyek — client bisa review kandidat tanpa bocor kontak langsung.</p>
                    </div>
                </div>
                <div class="services-item">
                    <div class="services-num">02</div>
                    <div class="services-body">
                        <strong>Negosiasi Fee Transparan</strong>
                        <p>Sistem nego fee multi-ronde ala InDrive, semua ronde tercatat — tidak ada celah sengketa "deal-nya berapa" setelah proyek selesai.</p>
                    </div>
                </div>
                <div class="services-item">
                    <div class="services-num">03</div>
                    <div class="services-body">
                        <strong>Kontrak &amp; Pembayaran Terdokumentasi</strong>
                        <p>Tanda tangan digital langsung di browser, bukti transfer terupload, status pembayaran bisa dicek extras kapan saja.</p>
                    </div>
                </div>
                <div class="services-item">
                    <div class="services-num">04</div>
                    <div class="services-body">
                        <strong>Absensi On-Set Berbasis Foto</strong>
                        <p>Korlap validasi kehadiran extras langsung dari lapangan — data absensi terhubung ke rekap honor, tidak bisa dimanipulasi.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="film-strip-divider" aria-hidden="true"></div>

    {{-- Lowongan Casting (AK.2) --}}
    <div class="lowongan-section" id="lowongan">
        <div class="lowongan-inner">
            <span class="section-eyebrow">Sedang Tayang</span>
            <div class="section-title">Lowongan Casting Terbuka</div>
            @if ($proyekTerbuka->isEmpty())
                <div class="lowongan-empty">Belum ada lowongan casting yang terbuka saat ini.</div>
            @else
                <div class="lowongan-grid">
                    @foreach ($proyekTerbuka as $proyek)
                        <div class="lowongan-card">
                            <div class="lowongan-poster-wrap">
                                @if ($proyek->poster_path)
                                    <img src="{{ Storage::url($proyek->poster_path) }}" alt="{{ $proyek->nama_produksi }}" class="lowongan-poster">
                                @else
                                    <div class="lowongan-poster-placeholder">
                                        <i class="ti ti-clapperboard"></i>
                                    </div>
                                @endif
                                <span class="lowongan-badge-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                @if ($proyek->isUrgent())
                                    <span class="lowongan-badge-urgent">URGENT</span>
                                @endif
                                @guest<span class="lowongan-badge-lihat">Lihat &#x2197;</span>@endguest
                                @auth
                                    @if (auth()->user()->role === 'extras')
                                        <span class="lowongan-badge-lihat">Apply &#x2197;</span>
                                    @endif
                                @endauth
                            </div>
                            <div class="lowongan-card-info">
                                <div class="lowongan-card-title">{{ $proyek->nama_produksi }}</div>
                                <div class="lowongan-card-meta">
                                    <span class="badge-dibuka">DIBUKA</span>
                                    <span class="lowongan-card-deadline">Deadline {{ $proyek->deadline->format('d M Y') }}</span>
                                </div>
                                @if ($proyek->classes->isNotEmpty())
                                    <ul class="lowongan-roles">
                                        @foreach ($proyek->classes as $kelas)
                                            <li>
                                                <span>{{ $kelas->nama_kelas }}</span>
                                                <span>{{ $kelas->kuota_kelas }} orang</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <div class="lowongan-card-cta">
                                    @guest
                                        <a href="{{ route('register') }}" class="btn-brand" style="font-size: 13px; min-height: 38px; width: 100%;">Daftar untuk Apply</a>
                                    @endguest
                                    @auth
                                        @if (auth()->user()->role === 'extras')
                                            <a href="{{ route('extras.projects.show', $proyek) }}" class="btn-brand" style="font-size: 13px; min-height: 38px; width: 100%;">Lihat &amp; Apply</a>
                                        @endif
                                    @endauth
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if ($adaLebih)
                    <div style="text-align: center; margin-top: 24px;">
                        @guest
                            <a href="{{ route('register') }}" class="btn-outline" style="font-size: 13px;">Daftar untuk lihat semua lowongan</a>
                        @endguest
                        @auth
                            @if (auth()->user()->role === 'extras')
                                <a href="/extras/projects" class="btn-outline" style="font-size: 13px;">Lihat semua lowongan</a>
                            @endif
                        @endauth
                    </div>
                @endif
            @endif
        </div>
    </div>

    @if ($proyekSelesai->isNotEmpty())
    <div class="film-strip-divider" aria-hidden="true"></div>
    <div class="produksi-section">
        <div class="produksi-inner">
            <span class="section-eyebrow">Arsip Produksi</span>
            <div class="section-title">Produksi yang Pernah Kami Tangani</div>
            @if (count($proyekSelesai) > 3)
                <div class="film-strip-divider" aria-hidden="true" style="margin-top: 2rem;"></div>
                <div class="produksi-grid-reel">
                    @foreach ($proyekSelesai as $p)
                        <div class="produksi-card">
                            @if ($p->poster_path)
                                <img src="{{ Storage::url($p->poster_path) }}" alt="{{ $p->nama_produksi }}" class="produksi-poster">
                            @else
                                <div class="produksi-placeholder"><i class="ti ti-clapperboard"></i></div>
                            @endif
                            <div class="produksi-name">{{ $p->nama_produksi }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="film-strip-divider" aria-hidden="true"></div>
            @else
                <div class="produksi-grid">
                    @foreach ($proyekSelesai as $p)
                        <div class="produksi-card">
                            @if ($p->poster_path)
                                <img src="{{ Storage::url($p->poster_path) }}" alt="{{ $p->nama_produksi }}" class="produksi-poster">
                            @else
                                <div class="produksi-placeholder"><i class="ti ti-clapperboard"></i></div>
                            @endif
                            <div class="produksi-name">{{ $p->nama_produksi }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Footer CTA (AK.4) --}}
    <div class="footer-cta">
        <div class="footer-cta-inner">
            <div>
                <span class="section-eyebrow">Mulai Sekarang</span>
                <div class="footer-cta-headline">
                    Bergabung<br>
                    <span class="footer-cta-accent">sebagai Extras.</span>
                </div>
                @guest
                    <a href="{{ route('register') }}" class="btn-brand">Daftar Gratis</a>
                @else
                    <a href="/dashboard" class="btn-outline">Buka Dashboard</a>
                @endguest
            </div>
            <div class="footer-cta-right">
                <div class="footer-cta-contact">
                    <strong>Lokasi</strong>
                    Pamulang, Tangerang Selatan, Banten
                </div>
                <div class="footer-cta-contact" style="margin-top: 24px;">
                    <strong>Ikuti Kami</strong>
                    @jbtb.casting (Instagram)
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-inner">
            <span>&copy; {{ date('Y') }} PT. JBTB Casting Creative Group</span>
            <a href="{{ route('privacy-policy') }}">Kebijakan Privasi</a>
        </div>
    </footer>

    @guest
        <dialog id="welcome-modal">
            <div class="modal-body">
                <div class="modal-title">Yuk gabung jadi Extras!</div>
                <p class="modal-text">Daftar akun gratis buat mulai apply proyek casting yang lagi buka pendaftaran.</p>
                <div class="cta-row">
                    <a href="{{ route('register') }}" class="btn-brand">Daftar</a>
                    <a href="{{ route('login') }}" class="btn-outline">Masuk</a>
                </div>
                <button type="button" class="modal-dismiss" id="welcome-modal-dismiss">Nanti dulu</button>
            </div>
        </dialog>
        <script>
        (function () {
            var dlg = document.getElementById('welcome-modal');
            if (!dlg || localStorage.getItem('homepage_modal_dismissed')) return;
            dlg.showModal();
            requestAnimationFrame(function () { dlg.classList.add('is-open'); });
            function dismiss() {
                localStorage.setItem('homepage_modal_dismissed', '1');
                dlg.classList.remove('is-open'); dlg.classList.add('is-closing');
                setTimeout(function () { dlg.close(); }, 250);
            }
            document.getElementById('welcome-modal-dismiss').addEventListener('click', dismiss);
            dlg.addEventListener('cancel', function (e) { e.preventDefault(); dismiss(); });
        })();
        </script>
    @endguest

    <script>
    (function () {
        var hero = document.querySelector('.hero');
        if (!hero) return;
        var spotlight = hero.querySelector('.hero-spotlight');
        if (!spotlight) return;
        var rect = hero.getBoundingClientRect();
        new ResizeObserver(function () { rect = hero.getBoundingClientRect(); }).observe(hero);
        hero.addEventListener('pointermove', function (e) {
            spotlight.style.setProperty('--spot-x', ((e.clientX - rect.left) / rect.width * 100).toFixed(1) + '%');
            spotlight.style.setProperty('--spot-y', ((e.clientY - rect.top) / rect.height * 100).toFixed(1) + '%');
        });
    })();

    if (!CSS.supports('(animation-timeline: view()) and (animation-range: entry)')
        && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        var reelGrid = document.querySelector('.produksi-grid-reel');
        if (reelGrid) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    entry.target.style.scale = 0.82 + entry.intersectionRatio * 0.18;
                    entry.target.style.opacity = 0.6 + entry.intersectionRatio * 0.4;
                });
            }, { threshold: Array.from({ length: 21 }, function (_, i) { return i / 20; }), root: reelGrid });
            document.querySelectorAll('.produksi-grid-reel .produksi-card').forEach(function (el) { io.observe(el); });
        }
    }
    </script>
    <script>
    (function () {
        var icon = document.getElementById('theme-icon');
        var current = document.documentElement.getAttribute('data-theme');
        icon.className = current === 'dark' ? 'ti ti-sun' : 'ti ti-moon';

        document.getElementById('theme-toggle').addEventListener('click', function () {
            var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('jbtb-theme-v2', next);
            icon.className = next === 'dark' ? 'ti ti-sun' : 'ti ti-moon';
        });

        var menuToggle = document.getElementById('menu-toggle');
        var navMenu = document.getElementById('hp-nav-menu');
        if (menuToggle && navMenu) {
            menuToggle.addEventListener('click', function () { navMenu.classList.toggle('open'); });
            navMenu.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () { navMenu.classList.remove('open'); }); });
        }
        var userMenu = document.getElementById('navbar-user-menu');
        if (userMenu) {
            document.addEventListener('click', function (e) { if (!userMenu.contains(e.target)) userMenu.removeAttribute('open'); });
        }
    })();
    </script>
</body>
</html>
