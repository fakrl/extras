{{-- Navbar pill mengambang (desktop) + dock bawah & sheet menu (mobile). Param: $page = 'home'|'tentang'. --}}
@php
    $isHome = ($page ?? 'home') === 'home';
    $hrefLowongan = $isHome ? '#lowongan' : route('home').'#lowongan';
    $hrefCaraKerja = $isHome ? '#cara-kerja' : route('home').'#cara-kerja';
    $hrefFaq = $isHome ? '#faq' : route('home').'#faq';
@endphp
@include('partials.list-group-style')
<style>
    main { padding-top: 84px; }
    .pill-nav { position: fixed; top: 18px; left: 0; right: 0; z-index: 200; display: flex; justify-content: center; padding: 0 16px; pointer-events: none; }
    .pill-nav-inner {
        pointer-events: auto; display: flex; align-items: center; gap: 14px;
        max-width: calc(100% - 2rem); width: fit-content; padding: 6px 10px 6px 16px;
        border-radius: 999px; background: color-mix(in srgb, var(--hp-bg) 72%, transparent);
        border: 1px solid var(--hp-line); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.14);
    }
    .pill-brand { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 14px; color: var(--hp-fg); text-decoration: none; white-space: nowrap; }
    .pill-logo { width: 28px; height: 28px; border-radius: 8px; display: block; flex-shrink: 0; }
    .pill-links { list-style: none; display: flex; gap: 2px; margin: 0; padding: 0; }
    .pill-links a { display: flex; align-items: center; min-height: 40px; padding: 0 12px; border-radius: 999px; font-size: 13px; color: var(--hp-muted); text-decoration: none; white-space: nowrap; }
    .pill-links a:hover, .pill-links a.is-active { color: var(--hp-fg); background: var(--hp-card); }
    .pill-links a.is-active { font-weight: 600; }
    .pill-actions { display: flex; align-items: center; gap: 8px; }
    .pill-nav .theme-toggle-btn { width: 40px; height: 40px; }
    .pill-nav .btn-brand { min-height: 40px; padding: 0 18px; font-size: 13px; }
    .pill-nav :focus-visible { outline: 2px solid var(--hp-accent); outline-offset: 2px; }

    .bottom-dock { display: none; }
    dialog#nav-sheet { margin: 0; width: 100%; max-width: 480px; border: none; border-radius: 24px 24px 0 0; padding: 0; background: var(--hp-bg); color: var(--hp-fg); position: fixed; left: 50%; bottom: 0; transform: translateX(-50%); max-height: 86vh; overflow-y: auto; }
    dialog#nav-sheet::backdrop { background: rgba(0, 0, 0, .5); }
    .nav-sheet-head { position: sticky; top: 0; display: flex; align-items: center; justify-content: space-between; padding: 18px 20px 10px; background: var(--hp-bg); }
    .nav-sheet-title { font-size: 16px; font-weight: 700; }
    .nav-sheet-close { width: 36px; height: 36px; border-radius: 50%; border: none; background: var(--hp-card); color: var(--hp-fg); display: flex; align-items: center; justify-content: center; cursor: pointer; }
    .nav-sheet-body { padding-top: 6px; padding-bottom: calc(16px + env(safe-area-inset-bottom)); }

    @media (max-width: 768px) {
        .pill-brand span { display: none; }
        .pill-links { display: none; }
        .pill-nav .theme-toggle-btn { display: none; }
        main { padding-top: 72px; }
        .bottom-dock {
            display: flex; position: fixed; left: 50%; transform: translateX(-50%);
            bottom: calc(14px + env(safe-area-inset-bottom)); z-index: 200;
            background: color-mix(in srgb, var(--hp-bg) 90%, transparent);
            border: 1px solid var(--hp-line); border-radius: 999px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            padding: 4px; gap: 2px; max-width: calc(100% - 24px);
        }
        .bottom-dock-item {
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;
            min-width: 56px; min-height: 48px; padding: 4px 6px; border-radius: 999px;
            color: var(--hp-muted); text-decoration: none; font-size: 10.5px; font-weight: 600;
            border: none; background: none; font-family: inherit; cursor: pointer;
        }
        .bottom-dock-item i { font-size: 19px; }
        .bottom-dock-item:hover, .bottom-dock-item.is-active { color: var(--hp-fg); background: var(--hp-card); }
        .bottom-dock-item:focus-visible { outline: 2px solid var(--hp-accent); outline-offset: -2px; }
    }

    @media (prefers-reduced-motion: no-preference) {
        .pill-links a, .bottom-dock-item { transition: background .15s ease, color .15s ease; }
    }
</style>

<nav class="pill-nav" aria-label="Menu utama">
    <div class="pill-nav-inner">
        <a href="{{ route('home') }}" class="pill-brand">
            <img src="{{ asset(config('brand.logo')) }}" alt="Logo {{ config('brand.name') }}" class="pill-logo">
            <span>{{ config('brand.name') }}</span>
        </a>
        <ul class="pill-links">
            <li><a href="{{ route('home') }}" data-section="beranda">Beranda</a></li>
            <li><a href="{{ $hrefLowongan }}" data-section="lowongan">Lowongan</a></li>
            <li><a href="{{ $hrefCaraKerja }}" data-section="cara-kerja">Cara Kerja</a></li>
            <li><a href="{{ $hrefFaq }}" data-section="faq">FAQ</a></li>
        </ul>
        <div class="pill-actions">
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
                <a href="{{ route('login') }}" class="btn-brand">Masuk</a>
            @endauth
        </div>
    </div>
</nav>

