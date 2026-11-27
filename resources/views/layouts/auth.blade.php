<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="dark light">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0F6B73" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#131110" media="(prefers-color-scheme: dark)">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon-180.png') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">
    <link rel="icon" sizes="32x32" href="{{ asset('images/brand/favicon-32.png') }}" type="image/png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>@yield('title', config('brand.name'))</title>
    <link rel="icon" href="{{ asset('images/brand/favicon.png') }}" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@@tabler/icons-webfont@3.48.0/dist/tabler-icons.min.css">
    <script>try { document.documentElement.setAttribute('data-theme', localStorage.getItem('jbtb-theme-v2') || 'light'); } catch (e) {}</script>
    @include('partials.theme-style')
    @include('partials.brand-tokens')
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        a { color: var(--accent-strong); }
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
            width: 32px; height: 32px; flex-shrink: 0; display: block;
            -webkit-mask-image: url('{{ asset(config('brand.logo')) }}');
            mask-image: url('{{ asset(config('brand.logo')) }}');
            -webkit-mask-size: contain; mask-size: contain;
            -webkit-mask-repeat: no-repeat; mask-repeat: no-repeat;
            -webkit-mask-position: center; mask-position: center;
            background-color: currentColor;
        }
        .auth-brand span { font-weight: 600; font-size: 15px; }
        h1.auth-title { font-size: 19px; font-weight: 600; margin: 0 0 4px; }
        p.auth-subtitle { font-size: 13px; color: var(--text-secondary); margin: 0 0 20px; }
        label { font-size: 13px; color: var(--text-secondary); display: block; margin-bottom: 4px; }
        input {
            width: 100%;
            background: var(--bg-page); color: var(--text-primary);
            border: 1px solid var(--border-color); border-radius: 8px;
            padding: 10px 12px; font-size: var(--fs-md); min-height: 44px;
            font-family: inherit; margin-bottom: 14px;
        }
        input:focus { border-color: var(--accent); }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
        .field-hint { font-size: var(--fs-xs); color: var(--text-muted); margin: -10px 0 14px; }
        .field-error { color: var(--danger); font-size: 12px; margin: -10px 0 14px; display: block; }
        .input-error { border-color: var(--danger) !important; }
        .btn-brand {
            width: 100%; min-height: 46px;
            background: var(--accent); color: var(--accent-on);
            border: none; border-radius: 10px;
            font-size: 14px; font-weight: 600; cursor: pointer;
            display: block; text-align: center; text-decoration: none; line-height: 46px; padding: 0;
        }
        a.btn-brand { color: var(--accent-on); }
        .btn-brand:hover { filter: brightness(1.08); }
        .btn-google {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; min-height: 44px; box-sizing: border-box;
            background: var(--bg-page); color: var(--text-primary);
            border: 1px solid var(--border-color); border-radius: 10px;
            font-size: 14px; font-weight: 600; text-decoration: none;
        }
        .btn-google:hover { border-color: var(--accent); }
        .auth-atau { display: flex; align-items: center; gap: 10px; margin: 16px 0; color: var(--text-muted); font-size: var(--fs-xs); }
        .auth-atau::before, .auth-atau::after { content: ''; flex: 1; border-top: 1px solid var(--border-color); }
        .auth-footer { text-align: center; font-size: 13px; color: var(--text-secondary); margin-top: 18px; }
        .checkbox-row { margin-bottom: 16px; }
        .checkbox-row label { display: flex; align-items: flex-start; gap: 10px; min-height: 44px; margin: 0; padding: 4px 0; font-size: 13px; line-height: 1.5; cursor: pointer; }
        input[type="checkbox"] { width: 22px; height: 22px; min-height: 0; flex-shrink: 0; margin: 0; accent-color: var(--accent); }
        .auth-opsi { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px 12px; margin: -8px 0 16px; font-size: 13px; }
        .auth-opsi label { display: flex; align-items: center; gap: 10px; min-height: 44px; margin: 0; font-weight: 400; cursor: pointer; }
        .auth-opsi a { display: inline-flex; align-items: center; min-height: 44px; }
        hr { border: none; border-top: 1px solid var(--border-color); margin: 20px 0; }
    </style>
</head>
<body>
    @include('partials.staging-banner')
    <main class="auth-card">
        <a href="{{ route('home') }}" class="auth-brand" style="text-decoration: none; color: inherit;" aria-label="{{ config('brand.name') }}, ke beranda">
            <span class="logo" role="img" aria-label="Logo {{ config('brand.name') }}"></span>
            <span>{{ config('brand.name') }}</span>
        </a>
        @if (session('status'))
            <div class="alert-success flash-banner" role="status" aria-live="polite">
                <i class="ti ti-circle-check" aria-hidden="true"></i>
                <span class="flash-msg">{{ session('status') }}</span>
                <button type="button" class="flash-close" aria-label="Tutup" data-flash-close>&times;</button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert-danger flash-banner" role="alert">
                <i class="ti ti-alert-circle" aria-hidden="true"></i>
                <span class="flash-msg">{{ session('error') }}</span>
                <button type="button" class="flash-close" aria-label="Tutup" data-flash-close>&times;</button>
            </div>
        @endif
        @yield('content')
    </main>
    @stack('scripts')
    <script>
        if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost')) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>
