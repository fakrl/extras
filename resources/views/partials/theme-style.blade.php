<style>
    /* Token default = terang (fallback bila data-theme belum terpasang) */
    :root, :root[data-theme="light"] {
        color-scheme: light;
        --bg-page: #eef2ea;
        --bg-sidebar: #ffffff;
        --bg-card: #ffffff;
        --bg-card-hover: #e2f0e6;
        --bg-nav-active: #d8efe0;
        --text-primary: #0c1a10;
        --text-secondary: #435449;
        --text-muted: #5b6b60;
        --border-color: rgba(0,0,0,0.09);
        --accent: #15803d;
        --accent-strong: #0b5e2c;
        --accent-on: #ffffff;
        --danger: #b91c1c;
        --danger-solid: #b91c1c;
        --warning: #92400e;
        --urgent: #c2410c;
        --urgent-on: #ffffff;
        --info: #1d4ed8;
        --highlight-cream: #f3ebd6;
    }
    :root[data-theme="dark"] {
        color-scheme: dark;
        --bg-page: #17181a;
        --bg-sidebar: #101112;
        --bg-card: #1c1e1e;
        --bg-card-hover: #242625;
        --bg-nav-active: #17251d;
        --text-primary: #eef4ef;
        --text-secondary: #9db3a2;
        --text-muted: #8a9a90;
        --border-color: rgba(255,255,255,0.08);
        --accent: #0f9a4c;
        --accent-strong: #22b862;
        --accent-on: #04140a;
        --danger: #fa7a7f;
        --danger-solid: #dc2626;
        --warning: #f59e0b;
        --urgent: #fb923c;
        --urgent-on: #1a0f05;
        --info: #60a5fa;
        --highlight-cream: #f3ebd6;
    }
    :root {
        --fs-xs: 12px; --fs-sm: 13px; --fs-base: 14px; --fs-md: 16px; --fs-lg: 18px; --fs-xl: 22px;
        --radius-sm: 6px; --radius-md: 8px; --radius-lg: 12px;
        --space-1: 4px; --space-2: 8px; --space-3: 12px; --space-4: 16px; --space-5: 20px; --space-6: 24px;
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

    .alert-success {
        background: rgba(34,197,94,0.12); color: var(--accent-strong);
        padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 16px; font-size: var(--fs-base);
    }
    .alert-danger {
        background: rgba(239,68,68,0.12); color: var(--danger);
        padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 16px; font-size: var(--fs-base);
    }

    .badge { display: inline-flex; padding: 3px 10px; border-radius: var(--radius-sm); border: 1px solid transparent; font-size: var(--fs-xs); font-weight: 500; }
    .badge-aktif { background: rgba(34,197,94,0.15); border-color: rgba(34,197,94,0.35); color: var(--accent-strong); }
    .badge-pending, .badge-warning { background: rgba(234,179,8,0.15); border-color: rgba(234,179,8,0.35); color: var(--warning); }
    .badge-tolak { background: rgba(239,68,68,0.12); border-color: rgba(239,68,68,0.35); color: var(--danger); }
    .badge-netral { background: rgba(148,163,184,0.18); border-color: rgba(148,163,184,0.4); color: var(--text-secondary); }
    .badge-info { background: rgba(29,78,216,0.12); border-color: rgba(29,78,216,0.35); color: var(--info); }
    .badge-urgent { background: var(--urgent); border-color: var(--urgent); color: var(--urgent-on); font-weight: 700; }
</style>
