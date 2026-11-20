{{-- Navbar pill mengambang (desktop) + dock bawah (mobile). Param: $page = 'home'|'tentang'. --}}
@php
    $isHome = ($page ?? 'home') === 'home';
    $hrefLowongan = $isHome ? '#lowongan' : route('home').'#lowongan';
    $hrefCaraKerja = $isHome ? '#cara-kerja' : route('home').'#cara-kerja';
    $hrefFaq = $isHome ? '#faq' : route('home').'#faq';
@endphp
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
    @media (max-width: 768px) {
        .pill-links { display: none; }
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
    <a href="{{ $hrefFaq }}" class="bottom-dock-item" data-section="faq"><i class="ti ti-help-circle" aria-hidden="true"></i><span>FAQ</span></a>
    @auth
        <a href="/dashboard" class="bottom-dock-item"><i class="ti ti-user" aria-hidden="true"></i><span>Akun</span></a>
    @else
        <a href="{{ route('login') }}" class="bottom-dock-item"><i class="ti ti-login" aria-hidden="true"></i><span>Masuk</span></a>
    @endauth
</nav>

<script>
(function () {
    var navLinks = document.querySelectorAll('[data-section]');
    var targets = ['beranda', 'lowongan', 'cara-kerja', 'faq']
        .map(function (id) { return document.getElementById(id); })
        .filter(Boolean);
    if (!navLinks.length || !targets.length || !('IntersectionObserver' in window)) return;

    function setActive(id) {
        navLinks.forEach(function (a) { a.classList.toggle('is-active', a.getAttribute('data-section') === id); });
    }
    var obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) { if (entry.isIntersecting) setActive(entry.target.id); });
    }, { rootMargin: '-45% 0px -50% 0px' });
    targets.forEach(function (t) { obs.observe(t); });
})();
</script>
