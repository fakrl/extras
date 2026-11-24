{{-- Navbar pill mengambang (desktop + mobile). Param: $page = 'home'|'tentang'. --}}
@php
    $isHome = ($page ?? 'home') === 'home';
    $hrefLowongan = $isHome ? '#lowongan' : route('home').'#lowongan';
    $hrefCaraKerja = $isHome ? '#cara-kerja' : route('home').'#cara-kerja';
    $hrefFaq = $isHome ? '#faq' : route('home').'#faq';
    $navItems = [
        ['href' => route('home'), 'section' => 'beranda', 'label' => 'Beranda'],
        ['href' => $hrefLowongan, 'section' => 'lowongan', 'label' => 'Lowongan'],
        ['href' => $hrefCaraKerja, 'section' => 'cara-kerja', 'label' => 'Cara Kerja'],
        ['href' => $hrefFaq, 'section' => 'faq', 'label' => 'FAQ'],
    ];
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
    .pill-logo {
        width: 28px; height: 28px; display: block; flex-shrink: 0;
        -webkit-mask-image: url('{{ asset('images/brand/symbol.svg') }}');
        mask-image: url('{{ asset('images/brand/symbol.svg') }}');
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
    .pill-nav .btn-brand { min-height: 40px; padding: 0 18px; font-size: 13px; }
    .pill-nav :focus-visible { outline: 2px solid var(--hp-accent); outline-offset: 2px; }

    .pill-burger { display: none; width: 44px; height: 44px; border-radius: 50%; border: none; background: none; color: var(--hp-fg); align-items: center; justify-content: center; cursor: pointer; }
    .pill-burger:hover { background: var(--hp-card); }
    .pill-burger-lines { width: 18px; height: 13px; position: relative; }
    .pill-burger-lines span { position: absolute; left: 0; width: 100%; height: 2px; border-radius: 2px; background: currentColor; transition: transform .2s ease, opacity .2s ease, top .2s ease; }
    .pill-burger-lines span:nth-child(1) { top: 0; }
    .pill-burger-lines span:nth-child(2) { top: 5.5px; }
    .pill-burger-lines span:nth-child(3) { top: 11px; }
    .pill-burger[aria-expanded="true"] .pill-burger-lines span:nth-child(1) { top: 5.5px; transform: rotate(45deg); }
    .pill-burger[aria-expanded="true"] .pill-burger-lines span:nth-child(2) { opacity: 0; }
    .pill-burger[aria-expanded="true"] .pill-burger-lines span:nth-child(3) { top: 5.5px; transform: rotate(-45deg); }
    @media (prefers-reduced-motion: reduce) { .pill-burger-lines span { transition: none; } }

    .pill-mobile-menu {
        position: fixed; top: 74px; left: 0.75rem; right: 0.75rem; z-index: 190;
        padding: 1rem; border-radius: 18px;
        background: color-mix(in srgb, var(--hp-bg) 94%, transparent);
        border: 1px solid var(--hp-line); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.2);
        opacity: 0; transform: translateY(-8px); visibility: hidden; pointer-events: none;
        transition: opacity .18s ease, transform .18s ease, visibility .18s;
    }
    .pill-mobile-menu.is-open { opacity: 1; transform: translateY(0); visibility: visible; pointer-events: auto; }
    .pill-mobile-menu :focus-visible { outline: 2px solid var(--hp-accent); outline-offset: 2px; }
    @media (prefers-reduced-motion: reduce) { .pill-mobile-menu { transition: none; } }
    .pill-mobile-links { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 4px; }
    .pill-mobile-links a { display: flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 14px; border-radius: 12px; font-size: 14px; font-weight: 600; color: var(--hp-fg); text-decoration: none; text-align: center; }
    .pill-mobile-links a:hover, .pill-mobile-links a.is-active { background: var(--hp-card); }
    .pill-mobile-links a.is-active { font-weight: 700; }
    .pill-mobile-masuk { margin-top: 6px; background: var(--hp-accent); color: var(--hp-accent-on); }

    @media (max-width: 768px) {
        .pill-links { display: none; }
        .pill-burger { display: flex; }
        main { padding-top: 72px; }
    }
    @media (min-width: 769px) {
        .pill-mobile-menu { display: none; }
    }
</style>

<nav class="pill-nav" aria-label="Menu utama">
    <div class="pill-nav-inner">
        <a href="{{ route('home') }}" class="pill-brand">
            <span class="pill-logo" role="img" aria-label="SiCasti"></span>
            <span>{{ config('brand.name') }}</span>
        </a>
        <ul class="pill-links">
            @foreach ($navItems as $item)
                <li><a href="{{ $item['href'] }}" data-section="{{ $item['section'] }}">{{ $item['label'] }}</a></li>
            @endforeach
        </ul>
        <div class="pill-actions">
            <button type="button" class="pill-burger" id="pill-burger" aria-expanded="false" aria-controls="pill-mobile-menu" aria-label="Buka menu">
                <span class="pill-burger-lines" aria-hidden="true"><span></span><span></span><span></span></span>
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

<div class="pill-mobile-menu" id="pill-mobile-menu" aria-hidden="true">
    <ul class="pill-mobile-links">
        @foreach ($navItems as $item)
            <li><a href="{{ $item['href'] }}" data-section="{{ $item['section'] }}">{{ $item['label'] }}</a></li>
        @endforeach
        @guest
            <li><a href="{{ route('login') }}" class="pill-mobile-masuk">Masuk</a></li>
        @endguest
    </ul>
</div>

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

    var burger = document.getElementById('pill-burger');
    var menu = document.getElementById('pill-mobile-menu');
    var userMenu = document.getElementById('navbar-user-menu');

    function closeMenu(restoreFocus) {
        if (!menu.classList.contains('is-open')) return;
        menu.classList.remove('is-open');
        menu.setAttribute('aria-hidden', 'true');
        burger.setAttribute('aria-expanded', 'false');
        burger.setAttribute('aria-label', 'Buka menu');
        if (restoreFocus) burger.focus();
    }
    function openMenu() {
        if (userMenu) userMenu.removeAttribute('open');
        menu.classList.add('is-open');
        menu.setAttribute('aria-hidden', 'false');
        burger.setAttribute('aria-expanded', 'true');
        burger.setAttribute('aria-label', 'Tutup menu');
    }

    if (burger && menu) {
        burger.addEventListener('click', function () {
            if (menu.classList.contains('is-open')) closeMenu(false); else openMenu();
        });
        menu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function (e) {
                var href = link.getAttribute('href');
                closeMenu(false);
                if (href.charAt(0) === '#') {
                    e.preventDefault();
                    var target = document.getElementById(href.slice(1));
                    if (target) target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeMenu(true);
        });
        document.addEventListener('click', function (e) {
            if (menu.classList.contains('is-open') && !menu.contains(e.target) && !burger.contains(e.target)) closeMenu(false);
        });
        if (window.matchMedia) {
            var mq = window.matchMedia('(min-width: 769px)');
            var onMqChange = function (e) { if (e.matches) closeMenu(false); };
            if (mq.addEventListener) mq.addEventListener('change', onMqChange);
            else if (mq.addListener) mq.addListener(onMqChange);
        }
    }

    if (userMenu) {
        userMenu.addEventListener('toggle', function () {
            if (userMenu.open) closeMenu(false);
        });
    }
})();
</script>
