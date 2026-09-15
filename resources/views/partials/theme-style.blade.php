<style>
    :root[data-theme="dark"] {
        color-scheme: dark;
        --bg-page: #17181a;
        --bg-sidebar: #101112;
        --bg-card: #1c1e1e;
        --bg-card-hover: #242625;
        --bg-nav-active: #17251d;
        --text-primary: #eef4ef;
        --text-secondary: #9db3a2;
        --text-muted: #62726a;
        --border-color: rgba(255,255,255,0.08);
        --accent: #0f9a4c;
        --accent-strong: #22b862;
        --accent-on: #04140a;
        --danger: #f0565c;
        --warning: #f59e0b;
    }
    :root[data-theme="light"] {
        color-scheme: light;
        --bg-page: #eef2ea;
        --bg-sidebar: #ffffff;
        --bg-card: #ffffff;
        --bg-card-hover: #e2f0e6;
        --bg-nav-active: #d8efe0;
        --text-primary: #0c1a10;
        --text-secondary: #435449;
        --text-muted: #7c8c81;
        --border-color: rgba(0,0,0,0.09);
        --accent: #15803d;
        --accent-strong: #0b5e2c;
        --accent-on: #ffffff;
        --danger: #dc2626;
        --warning: #d97706;
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
