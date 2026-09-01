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
            --text-muted: #6b8672;
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
        }
        .wrap { max-width: 640px; margin: 0 auto; padding: 48px 24px 80px; }
        .logo {
            width: 48px; height: 48px; border-radius: 12px;
            background: var(--accent); color: var(--accent-on);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 22px; margin-bottom: 20px;
        }
        h1 { font-size: 26px; font-weight: 700; margin: 0 0 8px; }
        .tagline { font-size: 15px; color: var(--text-secondary); line-height: 1.6; margin: 0 0 24px; }
        .cta-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 8px; }
        .btn-brand, .btn-outline {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: 48px; padding: 0 22px; border-radius: 10px;
            font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer;
        }
        .btn-brand { background: var(--accent); color: var(--accent-on); border: none; }
        .btn-brand:hover { filter: brightness(1.08); }
        .btn-outline { background: transparent; color: var(--text-primary); border: 1px solid var(--border-color); }
        .btn-outline:hover { background: var(--bg-card); }
        section { margin: 44px 0; }
        .section-title { font-size: 17px; font-weight: 600; margin-bottom: 12px; }
        .section-body { font-size: 14px; color: var(--text-secondary); line-height: 1.7; margin: 0; }

        .step-bar-wrap { overflow-x: auto; padding-bottom: 4px; -webkit-overflow-scrolling: touch; }
        .step-bar { display: flex; align-items: flex-start; min-width: max-content; }
        .step-bar-item { display: flex; flex-direction: column; align-items: center; width: 92px; flex-shrink: 0; }
        .step-bar-circle {
            width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center;
            justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0;
            border: 2px solid var(--border-color); background: var(--bg-card); color: var(--text-muted);
        }
        .step-bar-line { flex: 1; height: 2px; background: var(--border-color); margin-top: 13px; min-width: 20px; }
        .step-bar-label { font-size: 10.5px; color: var(--text-muted); text-align: center; margin-top: 6px; line-height: 1.25; padding: 0 2px; }

        .teaser-card {
            background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px;
            padding: 20px; font-size: 14.5px; line-height: 1.6;
        }
        .teaser-count { color: var(--accent); font-weight: 700; }

        dialog {
            border: none; border-radius: 16px; padding: 0; max-width: 360px; width: 90%;
            background: var(--bg-card); color: var(--text-primary);
            opacity: 0; transform: translateY(8px); transition: opacity .2s ease, transform .2s ease;
        }
        dialog.is-open { opacity: 1; transform: none; }
        dialog::backdrop { background: rgba(0,0,0,0.55); }
        .modal-body { padding: 24px; }
        .modal-title { font-size: 16px; font-weight: 600; margin-bottom: 8px; }
        .modal-text { font-size: 13.5px; color: var(--text-secondary); margin: 0 0 18px; line-height: 1.6; }
        .modal-dismiss {
            display: block; width: 100%; text-align: center; margin-top: 12px;
            background: none; border: none; color: var(--text-muted); font-size: 13px; cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="logo">J</div>
        <h1>SIM Casting JBTB</h1>
        <p class="tagline">Platform manajemen casting untuk JBTB Casting — dari daftar, apply proyek, seleksi, kontrak digital, sampai kerja & dibayar, semua dalam satu sistem.</p>

        @auth
            <a href="/dashboard" class="btn-brand">Ke Dashboard</a>
        @else
            <div class="cta-row">
                <a href="{{ route('register') }}" class="btn-brand">Daftar Akun</a>
                <a href="{{ route('login') }}" class="btn-outline">Masuk</a>
            </div>
        @endauth

        <section>
            <div class="section-title">Apa itu SIM Casting JBTB?</div>
            <p class="section-body">
                SIM Casting JBTB adalah sistem informasi manajemen casting talent & extras. Extras (figuran/talent)
                bisa daftar akun, melihat proyek casting yang lagi dibuka, dan apply langsung dari sistem. Proses
                seleksi, negosiasi fee, tanda tangan kontrak digital, hingga pembayaran honor — semuanya tercatat
                rapi di satu tempat, jadi jelas siapa deal apa dan sudah dibayar atau belum.
            </p>
        </section>

        <section>
            <div class="section-title">Cara Kerja buat Calon Extras</div>
            <div class="step-bar-wrap">
                <div class="step-bar">
                    @foreach ([
                        'Daftar akun',
                        'Lengkapi profil',
                        'Apply proyek casting terbuka',
                        'Seleksi Admin & CD',
                        'Tanda tangan kontrak digital',
                        'Kerja & dibayar',
                    ] as $i => $label)
                        <div class="step-bar-item">
                            <div class="step-bar-circle">{{ $i + 1 }}</div>
                            <div class="step-bar-label">{{ $label }}</div>
                        </div>
                        @if (! $loop->last)
                            <div class="step-bar-line"></div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>

        <section>
            <div class="teaser-card">
                <span class="teaser-count">{{ $proyekDibukaCount }}</span>
                proyek casting lagi buka pendaftaran sekarang — daftar buat lihat & apply.
            </div>
        </section>

        @guest
            <div class="cta-row">
                <a href="{{ route('register') }}" class="btn-brand">Daftar Akun</a>
                <a href="{{ route('login') }}" class="btn-outline">Masuk</a>
            </div>
        @endguest
    </div>

    @guest
        <dialog id="welcome-modal">
            <div class="modal-body">
                <div class="modal-title">Yuk gabung jadi Extras!</div>
                <p class="modal-text">Daftar akun gratis buat mulai apply proyek casting yang lagi buka pendaftaran.</p>
                <div class="cta-row">
                    <a href="{{ route('register') }}" class="btn-brand">Daftar</a>
                    <a href="{{ route('login') }}" class="btn-outline">Masuk</a>
                </div>
                <button type="button" class="modal-dismiss" id="welcome-modal-dismiss">Nanti dulu</button>
            </div>
        </dialog>
        <script>
            (function () {
                var dlg = document.getElementById('welcome-modal');
                if (! dlg || localStorage.getItem('homepage_modal_dismissed')) return;

                dlg.showModal();
                requestAnimationFrame(function () { dlg.classList.add('is-open'); });

                function dismiss() {
                    localStorage.setItem('homepage_modal_dismissed', '1');
                    dlg.classList.remove('is-open');
                    setTimeout(function () { dlg.close(); }, 200);
                }

                document.getElementById('welcome-modal-dismiss').addEventListener('click', dismiss);
                dlg.addEventListener('cancel', function (e) { e.preventDefault(); dismiss(); });
            })();
        </script>
    @endguest
</body>
</html>
