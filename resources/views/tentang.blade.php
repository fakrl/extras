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
    <title>Tentang Kami | {{ config('brand.name') }}</title>
    <link rel="canonical" href="{{ url('/tentang') }}">
    @include('partials.og-meta', [
        'noindex' => false,
        'ogTitle' => 'Tentang '.config('brand.legal_entity'),
        'ogDesc' => config('brand.legal_entity').', operator sistem '.config('brand.name').'.',
        'ogImage' => asset('images/brand/icon-512.png'),
    ])

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@@tabler/icons-webfont@3.48.0/dist/tabler-icons.min.css">
    @include('partials.theme-style')
    <style>
        :root[data-theme="dark"] {
            --hp-bg: #111311; --hp-fg: #f2f1eb; --hp-muted: rgba(242, 241, 235, 0.55);
            --hp-accent: #b7ff3c; --hp-accent-on: #111311; --hp-line: rgba(255, 255, 255, 0.13); --hp-card: #1a1c1a;
        }
        :root[data-theme="light"] {
            --hp-bg: #f1f0e9; --hp-fg: #171a16; --hp-muted: #52564e;
            --hp-accent: #486610; --hp-accent-on: #ffffff; --hp-line: rgba(23, 26, 22, 0.16); --hp-card: #e6e4da;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', sans-serif; background: var(--hp-bg); color: var(--hp-fg); }
        a:focus-visible, button:focus-visible, summary:focus-visible { outline: 2px solid var(--hp-accent); outline-offset: 2px; }
        .btn-brand { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 20px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; background: var(--hp-accent); color: var(--hp-accent-on); border: none; }
        .btn-brand:hover { filter: brightness(1.08); }
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

        .tentang-wrap { max-width: 760px; margin: 0 auto; padding: 48px 32px 72px; }
        .tentang-eyebrow { display: block; font-size: 12px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: var(--hp-accent); margin-bottom: 10px; }
        .tentang-title { font-family: var(--font-editorial, 'Newsreader', Georgia, serif); font-size: clamp(2rem, 4.5vw, 3rem); line-height: 1.1; letter-spacing: -0.02em; font-weight: 500; margin: 0 0 20px; color: var(--hp-fg); }
        .tentang-section { border-top: 1px solid var(--hp-line); padding-top: 28px; margin-top: 28px; }
        .tentang-section h2 { font-family: var(--font-editorial, 'Newsreader', Georgia, serif); font-size: 1.5rem; font-weight: 500; margin: 0 0 12px; color: var(--hp-fg); }
        .tentang-section p { font-size: 14.5px; line-height: 1.75; color: var(--hp-muted); margin: 0 0 12px; }
        .fact-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px 24px; margin-top: 16px; }
        .fact-item strong { display: block; font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--hp-muted); margin-bottom: 4px; }
        .fact-item span { font-size: 14px; color: var(--hp-fg); }
        .tentang-links { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 8px; }
        .tentang-links a { color: var(--hp-fg); font-size: 14px; text-decoration: none; border-bottom: 1px solid var(--hp-line); }
        .tentang-links a:hover { color: var(--hp-accent); border-color: var(--hp-accent); }
        @media (max-width: 767px) { .fact-grid { grid-template-columns: 1fr; } .tentang-wrap { padding: 32px 20px 56px; } }

        footer { border-top: 1px solid var(--hp-line); padding: 24px 32px; }
        .footer-inner { max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: 1fr auto auto; gap: 24px; align-items: center; }
        .footer-brand { display: flex; align-items: center; gap: 9px; font-size: 13px; font-weight: 600; color: var(--hp-fg); text-decoration: none; }
        .footer-brand img { width: 26px; height: 26px; border-radius: 6px; object-fit: contain; }
        .footer-nav { display: flex; gap: 20px; }
        .footer-nav a { display: inline-flex; align-items: center; min-height: 44px; font-size: 13px; color: var(--hp-muted); text-decoration: none; white-space: nowrap; }
        .footer-nav a:hover { color: var(--hp-fg); }
        .footer-legal { display: flex; gap: 16px; align-items: center; font-size: 13px; color: var(--hp-muted); }
        @media (max-width: 768px) {
            .footer-inner { grid-template-columns: 1fr; gap: 16px; padding-left: 4px; }
            footer { padding: 20px; padding-bottom: 96px; padding-bottom: calc(96px + env(safe-area-inset-bottom)); }
        }
    </style>
