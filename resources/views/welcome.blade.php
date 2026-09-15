<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('jbtb-theme-v2') || 'light');</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title>SIM Casting JBTB</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    @include('partials.theme-style')
    <style>
        .hp-nav {
            position: sticky; top: 0; z-index: 100;
            background: var(--bg-sidebar);
            border-bottom: 1px solid var(--border-color);
            padding: 0 32px;
            display: flex; align-items: center; justify-content: space-between; height: 56px;
        }
        .hp-nav-brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 15px; }
        .hp-logo {
            width: 34px; height: 34px; border-radius: 8px;
            background: var(--accent); color: var(--accent-on);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 16px;
        }
        .hp-nav-actions { display: flex; gap: 8px; align-items: center; }
        .theme-toggle-btn {
            width: 34px; height: 34px; border-radius: 50%; border: none; cursor: pointer;
            background: var(--bg-card-hover); color: var(--accent-strong);
            display: flex; align-items: center; justify-content: center; font-size: 15px;
        }

        .hero {
            background-color: var(--bg-sidebar);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20'%3E%3Cpath d='M0 10L10 0L20 10L10 20Z' stroke='rgba(16%2C185%2C129%2C0.06)' stroke-width='1' fill='none'/%3E%3C/svg%3E");
            border-bottom: 1px solid var(--border-color);
            padding: 64px 32px 56px;
            text-align: center;
        }
        .hero h1 { font-size: 32px; font-weight: 700; margin: 0 0 12px; line-height: 1.25; }
        .hero .tagline { font-size: 15.5px; color: var(--text-secondary); line-height: 1.7; margin: 0 auto 28px; max-width: 560px; }
        .cta-row { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }

        .btn-brand, .btn-outline {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: 46px; padding: 0 22px; border-radius: 10px;
            font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer;
        }
        .btn-brand { background: var(--accent); color: var(--accent-on); border: none; }
        .btn-brand:hover { filter: brightness(1.08); }
        .btn-outline { background: transparent; color: var(--text-primary); border: 1px solid var(--border-color); }
        .btn-outline:hover { background: var(--bg-card); }

        .container { max-width: 1100px; margin: 0 auto; padding: 0 32px; }

        .stats-section { padding: 48px 32px; }
        .stats-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
            max-width: 1100px; margin: 0 auto;
        }
        .stat-card {
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: 14px; padding: 24px 20px; text-align: center;
        }
        .stat-number { font-size: 36px; font-weight: 700; color: var(--accent); line-height: 1; margin-bottom: 6px; }
        .stat-label { font-size: 13.5px; color: var(--text-secondary); }

        .about-section { padding: 0 32px 48px; }
        .about-inner {
            max-width: 1100px; margin: 0 auto;
            display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: start;
        }
        .section-title { font-size: 18px; font-weight: 700; margin: 0 0 12px; }
        .section-body { font-size: 14px; color: var(--text-secondary); line-height: 1.75; margin: 0; }

        .how-section { background: var(--bg-card); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); padding: 48px 32px; }
        .how-inner { max-width: 1100px; margin: 0 auto; }
        .step-bar-wrap { overflow-x: auto; padding-bottom: 4px; -webkit-overflow-scrolling: touch; margin-top: 20px; }
        .step-bar { display: flex; align-items: flex-start; min-width: max-content; }
        .step-bar-item { display: flex; flex-direction: column; align-items: center; width: 100px; flex-shrink: 0; }
        .step-bar-circle {
            width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center;
            justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0;
            border: 2px solid var(--border-color); background: var(--bg-page); color: var(--text-muted);
        }
        .step-bar-line { flex: 1; height: 2px; background: var(--border-color); margin-top: 14px; min-width: 20px; }
        .step-bar-label { font-size: 11px; color: var(--text-muted); text-align: center; margin-top: 8px; line-height: 1.3; padding: 0 4px; }

        .teaser-section { padding: 48px 32px; }
        .teaser-inner { max-width: 1100px; margin: 0 auto; }
        .teaser-card {
            background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px;
            padding: 24px; font-size: 15px; line-height: 1.6;
        }
        .teaser-count { color: var(--accent); font-weight: 700; font-size: 22px; }

        footer {
            border-top: 1px solid var(--border-color);
            padding: 24px 32px;
            font-size: 13px; color: var(--text-muted);
        }
        .footer-inner { max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }

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

        @media (max-width: 768px) {
            .hp-nav { padding: 0 16px; }
            .hero { padding: 48px 16px 40px; }
            .hero h1 { font-size: 24px; }
            .stats-section, .about-section, .teaser-section, .how-section { padding-left: 16px; padding-right: 16px; }
            .stats-grid { grid-template-columns: 1fr; }
            .about-inner { grid-template-columns: 1fr; }
            footer { padding: 20px 16px; }
        }
        @media (min-width: 480px) and (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <nav class="hp-nav">
        <div class="hp-nav-brand">
            <div class="hp-logo">J</div>
            SIM Casting JBTB
        </div>
        <div class="hp-nav-actions">
            <button type="button" class="theme-toggle-btn" id="theme-toggle" aria-label="Ganti tema">
                <i class="ti ti-moon" id="theme-icon"></i>
            </button>
            @auth
                <a href="/dashboard" class="btn-brand">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn-outline">Masuk</a>
                <a href="{{ route('register') }}" class="btn-brand">Daftar</a>
            @endauth
        </div>
    </nav>

    <div class="hero">
        <h1>Sistem Manajemen Casting JBTB</h1>
        <p class="tagline">Platform digital untuk manajemen talent & extras JBTB Casting — dari pendaftaran, seleksi, negosiasi fee, kontrak digital, hingga pembayaran honor, semua tercatat dan transparan.</p>
        @guest
            <div class="cta-row">
                <a href="{{ route('register') }}" class="btn-brand">Daftar Jadi Extras</a>
                <a href="{{ route('login') }}" class="btn-outline">Masuk ke Sistem</a>
            </div>
        @endguest
    </div>

    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="stat-proyek">{{ $totalProyek }}</div>
                <div class="stat-label">Total Proyek Casting</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="stat-extras">{{ $jumlahExtras }}</div>
                <div class="stat-label">Extras Terdaftar</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="stat-admin">{{ $jumlahAdmin }}</div>
                <div class="stat-label">Tim Admin Agensi</div>
            </div>
        </div>
    </div>

    <div class="about-section">
        <div class="about-inner">
            <div>
                <div class="section-title">Tentang JBTB Casting</div>
                <p class="section-body">
                    JBTB Casting adalah agensi casting talent dan extras berbasis di Depok yang melayani kebutuhan
                    production house di industri film, sinetron, dan iklan. Dengan pengalaman mengelola ratusan extras
                    di berbagai proyek produksi, JBTB hadir dengan sistem digital untuk memastikan setiap kesepakatan
                    fee tercatat jelas, kontrak ditandatangani secara sah, dan pembayaran terpantau transparan — tidak
                    ada lagi konflik "sudah kerja belum dibayar" atau "fee tidak sesuai deal".
                </p>
            </div>
            <div>
                <div class="section-title">Kenapa Pakai Sistem Ini?</div>
                <p class="section-body">
                    Sebelumnya proses casting dikelola manual — grup WhatsApp, spreadsheet, dan scan dokumen fisik.
                    Sistem ini menggantikan semua itu dengan alur digital yang terintegrasi: extras apply sendiri,
                    admin seleksi dan nego fee di dalam platform, Casting Director review kandidat, dan kontrak
                    digital ditandatangani langsung di browser. Semua riwayat tersimpan dan bisa ditelusuri kapanpun.
                </p>
            </div>
        </div>
    </div>

    <div class="how-section">
        <div class="how-inner">
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
        </div>
    </div>

    <div class="teaser-section">
        <div class="teaser-inner">
            <div class="teaser-card">
                <span class="teaser-count">{{ $proyekDibukaCount }}</span>
                proyek casting sedang buka pendaftaran sekarang —
                @guest
                    <a href="{{ route('register') }}">daftar akun</a> untuk lihat dan apply.
                @else
                    <a href="/extras/projects">lihat semua proyek</a>.
                @endguest
            </div>
        </div>
    </div>

    @guest
        <div style="padding: 0 32px 48px; max-width: 1100px; margin: 0 auto; text-align: center;">
            <div class="cta-row">
                <a href="{{ route('register') }}" class="btn-brand">Daftar Akun Extras</a>
                <a href="{{ route('login') }}" class="btn-outline">Masuk ke Sistem</a>
            </div>
        </div>
    @endguest

    <footer>
        <div class="footer-inner">
            <span>&copy; {{ date('Y') }} JBTB Casting — Depok</span>
            <span>Sistem Informasi Manajemen Casting Talent &amp; Extras</span>
        </div>
    </footer>

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
