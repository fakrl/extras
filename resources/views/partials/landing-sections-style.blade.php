{{-- Rhythm, section headers, cards, dividers, dark band, buttons, footer — dipakai welcome.blade.php & tentang.blade.php. --}}
<style>
    html { scroll-padding-top: 84px; }
    @media (max-width: 768px) { html { scroll-padding-top: 72px; } }

    .wrap { max-width: 1080px; margin-inline: auto; padding-inline: 5%; }

    /* ── Uniform section header (.sh): hp-sec-head (Cast/Portofolio) sudah tanpa margin-bottom
       di welcome.blade.php sendiri; lp-lowongan-header dari theme-style.blade.php (tak bisa diedit) ── */
    .hp-sec-head, .lp-lowongan-header { margin-bottom: 26px; }
    .hp-sec-title, .services-left-title, .section-title, .contact-left-title {
        font-size: clamp(1.8rem, 5vw, 2.6rem);
        line-height: 1.1;
    }
    /* ── Hero (welcome.blade.php): judul besar ala editorial, lead lebih lega ── */
    .lp-hero-title { font-size: clamp(3rem, 10vw, 6.4rem); line-height: .95; }
    .lp-hero-desc { max-width: 30em; }
    .lp-hero-collage {
        border-radius: 28px; border: 1.5px solid var(--hp-line);
        box-shadow: 8px 8px 0 var(--hp-accent); padding: 10px; background: var(--hp-card);
    }
    @media (max-width: 767px) {
        .lp-hero-collage { border-radius: 20px; box-shadow: 5px 5px 0 var(--hp-accent); padding: 8px; }
    }

    /* ── Pil tombol (CTA, nav "Masuk") ── */
    .btn-brand, .btn-outline { border-radius: 999px; padding: 0 22px; }
    .btn-outline { border-width: 1.5px; }
    @media (prefers-reduced-motion: no-preference) {
        .btn-brand, .btn-outline { transition: transform .2s, filter .2s, background .2s; }
        .btn-brand:hover, .btn-outline:hover { transform: translateY(-2px); }
    }

    /* ── Kartu seragam: lowongan (.lp-job-card, theme-style.blade.php) + cara-kerja
       (.lp-chapter-col, dalam band gelap) ── */
    .lp-job-card { border-width: 1.5px; border-radius: 22px; }
    @media (hover: hover) and (prefers-reduced-motion: no-preference) {
        .lp-job-card { transition: transform .3s, box-shadow .3s, border-color .3s; }
        .lp-job-card:hover { transform: translate(-3px, -3px); box-shadow: 6px 6px 0 var(--hp-accent); }
    }

    /* ── Band gelap tetap (Cara Kerja): kontras AA fixed, lepas dari tema situs ── */
    .hp-band-dark {
        background: #0b1a12; color: #f4f3ee; border-top-color: rgba(244, 243, 238, .18);
    }
    .hp-band-dark .services-left-title,
    .hp-band-dark .lp-chapter-title { color: #f4f3ee; }
    .hp-band-dark .section-body,
    .hp-band-dark .lp-chapter-desc { color: rgba(244, 243, 238, .72); }
    .hp-band-dark .lp-chapter-col {
        border: 1.5px solid rgba(244, 243, 238, .18); border-radius: 22px;
        background: rgba(244, 243, 238, .04); padding: 22px 20px;
    }
    @media (hover: hover) and (prefers-reduced-motion: no-preference) {
        .hp-band-dark .lp-chapter-col { transition: transform .3s, box-shadow .3s; }
        .hp-band-dark .lp-chapter-col:hover { transform: translate(-3px, -3px); box-shadow: 6px 6px 0 #b7ff3c; }
    }

    /* ── Footer: pusatkan tiap kolom ── */
    .footer-inner > * { justify-self: center; }
    .footer-inner + div { text-align: center; }
</style>
