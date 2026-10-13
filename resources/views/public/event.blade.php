<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <script>try { document.documentElement.setAttribute('data-theme', localStorage.getItem('jbtb-theme-v2') || 'light'); } catch (e) {}</script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title>{{ $valid ? $project->nama_produksi : 'Pendaftaran Ditutup' }} | JBTB Casting</title>
    @if ($valid)
        @include('partials.og-meta', [
            'ogTitle' => $project->nama_produksi,
            'ogDesc' => 'Casting '.($project->classes->pluck('nama_kelas')->implode(', ') ?: 'Extras').' · deadline '.$project->deadline?->translatedFormat('d M Y').' · daftar di JBTB',
            'ogImage' => $project->poster_path ? asset('storage/'.$project->poster_path) : null,
        ])
    @else
        @include('partials.og-meta', ['ogTitle' => 'Pendaftaran Ditutup', 'ogDesc' => 'Pendaftaran casting ini sudah ditutup. Cek lowongan lain di JBTB Casting.'])
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@@tabler/icons-webfont@3.48.0/dist/tabler-icons.min.css">
    @include('partials.theme-style')
    <style>
        a { color: var(--accent-strong); }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
        .pub-top, .pub-foot { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 8px 20px; border-bottom: 1px solid var(--border-color); }
        .pub-foot { border-bottom: none; border-top: 1px solid var(--border-color); font-size: var(--fs-xs); color: var(--text-muted); flex-wrap: wrap; }
        .pub-brand { display: flex; align-items: center; gap: 10px; min-height: 44px; font-weight: 700; font-size: 15px; color: var(--text-primary) !important; text-decoration: none; }
        .logo {
            width: 32px; height: 32px; border-radius: 8px; background: var(--accent); color: var(--accent-on);
            display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px;
        }
        .pub-nav { display: flex; align-items: center; gap: 4px; }
        .pub-nav a { display: inline-flex; align-items: center; gap: 6px; min-height: 44px; padding: 0 10px; font-size: 13px; text-decoration: none; }
        .pub-foot a { display: inline-flex; align-items: center; min-height: 44px; }
        .wrap { max-width: 560px; margin: 0 auto; padding: 32px 24px 64px; }
        .theme-toggle-btn {
            width: 44px; height: 44px; border-radius: 50%; border: none; cursor: pointer;
            background: var(--bg-card-hover); color: var(--accent-strong);
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }
        h1 { font-size: 22px; font-weight: 700; margin: 0 0 8px; }
        .meta { font-size: 13.5px; color: var(--text-secondary); margin: 0 0 24px; }
        .card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 18px; margin-bottom: 24px; }
        .card-title { font-size: 14px; font-weight: 600; margin-bottom: 12px; }
        .class-detail { color: var(--text-secondary); font-size: 13.5px; }
        .xtag { font-size: var(--fs-xs); padding: 3px 7px; border-radius: var(--radius-sm); background: var(--bg-page); border: 1px solid var(--border-color); color: var(--text-secondary); line-height: 1.3; }
        .cta-row { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn-brand, .btn-outline {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: 48px; padding: 0 22px; border-radius: 10px;
            font-size: 14px; font-weight: 600; text-decoration: none;
        }
        .btn-brand { background: var(--accent); color: var(--accent-on); border: none; }
        .btn-brand:hover { filter: brightness(1.08); }
        .btn-outline { background: transparent; color: var(--text-primary); border: 1px solid var(--border-color); }
        .btn-outline:hover { background: var(--bg-card); }
        p { font-size: 14px; color: var(--text-secondary); line-height: 1.6; }
    </style>
</head>
<body>
    <header class="pub-top">
        <a href="{{ route('home') }}" class="pub-brand"><span class="logo" aria-hidden="true">J</span> JBTB Casting</a>
        <nav class="pub-nav" aria-label="Menu">
            <button type="button" class="theme-toggle-btn" id="theme-toggle" aria-label="Ganti tema" aria-pressed="false">
                <i class="ti ti-moon" id="theme-icon" aria-hidden="true"></i>
            </button>
            <a href="{{ route('home') }}"><i class="ti ti-arrow-left" aria-hidden="true"></i> Beranda</a>
        </nav>
    </header>
    <main class="wrap">

        @if (! $valid)
            <h1>Pendaftaran sudah tidak dibuka</h1>
            <p>Link ini sudah tidak menerima pendaftaran. Kuota mungkin sudah penuh, batas waktu terlewati, atau proyek telah ditutup.</p>
            <div class="cta-row">
                <a href="{{ route('home') }}#lowongan" class="btn-brand">Lihat lowongan lain</a>
                @guest
                    <a href="{{ route('login') }}" class="btn-outline">Masuk</a>
                    <a href="{{ route('register') }}" class="btn-outline">Daftar</a>
                @endguest
            </div>
        @else
            <h1>{{ $project->nama_produksi }}</h1>
            <p style="margin: 0 0 8px;">@include('partials.label-nego', ['project' => $project])</p>
            <p class="meta">Deadline pendaftaran: {{ $project->deadline->translatedFormat('d M Y') }} · Kuota: {{ $project->kuota }} orang</p>
            {{-- lokasi disembunyikan sebelum lolos (AZ.1, keputusan akhir D17) --}}

            <div class="card">
                <div class="card-title">Karakter yang Dibutuhkan</div>
                @if ($project->classes->isEmpty())
                    <div class="class-detail">Belum ada rincian kelas.</div>
                @else
                    @include('partials.peran-lowongan', ['classes' => $project->classes])
                @endif
            </div>

            @if ($sudahLoginExtras)
                <a href="{{ route('extras.projects.show', $project) }}" class="btn-brand">Apply Sekarang</a>
            @else
                <div class="cta-row">
                    <a href="{{ route('register', ['event' => $project->share_token]) }}" class="btn-brand">Daftar</a>
                    <a href="{{ route('login', ['event' => $project->share_token]) }}" class="btn-outline">Masuk</a>
                </div>
            @endif
        @endif
    </main>

    <footer class="pub-foot">
        <span>&copy; {{ date('Y') }} JBTB Casting</span>
        <a href="{{ route('privacy-policy') }}">Kebijakan Privasi</a>
    </footer>

    <script>
        (function () {
            var icon = document.getElementById('theme-icon');
            var tema = document.getElementById('theme-toggle');
            function ikon(t) { icon.className = t === 'dark' ? 'ti ti-sun' : 'ti ti-moon'; tema.setAttribute('aria-pressed', t === 'dark'); }
            ikon(document.documentElement.getAttribute('data-theme'));

            tema.addEventListener('click', function () {
                var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                try { localStorage.setItem('jbtb-theme-v2', next); } catch (e) {}
                ikon(next);
            });
        })();
    </script>
</body>
</html>
