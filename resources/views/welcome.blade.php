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
        .avatar-badge {
            width: 34px; height: 34px; border-radius: 50%; border: none; cursor: pointer;
            background: var(--accent); color: var(--accent-on); font-size: 11px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
        }
        .avatar-badge-img { object-fit: cover; padding: 0; }
        .navbar-user-menu { position: relative; list-style: none; }
        .navbar-user-menu > summary { list-style: none; cursor: pointer; }
        .navbar-user-menu > summary::-webkit-details-marker { display: none; }
        .navbar-user-menu-dropdown {
            display: none; position: absolute; right: 0; top: calc(100% + 8px); z-index: 200;
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: 10px; min-width: 160px; padding: 6px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.18);
        }
        .navbar-user-menu[open] .navbar-user-menu-dropdown { display: block; }
        .navbar-user-menu-dropdown a,
        .navbar-user-menu-dropdown button {
            display: flex; align-items: center; gap: 8px; width: 100%;
            padding: 9px 12px; border-radius: 7px; font-size: 13.5px; font-weight: 500;
            color: var(--text-primary); text-decoration: none; background: none; border: none; cursor: pointer;
        }
        .navbar-user-menu-dropdown a:hover,
        .navbar-user-menu-dropdown button:hover { background: var(--bg-card-hover); }

        .hero {
            min-height: 520px;
            border-top: 2px solid var(--accent);
            border-bottom: 1px solid var(--border-color);
            background-image:
                linear-gradient(rgba(16,17,18,0.65), rgba(16,17,18,0.65)),
                url('/images/hero-clapboard.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 32px;
            text-align: center;
            max-width: 720px;
            width: 100%;
        }
        .hero h1 { font-size: 38px; font-weight: 700; margin: 0 0 16px; line-height: 1.2; color: #fff; }
        .hero .tagline { font-size: 16px; color: rgba(238,244,239,0.82); line-height: 1.7; margin: 0 auto; max-width: 560px; }
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
        .stat-number { font-size: 36px; font-weight: 700; color: var(--highlight-cream, var(--accent)); line-height: 1; margin-bottom: 6px; }
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
        .talent-card { display: flex; flex-direction: column; align-items: center; gap: 12px; width: 150px; }
        .talent-figure svg { width: 100px; height: 130px; display: block; }
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

        .produksi-section { padding: 0 32px 48px; }
        .produksi-inner { max-width: 1100px; margin: 0 auto; }
        .produksi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; margin-top: 20px; }
        .produksi-card { display: flex; flex-direction: column; gap: 10px; }
        .produksi-poster { width: 100%; aspect-ratio: 2/3; object-fit: cover; border-radius: 10px; display: block; }
        .produksi-placeholder {
            width: 100%; aspect-ratio: 2/3; border-radius: 10px;
            background: var(--bg-card); border: 1px solid var(--border-color);
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; color: var(--text-muted);
        }
        .produksi-name { font-size: 12.5px; color: var(--text-secondary); text-align: center; font-weight: 500; }

        .cta-strip {
            background-image:
                linear-gradient(rgba(16,17,18,0.70), rgba(16,17,18,0.70)),
                url('/images/section-soundstage.jpg');
            background-size: cover;
            background-position: center;
            padding: 72px 32px;
            text-align: center;
            border-top: 1px solid var(--border-color);
        }
        .cta-strip-inner { max-width: 620px; margin: 0 auto; }
        .cta-strip .section-title { color: #fff; }
        .cta-strip .section-body { color: rgba(238,244,239,0.80); margin-top: 8px; }

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
            .hero { min-height: 360px; }
            .hero-content { padding: 48px 20px; }
            .hero h1 { font-size: 26px; }
            .stats-section, .about-section, .lowongan-section, .talent-section, .produksi-section, .cta-strip { padding-left: 16px; padding-right: 16px; }
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
                <details class="navbar-user-menu" id="navbar-user-menu">
                    <summary class="avatar-badge-summary">
                        @if (auth()->user()->extrasProfile?->foto_profil_path)
                            <img src="{{ route('extras.media.foto', auth()->user()->extrasProfile) }}"
                                 class="avatar-badge avatar-badge-img" alt="Foto profil">
                        @else
                            <div class="avatar-badge">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                        @endif
                    </summary>
                    <div class="navbar-user-menu-dropdown">
                        <a href="/dashboard"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"><i class="ti ti-logout"></i> Keluar</button>
                        </form>
                    </div>
                </details>
            @else
                <a href="{{ route('login') }}" class="btn-brand">Masuk / Daftar</a>
            @endauth
        </div>
    </nav>

    <div class="hero">
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

    @if ($proyekSelesai->isNotEmpty())
    <div class="produksi-section">
        <div class="produksi-inner">
            <div class="section-title">Produksi yang Pernah Kami Tangani</div>
            <div class="produksi-grid">
                @foreach ($proyekSelesai as $p)
                    <div class="produksi-card">
                        @if ($p->poster_path)
                            <img src="{{ Storage::url($p->poster_path) }}" alt="{{ $p->nama_produksi }}" class="produksi-poster">
                        @else
                            <div class="produksi-placeholder">
                                <i class="ti ti-clapperboard"></i>
                            </div>
                        @endif
                        <div class="produksi-name">{{ $p->nama_produksi }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <div class="cta-strip">
        <div class="cta-strip-inner">
            <div class="section-title">Siap Bergabung di Produksi Berikutnya?</div>
            <p class="section-body">Daftar jadi Extras JBTB dan mulai apply proyek casting yang terbuka — gratis, transparan, tercatat.</p>
            @guest
                <div style="margin-top: 20px;">
                    <a href="{{ route('register') }}" class="btn-brand">Daftar Sekarang</a>
                </div>
            @endguest
        </div>
    </div>

    <footer>
        <div class="footer-inner">
            <span>&copy; {{ date('Y') }} PT. JBTB Casting Creative Group — Pamulang, Tangerang Selatan</span>
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
            var userMenu = document.getElementById('navbar-user-menu');
            if (userMenu) {
                document.addEventListener('click', function (e) {
                    if (!userMenu.contains(e.target)) userMenu.removeAttribute('open');
                });
            }
        })();
    </script>
</body>
</html>
