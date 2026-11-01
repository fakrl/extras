<style>
    /* Seed: #15803D (brand JBTB hijau, sumber: UI-GUIDELINES.md) — nilai M3 diturunkan manual sesuai Material Theme Builder algorithm */

    /* ─── M3 tokens — Light ─────────────────────────────────────────────── */
    :root, :root[data-theme="light"] {
        color-scheme: light;

        /* Primary */
        --md-primary: #15803D;
        --md-on-primary: #FFFFFF;
        --md-primary-container: #B7F1CB;
        --md-on-primary-container: #002111;

        /* Secondary */
        --md-secondary: #506353;
        --md-on-secondary: #FFFFFF;
        --md-secondary-container: #D3E8D2;
        --md-on-secondary-container: #0E1F13;

        /* Tertiary */
        --md-tertiary: #3A6659;
        --md-on-tertiary: #FFFFFF;
        --md-tertiary-container: #BCECDD;
        --md-on-tertiary-container: #002019;

        /* Error */
        --md-error: #BA1A1A;
        --md-on-error: #FFFFFF;
        --md-error-container: #FFDAD6;
        --md-on-error-container: #410002;

        /* Surface */
        --md-surface: #F6FBF3;
        --md-surface-container-lowest: #FFFFFF;
        --md-surface-container-low: #F0F5EE;
        --md-surface-container: #EAF0E8;
        --md-surface-container-high: #E4EAE2;
        --md-surface-container-highest: #DEE4DC;
        --md-on-surface: #171D17;
        --md-on-surface-variant: #404942;
        --md-outline: #707971;
        --md-outline-variant: #BFC9C0;

        /* Custom semantic colors */
        --md-custom-success: #15803D;
        --md-custom-success-container: #B7F1CB;
        --md-custom-on-success-container: #002111;
        --md-custom-warning: #D97706;
        --md-custom-warning-container: #FEF3C7;
        --md-custom-on-warning-container: #78350F;
        --md-custom-urgent: #C2410C;
        --md-custom-urgent-container: #FFEDD5;
        --md-custom-on-urgent-container: #7C2D12;
        --md-custom-info: #1D4ED8;
        --md-custom-info-container: #DBEAFE;
        --md-custom-on-info-container: #1E3A8A;

        /* ── Legacy tokens — nilai dipetakan ke M3 (JANGAN hapus nama ini) ── */
        --accent: #15803D;
        --accent-strong: #0B5E2C;
        --accent-on: #FFFFFF;
        --bg-page: #F6FBF3;
        --bg-sidebar: #FFFFFF;
        --bg-card: #F0F5EE;
        --bg-card-hover: #EAF0E8;
        --bg-nav-active: #B7F1CB;
        --text-primary: #171D17;
        --text-secondary: #404942;
        --text-muted: #5B6B5E;
        --border-color: #BFC9C0;
        --danger: #BA1A1A;
        --danger-solid: #BA1A1A;
        --warning: #92400E;
        --urgent: #C2410C;
        --urgent-on: #FFFFFF;
        --info: #1D4ED8;
        --highlight-cream: #f3ebd6;
    }

    /* ─── M3 tokens — Dark ──────────────────────────────────────────────── */
    :root[data-theme="dark"] {
        color-scheme: dark;

        /* Primary */
        --md-primary: #6EDB96;
        --md-on-primary: #003920;
        --md-primary-container: #005230;
        --md-on-primary-container: #8CF6B1;

        /* Secondary */
        --md-secondary: #B7CCBA;
        --md-on-secondary: #223526;
        --md-secondary-container: #384B3C;
        --md-on-secondary-container: #D3E8D2;

        /* Tertiary */
        --md-tertiary: #A0D0C1;
        --md-on-tertiary: #00382C;
        --md-tertiary-container: #1F4E41;
        --md-on-tertiary-container: #BCECDD;

        /* Error */
        --md-error: #FFB4AB;
        --md-on-error: #690005;
        --md-error-container: #93000A;
        --md-on-error-container: #FFDAD6;

        /* Surface */
        --md-surface: #101510;
        --md-surface-container-lowest: #0A0F0A;
        --md-surface-container-low: #171D17;
        --md-surface-container: #1C221C;
        --md-surface-container-high: #272D27;
        --md-surface-container-highest: #323832;
        --md-on-surface: #DEE4DC;
        --md-on-surface-variant: #BFC9C0;
        --md-outline: #8A948C;
        --md-outline-variant: #404942;

        /* Custom semantic colors */
        --md-custom-success: #6EDB96;
        --md-custom-success-container: #005230;
        --md-custom-on-success-container: #8CF6B1;
        --md-custom-warning: #F59E0B;
        --md-custom-warning-container: #78350F;
        --md-custom-on-warning-container: #FEF3C7;
        --md-custom-urgent: #FB923C;
        --md-custom-urgent-container: #7C2D12;
        --md-custom-on-urgent-container: #FFEDD5;
        --md-custom-info: #60A5FA;
        --md-custom-info-container: #1E3A8A;
        --md-custom-on-info-container: #DBEAFE;

        /* ── Legacy tokens — nilai eksisting terverifikasi (JANGAN hapus) ── */
        --accent: #0f9a4c;
        --accent-strong: #22b862;
        --accent-on: #04140a;
        --bg-page: #101510;
        --bg-sidebar: #0A0F0A;
        --bg-card: #171D17;
        --bg-card-hover: #1C221C;
        --bg-nav-active: #005230;
        --text-primary: #DEE4DC;
        --text-secondary: #BFC9C0;
        --text-muted: #8A948C;
        --border-color: #404942;
        --danger: #FFB4AB;
        --danger-solid: #dc2626;
        --warning: #F59E0B;
        --urgent: #FB923C;
        --urgent-on: #1a0f05;
        --info: #60A5FA;
        --highlight-cream: #f3ebd6;
    }

    /* ─── Skala tidak tergantung tema ───────────────────────────────────── */
    :root {
        /* Tipografi — M3 scale (label-sm → display, dipetakan ke token lama) */
        --fs-xs: 12px;   /* label small  ≈ 11-12px */
        --fs-sm: 13px;   /* label medium ≈ 12-13px */
        --fs-base: 14px; /* body medium */
        --fs-md: 16px;   /* body large / title small */
        --fs-lg: 20px;   /* title medium (naik dari 18px → 20px) */
        --fs-xl: 24px;   /* headline small (naik dari 22px → 24px) */

        /* Radius legacy (tetap ada) */
        --radius-sm: 6px;
        --radius-md: 8px;
        --radius-lg: 12px;

        /* Space */
        --space-1: 4px; --space-2: 8px; --space-3: 12px;
        --space-4: 16px; --space-5: 20px; --space-6: 24px;

        /* M3 Shape tokens */
        --md-shape-xs: 4px;
        --md-shape-sm: 8px;
        --md-shape-md: 12px;
        --md-shape-lg: 16px;
        --md-shape-xl: 28px;
        --md-shape-full: 9999px;

        /* M3 Elevation */
        --md-elevation-0: none;
        --md-elevation-1: none;
        --md-elevation-2: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.08);
        --md-elevation-3: 0 4px 8px rgba(0,0,0,0.12), 0 2px 4px rgba(0,0,0,0.08);

        /* M3 State layer opacities */
        --md-state-hover: 0.08;
        --md-state-focus: 0.10;
        --md-state-pressed: 0.10;
    }

    * { box-sizing: border-box; }
    img, video { max-width: 100%; }
    body {
        font-family: 'Inter', sans-serif;
        background: var(--bg-page);
        color: var(--text-primary);
        margin: 0;
        min-height: 100vh;
        overflow-wrap: anywhere;
    }
    table, .btn, .badge { overflow-wrap: normal; }

    /* Password show/hide toggle, dipakai lewat komponen password-input */
    .password-field { position: relative; }
    .password-field input { padding-right: 48px; margin-bottom: 0; }
    .password-toggle {
        position: absolute; right: 4px; top: 50%; transform: translateY(-50%);
        width: 44px; height: 44px; border: none; background: transparent;
        color: var(--text-secondary); cursor: pointer; display: flex;
        align-items: center; justify-content: center; font-size: 17px;
    }
    .password-toggle:hover { color: var(--text-primary); }
    .password-toggle:focus-visible { outline: 2px solid var(--accent); outline-offset: -2px; border-radius: var(--radius-md); }
    .password-field-wrap { margin-bottom: 14px; }

    .wajib { color: var(--danger); font-weight: 600; margin-left: 2px; }
    .wajib-ket { font-size: var(--fs-xs); color: var(--text-muted); margin: 0 0 12px; }

    /* Alert / snackbar M3 */
    .alert-success {
        background: var(--md-primary-container, rgba(183,241,203,0.6));
        color: var(--md-on-primary-container, var(--accent-strong));
        border-left: 3px solid var(--md-primary, var(--accent));
        border-radius: var(--md-shape-sm, var(--radius-md));
        padding: 12px 16px; margin-bottom: 16px; font-size: var(--fs-base);
    }
    .alert-danger {
        background: var(--md-error-container, rgba(239,68,68,0.12));
        color: var(--md-on-error-container, var(--danger));
        border-left: 3px solid var(--md-error, var(--danger));
        border-radius: var(--md-shape-sm, var(--radius-md));
        padding: 12px 16px; margin-bottom: 16px; font-size: var(--fs-base);
    }
    .alert-info {
        background: var(--md-custom-info-container, rgba(59,130,246,0.12));
        color: var(--md-custom-on-info-container, var(--info));
        border-left: 3px solid var(--md-custom-info, var(--info));
        border-radius: var(--md-shape-sm, var(--radius-md));
        padding: 12px 16px; margin-bottom: 16px; font-size: var(--fs-base);
    }

    /* Badge → Chip M3 */
    .badge { display: inline-flex; padding: 4px 12px; border-radius: var(--md-shape-sm); border: 1px solid transparent; font-size: var(--fs-xs); font-weight: 500; }
    .badge-aktif {
        background: var(--md-primary-container, rgba(34,197,94,0.15));
        border-color: color-mix(in srgb, var(--md-primary, #15803D) 35%, transparent);
        color: var(--md-on-primary-container, var(--accent-strong));
    }
    .badge-pending, .badge-warning {
        background: var(--md-custom-warning-container, rgba(234,179,8,0.15));
        border-color: color-mix(in srgb, var(--md-custom-warning, #D97706) 35%, transparent);
        color: var(--md-custom-on-warning-container, var(--warning));
    }
    .badge-tolak {
        background: var(--md-error-container, rgba(239,68,68,0.12));
        border-color: color-mix(in srgb, var(--md-error, #BA1A1A) 35%, transparent);
        color: var(--md-on-error-container, var(--danger));
    }
    .badge-netral { background: rgba(148,163,184,0.18); border-color: rgba(148,163,184,0.4); color: var(--text-secondary); }
    .badge-info {
        background: var(--md-custom-info-container, rgba(29,78,216,0.12));
        border-color: color-mix(in srgb, var(--md-custom-info, #1D4ED8) 35%, transparent);
        color: var(--md-custom-on-info-container, var(--info));
    }
    .badge-urgent { background: var(--urgent); border-color: var(--urgent); color: var(--urgent-on); font-weight: 700; }

    /* CP.2: seksi <details> yang sedang terbuka beda jelas dari yang tertutup, konsisten terang/gelap lewat token accent. */
    .bagian-card[open],
    details.card[open] {
        border-left: 3px solid var(--accent);
        background: color-mix(in srgb, var(--accent) 8%, var(--bg-card));
    }
    .bagian-card[open] > .bagian-head .bagian-title,
    details.card[open] > summary {
        font-weight: 700;
    }

    /* ─── UI/UX Refactoring Enhancements ────────────────────────────────── */
    /* Sidebar badge count */
    .sb-dot.sb-badge-count {
        width: auto; min-width: 18px; height: 18px; border-radius: 9px;
        padding: 0 5px; font-size: 11px; font-weight: 700; line-height: 18px;
        text-align: center; color: #fff; vertical-align: middle;
        display: inline-flex; align-items: center; justify-content: center;
    }

    /* Next-Action Callout (Kotak Pemandu Langkah Selanjutnya) */
    .next-action-callout {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        border-radius: var(--radius-md);
        margin: 10px 0;
        flex-wrap: wrap;
    }
    .next-action-callout.nac-info {
        background: var(--md-surface-container, var(--bg-card));
        border: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    .next-action-callout.nac-primary {
        background: var(--md-primary-container, rgba(21,128,61,0.12));
        border: 1px solid color-mix(in srgb, var(--accent) 35%, transparent);
        color: var(--text-primary);
    }
    .next-action-callout.nac-warning {
        background: var(--md-custom-warning-container, rgba(245,158,11,0.12));
        border: 1px solid color-mix(in srgb, var(--warning) 40%, transparent);
        color: var(--text-primary);
    }
    .next-action-callout.nac-danger {
        background: var(--md-error-container, rgba(239,68,68,0.12));
        border: 1px solid color-mix(in srgb, var(--danger) 40%, transparent);
        color: var(--text-primary);
    }
    .next-action-callout .nac-content {
        flex: 1;
        min-width: 200px;
    }
    .next-action-callout .nac-title {
        font-size: 13.5px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        color: var(--text-primary);
    }
    .next-action-callout .nac-desc {
        font-size: 12.5px;
        color: var(--text-secondary);
        line-height: 1.45;
        margin-top: 3px;
    }
    .next-action-callout .nac-action {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    /* Penyatuan Konteks Proyek (Header Breadcrumb / Konteks) */
    .project-context-header {
        background: var(--md-surface-container, var(--bg-card));
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        padding: 12px 16px;
        margin-bottom: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .project-context-header .pch-main {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .project-context-header .pch-back {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: var(--accent);
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        padding: 4px 8px;
        border-radius: var(--radius-sm);
        background: var(--bg-page);
        border: 1px solid var(--border-color);
    }
    .project-context-header .pch-back:hover {
        background: var(--md-primary-container, var(--bg-card-hover));
    }
    .project-context-header .pch-title {
        font-size: 14.5px;
        font-weight: 700;
        color: var(--text-primary);
    }
    .project-context-header .pch-meta {
        font-size: 12px;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Quick Filter Chips */
    .quick-filter-bar {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 6px;
        margin: 10px 0 14px;
        scrollbar-width: thin;
    }
    .quick-filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12.5px;
        font-weight: 500;
        text-decoration: none;
        white-space: nowrap;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        color: var(--text-secondary);
        transition: all .15s ease;
    }
    .quick-filter-chip:hover {
        background: var(--bg-card-hover);
        color: var(--text-primary);
    }
    .quick-filter-chip.is-active,
    .quick-filter-chip.active {
        background: var(--accent);
        color: #fff;
        border-color: var(--accent);
        font-weight: 600;
    }

    /* Sticky Mobile CTA Bar */
    .sticky-mobile-cta {
        display: none;
    }
    @media (max-width: 640px) {
        .sticky-mobile-cta {
            display: flex;
            position: sticky;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: var(--bg-card);
            border-top: 1px solid var(--border-color);
            padding: 10px 14px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.08);
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
    }

    /* ─── MACRO LAYOUT OVERHAUL V2 TOKENS ────────────────────────────────── */

    /* 1. Asymmetric Command Grid (Super Admin Dashboard) */
    .sa-command-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        align-items: start;
        margin-top: 16px;
    }
    @media (min-width: 992px) {
        .sa-command-grid {
            grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
        }
    }
    .sa-action-ticket {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color);
        transition: background .15s ease;
    }
    .sa-action-ticket:last-child {
        border-bottom: none;
    }
    .sa-action-ticket:hover {
        background: var(--bg-card-hover);
    }
    .sa-sticky-panel {
        position: static;
    }
    @media (min-width: 992px) {
        .sa-sticky-panel {
            position: sticky;
            top: 84px;
        }
    }

    /* 2. Visual Pipeline Stage Strip (Admin Lineup) */
    .lineup-stage-strip {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 10px;
        margin: 10px 0 16px;
    }
    .lineup-stage-box {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 10px 12px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        text-decoration: none;
        color: var(--text-primary);
        min-height: 68px;
        transition: all .15s ease;
    }
    .lineup-stage-box:hover {
        border-color: var(--accent);
        transform: translateY(-1px);
    }
    .lineup-stage-box.is-active {
        border-color: var(--accent-strong);
        background: var(--bg-nav-active);
        box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 30%, transparent);
    }
    .lineup-stage-box .stage-count {
        font-size: 20px;
        font-weight: 700;
        line-height: 1.1;
    }
    .lineup-stage-box .stage-label {
        font-size: 11.5px;
        color: var(--text-muted);
        margin-top: 4px;
        font-weight: 500;
    }
    .lineup-stage-box.is-active .stage-label {
        color: var(--text-primary);
        font-weight: 600;
    }

    /* 3. Extras Card V2 (Modern Specs Grid & Top Bar) */
    .ecard-v2 {
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .ecard-top-bar {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        z-index: 2;
    }
    .ecard-top-bar.status-diajukan,
    .ecard-top-bar.status-direview_admin,
    .ecard-top-bar.status-nego_fee {
        background: var(--warning, #D97706);
    }
    .ecard-top-bar.status-deal,
    .ecard-top-bar.status-diajukan_ke_client,
    .ecard-top-bar.status-lolos,
    .ecard-top-bar.status-kontrak_ditandatangani,
    .ecard-top-bar.status-selesai_produksi {
        background: var(--accent, #15803D);
    }
    .ecard-top-bar.status-ditolak,
    .ecard-top-bar.status-dibatalkan {
        background: var(--danger, #DC2626);
    }
    .ecard-grade-pill {
        position: absolute;
        top: 8px;
        right: 8px;
        background: var(--accent-strong, #0B5E2C);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 12px;
        z-index: 3;
        box-shadow: 0 2px 6px rgba(0,0,0,0.25);
    }
    .ecard-specs-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
        margin: 8px 0;
        background: var(--md-surface-container, var(--bg-card-hover));
        border-radius: 6px;
        padding: 6px 4px;
        text-align: center;
        border: 1px solid var(--border-color);
    }
    .ecard-spec-item .spec-lbl {
        display: block;
        font-size: 9.5px;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: .03em;
    }
    .ecard-spec-item .spec-val {
        display: block;
        font-size: 12.5px;
        font-weight: 700;
        color: var(--text-primary);
        margin-top: 1px;
    }

    /* 4. Slide-Over Right Drawer Modal */
    dialog.xmodal.xdrawer-right {
        margin: 0 0 0 auto !important;
        height: 100dvh !important;
        max-height: 100dvh !important;
        width: min(680px, 96vw) !important;
        border-radius: 16px 0 0 16px !important;
        display: flex;
        flex-direction: column;
        padding: 0 !important;
        border: none !important;
        border-left: 1px solid var(--border-color) !important;
        box-shadow: -8px 0 32px rgba(0,0,0,0.2) !important;
        animation: drawerSlideIn .25s ease-out;
    }
    @keyframes drawerSlideIn {
        from { transform: translateX(100%); }
        to { transform: translateX(0); }
    }
    dialog.xmodal.xdrawer-right::backdrop {
        background: rgba(0,0,0,0.45);
        backdrop-filter: blur(2px);
    }
    .drawer-head-sticky {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-color);
        background: var(--bg-card);
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .drawer-body-scroll {
        padding: 16px 20px 30px;
        overflow-y: auto;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .decision-console-box {
        background: var(--md-surface-container, var(--bg-card));
        border: 2px solid var(--accent, #15803D);
        border-radius: var(--radius-md);
        padding: 14px 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    /* 5. Boarding-Pass Card (Extras Dashboard) */
    .boarding-pass-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        overflow: hidden;
        box-shadow: var(--md-elevation-2);
        margin-bottom: 16px;
        transition: border-color .15s ease;
    }
    .boarding-pass-card:hover {
        border-color: var(--accent);
    }
    .bp-header {
        background: #0B1A12;
        color: #B7F1CB;
        padding: 10px 16px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .bp-header.is-urgent {
        background: #78350F;
        color: #FEF3C7;
    }
    .bp-body {
        padding: 16px;
    }
    .bp-ticket-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 12px;
        margin-top: 10px;
        background: var(--md-surface-container, var(--bg-card-hover));
        padding: 12px;
        border-radius: var(--radius-md);
        border: 1px solid var(--border-color);
    }
    .bp-ticket-cell .bp-lbl {
        font-size: 10.5px;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-muted);
        display: block;
    }
    .bp-ticket-cell .bp-val {
        font-size: 13.5px;
        font-weight: 700;
        color: var(--text-primary);
        margin-top: 2px;
        display: block;
    }
    .bp-perforated {
        border-top: 2px dashed var(--border-color);
        margin: 14px 0;
        position: relative;
    }
    .bp-footer {
        padding: 0 16px 16px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* 6. Job Board Magazine Grid (Katalog Lowongan) */
    .job-board-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
    }
    @media (min-width: 768px) {
        .job-board-grid {
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        }
    }
    .jb-meta-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        background: var(--md-surface-container, var(--bg-card-hover));
        padding: 10px 12px;
        border-radius: var(--radius-md);
        font-size: 12.5px;
        border: 1px solid var(--border-color);
        margin: 10px 0;
    }

    /* 7. Split-Screen Project Apply View */
    .project-apply-split {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        align-items: start;
    }
    @media (min-width: 860px) {
        .project-apply-split {
            grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.95fr);
        }
        .pas-sticky-card {
            position: sticky;
            top: 84px;
        }
    }

    /* 8. Project Quota & Pipeline Summary Strip */
    .project-quota-strip {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 14px 18px;
        margin-bottom: 16px;
        box-shadow: var(--md-elevation-1);
    }
    .pqs-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
        gap: 12px;
        margin-bottom: 10px;
    }
    .pqs-progress {
        height: 8px;
        border-radius: 999px;
        background: var(--border-color);
        overflow: hidden;
        display: flex;
    }

    /* 9. Client Quota & Review Lookbook */
    .client-quota-banner {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        padding: 12px 16px;
        margin-bottom: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .talent-card-approved {
        border: 2px solid var(--accent, #15803D) !important;
        position: relative;
    }
    .talent-card-approved::after {
        content: "✓ Terpilih";
        position: absolute;
        top: 8px;
        right: 8px;
        background: var(--accent, #15803D);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 12px;
        z-index: 5;
    }

    /* 10. Mini 4-Step Pipeline Tracker (Client Dashboard) */
    .mini-pipeline-tracker {
        display: flex;
        gap: 4px;
        margin: 8px 0;
        align-items: center;
    }
    .mini-pipeline-step {
        flex: 1;
        height: 5px;
        border-radius: 999px;
        background: var(--border-color);
    }
    .mini-pipeline-step.is-done {
        background: var(--accent, #15803D);
    }
    .mini-pipeline-step.is-current {
        background: var(--warning, #D97706);
    }

    /* 11. Structured Action Row 5-Kolom (Admin Lineup Mode Daftar) */
    .is-daftar.xgrid {
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
    }
    .is-daftar .xcard {
        display: grid !important;
        grid-template-columns: minmax(190px, 1.2fr) minmax(140px, 1fr) minmax(160px, 1.1fr) minmax(130px, 0.9fr) auto !important;
        gap: 12px !important;
        align-items: center !important;
        padding: 10px 16px !important;
        border-radius: var(--radius-md) !important;
        background: var(--bg-card) !important;
        border: 1px solid var(--border-color) !important;
    }
    @media (max-width: 960px) {
        .is-daftar .xcard {
            grid-template-columns: 1fr !important;
            gap: 8px !important;
        }
    }
</style>