<nav class="bottom-dock" aria-label="Navigasi bawah">
    <a href="{{ route('home') }}" class="bottom-dock-item" data-section="beranda"><i class="ti ti-home" aria-hidden="true"></i><span>Beranda</span></a>
    <a href="{{ $hrefLowongan }}" class="bottom-dock-item" data-section="lowongan"><i class="ti ti-briefcase" aria-hidden="true"></i><span>Lowongan</span></a>
    <a href="{{ $hrefCaraKerja }}" class="bottom-dock-item" data-section="cara-kerja"><i class="ti ti-list-check" aria-hidden="true"></i><span>Cara Kerja</span></a>
    <button type="button" class="bottom-dock-item" id="dock-menu-item" aria-haspopup="dialog" aria-controls="nav-sheet"><i class="ti ti-menu-2" aria-hidden="true"></i><span>Menu</span></button>
</nav>

<dialog id="nav-sheet" aria-label="Menu">
    <div class="nav-sheet-head">
        <span class="nav-sheet-title">Menu</span>
        <button type="button" class="nav-sheet-close" id="nav-sheet-close" aria-label="Tutup menu"><i class="ti ti-x" aria-hidden="true"></i></button>
    </div>
    <div class="nav-sheet-body">
        <span class="list-group-label">Tampilan</span>
        <div class="list-group">
            <div class="list-row">
                <i class="ti ti-moon" aria-hidden="true"></i>
                <span class="label">Tema gelap</span>
                <button type="button" class="switch" id="sheet-theme-switch" role="switch" aria-checked="false" aria-label="Tema gelap">
                    <span class="switch-thumb"></span>
                </button>
            </div>
        </div>

        <span class="list-group-label">Jelajah</span>
        <div class="list-group">
            <a href="{{ $hrefFaq }}" class="list-row" id="sheet-faq">
                <i class="ti ti-help-circle" aria-hidden="true"></i>
                <span class="label">FAQ</span>
                <i class="ti ti-chevron-right chev" aria-hidden="true"></i>
            </a>
            @guest
                <a href="{{ route('register') }}" class="list-row list-row--accent">
                    <i class="ti ti-user-plus" aria-hidden="true"></i>
                    <span class="label">Daftar sebagai talent</span>
                    <i class="ti ti-chevron-right chev" aria-hidden="true"></i>
                </a>
            @endguest
        </div>

        <span class="list-group-label">Info</span>
        <div class="list-group">
            <a href="{{ route('tentang') }}" class="list-row">
                <i class="ti ti-building" aria-hidden="true"></i>
                <span class="label">Tentang PT JBTB</span>
                <i class="ti ti-chevron-right chev" aria-hidden="true"></i>
            </a>
            <a href="{{ route('privacy-policy') }}" class="list-row">
                <i class="ti ti-lock" aria-hidden="true"></i>
                <span class="label">Privasi</span>
                <i class="ti ti-chevron-right chev" aria-hidden="true"></i>
            </a>
            <a href="{{ route('syarat-ketentuan') }}" class="list-row">
                <i class="ti ti-file-text" aria-hidden="true"></i>
                <span class="label">Syarat dan ketentuan</span>
                <i class="ti ti-chevron-right chev" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</dialog>

<script>
(function () {
    var navLinks = document.querySelectorAll('[data-section]');
    var targets = ['beranda', 'lowongan', 'cara-kerja', 'faq']
        .map(function (id) { return document.getElementById(id); })
        .filter(Boolean);
    if (navLinks.length && targets.length && 'IntersectionObserver' in window) {
        function setActive(id) {
            navLinks.forEach(function (a) { a.classList.toggle('is-active', a.getAttribute('data-section') === id); });
        }
        var obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) { if (entry.isIntersecting) setActive(entry.target.id); });
        }, { rootMargin: '-45% 0px -50% 0px' });
        targets.forEach(function (t) { obs.observe(t); });
    }

    var sheet = document.getElementById('nav-sheet');
    var menuItem = document.getElementById('dock-menu-item');
    var closeBtn = document.getElementById('nav-sheet-close');
    if (sheet && menuItem) {
        menuItem.addEventListener('click', function () { sheet.showModal(); });
        sheet.addEventListener('click', function (e) { if (e.target === sheet) sheet.close(); });
        if (closeBtn) closeBtn.addEventListener('click', function () { sheet.close(); });
        new MutationObserver(function () { menuItem.classList.toggle('is-active', sheet.open); })
            .observe(sheet, { attributes: true, attributeFilter: ['open'] });
    }

    var faqLink = document.getElementById('sheet-faq');
    if (sheet && faqLink) {
        faqLink.addEventListener('click', function (e) {
            var href = faqLink.getAttribute('href');
            sheet.close();
            if (href.charAt(0) === '#') {
                e.preventDefault();
                var target = document.getElementById(href.slice(1));
                if (target) target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }

    var themeSwitch = document.getElementById('sheet-theme-switch');
    var themeIcon = document.getElementById('theme-icon');
    var themeToggle = document.getElementById('theme-toggle');
    function syncTheme() {
        var dark = document.documentElement.getAttribute('data-theme') === 'dark';
        if (themeSwitch) themeSwitch.setAttribute('aria-checked', dark ? 'true' : 'false');
        if (themeIcon) themeIcon.className = dark ? 'ti ti-sun' : 'ti ti-moon';
        if (themeToggle) themeToggle.setAttribute('aria-pressed', dark ? 'true' : 'false');
    }
    syncTheme();
    if (themeSwitch) {
        themeSwitch.addEventListener('click', function () {
            var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('jbtb-theme-v2', next); } catch (e) {}
            syncTheme();
        });
    }
    new MutationObserver(syncTheme).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
})();
</script>
