<style>
    :root[data-theme="dark"] {
        --bg-page: #0f1512;
        --bg-sidebar: #0a0f0c;
        --bg-card: #161d19;
        --bg-card-hover: #1d2620;
        --bg-nav-active: #1a2921;
        --text-primary: #eef2ef;
        --text-secondary: #9caaa1;
        --text-muted: #62726a;
        --border-color: rgba(255,255,255,0.07);
        --accent: #22c55e;
        --accent-strong: #4ade80;
        --accent-on: #052e16;
        --danger: #f0565c;
        --warning: #f0b90b;
    }
    :root[data-theme="light"] {
        --bg-page: #f4f7f5;
        --bg-sidebar: #ffffff;
        --bg-card: #ffffff;
        --bg-card-hover: #eaf5ee;
        --bg-nav-active: #e4f5e9;
        --text-primary: #0d1b12;
        --text-secondary: #4b5f52;
        --text-muted: #8a9a90;
        --border-color: rgba(0,0,0,0.08);
        --accent: #15803d;
        --accent-strong: #15803d;
        --accent-on: #ffffff;
        --danger: #dc2626;
        --warning: #b45309;
    }

    * { box-sizing: border-box; }
    body {
        font-family: 'Inter', sans-serif;
        background: var(--bg-page);
        color: var(--text-primary);
        margin: 0;
        min-height: 100vh;
    }

    /* Password show/hide toggle — dipakai lewat komponen password-input, sama di layouts/app.blade.php & layouts/auth.blade.php */
    .password-field { position: relative; }
    .password-field input { padding-right: 44px; margin-bottom: 0; }
    .password-toggle {
        position: absolute; right: 4px; top: 50%; transform: translateY(-50%);
        width: 36px; height: 36px; border: none; background: transparent;
        color: var(--text-secondary); cursor: pointer; display: flex;
        align-items: center; justify-content: center; font-size: 17px;
    }
    .password-toggle:hover { color: var(--text-primary); }
    .password-field-wrap { margin-bottom: 14px; }

    .alert-success {
        background: rgba(34,197,94,0.12); color: var(--accent-strong);
        padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 14px;
    }
    .alert-danger {
        background: rgba(239,68,68,0.12); color: var(--danger);
        padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 14px;
    }
</style>
