{{-- Navbar pill mengambang (desktop + mobile) & sheet menu. Param: $page = 'home'|'tentang'. --}}
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
    .pill-logo {
        width: 28px; height: 28px; display: block; flex-shrink: 0;
        -webkit-mask-image: url('{{ asset('images/brand/symbol-white.png') }}');
        mask-image: url('{{ asset('images/brand/symbol-white.png') }}');
        -webkit-mask-size: contain; mask-size: contain;
        -webkit-mask-repeat: no-repeat; mask-repeat: no-repeat;
        -webkit-mask-position: center; mask-position: center;
        background-color: currentColor;
    }
    .pill-links { list-style: none; display: flex; gap: 2px; margin: 0; padding: 0; }
    .pill-links a { display: flex; align-items: center; min-height: 40px; padding: 0 12px; border-radius: 999px; font-size: 13px; color: var(--hp-muted); text-decoration: none; white-space: nowrap; }
    .pill-links a:hover, .pill-links a.is-active { color: var(--hp-fg); background: var(--hp-card); }
    .pill-links a.is-active { font-weight: 600; }
    .pill-actions { display: flex; align-items: center; gap: 8px; }
    .pill-nav .theme-toggle-btn { width: 40px; height: 40px; }
    .pill-nav .btn-brand { min-height: 40px; padding: 0 18px; font-size: 13px; }
    .pill-nav :focus-visible { outline: 2px solid var(--hp-accent); outline-offset: 2px; }
    .pill-menu-btn { display: none; width: 40px; height: 40px; border-radius: 50%; border: none; background: none; color: var(--hp-fg); align-items: center; justify-content: center; cursor: pointer; font-size: 19px; }
    .pill-menu-btn:hover { background: var(--hp-card); }

    @media (max-width: 768px) {
        .pill-links { display: none; }
        .pill-nav .theme-toggle-btn { display: none; }
        .pill-menu-btn { display: flex; }
        main { padding-top: 72px; }
    }
</style>

<nav class="pill-nav" aria-label="Menu utama">
    <div class="pill-nav-inner">
        <a href="{{ route('home') }}" class="pill-brand">
            <span class="pill-logo" role="img" aria-label="SiCasti"></span>
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
            <button type="button" class="pill-menu-btn" id="pill-menu-btn" aria-haspopup="dialog" aria-controls="nav-sheet" aria-label="Buka menu">
                <i class="ti ti-menu-2" aria-hidden="true"></i>
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

<dialog id="nav-sheet" class="nav-sheet-dlg" aria-label="Menu">
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
            <a href="{{ $hrefLowongan }}" class="list-row sheet-anchor">
                <i class="ti ti-briefcase" aria-hidden="true"></i>
                <span class="label">Lowongan</span>
                <i class="ti ti-chevron-right chev" aria-hidden="true"></i>
            </a>
            <a href="{{ $hrefCaraKerja }}" class="list-row sheet-anchor">
                <i class="ti ti-list-check" aria-hidden="true"></i>
                <span class="label">Cara Kerja</span>
                <i class="ti ti-chevron-right chev" aria-hidden="true"></i>
            </a>
            <a href="{{ $hrefFaq }}" class="list-row sheet-anchor">
                <i class="ti ti-help-circle" aria-hidden="true"></i>
                <span class="label">FAQ</span>
                <i class="ti ti-chevron-right chev" aria-hidden="true"></i>
            </a>
        </div>

        @guest
            <span class="list-group-label">Akun</span>
            <div class="list-group">
                <a href="{{ route('login') }}" class="list-row list-row--accent">
                    <i class="ti ti-login" aria-hidden="true"></i>
                    <span class="label">Masuk</span>
                    <i class="ti ti-chevron-right chev" aria-hidden="true"></i>
                </a>
                <a href="{{ route('register') }}" class="list-row list-row--accent">
                    <i class="ti ti-user-plus" aria-hidden="true"></i>
                    <span class="label">Daftar sebagai talent</span>
                    <i class="ti ti-chevron-right chev" aria-hidden="true"></i>
                </a>
            </div>
        @endguest

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
    var menuBtn = document.getElementById('pill-menu-btn');
    var closeBtn = document.getElementById('nav-sheet-close');
    if (sheet && menuBtn) {
        menuBtn.addEventListener('click', function () { sheet.showModal(); });
        sheet.addEventListener('click', function (e) { if (e.target === sheet) sheet.close(); });
        if (closeBtn) closeBtn.addEventListener('click', function () { sheet.close(); });
    }

    document.querySelectorAll('.sheet-anchor').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var href = link.getAttribute('href');
            sheet.close();
            if (href.charAt(0) === '#') {
                e.preventDefault();
                var target = document.getElementById(href.slice(1));
                if (target) target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

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
