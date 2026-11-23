{{-- Sorot: sistem desain landing (welcome.blade.php & tentang.blade.php). Menggantikan restyle
     Re:Daily sebelumnya secara penuh — jangan tambah aturan baru di atas aturan lama, ganti di sini. --}}
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
<style>
    /* ── Token Sorot: dialiaskan ke --hp-* supaya pill-nav/list-group/style inline
       per-halaman yang sudah ada otomatis ikut ganti warna tanpa disentuh strukturnya. ── */
    :root[data-theme="light"] {
        --bg: #F7F4EE; --fg: #1A1714; --muted-fg: #6A6459; --card: #FFFDF8;
        --border: rgba(26, 23, 20, .14); --section-muted: rgba(247, 244, 238, .6);
        --primary: #0F6B73; --primary-hover: #0A4F56; --on-primary: #FFFFFF; --accent-soft: #CDE9EA; --on-accent-soft: #05282B;
        --hp-bg: var(--bg); --hp-fg: var(--fg); --hp-muted: var(--muted-fg); --hp-card: var(--card);
        --hp-line: var(--border); --hp-accent: var(--primary); --hp-accent-on: var(--on-primary);
    }
    :root[data-theme="dark"] {
        --bg: #131110; --fg: #F2EEE6; --muted-fg: #A39B8E; --card: #1B1816;
        --border: rgba(242, 238, 230, .13); --section-muted: #1B1816;
        --primary: #5CC3CB; --primary-hover: #8FDDE3; --on-primary: #07282B; --accent-soft: #0F4A50; --on-accent-soft: #BDEFF3;
        --hp-bg: var(--bg); --hp-fg: var(--fg); --hp-muted: var(--muted-fg); --hp-card: var(--card);
        --hp-line: var(--border); --hp-accent: var(--primary); --hp-accent-on: var(--on-primary);
    }
    :root { --font-editorial: 'Plus Jakarta Sans', sans-serif; --font-accent: 'Instrument Serif', serif; }

    html { scroll-padding-top: 84px; }
    @media (max-width: 768px) { html { scroll-padding-top: 72px; } }
    body { font-family: 'Plus Jakarta Sans', sans-serif; }

    .wrap { max-width: 1180px; margin-inline: auto; padding-inline: 5%; }
    .hero-inner, .services-inner, .lowongan-inner, .contact-inner, .hp-sec-head { max-width: 1180px; }
    .hero-inner { padding: 80px 5% 56px; }
    .services-section, .lowongan-section, .cast-section, .porto-section, .contact-section { padding-block: 80px; }
    @media (max-width: 768px) {
        .hero-inner { padding: 56px 5% 40px; }
        .services-section, .lowongan-section, .cast-section, .porto-section, .contact-section { padding-block: 56px; }
    }
    #cara-kerja { background: var(--section-muted); }
    #lowongan, #cast, #portofolio, #kontak { background: var(--bg); }
    #faq { background: var(--section-muted); }

    /* ── Heading: Plus Jakarta Sans tegas, aksen Instrument Serif italic hanya 1 kata ── */
    .hp-sec-title, .lp-hero-title, .services-left-title, .contact-left-title,
    .section-title, .lp-job-title, .cast-name, .porto-title,
    .lp-chapter-title, .lp-inquiry-title, .tentang-title, .tentang-section h2 {
        font-weight: 800; letter-spacing: -0.045em;
    }
    .hp-sec-title, .lp-hero-title, .services-left-title, .contact-left-title, .tentang-title {
        line-height: .98;
    }
    .hp-accent-serif { font-family: 'Instrument Serif', serif; font-style: italic; font-weight: 400; color: var(--primary); letter-spacing: 0; }

    .hp-sec-head, .lp-lowongan-header { margin-bottom: 26px; }

    /* ── Pil tombol (CTA, nav "Masuk") ── */
    .btn-brand, .btn-outline { border-radius: 999px; padding: 0 22px; }
    .btn-outline { border-width: 1.5px; }
    @media (prefers-reduced-motion: no-preference) {
        .btn-brand, .btn-outline { transition: transform .2s, filter .2s, background .2s; }
        .btn-brand:hover, .btn-outline:hover { transform: translateY(-2px); }
    }
    @media (prefers-reduced-motion: reduce) {
        .btn-brand, .btn-outline, .lp-job-card, .lp-chapter-col { transition: none !important; transform: none !important; }
    }

    /* ── Nav pill: pakai compound selector biar menang walau <style>-nya pill-nav
       dirender setelah partial ini (pill-nav di <body>, partial ini di <head>). ── */
    .pill-nav .pill-nav-inner { backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border-width: 1px; }
    .pill-nav .pill-links a.is-active { background: var(--accent-soft); color: var(--on-accent-soft); }
    .pill-nav .pill-actions > a.btn-brand { background: var(--fg); color: var(--bg); }
    .pill-nav .pill-actions > a.btn-brand:hover { filter: brightness(1.15); }

    /* ── Hero ── */
    .hero { background-image: radial-gradient(ellipse at 50% -20%, color-mix(in srgb, var(--primary) 14%, transparent), transparent 70%); }
    .lp-hero-desc { max-width: 34em; }
    .hp-eyebrow-pill {
        display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; margin-bottom: 18px;
        border-radius: 999px; background: var(--accent-soft); color: var(--on-accent-soft);
        font-size: 11px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase;
    }
    .hp-eyebrow-pill .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--primary); flex-shrink: 0; }
    .hp-stats { display: flex; gap: 32px; flex-wrap: wrap; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); }
    .hp-stat-num { font-size: 1.9rem; font-weight: 800; color: var(--fg); line-height: 1; }
    .hp-stat-label { display: block; margin-top: 4px; font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--muted-fg); }

    .hp-hero-visual {
        position: relative; aspect-ratio: 4 / 5; border-radius: 28px; overflow: hidden;
        background: var(--accent-soft); display: flex; align-items: center; justify-content: center; padding: 24px;
    }
    .hp-hero-visual .orb { position: absolute; border-radius: 50%; background: var(--primary); opacity: .3; filter: blur(42px); }
    .hp-hero-visual .orb-a { width: 170px; height: 170px; top: -50px; right: -50px; }
    .hp-hero-visual .orb-b { width: 150px; height: 150px; bottom: -40px; left: -40px; }
    .hp-mock-stack { position: relative; z-index: 1; display: flex; flex-direction: column; gap: 12px; width: 100%; max-width: 260px; }
    .hp-mock-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 14px 16px; }
    .hp-mock-card-title { font-size: 13px; font-weight: 700; color: var(--fg); }
    .hp-mock-card-sub { font-size: 11px; color: var(--muted-fg); margin-top: 2px; }
    .hp-mock-chip { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px; background: var(--accent-soft); color: var(--on-accent-soft); font-size: 10.5px; font-weight: 700; margin-top: 8px; }
    .hp-hero-float {
        position: absolute; left: 16px; bottom: 16px; z-index: 2; max-width: 220px;
        background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 12px 16px;
        box-shadow: 0 16px 32px -8px rgba(15, 21, 17, .22);
    }
    .hp-hero-float strong { display: block; font-size: 14px; font-weight: 800; color: var(--fg); }
    .hp-hero-float span { font-size: 11px; color: var(--muted-fg); }
    @media (max-width: 960px) { .hp-hero-visual { aspect-ratio: auto; height: 280px; } }

    /* ── Cara Kerja: kartu bernomor (01, 02, ...) via CSS counter, tanpa ubah markup ── */
    .lp-chapters-grid { counter-reset: chapter; }
    .lp-chapter-col {
        counter-increment: chapter; position: relative; border: 1px solid var(--border); border-top-width: 1px;
        border-radius: 24px; background: var(--card); padding: 24px;
    }
    .lp-chapter-col::before {
        content: counter(chapter, decimal-leading-zero); display: block; margin-bottom: 10px;
        font-size: 13px; font-weight: 800; letter-spacing: .05em; color: var(--muted-fg);
    }
    @media (hover: hover) and (prefers-reduced-motion: no-preference) {
        .lp-chapter-col { transition: transform .2s, border-color .2s; }
        .lp-chapter-col:hover { transform: translateY(-2px); border-color: var(--primary); }
    }

    /* ── Lowongan: kartu 24px + pill jumlah di kanan header ── */
    .lp-job-card { border-radius: 24px; }
    .lp-lowongan-header { align-items: center; }
    .lp-lowongan-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .lp-count-pill {
        display: inline-flex; align-items: center; padding: 6px 14px; border-radius: 999px;
        background: var(--accent-soft); color: var(--on-accent-soft); font-size: 12.5px; font-weight: 700; white-space: nowrap;
    }
    @media (hover: hover) and (prefers-reduced-motion: no-preference) {
        .lp-job-card:hover { transform: translateY(-2px); }
    }

    /* ── Talent: foto 3:4, radius 24 ── */
    .cast-photo-wrap { border-radius: 24px; aspect-ratio: 3 / 4; }

    /* ── Portofolio: radius 28, caption overlay gradasi gelap ── */
    .porto-img { border-radius: 28px; position: relative; }
    .porto-caption {
        position: absolute; left: 0; right: 0; bottom: 0; padding: 36px 16px 14px;
        background: linear-gradient(to top, rgba(0, 0, 0, .75), transparent);
    }
    .porto-caption .porto-title, .porto-caption .porto-sub { color: #fff; margin: 0; white-space: normal; }
    .porto-caption .porto-title { font-size: 1.2rem; }

    /* ── FAQ: kartu pill radius 20, tanpa shadow ── */
    .lowongan-list { border-top: none; display: flex; flex-direction: column; gap: 10px; margin-top: 24px; }
    .lowongan-item { border: 1px solid var(--border); border-bottom: 1px solid var(--border); border-radius: 20px; background: var(--card); overflow: hidden; }
    .lowongan-item[open] { border: 1px solid var(--primary); background: var(--card); padding-left: 0; margin-left: 0; }
    .lowongan-summary { padding: 16px 20px; }
    .lowongan-detail { padding: 0 20px 18px; }
    /* FAQ (satu-satunya pemakai .lowongan-item/.lowongan-summary sekarang) bukan
       kartu loker lagi - lepas grid thumb/title/chevron peninggalan pola lama. */
    @media (max-width: 767px) { .lowongan-summary { display: flex; grid-template-columns: none; grid-template-areas: none; } }

    /* ── Kontak: kartu tile ── */
    .contact-block { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 18px 20px; }

    /* ── Kolaborasi: satu-satunya band kontras tegas di halaman (pengganti band gelap lama) ── */
    .lp-inquiry-box { background: #0B1410; border-radius: 24px; border-color: rgba(255, 255, 255, .12); }
</style>
