<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('jbtb-theme-v2') || 'light');</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title>Profil {{ $profile->user->username ?? 'Extras' }} — SIM Casting JBTB</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    @include('partials.theme-style')
    <style>
        .wrap { max-width: 560px; margin: 0 auto; padding: 48px 24px 80px; }
        .top-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .logo {
            width: 48px; height: 48px; border-radius: 12px; background: var(--accent); color: var(--accent-on);
            display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 22px;
        }
        .theme-toggle-btn {
            width: 36px; height: 36px; border-radius: 50%; border: none; cursor: pointer;
            background: var(--bg-card-hover); color: var(--accent-strong);
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }
        .card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 18px; margin-bottom: 16px; }
        .card-title { font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 12px; text-transform: uppercase; letter-spacing: .4px; }
        .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px; font-size: 13px; }
        .field-label { color: var(--text-secondary); }
        .avatar-wrap { width: 160px; aspect-ratio: 3/4; margin: 0 auto 12px; border-radius: 14px; overflow: hidden; background: var(--bg-nav-active); display: flex; align-items: center; justify-content: center; }
        .avatar-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .alias { font-size: 20px; font-weight: 700; text-align: center; margin-bottom: 4px; }
        .note-sensitive { font-size: 11.5px; color: var(--text-muted); text-align: center; margin: 0 0 20px; }
        p { font-size: 14px; color: var(--text-secondary); line-height: 1.6; }
        @media (min-width: 900px) {
            html, body { height: 100%; overflow: hidden; }
            .wrap { max-width: 900px; height: 100%; padding: 0; overflow: hidden; display: grid; grid-template-columns: 300px 1fr; grid-template-rows: auto 1fr; }
            .top-row { grid-column: 1 / -1; padding: 14px 24px; margin-bottom: 0; border-bottom: 1px solid var(--border-color); }
            .avatar-wrap { width: 100%; margin: 0 auto 12px; }
            .pub-media-col { overflow: hidden; padding: 16px; border-right: 1px solid var(--border-color); }
            .pub-info-col { overflow-y: auto; padding: 16px; }
        }
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

        <div class="pub-media-col">
            {{-- Foto profil --}}
            <div class="avatar-wrap">
                @if ($profile->foto_profil_path)
                    <img src="{{ route('public.extras.foto', $token) }}" alt="Foto profil {{ $profile->user->username }}">
                @else
                    <i class="ti ti-photo-off" style="font-size: 36px; color: var(--text-muted);"></i>
                @endif
            </div>
            <div class="alias">{{ $profile->user->username ?? '—' }}</div>

            {{-- Video profil --}}
            @if ($profile->video_profil_path)
            <div class="card">
                <div class="card-title">Video Profil</div>
                <video src="{{ route('public.extras.video', $token) }}" controls
                       style="width: 100%; border-radius: 8px; background: #000; aspect-ratio: 16/9;"></video>
            </div>
            @else
            <div class="card">
                <div class="card-title">Video Profil</div>
                <div style="width: 100%; aspect-ratio: 16/9; border-radius: 8px; background: var(--bg-nav-active); display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                    <i class="ti ti-video-off" style="font-size: 28px;"></i>
                </div>
            </div>
            @endif
        </div>

        <div class="pub-info-col">
            <p class="note-sensitive">Data sensitif (NIK, rekening, nama asli) hanya dilihat Admin.</p>

            {{-- Data diri --}}
            <div class="card">
                <div class="card-title">Data Diri &amp; Ciri Fisik</div>
                <div class="field-grid">
                    <div class="field-label">Usia</div>
                    <div>{{ $profile->usia ? $profile->usia . ' tahun' : '—' }}</div>

                    <div class="field-label">Jenis Kelamin</div>
                    <div>{{ $profile->gender === 'pria' ? 'Laki-laki' : ($profile->gender === 'wanita' ? 'Perempuan' : '—') }}</div>

                    <div class="field-label">Tinggi Badan</div>
                    <div>{{ $profile->tinggi_badan ? $profile->tinggi_badan . ' cm' : '—' }}</div>

                    <div class="field-label">Ukuran Baju</div>
                    <div>{{ $profile->ukuran_baju ?: '—' }}</div>

                    <div class="field-label">Warna Kulit</div>
                    <div>{{ $profile->warna_kulit ?: '—' }}</div>
                </div>
            </div>

            {{-- Pengalaman & bahasa --}}
            <div class="card">
                <div class="card-title">Pengalaman &amp; Kemampuan</div>
                <div style="font-size: 13px; margin-bottom: 12px;">
                    <span class="field-label">Pengalaman</span>
                    <p style="margin: 4px 0 0; color: var(--text-primary);">{{ $profile->pengalaman ?: '—' }}</p>
                </div>
                <div class="field-grid">
                    <div class="field-label">Bahasa</div>
                    <div>{{ $profile->bahasa ?: '—' }}</div>
                </div>
            </div>

            {{-- Gallery --}}
            @if (count($fotosArr) > 0)
                <div class="card">
                    <div class="card-title">Gallery</div>
                    @include('partials.foto-lightbox', ['fotos' => $fotosArr, 'lightboxId' => 'pub-lb'])
                </div>
            @endif

            <p style="text-align:center; margin-top: 24px; font-size: 12px;">
                <a href="{{ route('home') }}" style="color: var(--accent);">SIM Casting JBTB</a>
            </p>
        </div>
    </div>

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
