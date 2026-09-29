<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('jbtb-theme-v2') || 'light');</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title>Profil {{ $profile->user->username ?? 'Extras' }} | SIM Casting JBTB</title>
    {{-- tanpa tag Look/etnis di description (D22) --}}
    @include('partials.og-meta', [
        'ogTitle' => '@'.($profile->user->username ?? 'extras').' di JBTB',
        'ogDesc' => 'Profil talent @'.($profile->user->username ?? 'extras').' di JBTB Casting.',
        'ogImage' => $profile->foto_profil_path ? route('public.extras.foto', $token) : null,
    ])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@@tabler/icons-webfont@3.48.0/dist/tabler-icons.min.css">
    @include('partials.theme-style')
    <style>
        :root[data-theme="dark"] { --hp-bg: #111311; --hp-fg: #f2f1eb; --hp-muted: rgba(242, 241, 235, 0.55); --hp-accent: #b7ff3c; --hp-accent-on: #111311; --hp-line: rgba(255, 255, 255, 0.13); --hp-card: #1a1c1a; }
        :root[data-theme="light"] { --hp-bg: #f1f0e9; --hp-fg: #171a16; --hp-muted: rgba(23, 26, 22, 0.55); --hp-accent: #76a51b; --hp-accent-on: #ffffff; --hp-line: rgba(23, 26, 22, 0.16); --hp-card: #e6e4da; }
        body { background: var(--hp-bg); color: var(--hp-fg); }
        .pub-top, .pub-foot { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 20px; border-bottom: 1px solid var(--hp-line); }
        .pub-foot { border-bottom: none; border-top: 1px solid var(--hp-line); font-size: 12px; color: var(--hp-muted); flex-wrap: wrap; }
        .pub-brand { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 15px; color: var(--hp-fg); text-decoration: none; min-height: 44px; }
        .pub-brand::before { content: ''; width: 10px; height: 10px; border-radius: 50%; background: var(--hp-accent); }
        .pub-nav { display: flex; align-items: center; gap: 8px; }
        .pub-nav a, .pub-nav button { display: inline-flex; align-items: center; gap: 6px; min-height: 44px; min-width: 44px; justify-content: center; padding: 0 10px; border: none; background: none; color: var(--hp-muted); font: inherit; font-size: 13px; text-decoration: none; cursor: pointer; }
        .pub-nav a:hover, .pub-nav button:hover { color: var(--hp-accent); }
        .pub-foot a { color: var(--hp-accent); }
        @media (min-width: 900px) { .pub-top, .pub-foot { padding: 12px 40px; } }
    </style>
</head>
<body>
    <header class="pub-top">
        <a href="{{ route('home') }}" class="pub-brand">JBTB Casting</a>
        <nav class="pub-nav">
            <button type="button" id="theme-toggle" aria-label="Ganti tema"><i class="ti ti-moon" id="theme-icon"></i></button>
            <a href="{{ route('home') }}"><i class="ti ti-arrow-left"></i> Beranda</a>
        </nav>
    </header>

    @include('partials.profil-extras-editorial', [
        'mode' => 'publik',
        'fotoUrl' => $profile->foto_profil_path ? route('public.extras.foto', $token) : null,
        'videoUrl' => null,
        'fotos' => $fotosArr,
    ])

    <footer class="pub-foot">
        <span>Data sensitif (NIK, rekening, nama asli) hanya dilihat Admin.</span>
        <a href="{{ route('home') }}">SIM Casting JBTB</a>
    </footer>

    <script>
        (function () {
            var icon = document.getElementById('theme-icon');
            var current = document.documentElement.getAttribute('data-theme');
            icon.className = current === 'dark' ? 'ti ti-sun' : 'ti ti-moon';

            document.getElementById('theme-toggle').addEventListener('click', function () {
                var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('jbtb-theme-v2', next);
                icon.className = next === 'dark' ? 'ti ti-sun' : 'ti ti-moon';
            });
        })();
    </script>
</body>
</html>