</head>
<body>
    @include('partials.pill-nav', ['page' => 'tentang'])

    <main>
        <div class="tentang-wrap">
            <span class="tentang-eyebrow">Tentang Kami</span>
            <h1 class="tentang-title">{{ config('brand.legal_entity') }}</h1>
            <p>
                {{ config('brand.legal_entity') }} adalah badan hukum PT yang mengoperasikan dan memiliki sistem
                {{ config('brand.name') }} — platform casting dan manajemen talent extras yang digunakan Admin,
                Korlap, Client, dan Extras dalam alur kerja sehari-hari.
            </p>

            <div class="tentang-section">
                <h2>Profil Perusahaan</h2>
                <p>
                    Berdiri sejak 2020, {{ config('brand.legal_entity') }} bergerak di bidang jasa casting dan
                    penyediaan talent extras untuk kebutuhan produksi film dan iklan di wilayah Jabodetabek.
                </p>
                <div class="fact-grid">
                    <div class="fact-item">
                        <strong>Nama Perusahaan</strong>
                        <span>{{ config('brand.legal_entity') }}</span>
                    </div>
                    <div class="fact-item">
                        <strong>Legalitas</strong>
                        <span>NIB terdaftar OSS</span>
                    </div>
                    <div class="fact-item">
                        <strong>Domisili</strong>
                        <span>Pamulang, Tangerang Selatan, Banten, Indonesia</span>
                    </div>
                    <div class="fact-item">
                        <strong>Skala Operasional</strong>
                        <span>5 karyawan tetap · 50–80 extras aktif per bulan</span>
                    </div>
                </div>
                {{-- TODO(Fakrul): alamat kantor lengkap (jalan, nomor, kode pos) belum ada di repo, isi manual. --}}
                {{-- TODO(Fakrul): sejarah/cerita pendirian perusahaan versi lengkap belum ada di repo, isi manual. --}}
            </div>

            <div class="tentang-section">
                <h2>Visi &amp; Misi</h2>
                {{-- TODO(Fakrul): teks Visi (1 kalimat) & Misi (4 poin) verbatim dari dokumen proposal belum
                     tersalin ke repo ini — isi manual supaya tidak ada teks karangan di halaman resmi. --}}
                <p>Teks Visi &amp; Misi menyusul — lihat dokumen proposal resmi perusahaan.</p>
            </div>

            <div class="tentang-section">
                <h2>Tautan Terkait</h2>
                <p>Kebijakan privasi dan syarat &amp; ketentuan penggunaan sistem {{ config('brand.name') }}:</p>
                <div class="tentang-links">
                    <a href="{{ route('privacy-policy') }}">Kebijakan Privasi</a>
                    <a href="{{ route('syarat-ketentuan') }}">Syarat &amp; Ketentuan</a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-inner">
            <a href="{{ route('home') }}" class="footer-brand">
                <img src="{{ asset('images/logo-jbtb.jpg') }}" alt="Logo {{ config('brand.legal_entity') }}" width="26" height="26">
                {{ config('brand.legal_entity') }}
            </a>
            <nav class="footer-nav" aria-label="Tautan footer">
                <a href="{{ route('home') }}">Beranda {{ config('brand.name') }}</a>
                <a href="{{ route('privacy-policy') }}">Privasi</a>
            </nav>
            <div class="footer-legal">
                <span>&copy; {{ date('Y') }} {{ config('brand.legal_entity') }}</span>
            </div>
        </div>
    </footer>

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
    })();
    </script>
</body>
</html>
