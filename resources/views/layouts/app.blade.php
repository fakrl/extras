<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="dark light">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIM Casting JBTB')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@@tabler/icons-webfont@3.48.0/dist/tabler-icons.min.css">
    @include('partials.theme-style')
    <style>
        body { transition: background 0.2s ease, color 0.2s ease; }
        a { color: inherit; text-decoration: none; }

        .app-shell { display: flex; min-height: 100vh; }

        .sidebar {
            width: 220px;
            flex-shrink: 0;
            background-color: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            padding: 20px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        [data-theme="dark"] .sidebar {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20'%3E%3Cpath d='M0 10L10 0L20 10L10 20Z' stroke='rgba(16%2C185%2C129%2C0.05)' stroke-width='1' fill='none'/%3E%3C/svg%3E");
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
            font-size: var(--fs-xs); text-transform: uppercase; letter-spacing: 0.5px;
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

        .sidebar-dropdown { margin-bottom: 2px; }
        .sidebar-dropdown-summary {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 10px; border-radius: 8px;
            color: var(--text-secondary); font-size: 13.5px; font-weight: 600;
            cursor: pointer; list-style: none; min-height: 40px;
            user-select: none;
        }
        .sidebar-dropdown-summary::-webkit-details-marker { display: none; }
        .sidebar-dropdown-summary:hover { background: var(--bg-card-hover); color: var(--text-primary); }
        .sidebar-dropdown[open] .sidebar-dropdown-summary { color: var(--accent-strong); }
        .sidebar-dropdown-summary .chevron-icon {
            font-size: 14px; transition: transform 0.2s ease;
        }
        .sidebar-dropdown[open] .sidebar-dropdown-summary .chevron-icon {
            transform: rotate(90deg);
        }
        .sidebar-submenu {
            display: flex; flex-direction: column; gap: 2px;
            padding-left: 14px; margin-top: 2px;
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
        .avatar-badge { background: var(--accent); color: var(--accent-on); font-size: var(--fs-xs); font-weight: 700; }
        .avatar-badge-img { object-fit: cover; padding: 0; }
        .navbar-user-menu { position: relative; list-style: none; }
        .navbar-user-menu > summary { list-style: none; cursor: pointer; }
        .navbar-user-menu > summary::-webkit-details-marker { display: none; }
        .navbar-user-menu-dropdown {
            display: none; position: absolute; right: 0; top: calc(100% + 8px); z-index: 200;
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: 10px; min-width: 180px; padding: 6px;
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

        .content { padding: 24px 28px; flex: 1; }

        .card {
            background: var(--bg-card); border-radius: var(--radius-lg); padding: var(--space-4);
            border: 1px solid var(--border-color);
        }
        .metric-card { background: var(--bg-card); border-radius: var(--radius-lg); padding: var(--space-3); }
        .metric-label { font-size: var(--fs-xs); color: var(--text-secondary); }
        .metric-value { font-size: var(--fs-xl); font-weight: 600; color: var(--text-primary); margin-top: 4px; }

        .btn {
            min-height: 44px;
            padding: 0 18px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-primary);
            font-size: var(--fs-base); font-weight: 500;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            cursor: pointer;
        }
        .btn:hover { background: var(--bg-card-hover); }
        .btn-brand { background: var(--accent); border-color: var(--accent); color: var(--accent-on); }
        .btn-brand:hover { filter: brightness(1.08); color: var(--accent-on); }
        .btn-danger-outline { color: var(--danger); border-color: var(--danger); background: transparent; }

        .tag-grup { font-size: var(--fs-xs); color: var(--text-secondary); font-weight: 600; margin: 10px 0 6px; }
        .tag-chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .tag-chip { display: inline-flex; align-items: center; min-height: 44px; padding: 0 14px; margin: 0; border-radius: var(--radius-md); border: 1px solid var(--border-color); background: var(--bg-card-hover); color: var(--text-primary); font-size: var(--fs-sm); font-weight: 500; cursor: pointer; user-select: none; }
        .tag-chip:has(:checked) { background: var(--accent); border-color: var(--accent); color: var(--accent-on); }
        .tag-chip:has(:focus-visible) { outline: 2px solid var(--accent); outline-offset: 2px; }
        .badge { display: inline-flex; padding: 3px 10px; border-radius: var(--radius-sm); border: 1px solid transparent; font-size: var(--fs-xs); font-weight: 500; }
        .badge-aktif { background: rgba(34,197,94,0.15); border-color: rgba(34,197,94,0.35); color: var(--accent-strong); }
        .badge-pending { background: rgba(234,179,8,0.15); border-color: rgba(234,179,8,0.35); color: var(--warning); }
        .badge-tolak { background: rgba(239,68,68,0.15); border-color: rgba(239,68,68,0.35); color: var(--danger); }
        .badge-netral { background: rgba(148,163,184,0.18); border-color: rgba(148,163,184,0.4); color: var(--text-secondary); }
        .badge-info { background: rgba(29,78,216,0.12); border-color: rgba(29,78,216,0.35); color: var(--info); }

        /* Grid util ringan: pengganti Bootstrap row/col, dipakai form multi-kolom */
        .form-row { display: flex; gap: 14px; flex-wrap: wrap; }
        .form-row > div { flex: 1; min-width: 180px; }
        .form-check { display: flex; align-items: center; gap: 8px; }
        .form-check input { min-height: auto; width: auto; margin: 0; }
        .btn-sm { min-height: 32px; padding: 0 12px; font-size: var(--fs-sm); }
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

        /* Progress steps: visualisasi progress alur pendaftaran */
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

        /* Step-bar horizontal: progress pendaftaran Extras (dashboard Extras).
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
            font-size: var(--fs-xs); color: var(--text-muted); text-align: center; margin-top: 6px;
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
            .step-bar-circle { width: 22px; height: 22px; font-size: var(--fs-xs); }
            .step-bar-line { margin-top: 11px; min-width: 14px; }
            .step-bar-label { font-size: var(--fs-xs); }
        }

        /* Card grid untuk daftar Proyek Casting & Pendaftar, desktop/iPad-first
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

        /* BA.4: kartu Extras (partials/extras-card) + modal detail, ikut prototype 07 */
        .xgrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 14px; margin-bottom: var(--space-4); }
        .xcard { position: relative; display: flex; flex-direction: column; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; scroll-margin-top: 80px; transition: box-shadow .15s ease; }
        .xcard:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
        .xcard.is-highlight { border: 2px solid var(--accent); }
        .xcard-ph { position: relative; aspect-ratio: 3/4; background: linear-gradient(160deg, hsl(var(--h) 24% 72%), hsl(var(--h) 20% 40%)); }
        .xcard-ph-btn { display: block; width: 100%; height: 100%; padding: 0; margin: 0; border: 0; background: none; cursor: pointer; color: inherit; }
        .xcard-ph-btn:focus-visible { outline: 3px solid var(--accent); outline-offset: -3px; }
        .xcard-ph img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .xcard-inisial { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; font-size: 48px; font-weight: 700; letter-spacing: 1px; color: rgba(255,255,255,0.92); text-shadow: 0 1px 8px rgba(0,0,0,0.18); }
        .xcard .xcard-st { position: absolute; top: 10px; left: 10px; background: var(--bg-card); font-weight: 600; box-shadow: 0 1px 4px rgba(0,0,0,0.15); pointer-events: none; }
        .xcard-ck { position: absolute; top: 2px; right: 2px; width: 44px; height: 44px; margin: 0; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .xcard-ck input[type="checkbox"] { width: 22px; height: 22px; border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.35); }
        .xcard-ring { position: absolute; right: 10px; bottom: -24px; width: 56px; height: 56px; border-radius: 50%; background: conic-gradient(var(--accent) var(--p), var(--bg-nav-active) 0); display: flex; align-items: center; justify-content: center; box-shadow: 0 0 0 3px var(--bg-card), 0 3px 10px rgba(0,0,0,0.18); pointer-events: none; }
        .xcard-ring span { width: 44px; height: 44px; border-radius: 50%; background: var(--bg-card); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: var(--fs-sm); color: var(--accent-strong); }
        .xcard-body { padding: 14px 14px 12px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
        .xcard-name { font-size: var(--fs-md); font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .xcard-sub { font-size: var(--fs-xs); color: var(--text-muted); margin-top: -4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .xcard.has-ring .xcard-name, .xcard.has-ring .xcard-sub { padding-right: 58px; }
        .xcard-line { display: flex; gap: 8px; align-items: flex-start; font-size: var(--fs-sm); color: var(--text-secondary); line-height: 1.35; }
        .xcard-line i { font-size: 16px; color: var(--text-muted); flex-shrink: 0; }
        .xcard-line.is-warn, .xcard-line.is-warn i { color: var(--warning); }
        .xcard-tags { display: flex; flex-wrap: wrap; gap: 4px; }
        .xtag { font-size: var(--fs-xs); padding: 3px 7px; border-radius: var(--radius-sm); background: var(--bg-page); border: 1px solid var(--border-color); color: var(--text-secondary); line-height: 1.3; }
        .xtag.is-hit { color: var(--accent-strong); border-color: rgba(34,197,94,0.45); background: rgba(34,197,94,0.08); }
        .xtag-grup { font-size: var(--fs-xs); color: var(--text-muted); font-weight: 600; margin: 8px 0 4px; }
        .xcard-btns { display: flex; gap: 8px; margin-top: auto; padding-top: 8px; }
        .xcard-btns > *, .xcard-btns form .btn { flex: 1; min-width: 0; width: 100%; }
        .xcard-btns .btn { padding: 0 8px; font-weight: 600; font-size: var(--fs-sm); line-height: 1.2; text-align: center; }
        .btn-outline-brand { border-color: var(--accent); color: var(--accent-strong); }
        .xfilter { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; margin-bottom: var(--space-3); }
        .xfilter-label { font-size: var(--fs-xs); color: var(--text-muted); font-weight: 600; margin-right: 2px; }
        .xtoolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 10px 12px; margin-bottom: var(--space-3); }
        .xtoolbar input, .xtoolbar select { width: auto; min-height: 40px; margin: 0; padding: 6px 10px; font-size: var(--fs-sm); }
        .xtoolbar .xtoolbar-cari { flex: 1 1 220px; font-size: var(--fs-md); }
        .xtoolbar label { margin: 0; font-size: var(--fs-xs); color: var(--text-muted); }
        .xtoolbar-more > summary { list-style: none; }
        .xtoolbar-more > summary::-webkit-details-marker { display: none; }
        .xtoolbar-more[open] { flex-basis: 100%; }
        .xtoolbar-more-isi { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 8px; }
        @media (max-width: 480px) {
            .xgrid { grid-template-columns: 1fr; }
            .xcard-ph { aspect-ratio: 4/5; }
            /* chip tag & urutkan jadi 1 baris geser di HP, bukan numpuk 3 baris */
            .xfilter { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; -webkit-overflow-scrolling: touch; }
            .xfilter > * { flex-shrink: 0; }
        }

        .xmodal { border: 0; padding: 0; border-radius: 16px; width: min(560px, 94vw); max-height: 92vh; background: var(--bg-card); color: var(--text-primary); box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .xmodal::backdrop { background: rgba(0,0,0,0.55); }
        .xmodal-ph { position: relative; height: 280px; background: linear-gradient(160deg, hsl(var(--h, 140) 24% 72%), hsl(var(--h, 140) 20% 40%)); }
        .xmodal-ph img { width: 100%; height: 100%; object-fit: cover; object-position: top; display: block; }
        .xmodal-ph .xcard-inisial { font-size: 64px; }
        .xmodal-x { position: absolute; top: 10px; right: 10px; width: 44px; height: 44px; border-radius: 50%; border: 0; background: rgba(255,255,255,0.92); color: #0c1a10; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 6px rgba(0,0,0,0.2); }
        .xmodal-body { padding: 18px; }
        .xmodal-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
        .xmodal-name { font-size: var(--fs-xl); font-weight: 700; word-break: break-word; }
        .xmodal-sub { font-size: var(--fs-sm); color: var(--text-secondary); margin-top: 2px; }
        .xmodal-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
        .xsec { font-size: var(--fs-xs); font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--text-muted); margin: 18px 0 8px; }
        .xkv { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .xkv > div { background: var(--bg-page); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 8px 10px; min-width: 0; }
        .xkv-l { display: block; font-size: var(--fs-xs); color: var(--text-muted); margin-bottom: 2px; }
        .xkv b { font-size: var(--fs-base); font-weight: 600; word-break: break-word; }
        .xkv .full { grid-column: 1 / -1; }
        .xrow { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 8px 0; border-top: 1px solid var(--border-color); font-size: var(--fs-sm); }
        .xsec + .xrow { border-top: 0; }
        .xrow i { font-size: 17px; vertical-align: -3px; }
        .xrow-ok { color: var(--accent-strong); }
        .xrow-no, .xrow-muted { color: var(--text-muted); }
        .xaksi { display: flex; flex-wrap: wrap; gap: 8px; }
        .xaksi > form { margin: 0; }
        .xmodal-foot { position: sticky; bottom: 0; background: var(--bg-card); border-top: 1px solid var(--border-color); padding: 12px 18px; display: flex; gap: 8px; flex-wrap: wrap; }
        .xmodal-foot > * { flex: 1; }

        /* Grid dashboard 2 kolom (Super Admin, dll), collapse ke 1 kolom di
           mobile supaya chart tidak diperas jadi sempit & tinggi tidak proporsional. */
        .dashboard-grid-2col { display: grid; gap: 16px; margin-bottom: 16px; align-items: start; }
        .dashboard-grid-2col.is-wide-narrow { grid-template-columns: 1.4fr 1fr; }
        .dashboard-grid-2col.is-even { grid-template-columns: 1fr 1fr; }

        /* Wrapper canvas Chart.js: tinggi dikontrol lewat CSS (bukan attribute
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
            border: 1px solid var(--border-color); border-radius: var(--radius-md);
            padding: 10px 12px; font-size: var(--fs-md); min-height: 48px;
            font-family: inherit; width: 100%; margin-bottom: 14px;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--accent);
        }
        :focus-visible {
            outline: 2px solid var(--accent); outline-offset: 2px;
        }
        .sr-only {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
        }
        input[type="checkbox"],
        input[type="radio"] {
            width: 15px;
            height: 15px;
            accent-color: var(--accent);
            cursor: pointer;
            vertical-align: middle;
            min-height: unset;
            margin-bottom: 0;
        }
        label { font-size: 13.5px; color: var(--text-secondary); display: block; margin-bottom: 6px; font-weight: 500; }
        .required-mark { color: var(--danger); }

        /* Override untuk input yang sengaja sejajar tombol dalam satu baris
           (form nego fee, tambah komponen pembayaran, dsb), bukan full-width */
        .input-inline { width: auto; flex: 1; margin-bottom: 0; min-width: 0; }

        /* Form profil Extras: grouping per section */
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

        /* Upload foto/video: tap area besar, bukan input file kecil bawaan
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
            font-size: var(--fs-xs); text-align: center; padding: 6px 4px;
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
            background: rgba(59,130,246,0.12); color: var(--info);
            padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 16px; font-size: var(--fs-base);
        }
        .table-container { overflow-x: auto; }

        /* ===== Mobile: sidebar berubah jadi bottom navigation bar =====
           Extras (pengguna utama di HP) butuh navigasi yang selalu kelihatan
           tanpa perlu membuka menu terpisah (pola navigasi mobile)
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
                font-size: var(--fs-xs); flex: 1 0 auto; min-width: 64px; text-align: center;
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
    <style>
        .notif-badge {
            position:absolute; top:-4px; right:-4px;
            background:var(--danger); color:#fff;
            font-size: var(--fs-xs); font-weight:700; min-width:16px; height:16px;
            border-radius:8px; display:flex; align-items:center; justify-content:center;
            padding:0 3px; pointer-events:none;
        }
        .notif-dropdown {
            position:absolute; right:0; top:calc(100% + 8px); z-index:300;
            background:var(--bg-card); border:1px solid var(--border-color);
            border-radius:10px; min-width:280px; max-width:320px;
            box-shadow:0 4px 16px rgba(0,0,0,0.18); overflow:hidden;
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
                        <div class="notif-bell-wrap" style="position:relative;">
                            <button type="button" class="theme-toggle-btn" id="notif-bell-btn" aria-label="Notifikasi" style="position:relative;">
                                <i class="ti ti-bell"></i>
                                @if(auth()->user()->unreadNotifications->count() > 0)
                                    <span class="notif-badge">{{ auth()->user()->unreadNotifications->count() }}</span>
                                @endif
                            </button>
                            <div class="notif-dropdown" id="notif-dropdown" style="display:none;">
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px 6px;border-bottom:1px solid var(--border-color);">
                                    <span style="font-size:13px;font-weight:600;">Notifikasi</span>
                                    @if(auth()->user()->unreadNotifications->count() > 0)
                                        <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0;">
                                            @csrf
                                            <button type="submit" style="font-size: var(--fs-xs);color:var(--accent-strong);background:none;border:none;cursor:pointer;padding:0;min-height:auto;">Tandai semua dibaca</button>
                                        </form>
                                    @endif
                                </div>
                                @forelse(auth()->user()->notifications->take(5) as $notif)
                                    @php($notifUrl = $notif->data['url'] ?? null)
                                    <{{ $notifUrl ? 'a' : 'div' }} @if($notifUrl) href="{{ $notifUrl }}" @endif style="display:block;color:inherit;text-decoration:none;padding:10px 14px;border-bottom:1px solid var(--border-color);{{ $notif->read_at ? '' : 'background:var(--bg-nav-active);' }}">
                                        <div style="font-size:13px;font-weight:{{ $notif->read_at ? '400' : '600' }};margin-bottom:2px;">{{ $notif->data['judul'] ?? '' }}</div>
                                        <div style="font-size:12px;color:var(--text-secondary);line-height:1.4;">{{ mb_substr($notif->data['pesan'] ?? '', 0, 80) }}</div>
                                        <div style="font-size: var(--fs-xs);color:var(--text-muted);margin-top:3px;">{{ $notif->created_at->diffForHumans() }}</div>
                                    </{{ $notifUrl ? 'a' : 'div' }}>
                                @empty
                                    <div style="padding:16px 14px;font-size:13px;color:var(--text-muted);text-align:center;">Tidak ada notifikasi.</div>
                                @endforelse
                                @if(auth()->user()->notifications->count() > 5)
                                    <div style="padding:8px 14px;text-align:center;">
                                        <span style="font-size:12px;color:var(--text-muted);">+{{ auth()->user()->notifications->count() - 5 }} notifikasi lainnya</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <details class="navbar-user-menu" id="topbar-user-menu">
                            <summary class="avatar-badge-summary">
                                @if (auth()->user()->isExtras() && auth()->user()->extrasProfile?->foto_profil_path)
                                    <img src="{{ route('extras.media.foto', auth()->user()->extrasProfile) }}" class="avatar-badge avatar-badge-img" alt="Foto profil">
                                @else
                                    <div class="avatar-badge">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                                @endif
                            </summary>
                            <div class="navbar-user-menu-dropdown">
                                @if (auth()->user()->role === 'extras')
                                    <a href="{{ route('extras.profile.edit') }}"><i class="ti ti-user"></i> Profil Saya</a>
                                @endif
                                <a href="{{ route('ubah-password') }}"><i class="ti ti-lock"></i> Ubah Kata Sandi</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"><i class="ti ti-logout"></i> Keluar</button>
                                </form>
                            </div>
                        </details>
                    @endauth
                </div>
            </div>

            <main class="content">
                @if (session('status'))
                    <div class="alert-success">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert-danger">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert-danger">
                        <ul style="margin: 0; padding-left: 18px;">
                            @foreach ($errors->all() as $pesan)
                                <li>{{ $pesan }}</li>
                            @endforeach
                        </ul>
                    </div>
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

        var topbarUserMenu = document.getElementById('topbar-user-menu');
        if (topbarUserMenu) {
            document.addEventListener('click', function (e) {
                if (!topbarUserMenu.contains(e.target)) topbarUserMenu.removeAttribute('open');
            });
        }
    </script>
    <script>
        (function() {
            var bellBtn = document.getElementById('notif-bell-btn');
            var notifDrop = document.getElementById('notif-dropdown');
            if (bellBtn && notifDrop) {
                bellBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notifDrop.style.display = notifDrop.style.display === 'none' ? 'block' : 'none';
                });
                document.addEventListener('click', function() {
                    if (notifDrop) notifDrop.style.display = 'none';
                });
            }
        })();
    </script>
    @auth
        @if(auth()->user()?->role === 'super_admin')
            <x-command-palette />
        @endif
    @endauth
    @if (session('kredensial'))
        @include('partials.kredensial-dialog')
    @endif
    @stack('scripts')
</body>
</html>
