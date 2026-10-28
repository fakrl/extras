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
</style>
