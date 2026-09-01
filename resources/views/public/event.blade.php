<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('jbtb-theme') || 'light');</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title>{{ $valid ? $project->nama_produksi : 'Pendaftaran Ditutup' }} — SIM Casting JBTB</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    @include('partials.theme-style')
    <style>
        .wrap { max-width: 560px; margin: 0 auto; padding: 48px 24px 80px; }
        .top-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .logo {
            width: 48px; height: 48px; border-radius: 12px; background: var(--accent); color: var(--accent-on);
            display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 22px;
        }
        .theme-toggle-btn {
            width: 36px; height: 36px; border-radius: 50%; border: none; cursor: pointer;
            background: var(--bg-card-hover); color: var(--accent-strong);
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }
        h1 { font-size: 22px; font-weight: 700; margin: 0 0 8px; }
        .meta { font-size: 13.5px; color: var(--text-secondary); margin: 0 0 24px; }
        .card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 18px; margin-bottom: 24px; }
        .card-title { font-size: 14px; font-weight: 600; margin-bottom: 12px; }
        .class-row { padding: 10px 0; border-bottom: 1px solid var(--border-color); font-size: 13.5px; }
        .class-row:last-child { border-bottom: none; }
        .class-name { font-weight: 600; margin-bottom: 2px; }
        .class-detail { color: var(--text-secondary); }
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
    <div class="wrap">
        <div class="top-row">
            <div class="logo">J</div>
            <button type="button" class="theme-toggle-btn" id="theme-toggle" aria-label="Ganti tema">
                <i class="ti ti-moon" id="theme-icon"></i>
            </button>
        </div>

        @if (! $valid)
            <h1>Pendaftaran sudah tidak dibuka</h1>
            <p>Link ini sudah tidak menerima pendaftaran — mungkin kuota sudah penuh, deadline sudah lewat, atau proyeknya sudah ditutup.</p>
            <a href="{{ route('home') }}" class="btn-outline">Ke Beranda SIM Casting JBTB</a>
        @else
            <h1>{{ $project->nama_produksi }}</h1>
            <p class="meta">Deadline pendaftaran: {{ $project->deadline->format('d M Y') }} · Kuota: {{ $project->kuota }} orang</p>

            <div class="card">
                <div class="card-title">Kelas / Kriteria yang Dicari</div>
                @forelse ($project->classes as $class)
                    <div class="class-row">
                        <div class="class-name">{{ $class->nama_kelas }}</div>
                        @if ($class->kriteria)
                            <div class="class-detail">{{ implode(', ', $class->kriteria) }}</div>
                        @endif
                        <div class="class-detail">Kuota kelas: {{ $class->kuota_kelas }} orang</div>
                    </div>
                @empty
                    <div class="class-detail">Belum ada rincian kelas.</div>
                @endforelse
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
    </div>

    <script>
        (function () {
            var icon = document.getElementById('theme-icon');
            var current = document.documentElement.getAttribute('data-theme');
            icon.className = current === 'dark' ? 'ti ti-sun' : 'ti ti-moon';

            document.getElementById('theme-toggle').addEventListener('click', function () {
                var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('jbtb-theme', next);
                icon.className = next === 'dark' ? 'ti ti-sun' : 'ti ti-moon';
            });
        })();
    </script>
</body>
</html>
