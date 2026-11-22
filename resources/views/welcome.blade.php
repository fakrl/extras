<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <script>try { document.documentElement.setAttribute('data-theme', localStorage.getItem('jbtb-theme-v2') || 'light'); } catch (e) {}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="dark light">
    <meta name="theme-color" content="#15803D" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0f9a4c" media="(prefers-color-scheme: dark)">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/icon-192.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="icon" href="{{ asset('images/brand/favicon.png') }}" type="image/png">
    <title>{{ config('brand.name') }} · Sistem Casting Extras & Talent Jakarta</title>
    <link rel="canonical" href="{{ url('/') }}">
    @include('partials.og-meta', [
        'noindex' => false,
        'ogTitle' => config('brand.name').' · Sistem Casting Extras & Talent Jakarta',
        'ogDesc' => 'Daftar jadi extras, lamar casting yang sedang buka, kontrak dan honor tercatat jelas di satu tempat di '.config('brand.name').'.',
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

        /* ── Navbar: lihat partials/pill-nav.blade.php untuk pill mengambang + dock mobile ── */
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

        /* ── Hero (AK.1 + AK.6 + Stitch 2-Kolom Hybrid) ── */
        .hero {
            position: relative; overflow: hidden;
            border-top: 1px solid var(--hp-accent);
            min-height: 560px;
            display: flex; align-items: center;
            background: var(--hp-bg);
            background-image:
                linear-gradient(180deg,
                    color-mix(in srgb, var(--hp-bg) 96%, transparent) 0%,
                    var(--hp-bg) 100%),
                radial-gradient(ellipse at 50% -20%, color-mix(in srgb, var(--hp-accent) 12%, transparent), transparent 70%);
        }
        .hero-inner { position: relative; z-index: 2; max-width: 1140px; margin: 0 auto; width: 100%; padding: 64px 32px 56px; }
        .hero-headline {
            font-family: var(--font-editorial, 'Newsreader', Georgia, 'Times New Roman', serif);
            font-size: clamp(2.5rem, 5.2vw, 4.2rem);
            line-height: 1.08;
            letter-spacing: -0.03em;
            font-weight: 500;
            margin: 0 0 16px;
            color: var(--hp-fg);
        }
        .hero-headline em {
            font-style: italic;
            color: var(--hp-accent);
        }
        .hero-h-indent { display: inline-block; margin-left: 10vw; }
        .hero-h-italic { font-style: italic; color: var(--hp-muted); }
        .hero-h-accent { color: var(--hp-accent); }
        @media (max-width: 767px) {
            .hero { min-height: 480px; }
            .hero-inner { padding: 48px 20px 40px; }
            .hero-headline { font-size: clamp(2.1rem, 7.8vw, 3rem); }
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
        .hp-sec-head { max-width: 1080px; margin: 0 auto; padding-inline: 5%; display: flex; justify-content: space-between; align-items: flex-end; gap: 16px 24px; flex-wrap: wrap; }
        .hp-sec-title { font-family: var(--font-editorial, 'Newsreader', Georgia, 'Times New Roman', serif); font-weight: 400; letter-spacing: -0.04em; margin: 0; color: var(--hp-fg); }
        .hp-sec-note { font-size: 12px; letter-spacing: 0.16em; text-transform: uppercase; color: var(--hp-muted); }

        /* ── Cast (BH.2/BH.4): grid 6 kartu acak + chip filter kategori, tanpa carousel ── */
        .cast-section { padding-block: 46px; border-top: 1px solid var(--hp-line); }
        .cast-chips { display: flex; gap: 8px; align-items: center; overflow-x: auto; scrollbar-width: none; margin-bottom: 18px; }
        .cast-chips::-webkit-scrollbar { display: none; }
        .cast-chip { flex: 0 0 auto; padding: 8px 16px; border-radius: 999px; border: 1px solid var(--border); background: var(--card); color: var(--fg); font-size: 13px; font-weight: 600; text-decoration: none; white-space: nowrap; }
        .cast-chip.is-active { background: var(--primary); border-color: var(--primary); color: var(--on-primary); }
        .cast-shuffle { flex: 0 0 auto; margin-left: auto; display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 999px; border: 1px solid var(--border); background: var(--card); color: var(--fg); font-size: 13px; font-weight: 600; cursor: pointer; }
        .cast-shuffle:hover { border-color: var(--primary); color: var(--primary); }
        .cast-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .cast-card { display: block; color: inherit; text-decoration: none; }
        .cast-card:focus-visible { outline: 2px solid var(--hp-accent); outline-offset: 4px; }
        .cast-photo-wrap { overflow: hidden; background: var(--hp-card); position: relative; }
        .cast-photo { width: 100%; height: 100%; object-fit: cover; display: block; }
        .cast-name { font-family: var(--font-editorial, 'Newsreader', Georgia, 'Times New Roman', serif); font-size: 1.1rem; letter-spacing: -0.03em; margin-top: 12px; color: var(--hp-fg); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cast-tags { display: flex; gap: 6px; margin-top: 6px; flex-wrap: wrap; }
        .cast-tag-pill { padding: 3px 10px; border-radius: 999px; background: var(--accent-soft); color: var(--on-accent-soft); font-size: 11px; font-weight: 700; }
        .cast-empty { text-align: center; padding: 40px 0; color: var(--muted-fg); }
        .cast-empty a { color: var(--primary); font-weight: 700; }
        @media (max-width: 767px) { .cast-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; } }

        .section-title { font-size: 18px; font-weight: 700; margin: 0 0 16px; color: var(--hp-fg); }
        .section-body { font-size: 14px; color: var(--hp-muted); line-height: 1.75; margin: 0; }

        /* ── Cara Kerja (CK.2.3 + Stitch 3-Langkah) ── */
        .services-section { padding-block: 46px; border-top: 1px solid var(--hp-line); }
        .services-inner { max-width: 1080px; margin: 0 auto; padding-inline: 5%; }
        .services-split-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; flex-wrap: wrap; margin-bottom: 26px; }
        .services-left-title { font-family: var(--font-editorial, 'Newsreader', Georgia, 'Times New Roman', serif); font-weight: 400; letter-spacing: -0.02em; margin: 8px 0 0; color: var(--hp-fg); }
        .services-list { border-top: 1px solid var(--hp-line); }
        .services-item { display: flex; gap: 20px; align-items: flex-start; padding: 20px 0; border-bottom: 1px solid var(--hp-line); }
        .services-num { font-family: var(--font-editorial, 'Newsreader', Georgia, 'Times New Roman', serif); font-size: 13px; color: var(--hp-muted); flex-shrink: 0; padding-top: 2px; letter-spacing: 1px; min-width: 28px; }
        .services-body p { font-size: 13.5px; color: var(--hp-muted); margin: 0; line-height: 1.65; }

        /* ── Lowongan list ala loker & kartu editorial (AK.9 + Stitch) ── */
        .lowongan-section { padding-block: 46px; border-top: 1px solid var(--hp-line); }
        .lowongan-inner { max-width: 1080px; margin: 0 auto; padding-inline: 5%; }
        .lowongan-list { display: flex; flex-direction: column; border-top: 1px solid var(--hp-line); margin-top: 2.5rem; }
        .lowongan-item { border-bottom: 1px dashed var(--hp-line); }
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
        /* CU.8: HP - judul baris sendiri (penuh, tidak terjepit badge), badge baris
           kedua, deadline baris ketiga (lewat grid, thumb span 3 baris di kiri). */
        @media (max-width: 767px) {
            .lowongan-summary {
                display: grid;
                grid-template-columns: 56px 1fr auto;
                grid-template-areas: "thumb title chevron" "thumb badges badges" "thumb meta meta";
                column-gap: 16px; row-gap: 4px;
            }
            .lowongan-thumb { grid-area: thumb; }
            .lowongan-summary-info { display: contents; }
            .lowongan-summary-title { grid-area: title; }
            .lowongan-chevron { grid-area: chevron; }
            .lowongan-summary-badges { grid-area: badges; justify-self: start; }
            .lowongan-summary-meta { grid-area: meta; margin-top: 0; }
        }
        .lowongan-detail { padding: 4px 0 20px 72px; display: flex; flex-direction: column; gap: 12px; }
        .lowongan-roles { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 6px; }
        .lowongan-roles li { font-size: 13px; color: var(--hp-muted); display: flex; justify-content: space-between; gap: 8px; }
        .lowongan-empty { text-align: center; color: var(--hp-muted); font-size: 14px; padding: 40px; border: 1px dashed var(--hp-line); border-radius: 12px; margin-top: 2.5rem; }

        /* ── Kontak (AK.10) ── */
        .contact-section { padding-block: 46px; border-top: 1px solid var(--hp-line); }
        .contact-inner { max-width: 1080px; margin: 0 auto; padding-inline: 5%; display: grid; grid-template-columns: 1fr 2fr; gap: 56px; align-items: start; }
        .contact-left-title { font-family: var(--font-editorial, 'Newsreader', Georgia, 'Times New Roman', serif); font-weight: 400; letter-spacing: -0.02em; margin: 8px 0 0; color: var(--hp-fg); }
        .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px 40px; }
        .contact-block strong { display: block; font-size: var(--fs-xs); font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--hp-muted); margin-bottom: 8px; }
        .contact-block p { font-size: 14px; color: var(--hp-fg); margin: 0; line-height: 1.6; }
        .contact-block a { font-size: 14px; color: var(--hp-fg); text-decoration: none; border-bottom: 1px solid var(--hp-line); }
        .contact-block a:hover { color: var(--hp-accent); border-color: var(--hp-accent); }

        /* ── Portofolio (BH.3) ── */
        .porto-section { padding-block: 46px; border-top: 1px solid var(--hp-line); overflow: hidden; }
        .porto-card { flex: 0 0 380px; margin: 0; }
        .porto-img { aspect-ratio: 1.28; overflow: hidden; border-radius: 4px; position: relative; background: linear-gradient(135deg, color-mix(in srgb, var(--hp-accent) 35%, var(--hp-card)), var(--hp-card) 70%); transition: box-shadow .3s ease; }
        .porto-card:hover .porto-img { box-shadow: 0 10px 24px rgba(0, 0, 0, 0.25); }
        .porto-img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .4s cubic-bezier(0.2, 0, 0, 1), filter .3s ease; }
        .porto-card:hover .porto-img img { transform: scale(1.06); filter: brightness(1.04); }
        .porto-img .ti { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 44px; color: var(--hp-muted); }
        .porto-title { font-family: var(--font-editorial, 'Newsreader', Georgia, 'Times New Roman', serif); font-size: 1.5rem; font-weight: 400; letter-spacing: -0.03em; line-height: 1.15; margin: 16px 0 0; color: var(--hp-fg); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .porto-sub { margin-top: 6px; font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; color: var(--hp-muted); }
        @media (prefers-reduced-motion: reduce) { .porto-img img { transition: none; } .porto-card:hover .porto-img img { transform: none; } }
        @media (max-width: 767px) { .porto-card { flex-basis: 280px; } }

        .cta-row { display: flex; gap: 12px; flex-wrap: wrap; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .cast-section, .services-section, .lowongan-section, .porto-section, .contact-section { padding-block: 36px; }
            .services-inner { grid-template-columns: 1fr; gap: 28px; }
            .contact-inner { grid-template-columns: 1fr; gap: 32px; }
            .contact-grid { grid-template-columns: 1fr; gap: 24px; }
            .lowongan-detail { padding-left: 0; }
        }
    </style>
    @include('partials.landing-sections-style')
</head>
<body id="atas">
    @include('partials.pill-nav', ['page' => 'home'])

    <main>
    {{-- Hero 2-Kolom (Faktual, Bahasa Sehari-hari, Foto Roster Nyata) --}}
    <div class="hero" id="beranda">
        <div class="hero-inner">
            <div class="lp-hero-grid">
                <div>
                    <span class="hp-eyebrow-pill"><span class="dot" aria-hidden="true"></span>Sistem casting</span>
                    @php
                        $heroTitleRaw = config('brand.hero_title');
                        $heroTitleHtml = preg_replace('/\*(.+?)\*/', '<span class="hp-accent-serif">$1</span>', e($heroTitleRaw));
                    @endphp
                    <h1 class="lp-hero-title">{!! $heroTitleHtml !!}</h1>
                    <p class="lp-hero-desc">
                        {{ config('brand.name') }} menghubungkan rumah produksi dengan talenta extras di Jabodetabek. Pendaftaran profil gratis, kontrak kerja digital, presensi kehadiran di lokasi syuting dengan foto selfie, serta catatan honor transparan di satu tempat.
                    </p>
                    <div class="cta-row">
                        @guest
                            <a href="{{ route('register') }}" class="btn-brand">Daftar jadi extras <i class="ti ti-arrow-right"></i></a>
                            <a href="#lowongan" class="btn-outline">Lihat lowongan <i class="ti ti-arrow-down"></i></a>
                        @else
                            @if (auth()->user()->role === 'extras')
                                <a href="#lowongan" class="btn-brand">Lihat lowongan <i class="ti ti-arrow-right"></i></a>
                            @else
                                <a href="/dashboard" class="btn-brand">Buka dashboard <i class="ti ti-arrow-right"></i></a>
                            @endif
                        @endguest
                    </div>
                    @if ($totalCastingDibuka > 0 || $totalPeranDibuka > 0 || $totalTalentTerdaftar > 0)
                        <div class="hp-stats">
                            @if ($totalCastingDibuka > 0)
                                <div><span class="hp-stat-num">{{ $totalCastingDibuka }}</span><span class="hp-stat-label">Casting dibuka</span></div>
                            @endif
                            @if ($totalPeranDibuka > 0)
                                <div><span class="hp-stat-num">{{ $totalPeranDibuka }}</span><span class="hp-stat-label">Peran dibuka</span></div>
                            @endif
                            @if ($totalTalentTerdaftar > 0)
                                <div><span class="hp-stat-num">{{ $totalTalentTerdaftar }}</span><span class="hp-stat-label">Talent terdaftar</span></div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="hp-hero-visual" aria-hidden="true">
                    <span class="orb orb-a"></span>
                    <span class="orb orb-b"></span>
                    <div class="hp-mock-stack">
                        <div class="hp-mock-card">
                            <div class="hp-mock-card-title">Casting Iklan Minuman</div>
                            <div class="hp-mock-card-sub">Contoh tampilan &middot; Kebutuhan: 8 orang</div>
                            <span class="hp-mock-chip"><i class="ti ti-check"></i> Lolos</span>
                        </div>
                        <div class="hp-mock-card">
                            <div class="hp-mock-card-title"><i class="ti ti-camera-selfie"></i> Presensi lokasi syuting</div>
                            <div class="hp-mock-card-sub">Contoh tampilan &middot; Foto selfie di lokasi</div>
                        </div>
                    </div>
                    @if ($totalCastingDibuka > 0)
                        <div class="hp-hero-float">
                            <strong>Sedang dibuka {{ $totalCastingDibuka }} peran</strong>
                            @if ($rentangDeadline)
                                <span>Batas daftar {{ $rentangDeadline }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Cara Kerja (3 Kolom Faktual) --}}
    <div class="services-section" id="cara-kerja">
        <div class="services-inner">
            <div class="services-split-head">
                <div>
                    <h2 class="services-left-title font-editorial">Cara kerja casting extras</h2>
                </div>
                <p class="section-body">Alur dari pendaftaran sampai penerimaan honor syuting.</p>
            </div>
            <div class="lp-chapters-grid">
                <div class="lp-chapter-col">
                    <h3 class="lp-chapter-title">Lengkapi profil lookbook</h3>
                    <p class="lp-chapter-desc">Daftar akun gratis dan lengkapi data fisik: foto lookbook (disarankan tiga foto: close-up, setengah badan, seluruh badan), tinggi badan, usia, dan nomor WhatsApp aktif.</p>
                </div>

                <div class="lp-chapter-col">
                    <h3 class="lp-chapter-title">Lamar lowongan yang cocok</h3>
                    <p class="lp-chapter-desc">Pilih peran yang sesuai dengan fisik dan jadwalmu di halaman lowongan. Jika sutradara atau pengarah peran memilih profilmu, kamu akan menerima panggilan jadwal syuting.</p>
                </div>

                <div class="lp-chapter-col">
                    <h3 class="lp-chapter-title">Syuting dan pencairan honor</h3>
                    <p class="lp-chapter-desc">Tanda tangani kontrak kerja digital sebelum syuting. Hadir di lokasi dan lakukan presensi dengan foto selfie. Setelah produksi selesai, pantau status pembayaran dan konfirmasi penerimaan honor di aplikasi.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Lowongan Casting --}}
    <div class="lowongan-section" id="lowongan">
        <div class="lowongan-inner">
            <div class="lp-lowongan-header">
                <div>
                    <h2 class="section-title font-editorial">Lowongan casting terbuka</h2>
                </div>
                <div class="lp-lowongan-right">
                    <span class="lp-count-pill">{{ $proyekTerbuka->count() }} lowongan sedang menerima pendaftaran</span>
                    <div class="lp-filter-pills" role="tablist">
                        <button type="button" class="lp-filter-pill is-active" data-filter="all">Semua lowongan</button>
                        <button type="button" class="lp-filter-pill" data-filter="urgent"><i class="ti ti-bolt"></i> Butuh mendadak</button>
                    </div>
                </div>
            </div>

            @if ($proyekTerbuka->isEmpty())
                <div class="lowongan-empty">Belum ada lowongan casting yang terbuka saat ini.</div>
            @else
                <div class="lp-jobs-grid">
                    @foreach ($proyekTerbuka as $proyek)
                        <div class="lp-job-card" data-urgent="{{ $proyek->isUrgent() ? '1' : '0' }}">
                            <div>
                                <div class="lp-job-top">
                                    <div class="lp-job-meta-item">
                                        <span class="badge-dibuka">Dibuka</span>
                                        @if ($proyek->isUrgent())
                                            <span class="badge badge-urgent"><i class="ti ti-bolt"></i> Butuh mendadak</span>
                                        @endif
                                    </div>
                                    <span class="lp-lowongan-count">
                                        <i class="ti ti-calendar"></i> Batas {{ $proyek->deadline->translatedFormat('d M Y') }}
                                    </span>
                                </div>
                                <h3 class="lp-job-title">{{ $proyek->nama_produksi }}</h3>
                                <div class="lp-job-meta">
                                    <span class="lp-job-meta-item"><i class="ti ti-users"></i> Kebutuhan: {{ $proyek->kuota }} orang</span>
                                    @if ($proyek->shootingDates->isNotEmpty())
                                        <span class="lp-job-meta-item"><i class="ti ti-video"></i> {{ $proyek->shootingDates->count() }} hari syuting</span>
                                    @endif
                                </div>
                                @if ($proyek->classes->isNotEmpty())
                                    <div class="lp-job-roles">
                                        @foreach ($proyek->classes as $kelas)
                                            <span class="lp-role-pill">
                                                <span>{{ $kelas->nama_kelas }}</span>
                                                <span style="color: var(--hp-muted); margin-left: 4px;">({{ $kelas->kuota_kelas }} orang)</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="lp-job-footer">
                                @guest
                                    <a href="{{ route('register') }}" class="btn-brand lp-btn-block">
                                        Daftar untuk melamar <i class="ti ti-arrow-right"></i>
                                    </a>
                                @endguest
                                @auth
                                    @if (auth()->user()->role === 'extras')
                                        <a href="{{ route('extras.projects.show', $proyek) }}" class="btn-brand lp-btn-block">
                                            Lihat detail lowongan <i class="ti ti-arrow-right"></i>
                                        </a>
                                    @else
                                        <span style="font-size: 12px; color: var(--hp-muted); display: block; text-align: center; padding: 6px 0;">Masuk sebagai extras untuk melamar</span>
                                    @endif
                                @endauth
                            </div>
                        </div>
                    @endforeach
                </div>
                @if ($adaLebih)
                    <div style="text-align: center; margin-top: 24px;">
                        @guest
                            <a href="{{ route('register') }}" class="btn-outline">Daftar untuk melihat semua lowongan</a>
                        @endguest
                        @auth
                            @if (auth()->user()->role === 'extras')
                                <a href="{{ route('extras.projects.index') }}" class="btn-outline">Lihat semua lowongan</a>
                            @endif
                        @endauth
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- Cast (Roster Talent Extras) --}}
    @if ($castTampil)
    <section class="cast-section" id="cast" aria-labelledby="cast-judul">
        <div class="hp-sec-head">
            <div>
                <h2 class="hp-sec-title font-editorial" id="cast-judul">Roster talent extras</h2>
            </div>
        </div>
        @include('partials.cast-fragment')
    </section>
    @endif

    {{-- Portofolio --}}
    @if ($portofolio->isNotEmpty())
    <section class="porto-section" id="portofolio" aria-labelledby="porto-judul">
        <div class="hp-sec-head">
            <div>
                <h2 class="hp-sec-title font-editorial" id="porto-judul">Proyek yang pernah dikerjakan</h2>
            </div>
            <span class="hp-sec-note">Geser untuk melihat proyek <i class="ti ti-arrows-left-right"></i></span>
        </div>
        <div class="marquee-wrap">
            <button type="button" class="marquee-nav prev" data-dir="-1" aria-label="Sebelumnya"><i class="ti ti-chevron-left"></i></button>
            <button type="button" class="marquee-nav next" data-dir="1" aria-label="Berikutnya"><i class="ti ti-chevron-right"></i></button>
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
                                <div class="porto-caption">
                                    <h3 class="porto-title font-editorial">{{ $p->portofolio_judul ?: $p->nama_produksi }}</h3>
                                    <div class="porto-sub">{{ implode(' · ', array_filter([$p->portofolio_jenis, $p->portofolio_tahun, $p->tampilkan_nama_client ? $p->namaClient() : null])) }}</div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- FAQ (4 Accordion Bersih) --}}
    <div class="lowongan-section" id="faq" aria-label="Pertanyaan umum">
        <div class="lowongan-inner" style="max-width: 780px;">
            <div style="text-align: center; margin-bottom: 28px;">
                <h2 class="section-title font-editorial">Pertanyaan yang sering ditanyakan</h2>
            </div>
            <div class="lowongan-list">
                <details class="lowongan-item">
                    <summary class="lowongan-summary">
                        <span style="flex: 1; font-size: 15px; font-weight: 600; color: var(--hp-fg);">Daftarnya bayar?</span>
                        <span class="lowongan-chevron" aria-hidden="true"><i class="ti ti-plus"></i></span>
                    </summary>
                    <div class="lowongan-detail" style="padding-left: 0;">
                        <p class="section-body" style="margin: 0;">Gratis.</p>
                    </div>
                </details>
                <details class="lowongan-item">
                    <summary class="lowongan-summary">
                        <span style="flex: 1; font-size: 15px; font-weight: 600; color: var(--hp-fg);">Kapan honor dibayar?</span>
                        <span class="lowongan-chevron" aria-hidden="true"><i class="ti ti-plus"></i></span>
                    </summary>
                    <div class="lowongan-detail" style="padding-left: 0;">
                        <p class="section-body" style="margin: 0;">Setelah kontrak ditandatangani dan produksi berjalan; status transfer bisa kamu cek dan konfirmasi di aplikasi.</p>
                    </div>
                </details>
                <details class="lowongan-item">
                    <summary class="lowongan-summary">
                        <span style="flex: 1; font-size: 15px; font-weight: 600; color: var(--hp-fg);">Kalau batal ikut gimana?</span>
                        <span class="lowongan-chevron" aria-hidden="true"><i class="ti ti-plus"></i></span>
                    </summary>
                    <div class="lowongan-detail" style="padding-left: 0;">
                        <p class="section-body" style="margin: 0;">Batal kurang dari 2 hari sebelum shooting dihitung batal mendadak; 3 kali batal mendadak membuat akun dinonaktifkan.</p>
                    </div>
                </details>
                <details class="lowongan-item">
                    <summary class="lowongan-summary">
                        <span style="flex: 1; font-size: 15px; font-weight: 600; color: var(--hp-fg);">Data KTP dan rekening aman?</span>
                        <span class="lowongan-chevron" aria-hidden="true"><i class="ti ti-plus"></i></span>
                    </summary>
                    <div class="lowongan-detail" style="padding-left: 0;">
                        <p class="section-body" style="margin: 0;">Disimpan terenkripsi dan hanya dipakai untuk kontrak dan pembayaran; tidak terlihat oleh Client.</p>
                    </div>
                </details>
            </div>
        </div>
    </div>

    {{-- Kolaborasi (Dua Jalur Inquiries) --}}
    <div class="lp-dual-section" style="padding: 0 32px 56px;">
        <div style="max-width: 1100px; margin: 0 auto;">
            <div class="lp-inquiry-box">
                <div class="lp-inquiry-grid">
                    {{-- Kolom Kiri: Calon Extras --}}
                    <div class="lp-inquiry-col lp-inquiry-col-left">
                        <div>
                            <h3 class="lp-inquiry-title">
                                Ingin ikut syuting film atau iklan?
                            </h3>
                            <p class="lp-inquiry-desc">
                                Daftar akun gratis, lengkapi foto lookbook profil, dan lamar peran casting yang sedang buka. Kontrak kerja tercatat di aplikasi dan pencairan honor ditransfer langsung ke rekening bank kamu.
                            </p>
                        </div>
                        <div>
                            @guest
                                <a href="{{ route('register') }}" class="btn-brand" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                                    Daftar jadi extras <i class="ti ti-arrow-right"></i>
                                </a>
                            @else
                                <a href="#lowongan" class="btn-brand" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                                    Lihat lowongan casting <i class="ti ti-arrow-right"></i>
                                </a>
                            @endguest
                        </div>
                    </div>

                    {{-- Kolom Kanan: Production House & Client --}}
                    <div class="lp-inquiry-col">
                        <div>
                            <h3 class="lp-inquiry-title">
                                Kebutuhan extras untuk produksi film?
                            </h3>
                            <p class="lp-inquiry-desc">
                                Dapatkan data talent dengan spesifikasi fisik jelas, koordinasi lapangan oleh korlap di lokasi set, presensi kehadiran berbasis foto selfie, serta administrasi kontrak kerja terpusat.
                            </p>
                        </div>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <a href="{{ route('login') }}" class="btn-brand" style="background: #22B862; color: #04140A; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; border-color: #22B862;">
                                <i class="ti ti-user-check"></i> Masuk portal client
                            </a>
                            <a href="#kontak" class="btn-outline" style="color: #F4F3EE; border-color: rgba(255,255,255,0.25); text-decoration: none;">
                                Hubungi agensi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Contact Section --}}
    @php
        $kontakWa = config('app.kontak.wa');
        $kontakEmail = config('app.kontak.email');
    @endphp
    <section class="contact-section" id="kontak" aria-label="Hubungi kami">
        <div class="contact-inner">
            <div>
                <div class="contact-left-title">Hubungi kami</div>
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

    @include('partials.landing-footer', ['page' => 'home'])

    <script>
    // Carousel geser manual: drag mouse/trackpad, swipe native di touch.
    document.querySelectorAll('[data-carousel]').forEach(function (el) {
        var dragging = false, moved = false, startX, startScroll, pointerId, captured = false;
        // Browser nge-drag <img>/<a> secara native by default - itu nyegat pointermove
        // sebelum logika drag kita sempat jalan, jadi geser mouse kelihatan nggak ngapa-ngapain.
        el.addEventListener('dragstart', function (e) { e.preventDefault(); });
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
                    pauseAuto(); resumeAutoLater();
                    el.scrollBy({ left: Number(btn.dataset.dir) * el.clientWidth * 0.8, behavior: 'smooth' });
                });
            });
        }

        // Auto-geser pulang-pergi pas idle, berhenti saat disentuh/di-hover/digeser manual.
        var autoDir = 1, autoPaused = false, resumeTimer;
        function pauseAuto() { autoPaused = true; clearTimeout(resumeTimer); }
        function resumeAutoLater() { clearTimeout(resumeTimer); resumeTimer = setTimeout(function () { autoPaused = false; }, 2500); }
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            el.addEventListener('pointerdown', pauseAuto);
            el.addEventListener('pointerup', resumeAutoLater);
            el.addEventListener('pointercancel', resumeAutoLater);
            el.addEventListener('mouseenter', pauseAuto);
            el.addEventListener('mouseleave', resumeAutoLater);
            (function tick() {
                var max = el.scrollWidth - el.clientWidth;
                if (!autoPaused && max > 2) {
                    el.scrollLeft += autoDir * 0.6;
                    if (el.scrollLeft >= max) autoDir = -1;
                    else if (el.scrollLeft <= 0) autoDir = 1;
                }
                requestAnimationFrame(tick);
            })();
        }
    });
    </script>
    <script>
    // Cast (BH.4): klik chip kategori/"Acak lagi" -> fetch fragmen, ganti grid+chip tanpa reload.
    // Gagal fetch -> fallback navigasi penuh ke href asal (chip tetap jalan sebagai <a> biasa).
    (function () {
        function muat(url, cariFokus) {
            var frag = document.getElementById('cast-fragment');
            if (!frag) { location.href = url; return; }
            frag.style.minHeight = frag.getBoundingClientRect().height + 'px';
            frag.setAttribute('aria-busy', 'true');
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw r; return r.text(); })
                .then(function (html) {
                    var dok = new DOMParser().parseFromString(html, 'text/html');
                    var baru = dok.getElementById('cast-fragment');
                    if (!baru) { location.href = url; return; }
                    frag.replaceWith(baru);
                    history.replaceState(null, '', url);
                    var fokus = cariFokus(baru);
                    if (fokus) fokus.focus();
                })
                .catch(function () { location.href = url; });
        }

        document.addEventListener('click', function (e) {
            var chip = e.target.closest('[data-cast-chip]');
            if (chip) {
                e.preventDefault();
                var kategori = chip.dataset.kategori;
                muat(chip.getAttribute('href'), function (root) {
                    var all = root.querySelectorAll('[data-cast-chip]');
                    for (var i = 0; i < all.length; i++) if (all[i].dataset.kategori === kategori) return all[i];
                });
                return;
            }
            if (e.target.closest('[data-cast-shuffle]')) {
                e.preventDefault();
                muat(location.pathname + location.search, function (root) { return root.querySelector('[data-cast-shuffle]'); });
            }
        });
    })();
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

        var userMenu = document.getElementById('navbar-user-menu');
        if (userMenu) {
            document.addEventListener('click', function (e) { if (!userMenu.contains(e.target)) userMenu.removeAttribute('open'); });
        }

        var filterPills = document.querySelectorAll('.lp-filter-pill');
        if (filterPills.length > 0) {
            filterPills.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    filterPills.forEach(function (b) { b.classList.remove('is-active'); });
                    btn.classList.add('is-active');
                    var filter = btn.getAttribute('data-filter');
                    document.querySelectorAll('.lp-job-card').forEach(function (card) {
                        if (filter === 'all') {
                            card.style.display = '';
                        } else if (filter === 'urgent') {
                            card.style.display = card.getAttribute('data-urgent') === '1' ? '' : 'none';
                        }
                    });
                });
            });
        }
    })();
    </script>
    <script>
        if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost')) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
