{{-- Footer Sorot bersama landing (welcome.blade.php) & tentang.blade.php. Param: $page = 'home'|'tentang'. --}}
@php
    $isHome = ($page ?? 'home') === 'home';
    $hrefLowongan = $isHome ? '#lowongan' : route('home').'#lowongan';
    $hrefCaraKerja = $isHome ? '#cara-kerja' : route('home').'#cara-kerja';
    $hrefFaq = $isHome ? '#faq' : route('home').'#faq';
    $kontakWa = config('app.kontak.wa');
    $kontakEmail = config('app.kontak.email');
@endphp
<style>
    .lp-footer { border-top: 1px solid var(--hp-line); padding: 56px 5% 96px; background: var(--bg, var(--hp-bg)); }
    .lp-footer-cols { max-width: 1180px; margin: 0 auto; display: grid; grid-template-columns: 1.4fr 1fr 1fr 1fr; gap: 32px; }
    .lp-footer-brand { display: flex; align-items: center; gap: 9px; font-size: 14px; font-weight: 700; color: var(--hp-fg); text-decoration: none; }
    .lp-footer-brand .symbol-mark {
        width: 26px; height: 26px; display: block; flex-shrink: 0;
        -webkit-mask-image: url('{{ asset('images/brand/symbol.svg') }}');
        mask-image: url('{{ asset('images/brand/symbol.svg') }}');
        -webkit-mask-size: contain; mask-size: contain;
        -webkit-mask-repeat: no-repeat; mask-repeat: no-repeat;
        -webkit-mask-position: center; mask-position: center;
        background-color: currentColor;
    }
    .lp-footer-desc { margin: 12px 0 0; font-size: 13px; line-height: 1.6; color: var(--hp-muted); max-width: 32em; }
    .lp-footer-label { font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--hp-muted); margin: 0 0 14px; }
    .lp-footer-links { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
    .lp-footer-links a { display: inline-flex; align-items: center; min-height: 28px; font-size: 13.5px; color: var(--hp-fg); text-decoration: none; }
    .lp-footer-links a:hover { color: var(--hp-accent); }
    .lp-footer-bottom {
        max-width: 1180px; margin: 40px auto 0; padding-top: 20px; border-top: 1px solid var(--hp-line);
        display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;
        font-size: 12.5px; color: var(--hp-muted);
    }
    .lp-footer-bottom-legal { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
    .lp-footer-bottom a { color: var(--hp-muted); text-decoration: none; display: inline-flex; align-items: center; min-height: 28px; }
    .lp-footer-bottom a:hover { color: var(--hp-fg); }
    @media (max-width: 768px) {
        .lp-footer-cols { grid-template-columns: 1fr; gap: 28px; }
        .lp-footer { padding-bottom: calc(88px + env(safe-area-inset-bottom)); }
    }
</style>
<footer class="lp-footer">
    <div class="lp-footer-cols">
        <div>
            <a href="{{ route('home') }}" class="lp-footer-brand">
                <span class="symbol-mark" role="img" aria-label="SiCasti"></span>
                {{ config('brand.name') }}
            </a>
            <p class="lp-footer-desc">{{ config('brand.tagline') }}</p>
        </div>
        <div>
            <p class="lp-footer-label">Navigasi</p>
            <ul class="lp-footer-links">
                <li><a href="{{ $hrefLowongan }}">Lowongan</a></li>
                <li><a href="{{ $hrefCaraKerja }}">Cara Kerja</a></li>
                <li><a href="{{ $hrefFaq }}">FAQ</a></li>
            </ul>
        </div>
        <div>
            <p class="lp-footer-label">Info</p>
            <ul class="lp-footer-links">
                <li><a href="{{ route('tentang') }}">Tentang PT JBTB</a></li>
                <li><a href="{{ route('privacy-policy') }}">Privasi</a></li>
                <li><a href="{{ route('syarat-ketentuan') }}">Syarat dan ketentuan</a></li>
            </ul>
        </div>
        @if ($kontakWa || $kontakEmail)
        <div>
            <p class="lp-footer-label">Kontak</p>
            <ul class="lp-footer-links">
                @if ($kontakWa)
                    <li><a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $kontakWa)) }}" target="_blank" rel="noopener">{{ $kontakWa }}</a></li>
                @endif
                @if ($kontakEmail)
                    <li><a href="mailto:{{ $kontakEmail }}">{{ $kontakEmail }}</a></li>
                @endif
            </ul>
        </div>
        @endif
    </div>
    <div class="lp-footer-bottom">
        <span>&copy; {{ date('Y') }} {{ config('brand.name') }}</span>
        <div class="lp-footer-bottom-legal">
            <span>PT. JBTB Casting Creative Group &middot; NIB terdaftar OSS</span>
            <a href="{{ route('tentang') }}">Tentang PT JBTB</a>
            <a href="#atas">Kembali ke atas</a>
        </div>
    </div>
</footer>
