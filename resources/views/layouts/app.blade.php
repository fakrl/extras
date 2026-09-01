<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title>@yield('title', 'SIM Casting JBTB')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    @include('partials.theme-style')
    <style>
        body { transition: background 0.2s ease, color 0.2s ease; }
        a { color: inherit; text-decoration: none; }

        .app-shell { display: flex; min-height: 100vh; }

        .sidebar {
            width: 220px;
            flex-shrink: 0;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            padding: 20px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .sidebar-brand {
            display: flex; align-items: center; gap: 10px;
            padding: 0 8px 20px;
        }
        .sidebar-brand .logo {
            width: 30px; height: 30px; border-radius: 8px;
            background: var(--accent); color: var(--accent-on);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 15px;
        }
        .sidebar-brand span { font-weight: 600; font-size: 15px; }
        .sidebar-group-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;
            color: var(--text-muted); padding: 14px 10px 4px;
        }
        .sidebar-link {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 10px; border-radius: 8px;
            color: var(--text-secondary); font-size: 13.5px;
            min-height: 40px;
        }
        .sidebar-link i { font-size: 17px; }
        .sidebar-link:hover { background: var(--bg-card-hover); color: var(--text-primary); }
        .sidebar-link.active {
            background: var(--bg-nav-active); color: var(--accent-strong); font-weight: 500;
        }

        .main-area { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .topbar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 28px; border-bottom: 1px solid var(--border-color);
        }
        .topbar-title { font-size: 16px; font-weight: 500; }
        .topbar-actions { display: flex; align-items: center; gap: 12px; }
        .theme-toggle-btn, .avatar-badge {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            border: none; cursor: pointer;
        }
        .theme-toggle-btn { background: var(--bg-card-hover); color: var(--accent-strong); }
        .avatar-badge { background: var(--accent); color: var(--accent-on); font-size: 11px; font-weight: 700; }

        .content { padding: 24px 28px; flex: 1; }

        .card {
            background: var(--bg-card); border-radius: 12px; padding: 16px;
            border: 1px solid var(--border-color);
        }
        .metric-card { background: var(--bg-card); border-radius: 12px; padding: 14px; }
        .metric-label { font-size: 11px; color: var(--text-secondary); }
        .metric-value { font-size: 22px; font-weight: 600; color: var(--text-primary); margin-top: 4px; }

        .btn {
            min-height: 44px;
            padding: 0 18px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-primary);
            font-size: 14px; font-weight: 500;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            cursor: pointer;
        }
        .btn:hover { background: var(--bg-card-hover); }
        .btn-brand { background: var(--accent); border-color: var(--accent); color: var(--accent-on); }
        .btn-brand:hover { filter: brightness(1.08); color: var(--accent-on); }
        .btn-danger-outline { color: var(--danger); border-color: var(--danger); background: transparent; }

        .badge { display: inline-flex; padding: 3px 10px; border-radius: 6px; border: 1px solid transparent; font-size: 12px; font-weight: 500; }
        .badge-aktif { background: rgba(34,197,94,0.15); border-color: rgba(34,197,94,0.35); color: var(--accent-strong); }
        .badge-pending { background: rgba(234,179,8,0.15); border-color: rgba(234,179,8,0.35); color: var(--warning); }
        .badge-tolak { background: rgba(239,68,68,0.15); border-color: rgba(239,68,68,0.35); color: var(--danger); }

        /* Grid util ringan — pengganti Bootstrap row/col, dipakai form multi-kolom */
        .form-row { display: flex; gap: 14px; flex-wrap: wrap; }
        .form-row > div { flex: 1; min-width: 180px; }
        .form-check { display: flex; align-items: center; gap: 8px; }
        .form-check input { min-height: auto; width: auto; margin: 0; }
        .btn-sm { min-height: 32px; padding: 0 12px; font-size: 12.5px; }
        .card-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .tautan-row { display: flex; gap: 8px; margin-bottom: 8px; align-items: center; }
        .btn-icon-danger {
            background: transparent; border: 1px solid var(--border-color); color: var(--danger);
            border-radius: 8px; width: 36px; height: 36px; cursor: pointer; font-size: 16px;
        }
        .card-title {
            font-size: 14px; font-weight: 500; margin-bottom: 12px;
            color: var(--text-primary); text-decoration: none;
        }

        /* Progress steps ala Jira — pengganti bar chart untuk data funnel/tahapan */
        .funnel-steps { display: flex; flex-direction: column; gap: 10px; }
        .funnel-step { display: grid; grid-template-columns: 110px 1fr 34px; align-items: center; gap: 10px; }
        .funnel-step-label { font-size: 12.5px; color: var(--text-secondary); }
        .funnel-step-track {
            background: var(--bg-nav-active); border-radius: 20px; height: 8px; overflow: hidden;
        }
        .funnel-step-fill {
            background: var(--accent); height: 100%; border-radius: 20px;
            transition: width 0.3s ease; min-width: 2px;
        }
        .funnel-step-value { font-size: 12.5px; color: var(--text-primary); text-align: right; font-weight: 500; }

        /* Step-bar horizontal — progress pendaftaran Extras (dashboard Extras).
           Bisa discroll ke samping di layar kecil, bukan wrap/vertical. */
        .step-bar-wrap { overflow-x: auto; padding-bottom: 4px; -webkit-overflow-scrolling: touch; }
        .step-bar { display: flex; align-items: flex-start; min-width: max-content; }
        .step-bar-item { display: flex; flex-direction: column; align-items: center; width: 84px; flex-shrink: 0; }
        .step-bar-circle {
            width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center;
            justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0;
            border: 2px solid var(--border-color); background: var(--bg-card); color: var(--text-muted);
        }
        .step-bar-line {
            flex: 1; height: 2px; background: var(--border-color); margin-top: 13px;
            min-width: 20px;
        }
        .step-bar-label {
            font-size: 10.5px; color: var(--text-muted); text-align: center; margin-top: 6px;
            line-height: 1.25; padding: 0 2px;
        }
        .step-bar-item.is-done .step-bar-circle { background: var(--accent); border-color: var(--accent); color: var(--accent-on); }
        .step-bar-item.is-done .step-bar-label { color: var(--text-secondary); }
        .step-bar-item.is-done + .step-bar-line { background: var(--accent); }
        .step-bar-item.is-active .step-bar-circle {
            border-color: var(--accent); color: var(--accent-strong); background: var(--bg-card);
            box-shadow: 0 0 0 3px rgba(34,197,94,0.15);
        }
        .step-bar-item.is-active .step-bar-label { color: var(--text-primary); font-weight: 600; }

        .step-bar-stopped {
            display: flex; align-items: center; gap: 10px; padding: 10px 12px;
            border-radius: 8px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25);
        }
        .step-bar-stopped i { color: var(--danger); font-size: 18px; flex-shrink: 0; }
        .step-bar-stopped-title { font-size: 13px; font-weight: 600; color: var(--danger); }
        .step-bar-stopped-reason { font-size: 12.5px; color: var(--text-secondary); margin-top: 2px; }

        @media (max-width: 480px) {
            .step-bar-item { width: 68px; }
            .step-bar-circle { width: 22px; height: 22px; font-size: 11px; }
            .step-bar-line { margin-top: 11px; min-width: 14px; }
            .step-bar-label { font-size: 10px; }
        }

        /* Card grid untuk daftar Proyek Casting & Pendaftar — desktop/iPad-first
           (Admin pakai perangkat itu), tapi tetap collapse rapi ke 1 kolom di
           mobile karena CD kadang buka dari HP juga. */
        .entity-card-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 14px; align-items: start;
        }
        .entity-card {
            border: 1px solid var(--border-color); border-radius: 12px;
            background: var(--bg-card); padding: 16px;
        }
        .entity-card-title { font-size: 15px; font-weight: 600; margin-bottom: 2px; }
        .entity-card-sub { font-size: 12.5px; color: var(--text-secondary); margin-bottom: 12px; }
        .entity-card-row {
            display: flex; justify-content: space-between; gap: 10px;
            font-size: 13px; padding: 5px 0; border-bottom: 1px solid var(--border-color);
        }
        .entity-card-row:last-of-type { border-bottom: none; }
        .entity-card-row-label { color: var(--text-secondary); }
        .entity-card-row-value { font-weight: 500; text-align: right; }
        .entity-card-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }

        /* Card kandidat di halaman Pendaftar — foto besar kiri, info+aksi kanan.
           Di mobile stack jadi 1 kolom (foto di atas). */
        .applicant-card {
            display: grid; grid-template-columns: 120px 1fr; gap: 14px;
            border: 1px solid var(--border-color); border-radius: 12px;
            background: var(--bg-card); padding: 14px; margin-bottom: 14px;
        }
        .applicant-card-photo img, .applicant-card-photo .thumb-photo-empty {
            width: 100%; aspect-ratio: 3/4; object-fit: cover; border-radius: 10px; display: block;
        }
        .applicant-card-photo .thumb-photo-empty {
            display: flex; align-items: center; justify-content: center;
            background: var(--bg-nav-active); color: var(--text-muted); font-size: 24px;
        }
        .applicant-card-extra-photos { display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; }
        @media (max-width: 640px) {
            .applicant-card { grid-template-columns: 1fr; }
            .applicant-card-photo { max-width: 160px; }
        }

        /* Grid dashboard 2 kolom (Super Admin, dll) — collapse ke 1 kolom di
           mobile supaya chart tidak diperas jadi sempit & tinggi tidak proporsional. */
        .dashboard-grid-2col { display: grid; gap: 16px; margin-bottom: 16px; align-items: start; }
        .dashboard-grid-2col.is-wide-narrow { grid-template-columns: 1.4fr 1fr; }
        .dashboard-grid-2col.is-even { grid-template-columns: 1fr 1fr; }

        /* Wrapper canvas Chart.js — tinggi dikontrol lewat CSS (bukan attribute
           height di <canvas>), dipasangkan dengan maintainAspectRatio:false di
           JS supaya chart selalu proporsional dengan lebar container-nya. */
        .chart-box { position: relative; height: 240px; width: 100%; }

        @media (max-width: 860px) {
            .dashboard-grid-2col.is-wide-narrow,
            .dashboard-grid-2col.is-even {
                grid-template-columns: 1fr;
            }
            .chart-box { height: 200px; }
        }

        /* Baris tampilan read-only (halaman "Lihat Profil") */
        .profile-view-row {
            display: flex; justify-content: space-between; gap: 12px;
            padding: 7px 0; font-size: 13.5px;
        }
        .profile-view-label { color: var(--text-secondary); flex-shrink: 0; }
        .profile-view-value { color: var(--text-primary); font-weight: 500; text-align: right; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 8px; text-align: left; font-size: 13.5px; border-bottom: 1px solid var(--border-color); }
        th { color: var(--text-secondary); font-weight: 500; font-size: 12px; text-transform: uppercase; letter-spacing: 0.3px; }

        input, select, textarea {
            background: var(--bg-card); color: var(--text-primary);
            border: 1px solid var(--border-color); border-radius: 8px;
            padding: 10px 12px; font-size: 15px; min-height: 48px;
            font-family: inherit; width: 100%; margin-bottom: 14px;
        }
        input:focus, select:focus, textarea:focus {
            outline: none; border-color: var(--accent);
        }
        label { font-size: 13.5px; color: var(--text-secondary); display: block; margin-bottom: 6px; font-weight: 500; }
        .required-mark { color: var(--danger); }

        /* Override untuk input yang sengaja sejajar tombol dalam satu baris
           (form nego fee, tambah komponen pembayaran, dsb) — bukan full-width */
        .input-inline { width: auto; flex: 1; margin-bottom: 0; min-width: 0; }

        /* Form profil Extras — grouping per section biar nggak berasa panjang/berat */
        .profile-section {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 18px; margin-bottom: 18px;
        }
        .profile-section:last-of-type { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .profile-section-title {
            font-size: 13px; font-weight: 600; color: var(--accent-strong);
            text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 14px;
        }
        .field-hint {
            font-size: 12px; color: var(--text-muted); line-height: 1.4;
            margin: -8px 0 14px;
        }

        /* Upload foto/video — tap area besar, bukan input file kecil bawaan
           browser yang susah disentuh di HP */
        .media-upload-box {
            display: flex; align-items: center; justify-content: center;
            position: relative;
            background: var(--bg-nav-active); border: 2px dashed var(--border-color);
            border-radius: 14px; overflow: hidden;
            aspect-ratio: 3 / 4; max-width: 220px;
            cursor: pointer; margin: 0 auto;
        }
        .media-upload-box-video { aspect-ratio: 16 / 9; max-width: 100%; }
        .media-upload-empty {
            display: flex; flex-direction: column; align-items: center; gap: 8px;
            color: var(--text-secondary); font-size: 13px; text-align: center; padding: 16px;
        }
        .media-upload-empty i { font-size: 32px; color: var(--accent-strong); }
        .media-upload-preview {
            width: 100%; height: 100%; object-fit: cover; display: block;
        }
        .media-upload-box-video .media-upload-preview { object-fit: contain; background: #000; }
        .media-upload-overlay {
            position: absolute; inset: auto 0 0 0;
            background: rgba(0,0,0,0.55); color: #fff;
            font-size: 11.5px; text-align: center; padding: 6px 4px;
        }

        /* Grid 4 slot foto tambahan (RF-06 perluasan) */
        .photo-slot-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;
            max-width: 320px;
        }
        .photo-slot-box { max-width: 100%; aspect-ratio: 1 / 1; }
        .photo-slot-box .media-upload-empty { padding: 8px; }
        .photo-slot-box .media-upload-empty i { font-size: 22px; }

        /* Thumbnail kecil di tabel pendaftar (Admin & CD) */
        .thumb-photo {
            width: 44px; height: 56px; object-fit: cover;
            border-radius: 8px; display: block;
            background: var(--bg-nav-active);
        }
        .thumb-photo-empty {
            display: flex; align-items: center; justify-content: center;
            color: var(--text-muted); font-size: 18px;
        }
        .thumb-photo-mini {
            width: 26px; height: 26px; object-fit: cover;
            border-radius: 5px; display: inline-block;
            margin-right: 3px; vertical-align: middle;
        }

        .alert-info {
            background: rgba(59,130,246,0.12); color: #60a5fa;
            padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 14px;
        }

        /* ===== Mobile: sidebar berubah jadi bottom navigation bar =====
           Extras (pengguna utama di HP) butuh navigasi yang selalu kelihatan
           tanpa perlu "nemu" tombol menu dulu — pola bottom nav ala WA/IG
           yang kemungkinan besar sudah familiar buat mereka. */
        @media (max-width: 860px) {
            .app-shell { flex-direction: column; }

            .sidebar {
                position: fixed; bottom: 0; left: 0; right: 0; top: auto;
                width: 100%; height: 64px;
                flex-direction: row; align-items: center;
                justify-content: flex-start;
                overflow-x: auto; overflow-y: hidden;
                padding: 6px 4px;
                border-right: none; border-top: 1px solid var(--border-color);
                z-index: 50;
                gap: 0;
            }
            .sidebar-brand { display: none; }
            .sidebar-group-label { display: none; }
            .sidebar-link {
                flex-direction: column; justify-content: center;
                gap: 2px; padding: 6px 8px; min-height: 52px;
                font-size: 10.5px; flex: 1 0 64px; min-width: 64px; text-align: center;
                border-radius: 10px; white-space: nowrap;
            }
            .sidebar-link i { font-size: 20px; }
            .sidebar-link.active { background: var(--bg-nav-active); }

            .main-area { padding-bottom: 64px; }
            .content { padding: 16px; }
            .topbar { padding: 14px 16px; }
        }

        @media (max-width: 480px) {
            .content { padding: 12px; }
            .card, .metric-card { padding: 12px; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="logo">J</div>
                <span>JBTB Casting</span>
            </div>
            @include('partials.sidebar-' . (auth()->user()->role ?? 'guest'))
        </aside>

        <div class="main-area">
            <div class="topbar">
                <div class="topbar-title">@yield('title', 'SIM Casting JBTB')</div>
                <div class="topbar-actions">
                    @auth
                        <button type="button" class="theme-toggle-btn" id="theme-toggle" aria-label="Ganti tema">
                            <i class="ti ti-sun" id="theme-icon"></i>
                        </button>
                        <div class="avatar-badge">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn" style="min-height:36px; padding:0 14px; font-size:13px;">Keluar</button>
                        </form>
                    @endauth
                </div>
            </div>

            <main class="content">
                @if (session('status'))
                    <div class="alert-success">{{ session('status') }}</div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        (function () {
            var saved = localStorage.getItem('jbtb-theme-v2') || 'light';
            document.documentElement.setAttribute('data-theme', saved);

            document.addEventListener('DOMContentLoaded', function () {
                var icon = document.getElementById('theme-icon');
                if (icon) icon.className = saved === 'dark' ? 'ti ti-sun' : 'ti ti-moon';

                var btn = document.getElementById('theme-toggle');
                if (btn) {
                    btn.addEventListener('click', function () {
                        var current = document.documentElement.getAttribute('data-theme');
                        var next = current === 'dark' ? 'light' : 'dark';
                        document.documentElement.setAttribute('data-theme', next);
                        localStorage.setItem('jbtb-theme-v2', next);
                        icon.className = next === 'dark' ? 'ti ti-sun' : 'ti ti-moon';
                    });
                }
            });
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    @stack('scripts')
</body>
</html>
