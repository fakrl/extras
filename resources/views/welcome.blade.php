<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIM Casting JBTB</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root[data-theme="dark"] {
            --bg-page: #0d1b12;
            --bg-card: #132a1b;
            --text-primary: #f0fdf4;
            --text-secondary: #a8c2ae;
            --border-color: rgba(255,255,255,0.06);
            --accent: #22c55e;
            --accent-on: #052e16;
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
        .landing-card {
            width: 100%;
            max-width: 460px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 40px 32px;
            text-align: center;
        }
        .logo {
            width: 48px; height: 48px; border-radius: 12px;
            background: var(--accent); color: var(--accent-on);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 22px;
            margin: 0 auto 20px;
        }
        h1 { font-size: 20px; font-weight: 600; margin: 0 0 8px; }
        p { font-size: 14px; color: var(--text-secondary); line-height: 1.6; margin: 0 0 28px; }
        .btn-brand {
            display: inline-flex; align-items: center; justify-content: center;
            width: 100%; min-height: 46px;
            background: var(--accent); color: var(--accent-on);
            border: none; border-radius: 10px; text-decoration: none;
            font-size: 14px; font-weight: 600; cursor: pointer;
        }
        .btn-brand:hover { filter: brightness(1.08); }
    </style>
</head>
<body>
    <div class="landing-card">
        <div class="logo">J</div>
        <h1>SIM Casting JBTB</h1>
        <p>Sistem informasi manajemen casting untuk JBTB Casting Creative Group — kelola proyek casting, seleksi talent, negosiasi fee, hingga kontrak dan pembayaran dalam satu tempat.</p>
        <a href="{{ route('login') }}" class="btn-brand">Masuk</a>
    </div>
</body>
</html>
