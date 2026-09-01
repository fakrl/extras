<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title>@yield('title', 'SIM Casting JBTB')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    @include('partials.theme-style')
    <style>
        body {
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
        .checkbox-row {
            display: flex; align-items: flex-start; gap: 8px; margin-bottom: 16px;
        }
        .checkbox-row input[type="checkbox"] { width: auto; min-height: auto; margin: 3px 0 0; }
        .checkbox-row label { margin-bottom: 0; font-size: 12.5px; line-height: 1.5; }
        hr { border: none; border-top: 1px solid var(--border-color); margin: 20px 0; }
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
