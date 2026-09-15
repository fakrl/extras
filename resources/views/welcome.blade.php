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
            object-fit: contain; display: block; flex-shrink: 0;
        }
        .hp-nav-menu {
            list-style: none; margin: 0; padding: 0;
            display: flex; gap: 2px; align-items: center; flex: 1; justify-content: center;
        }
        .hp-nav-menu a {
            display: block; font-size: 13.5px; color: var(--text-secondary); text-decoration: none;
            padding: 6px 11px; border-radius: 7px;
        }
        .hp-nav-menu a:hover { color: var(--text-primary); background: var(--bg-card); }
        .hp-menu-toggle {
            display: none; width: 34px; height: 34px; border-radius: 8px; border: none; cursor: pointer;
            background: var(--bg-card-hover); color: var(--text-primary);
            align-items: center; justify-content: center; font-size: 18px;
        }
        .hp-nav-actions { display: flex; gap: 8px; align-items: center; }
        .theme-toggle-btn {
            width: 34px; height: 34px; border-radius: 50%; border: none; cursor: pointer;
            background: var(--bg-card-hover); color: var(--text-secondary);
            display: flex; align-items: center; justify-content: center; font-size: 15px;
        }

        .hero {
            display: flex;
            min-height: 420px;
            border-top: 2px solid var(--accent);
            border-bottom: 1px solid var(--border-color);
        }
        .hero-photo {
            flex: 1;
            background-image:
                linear-gradient(to right, rgba(15,154,76,0.22) 0%, rgba(16,17,18,0.80) 100%),
                url('/images/hero-clapboard.jpg');
            background-size: cover;
            background-position: center;
        }
        .hero-content {
            flex: 1;
            background-color: var(--bg-sidebar);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20'%3E%3Cpath d='M0 10L10 0L20 10L10 20Z' stroke='rgba(15%2C154%2C76%2C0.06)' stroke-width='1' fill='none'/%3E%3C/svg%3E");
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 64px 32px 56px;
            text-align: center;
        }
        .hero h1 { font-size: 32px; font-weight: 700; margin: 0 0 12px; line-height: 1.25; }
        .hero .tagline { font-size: 15.5px; color: var(--text-secondary); line-height: 1.7; margin: 0 auto; max-width: 560px; }
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
        .about-inner { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 32px; }
        .history-facts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; }
        .quick-facts { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px 20px; }
        .quick-facts dl { display: grid; grid-template-columns: auto 1fr; gap: 6px 16px; margin: 0; }
        .quick-facts dt { font-size: 12px; color: var(--text-muted); font-weight: 600; white-space: nowrap; padding-top: 2px; }
        .quick-facts dd { font-size: 13px; color: var(--text-primary); margin: 0; }
        .vm-section { display: flex; gap: 24px; }
        .vm-block { flex: 1; }
        .vm-title { font-size: 18px; font-weight: 700; margin: 0 0 12px; }
        .vm-body { font-size: 15px; color: var(--text-secondary); margin: 0; line-height: 1.8; }
        .vm-list { margin: 0; padding-left: 20px; font-size: 15px; color: var(--text-secondary); line-height: 2.2; }
        .section-title { font-size: 18px; font-weight: 700; margin: 0 0 12px; }
        .section-body { font-size: 14px; color: var(--text-secondary); line-height: 1.75; margin: 0; }

        .talent-section { padding: 0 32px 48px; }
        .talent-inner { max-width: 1100px; margin: 0 auto; }
        .talent-grid { display: flex; gap: 24px; justify-content: center; flex-wrap: wrap; margin-top: 20px; }
        .talent-card { display: flex; flex-direction: column; align-items: center; gap: 10px; width: 110px; }
        .talent-figure svg { width: 64px; height: 84px; display: block; }
        .talent-label { font-size: 12.5px; color: var(--text-secondary); text-align: center; font-weight: 500; }

        .lowongan-section { padding: 0 32px 48px; }
        .lowongan-inner { max-width: 1100px; margin: 0 auto; }
        .lowongan-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; margin-top: 20px; }
        .lowongan-card { position: relative; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 20px; display: flex; flex-direction: column; gap: 12px; }
        .lowongan-num { position: absolute; top: 14px; right: 16px; font-size: 11px; font-weight: 700; color: var(--text-muted); letter-spacing: 1px; }
        .lowongan-card-title { font-size: 15px; font-weight: 600; margin: 0; }
        .lowongan-card-deadline { font-size: 12.5px; color: var(--text-muted); margin-top: 4px; }
        .badge-dibuka { display: inline-block; font-size: 10px; font-weight: 700; letter-spacing: 0.5px; background: var(--accent); color: var(--accent-on); padding: 2px 7px; border-radius: 4px; vertical-align: middle; margin-left: 6px; }
        .lowongan-roles { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 6px; }
        .lowongan-roles li { font-size: 13px; color: var(--text-secondary); display: flex; justify-content: space-between; }
        .lowongan-roles .role-quota { color: var(--text-muted); font-size: 12px; }
        .lowongan-empty { text-align: center; color: var(--text-muted); font-size: 14px; padding: 32px; border: 1px dashed var(--border-color); border-radius: 12px; margin-top: 20px; }
        footer {
            border-top: 1px solid var(--border-color);
            padding: 24px 32px;
            font-size: 13px; color: var(--text-muted);
        }
        .footer-inner { max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }

        dialog {
            border: none; border-radius: 16px; padding: 0; max-width: 360px; width: 90%;
            background: var(--bg-card); color: var(--text-primary);
            opacity: 0; transform: translateY(8px); transition: opacity .25s ease, transform .25s ease;
        }
        dialog.is-open { opacity: 1; transform: none; }
        dialog.is-closing { opacity: 0; transform: translateY(-140px) scale(0.75); transition: opacity .25s ease, transform .25s ease; }
        dialog::backdrop { background: rgba(0,0,0,0.55); }
        .modal-body { padding: 24px; }
        .modal-title { font-size: 16px; font-weight: 600; margin-bottom: 8px; }
        .modal-text { font-size: 13.5px; color: var(--text-secondary); margin: 0 0 18px; line-height: 1.6; }
        .modal-dismiss {
            display: block; width: 100%; text-align: center; margin-top: 12px;
            background: none; border: none; color: var(--text-muted); font-size: 13px; cursor: pointer;
        }

        @media (max-width: 768px) {
            .hp-nav { padding: 0 16px; position: relative; }
            .hp-nav-menu {
                display: none; position: absolute; top: 56px; left: 0; right: 0;
                flex-direction: column; align-items: flex-start;
                background: var(--bg-sidebar); border-bottom: 1px solid var(--border-color);
                padding: 8px 0; z-index: 99;
            }
            .hp-nav-menu.open { display: flex; }
            .hp-nav-menu a { padding: 10px 20px; border-radius: 0; width: 100%; box-sizing: border-box; }
            .hp-menu-toggle { display: flex; }
            .hero { flex-direction: column; }
            .hero-photo { min-height: 200px; }
            .hero-content { padding: 40px 16px 36px; }
            .hero h1 { font-size: 24px; }
            .stats-section, .about-section, .lowongan-section, .talent-section { padding-left: 16px; padding-right: 16px; }
            .stats-grid { grid-template-columns: 1fr; }
            .history-facts-row { grid-template-columns: 1fr; }
            .vm-section { flex-direction: column; gap: 16px; }
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
            <img src="{{ asset('images/logo-jbtb.jpg') }}" alt="Logo PT. JBTB Casting Creative Group" class="hp-logo">
            SIM Casting JBTB
        </div>
        <ul class="hp-nav-menu" id="hp-nav-menu">
            <li><a href="#">Beranda</a></li>
            <li><a href="#tentang">Tentang Kami</a></li>
            <li><a href="#lowongan">Lowongan Terbuka</a></li>
        </ul>
        <div class="hp-nav-actions">
            <button type="button" class="hp-menu-toggle" id="menu-toggle" aria-label="Buka menu">
                <i class="ti ti-menu-2"></i>
            </button>
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
        <div class="hero-photo"></div>
        <div class="hero-content">
            <h1>Sistem Manajemen Casting JBTB</h1>
            <p class="tagline">Platform digital untuk manajemen talent & extras JBTB Casting — dari pendaftaran, seleksi, negosiasi fee, kontrak digital, hingga pembayaran honor, semua tercatat dan transparan.</p>
        </div>
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

    <div class="about-section" id="tentang">
        <div class="about-inner">
            {{-- Block 1: Sejarah + Quick Facts --}}
            <div class="history-facts-row">
                <div>
                    <div class="section-title">Tentang PT. JBTB Casting Creative Group</div>
                    <p class="section-body">
                        PT. JBTB Casting Creative Group adalah perusahaan talent agency dan casting management yang berfokus pada penyediaan extras/pemeran figuran untuk kebutuhan produksi film, iklan, dan konten kreatif di Indonesia. Resmi berdiri sejak 2020 dengan badan hukum PT dan NIB terdaftar OSS. Berkantor pusat di Pamulang, Tangerang Selatan, dengan tim inti 5 orang profesional, JBTB aktif mengelola 50–80 extras dan menangani 4–5 proyek per bulan, melayani klien dari rumah produksi, brand, dan tim iklan di industri hiburan Indonesia.
                    </p>
                </div>
                <div class="quick-facts">
                    <dl>
                        <dt>Nama Perusahaan</dt>
                        <dd>PT. JBTB Casting Creative Group</dd>
                        <dt>Tahun Berdiri</dt>
                        <dd>2020</dd>
                        <dt>Bidang Usaha</dt>
                        <dd>Talent Agency &amp; Casting Management</dd>
                        <dt>Domisili</dt>
                        <dd>Pamulang, Tangerang Selatan, Banten</dd>
                        <dt>Legalitas</dt>
                        <dd>PT &amp; NIB terdaftar OSS Kemenves RI</dd>
                        <dt>Karyawan</dt>
                        <dd>5 orang (tim inti)</dd>
                        <dt>Skala Operasional</dt>
                        <dd>50–80 extras aktif, 4–5 proyek/bulan</dd>
                    </dl>
                </div>
            </div>

            {{-- Block 2: Visi & Misi --}}
            <div class="vm-section">
                <div class="vm-block">
                    <div class="vm-title">Visi</div>
                    <p class="vm-body">Menjadi platform casting digital terdepan di Indonesia yang transparan, profesional, dan terintegrasi bagi seluruh ekosistem film, periklanan, dan segala yang berhubungan dengan manajemen talent di industri entertainment.</p>
                </div>
                <div class="vm-block">
                    <div class="vm-title">Misi</div>
                    <ol class="vm-list">
                        <li>Menyediakan sistem manajemen talent yang rapi, aman, dan mudah digunakan.</li>
                        <li>Menjadi sarana resmi open casting yang kredibel bagi seluruh pelaku industri kreatif.</li>
                        <li>Mempercepat proses seleksi melalui fitur filter dan rekomendasi otomatis.</li>
                        <li>Membantu talent mengembangkan karir profesional di industri hiburan Indonesia.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="talent-section">
        <div class="talent-inner">
            <div class="section-title">Talent Kami</div>
            <p class="section-body">Berbagai kategori talent yang siap mendukung kebutuhan produksi klien kami.</p>
            <div class="talent-grid">
                @foreach (['Ibu-ibu', 'Bapak-bapak', 'Remaja', 'Anak-anak', 'Dewasa'] as $kategori)
                    <div class="talent-card">
                        <div class="talent-figure">
                            <svg viewBox="0 0 60 80" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <circle cx="30" cy="18" r="11" fill="var(--text-muted)"/>
                                <rect x="14" y="33" width="32" height="38" rx="10" fill="var(--text-muted)"/>
                            </svg>
                        </div>
                        <div class="talent-label">{{ $kategori }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="lowongan-section" id="lowongan">
        <div class="lowongan-inner">
            <div class="section-title">Lowongan Casting Terbuka</div>
            @if ($proyekTerbuka->isEmpty())
                <div class="lowongan-empty">Belum ada lowongan casting yang terbuka saat ini — cek lagi nanti.</div>
            @else
                <div class="lowongan-grid">
                    @foreach ($proyekTerbuka as $proyek)
                        <div class="lowongan-card">
                            <div class="lowongan-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                            <div>
                                <span class="lowongan-card-title">{{ $proyek->nama_produksi }}</span>
                                <span class="badge-dibuka">DIBUKA</span>
                                <div class="lowongan-card-deadline">Deadline: {{ $proyek->deadline->format('d M Y') }}</div>
                            </div>
                            @if ($proyek->classes->isNotEmpty())
                                <ul class="lowongan-roles">
                                    @foreach ($proyek->classes as $kelas)
                                        <li>
                                            <span>{{ $kelas->nama_kelas }}</span>
                                            <span class="role-quota">{{ $kelas->kuota_kelas }} orang</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @guest
                                <a href="{{ route('register') }}" class="btn-brand" style="margin-top: auto; font-size: 13px; min-height: 38px;">Daftar untuk Apply</a>
                            @endguest
                            @auth
                                @if (auth()->user()->role === 'extras')
                                    <a href="{{ route('extras.projects.show', $proyek) }}" class="btn-brand" style="margin-top: auto; font-size: 13px; min-height: 38px;">Lihat &amp; Apply</a>
                                @endif
                            @endauth
                        </div>
                    @endforeach
                </div>
                @if ($adaLebih)
                    <div style="text-align: center; margin-top: 20px;">
                        @guest
                            <a href="{{ route('register') }}" class="btn-outline" style="font-size: 13px;">Daftar untuk lihat semua lowongan</a>
                        @endguest
                        @auth
                            @if (auth()->user()->role === 'extras')
                                <a href="/extras/projects" class="btn-outline" style="font-size: 13px;">Lihat semua lowongan</a>
                            @endif
                        @endauth
                    </div>
                @endif
            @endif
        </div>
    </div>

    <footer>
        <div class="footer-inner">
            <span>&copy; {{ date('Y') }} PT. JBTB Casting Creative Group — Pamulang, Tangerang Selatan &nbsp;·&nbsp; <span style="font-size:12px;color:var(--text-muted)">Foto: Jakob Owens (Unsplash)</span></span>
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
                    dlg.classList.add('is-closing');
                    setTimeout(function () { dlg.close(); }, 250);
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

            var menuToggle = document.getElementById('menu-toggle');
            var navMenu = document.getElementById('hp-nav-menu');
            if (menuToggle && navMenu) {
                menuToggle.addEventListener('click', function () { navMenu.classList.toggle('open'); });
                navMenu.querySelectorAll('a').forEach(function (a) {
                    a.addEventListener('click', function () { navMenu.classList.remove('open'); });
                });
            }
        })();
    </script>
</body>
</html>
