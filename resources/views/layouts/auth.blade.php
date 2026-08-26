<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIM Casting JBTB')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <style>
        :root[data-theme="dark"] {
            --bg-page: #0d1b12;
            --bg-card: #132a1b;
            --bg-card-hover: #16321f;
            --text-primary: #f0fdf4;
            --text-secondary: #a8c2ae;
            --text-muted: #5a7a63;
            --border-color: rgba(255,255,255,0.06);
            --accent: #22c55e;
            --accent-on: #052e16;
            --danger: #ef4444;
        }
        :root[data-theme="light"] {
            --bg-page: #f4f7f5;
            --bg-card: #ffffff;
            --bg-card-hover: #eaf5ee;
            --text-primary: #0d1b12;
            --text-secondary: #4b5f52;
            --text-muted: #8a9a90;
            --border-color: rgba(0,0,0,0.08);
            --accent: #15803d;
            --accent-on: #ffffff;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-page);
            color: var(--text-primary);
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        a { color: var(--accent); }
        .auth-card {
            width: 100%;
            max-width: 420px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 32px;
        }
        .auth-brand {
            display: flex; align-items: center; gap: 10px; margin-bottom: 24px;
        }
        .auth-brand .logo {
            width: 32px; height: 32px; border-radius: 8px;
            background: var(--accent); color: var(--accent-on);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 16px;
        }
        .auth-brand span { font-weight: 600; font-size: 15px; }
        h1.auth-title { font-size: 19px; font-weight: 600; margin: 0 0 4px; }
        p.auth-subtitle { font-size: 13px; color: var(--text-secondary); margin: 0 0 20px; }
        label { font-size: 13px; color: var(--text-secondary); display: block; margin-bottom: 4px; }
        input {
            width: 100%;
            background: var(--bg-page); color: var(--text-primary);
            border: 1px solid var(--border-color); border-radius: 8px;
            padding: 10px 12px; font-size: 14px; min-height: 44px;
            font-family: inherit; margin-bottom: 14px;
        }
        input:focus { outline: none; border-color: var(--accent); }
        .btn-brand {
            width: 100%; min-height: 46px;
            background: var(--accent); color: var(--accent-on);
            border: none; border-radius: 10px;
            font-size: 14px; font-weight: 600; cursor: pointer;
        }
        .btn-brand:hover { filter: brightness(1.08); }
        .auth-footer { text-align: center; font-size: 13px; color: var(--text-secondary); margin-top: 18px; }
        .alert-danger {
            background: rgba(239,68,68,0.12); color: var(--danger);
            padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13.5px;
        }
        .alert-success {
            background: rgba(34,197,94,0.12); color: var(--accent);
            padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13.5px;
        }
        .checkbox-row {
            display: flex; align-items: flex-start; gap: 8px; margin-bottom: 16px;
        }
        .checkbox-row input[type="checkbox"] { width: auto; min-height: auto; margin: 3px 0 0; }
        .checkbox-row label { margin-bottom: 0; font-size: 12.5px; line-height: 1.5; }
        hr { border: none; border-top: 1px solid var(--border-color); margin: 20px 0; }

        /* Input password dengan tombol show/hide (ikon mata) */
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
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-brand">
            <div class="logo">J</div>
            <span>SIM Casting JBTB</span>
        </div>
        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
