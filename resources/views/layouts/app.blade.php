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
        .tag-chip[aria-pressed="true"] { background: var(--accent); border-color: var(--accent); color: var(--accent-on); }
        .tag-input-box { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; padding: 6px; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: var(--bg-card); }
        .tag-input-box:focus-within { border-color: var(--accent); }
        .tag-input-chip { display: inline-flex; align-items: center; gap: 2px; min-height: 36px; padding-left: 12px; border-radius: 999px; background: var(--accent); color: var(--accent-on); font-size: var(--fs-sm); font-weight: 500; }
        .tag-input-chip button { width: 36px; height: 36px; border: 0; background: none; color: inherit; cursor: pointer; border-radius: 999px; font-size: 14px; }
        .tag-input-field { flex: 1 1 140px; width: auto; min-width: 0; min-height: 36px; margin: 0; border: 0; background: transparent; padding: 0 6px; }
        .tag-input-field:focus-visible { outline: none; }
        .tag-input-wrap { position: relative; }
        .tag-input .field-hint { margin-top: 6px; }
        .tag-panel { position: absolute; z-index: 45; left: 0; right: 0; top: calc(100% + 4px); max-height: min(340px, 55vh); overflow-y: auto; padding: 4px 12px 12px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: 0 10px 28px rgba(0,0,0,.18); }
        .tag-panel[hidden], .tag-panel [hidden] { display: none; }
        .tag-panel-kosong { margin: 10px 0 0; font-size: var(--fs-sm); color: var(--text-muted); }
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
            line-height: 1.25; padding: 0 2px; max-width: 100%; overflow-wrap: anywhere;
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
           mobile karena Client kadang buka dari HP juga. */
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
        .xfilter-note { margin: calc(-1 * var(--space-2)) 0 var(--space-3); font-size: var(--fs-xs); color: var(--text-muted); }
        .xtoolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 10px 12px; margin-bottom: var(--space-3); }
        .xtoolbar input, .xtoolbar select { width: auto; min-height: 40px; margin: 0; padding: 6px 10px; font-size: var(--fs-sm); }
        .xtoolbar .xtoolbar-cari { flex: 1 1 220px; font-size: var(--fs-md); }
        .xtoolbar label { margin: 0; font-size: var(--fs-xs); color: var(--text-muted); }
        .xtoolbar-more > summary { list-style: none; }
        .xtoolbar-more > summary::-webkit-details-marker { display: none; }
        .xtoolbar-more[open] { flex-basis: 100%; }
        .xtoolbar-more-isi { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 8px; }
        .xtoolbar:has(> .fpanel) .xtoolbar-cari { flex: 1 1 120px; min-width: 0; }
        .fpanel { position: relative; flex-shrink: 0; }
        .fpanel > summary { list-style: none; gap: 6px; }
        .fpanel > summary::-webkit-details-marker { display: none; }
        .fpanel[open] > summary { border-color: var(--accent); color: var(--accent-strong); }
        .fpanel-n { min-width: 20px; height: 20px; padding: 0 6px; border-radius: 10px; background: var(--accent); color: var(--accent-on); font-size: var(--fs-xs); font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .fpanel-isi { position: absolute; right: 0; top: calc(100% + 6px); z-index: 60; width: 400px; max-width: calc(100vw - 32px); max-height: min(70vh, 560px); display: flex; flex-direction: column; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); box-shadow: 0 12px 32px rgba(0,0,0,0.18); }
        .fpanel-body { overflow-y: auto; padding: 12px 14px; display: flex; flex-direction: column; gap: 12px; }
        .fpanel-foot { display: flex; justify-content: space-between; gap: 8px; padding: 10px 14px; border-top: 1px solid var(--border-color); }
        .fpanel .fpanel-label { display: block; font-size: var(--fs-xs); font-weight: 600; color: var(--text-muted); margin: 0 0 6px; text-transform: uppercase; letter-spacing: .03em; }
        .fpanel-chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .fpanel-chips.is-baris { flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none; margin: 0 -14px; padding: 0 14px; }
        .fpanel-chips.is-baris { gap: 4px; }
        .fpanel-chips.is-baris > .tag-chip { flex-shrink: 0; padding: 0 7px; font-size: var(--fs-xs); }
        .fpanel-sub { border-top: 1px solid var(--border-color); padding-top: 12px; display: flex; flex-direction: column; gap: 8px; }
        .fpanel-sub > .fpanel-judul { font-size: var(--fs-sm); font-weight: 600; color: var(--text-primary); }
        .fpanel .tag-chip { min-height: 32px; padding: 0 10px; font-size: var(--fs-sm); color: var(--text-primary); }
        .fpanel .tag-chip:has(:checked) { color: var(--accent-on); }
        .fpanel input:not([type=radio], [type=checkbox]), .fpanel select { width: 100%; }
        .fpanel-dua { display: flex; gap: 8px; align-items: center; }
        .fpanel-dua > input { flex: 1 1 0; min-width: 0; }
        .fpanel-acc { border-top: 1px solid var(--border-color); padding-top: 6px; }
        .fpanel-acc > summary { cursor: pointer; font-size: var(--fs-sm); font-weight: 500; color: var(--text-primary); padding: 4px 0; }
        .fpanel-acc > .fpanel-grup { margin: 4px 0 6px; }
        .fpanel .xfilter-note { margin: 4px 0 0; }
        .fpanel .fswitch { display: flex; align-items: center; justify-content: space-between; gap: 10px; cursor: pointer; font-size: var(--fs-sm); color: var(--text-primary); }
        .fswitch input[type=checkbox] { appearance: none; -webkit-appearance: none; width: 40px; height: 24px; min-height: 0; padding: 0; margin: 0; border-radius: 12px; background: color-mix(in srgb, var(--text-muted) 40%, transparent); position: relative; cursor: pointer; flex-shrink: 0; border: 0; transition: background .15s; }
        .fswitch input[type=checkbox]::after { content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.3); transition: transform .15s; }
        .fswitch input[type=checkbox]:checked { background: var(--accent); }
        .fswitch input[type=checkbox]:checked::after { transform: translateX(16px); }
        .fswitch input:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
        .fchips { flex-basis: 100%; display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
        .fchips:not(:has(a)) { display: none; }
        .fchip { display: inline-flex; align-items: center; gap: 4px; min-height: 30px; padding: 0 10px; border-radius: 15px; background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.4); color: var(--accent-strong); font-size: var(--fs-xs); font-weight: 500; text-decoration: none; }
        .fchip:hover { background: rgba(34,197,94,0.18); }
        .fchips-hapus { font-size: var(--fs-xs); color: var(--text-muted); margin-left: 4px; }
        @media (max-width: 560px) {
            .xtoolbar:has(> .fpanel) .per-halaman { font-size: 0; }
            .xtoolbar:has(> .fpanel) .per-halaman select { font-size: var(--fs-sm); }
            .fpanel[open]::before { content: ''; position: fixed; inset: 0; z-index: 59; background: rgba(0,0,0,0.45); }
            .fpanel-isi { position: fixed; left: 0; right: 0; bottom: 0; top: auto; width: auto; max-width: none; max-height: 85vh; border-radius: 16px 16px 0 0; }
            .fpanel-foot { padding-bottom: calc(10px + env(safe-area-inset-bottom)); }
            .fpanel-foot .btn { min-height: 44px; flex: 1; }
        }
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

        /* Dashboard semua role: kartu sejajar (Jadwal, Perlu tindakan, ringkasan), acuan dashboard SA */
        .dash-tiga { display: grid; gap: 16px; margin-bottom: 16px; align-items: stretch; }
        .dash-tiga > .card { margin: 0; min-width: 0; }
        @media (min-width: 861px) { .dash-tiga { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1180px) { .dash-tiga { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 860px) { .dash-tiga > .dash-perlu { order: -1; } }
        .dash-kolom { max-width: 760px; margin: 0 auto; display: flex; flex-direction: column; gap: 16px; }
        .dash-kolom > .card, .dash-kolom > section > .card:last-child { margin: 0; }
        .dash-perlu { border: 2px solid var(--accent-strong); max-height: 720px; overflow-y: auto; }
        .dash-perlu > .card-title { color: var(--accent-strong); }
        .dash-perlu.is-aman { border-width: 1px; border-color: var(--accent); display: flex; flex-direction: column; }
        .dash-aman { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border: 1px solid var(--accent); border-radius: var(--radius-lg, 12px); background: var(--bg-nav-active); color: var(--accent-strong); font-weight: 600; font-size: var(--fs-sm, 13px); }
        .dash-aman[hidden] { display: none; }
        .dash-perlu.is-aman .dash-aman { flex: 1; flex-direction: column; justify-content: center; text-align: center; min-height: 140px; border: none; background: transparent; }
        .dash-perlu.is-aman .dash-aman i { font-size: 40px; }
        .dash-row { display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; padding: 10px 0; border-bottom: 1px solid var(--border-color); font-size: 13.5px; }
        .dash-row:last-child { border-bottom: none; }
        a.dash-row { text-decoration: none; color: inherit; }
        a.dash-row:hover { color: var(--accent); }
        .dash-sub { font-size: 12px; color: var(--text-muted); }
        .dash-pill { display: inline-flex; align-items: center; gap: 6px; min-height: 36px; padding: 0 12px; border: 1px solid var(--border-color); border-radius: 999px; font-size: var(--fs-sm, 13px); color: var(--text-secondary); text-decoration: none; background: var(--bg-card); }
        .dash-pill strong { font-size: var(--fs-md, 16px); color: var(--text-primary); }
        a.dash-pill:hover { border-color: var(--accent); }
        .dash-pills { display: flex; flex-wrap: wrap; gap: 6px; }
        .dash-metrik { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        a.metric-card { display: block; text-decoration: none; color: inherit; border: 1px solid var(--border-color); }
        a.metric-card:hover { border-color: var(--accent); }

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

        /* Thumbnail kecil di tabel pendaftar (Admin & Client) */
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

        /* Pagination (markup pagination::bootstrap-4, lihat AppServiceProvider) */
        .pagination { display: flex; flex-wrap: wrap; gap: 4px; list-style: none; padding: 0; margin: 0; }
        .pagination .page-link { display: inline-flex; align-items: center; justify-content: center; min-width: 40px; min-height: 40px; padding: 0 10px; border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); background: var(--bg-card); color: var(--text-primary); text-decoration: none; font-size: var(--fs-sm, 13px); }
        .pagination a.page-link:hover { background: var(--bg-card-hover); }
        .pagination .active .page-link { background: var(--accent); border-color: var(--accent); color: var(--accent-on); font-weight: 600; }
        .pagination .disabled .page-link { opacity: .45; }
        .pagebar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; margin-top: 16px; }
        .pagebar-info { font-size: var(--fs-xs); color: var(--text-muted); }
        .pagebar .per-halaman { margin-left: auto; }
        .per-halaman { display: inline-flex; align-items: center; gap: 6px; margin: 0; font-size: var(--fs-xs); color: var(--text-muted); white-space: nowrap; }
        .per-halaman select { width: auto; min-height: 40px; margin: 0; padding: 4px 8px; font-size: var(--fs-sm); }
        .sa-mode-banner {
            display: flex; align-items: center; justify-content: space-between; gap: 8px 12px; flex-wrap: wrap;
            background: rgba(234,179,8,0.14); border: 1px solid rgba(234,179,8,0.45); color: var(--text-primary);
            padding: 10px 14px; border-radius: var(--radius-md); margin-bottom: 16px; font-size: var(--fs-base);
        }
        .sa-mode-banner form { margin: 0; }
        .sa-lihat-saja form :disabled:not([type="hidden"]), .sa-lihat-saja [aria-disabled="true"] { opacity: .5; cursor: not-allowed; }
        .sa-lock-note { margin: -8px 0 16px; font-size: var(--fs-sm); color: var(--text-secondary); }

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
            .sidebar-link.is-utama i { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--accent); color: var(--accent-on); font-size: 20px; }
            .sidebar-link.is-utama { font-weight: 600; color: var(--accent-strong); }
            .sidebar-link.is-utama.active { background: transparent; }
            /* dropdown (Monitoring SA) di bottom bar: 1 item, submenu jadi popup di atas bar */
            .sidebar-dropdown { flex: 1 0 auto; min-width: 64px; margin: 0; }
            .sidebar-dropdown-summary {
                flex-direction: column; justify-content: center; gap: 2px;
                padding: 6px 8px; min-height: 52px; font-size: var(--fs-xs); font-weight: 400;
                border-radius: 10px; white-space: nowrap;
            }
            .sidebar-dropdown-summary > span { display: flex; flex-direction: column; align-items: center; gap: 2px; }
            .sidebar-dropdown-summary > span i { font-size: 20px; margin: 0 !important; }
            .sidebar-dropdown-summary .chevron-icon { display: none; }
            .sidebar-dropdown[open] .sidebar-dropdown-summary { background: var(--bg-nav-active); }
            .sidebar-dropdown .sidebar-submenu {
                position: fixed; bottom: 70px; right: 8px; z-index: 60;
                background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg);
                box-shadow: 0 8px 24px rgba(0,0,0,0.18); padding: 6px; margin: 0; min-width: 180px;
            }
            .sidebar-dropdown .sidebar-submenu .sidebar-link { flex-direction: row; justify-content: flex-start; min-height: 44px; font-size: var(--fs-sm); }

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
            @include('partials.sidebar-' . (auth()->user()?->modeSa() ?? auth()->user()->role ?? 'guest'))
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
                                    @php($notifUrl = \App\Notifications\InAppNotification::relatif($notif->data['url'] ?? null))
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
                                <form method="POST" action="{{ route('logout') }}" data-sa-allow>
                                    @csrf
                                    <button type="submit"><i class="ti ti-logout"></i> Keluar</button>
                                </form>
                            </div>
                        </details>
                    @endauth
                </div>
            </div>

            <main class="content">
                @isset($saMonitoring)
                    <div class="sa-mode-banner" role="status">
                        <span>
                            <i class="ti ti-eye"></i> <strong>Super Admin</strong> · sebagai {{ \App\Models\User::LABELS[$saMonitoring['mode']] }}
                            @if ($saMonitoring['target'])
                                ({{ $saMonitoring['target']->name }}) · <strong>Mode lihat saja</strong>
                            @endif
                        </span>
                        <form method="POST" action="{{ route('super-admin.mode.keluar') }}" data-sa-allow>
                            @csrf
                            <button type="submit" class="btn btn-sm"><i class="ti ti-arrow-back-up"></i> Kembali ke Super Admin</button>
                        </form>
                    </div>
                    @if ($saMonitoring['target'])
                        <p class="sa-lock-note" data-sa-lock hidden><i class="ti ti-lock"></i> Mode lihat saja — tampilan ini persis yang dilihat {{ $saMonitoring['target']->name }}, tapi nggak bisa diubah.</p>
                    @endif
                @endisset
                @if (session('status'))
                    <div class="alert-success">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert-danger">{{ session('error') }} @if (session('bentrok_link')) <a href="{{ session('bentrok_link') }}" style="color: inherit; font-weight: 600;">Lihat pendaftaran yang bentrok &rarr;</a> @endif</div>
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
        <x-profil-modal />
    @endauth
    @if (session('kredensial'))
        @include('partials.kredensial-dialog')
    @endif
    @if (isset($saMonitoring) && $saMonitoring['target'])
        <script>
            document.documentElement.classList.add('sa-lihat-saja');
            (function () {
                var blok = function (f) { return f.method === 'post' && !f.hasAttribute('data-sa-allow'); };
                var asli = HTMLFormElement.prototype.submit;
                HTMLFormElement.prototype.submit = function () { blok(this) ? alert('Mode lihat saja') : asli.call(this); };
                document.addEventListener('submit', function (e) {
                    if (blok(e.target)) { e.preventDefault(); alert('Mode lihat saja'); }
                }, true);
                var post = 'form[method="post" i]:not([data-sa-allow]) ';
                var sel = [post + 'input', post + 'select', post + 'textarea', post + 'button', 'input[type="file"]'].join(',');
                function kunci() {
                    var ada = false;
                    document.querySelectorAll(sel).forEach(function (el) {
                        ada = ada || el.type !== 'hidden';
                        if (el.disabled) return;
                        el.disabled = true;
                        el.title = 'Mode lihat saja';
                        if (el.type === 'file' && el.id) document.querySelectorAll('label[for="' + el.id + '"]').forEach(function (l) { l.setAttribute('aria-disabled', 'true'); });
                    });
                    var note = document.querySelector('[data-sa-lock]');
                    if (note) note.hidden = !ada;
                }
                document.addEventListener('click', function (e) {
                    if (e.target.closest('[aria-disabled="true"]')) e.preventDefault();
                }, true);
                document.addEventListener('DOMContentLoaded', function () {
                    kunci();
                    new MutationObserver(kunci).observe(document.body, { childList: true, subtree: true });
                });
            })();
        </script>
    @endif
    <script>
    // Live search: form GET ber-atribut data-live → hasil diganti via fetch tanpa reload.
    // Area yang diganti = [data-live-target]; listener halaman wajib pakai delegasi (document).
    // ponytail: ambil HTML halaman penuh lalu ambil potongannya, tanpa endpoint JSON terpisah.
    (function () {
        var ctrl, timer;
        function urlDari(form) {
            var p = new URLSearchParams();
            new FormData(form).forEach(function (v, k) { if (v !== '' && p.getAll(k).indexOf(v) < 0) p.append(k, v); });
            var q = p.toString();
            return form.action.split('?')[0] + (q ? '?' + q : '');
        }
        function muat(url, push) {
            var target = document.querySelector('[data-live-target]');
            if (!target) { location.href = url; return; }
            if (ctrl) ctrl.abort();
            ctrl = new AbortController();
            target.setAttribute('aria-busy', 'true');
            target.style.opacity = '.55';
            fetch(url, { signal: ctrl.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw r; return r.text(); })
                .then(function (html) {
                    var dok = new DOMParser().parseFromString(html, 'text/html');
                    var baru = dok.querySelector('[data-live-target]');
                    if (!baru) { location.href = url; return; }
                    target.replaceWith(baru);
                    // klik link/back: form (hidden input filter) ikut diganti biar sinkron; saat ngetik jangan (fokus hilang)
                    // ganti filter (change): form juga diganti (opsi yang tergantung filter, mis. pilihan per halaman), popover yang kebuka dibuka lagi
                    var formLama = document.querySelector('form[data-live]');
                    var formBaru = push !== undefined && formLama && dok.querySelector('form[data-live]');
                    var kunci = function (d, i) { return d.dataset.k || i; };
                    if (formBaru) {
                        var terbuka = Array.prototype.map.call(formLama.querySelectorAll('details'), function (d, i) { return d.open ? kunci(d, i) : null; });
                        formLama.replaceWith(formBaru);
                        formBaru.querySelectorAll('details').forEach(function (d, i) { if (terbuka.indexOf(kunci(d, i)) > -1) d.open = true; });
                    } else if (formLama) {
                        // saat ngetik form tetap; cuma badge, chip filter aktif & Reset yang disinkronkan
                        formLama.querySelectorAll('[data-live-sync]').forEach(function (el) {
                            var n = dok.querySelector('[data-live-sync="' + el.dataset.liveSync + '"]');
                            if (n) el.replaceWith(n);
                        });
                    }
                    if (push !== 'pop') history[push === true ? 'pushState' : 'replaceState'](null, '', url);
                })
                .catch(function (e) {
                    if (e && e.name === 'AbortError') return;
                    location.href = url;
                });
        }
        var teks = 'input[type=search], input[type=text]';
        document.addEventListener('input', function (e) {
            var form = e.target.closest('form[data-live]');
            if (!form || !e.target.matches(teks)) return;
            clearTimeout(timer);
            timer = setTimeout(function () { muat(urlDari(form)); }, 350);
        });
        document.addEventListener('change', function (e) {
            // e.target.form: ikut elemen di luar form yang pakai atribut form= (mis. select per di bawah paginasi)
            var form = e.target.form;
            if (!form || !form.matches('form[data-live]') || e.target.matches(teks)) return;
            Array.prototype.forEach.call(form.elements, function (el) { if (el !== e.target && el.tagName === 'SELECT' && el.name === e.target.name) el.value = e.target.value; });
            muat(urlDari(form), 'change');
        });
        document.addEventListener('submit', function (e) {
            var form = e.target.closest('form[data-live]');
            if (!form) return;
            e.preventDefault(); clearTimeout(timer); muat(urlDari(form));
        });
        // link paginasi & chip filter (area hasil, chip aktif & Reset di form) ikut AJAX
        document.addEventListener('click', function (e) {
            var a = e.target.closest('[data-live-target] a[href], form[data-live] a[href]');
            if (!a || e.ctrlKey || e.metaKey || e.shiftKey || a.target) return;
            var form = document.querySelector('form[data-live]');
            if (!form || new URL(a.href, location.href).pathname !== new URL(form.action, location.href).pathname) return;
            e.preventDefault();
            muat(a.href, true);
        });
        window.addEventListener('popstate', function () { if (document.querySelector('form[data-live]')) muat(location.href, 'pop'); });
        // BI.2 panel filter: tutup lewat klik di luar, tombol Tutup, atau Esc
        document.addEventListener('click', function (e) {
            document.querySelectorAll('details.fpanel[open]').forEach(function (d) {
                if (e.target.closest('[data-fpanel-tutup]') || !d.querySelector('.fpanel-isi').contains(e.target) && !d.querySelector('summary').contains(e.target)) d.open = false;
            });
        });
        document.addEventListener('keydown', function (e) {
            var d = e.key === 'Escape' && !document.querySelector('dialog[open]') && document.querySelector('details.fpanel[open]');
            if (d) { d.open = false; d.querySelector('summary').focus(); }
        });
    }());
    // BJ.1 input tag bebas (partials/tag-input): Enter/koma = chip; panel saran (per grup + tag lain dari DB) muncul saat fokus, tersaring sesuai ketikan.
    (function () {
        var timer, seq = 0;
        var norm = function (s) { return s.trim().replace(/^#+/, '').replace(/\s+/g, ' ').trim().slice(0, 30).trim(); };
        var nama = function (w) { return Array.prototype.map.call(w.querySelectorAll('.tag-input-box input[type=hidden]'), function (i) { return i.value.toLowerCase(); }); };
        var field = function (w) { return w.querySelector('.tag-input-field'); };
        function segarkan(w) {
            var ada = nama(w);
            w.querySelectorAll('[data-tag-saran]').forEach(function (b) { b.setAttribute('aria-pressed', ada.indexOf(b.dataset.tagSaran.toLowerCase()) > -1); });
            w.querySelector('[data-tag-hitung]').textContent = ada.length;
        }
        function tambah(w, n) {
            n = norm(n);
            if (!n || nama(w).indexOf(n.toLowerCase()) > -1) return;
            if (nama(w).length >= +w.dataset.max) return alert('Maksimal ' + w.dataset.max + ' tag.');
            var chip = document.createElement('span'), inp = document.createElement('input'), b = document.createElement('button');
            chip.className = 'tag-input-chip';
            inp.type = 'hidden'; inp.name = w.dataset.name; inp.value = n;
            b.type = 'button'; b.setAttribute('data-tag-hapus', ''); b.setAttribute('aria-label', 'Hapus tag ' + n); b.innerHTML = '<i class="ti ti-x" aria-hidden="true"></i>';
            chip.append('#' + n, inp, b);
            field(w).before(chip);
            segarkan(w);
        }
        function hapus(w, n) {
            w.querySelectorAll('.tag-input-box input[type=hidden]').forEach(function (i) { if (i.value.toLowerCase() === n.toLowerCase()) i.parentNode.remove(); });
            segarkan(w);
        }
        function saring(w) {
            var q = norm(field(w).value).toLowerCase(), panel = w.querySelector('.tag-panel'), lain = panel.querySelector('[data-tag-lain]');
            var bawaan = [];
            panel.querySelectorAll('.tag-panel-grup:not([data-tag-lain])').forEach(function (g) {
                var ada = false;
                g.querySelectorAll('[data-tag-saran]').forEach(function (b) {
                    bawaan.push(b.dataset.tagSaran.toLowerCase());
                    b.hidden = q && b.dataset.tagSaran.toLowerCase().indexOf(q) < 0;
                    ada = ada || !b.hidden;
                });
                g.hidden = !ada;
            });
            var isi = lain.querySelector('.tag-chips');
            clearTimeout(timer);
            if (!q) { isi.replaceChildren(); lain.hidden = true; kosong(); return; }
            timer = setTimeout(function () {
                fetch(w.dataset.cari + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (daftar) {
                    var ada = nama(w);
                    isi.replaceChildren.apply(isi, daftar.filter(function (n) { return bawaan.indexOf(n.toLowerCase()) < 0; }).map(function (n) {
                        var b = document.createElement('button');
                        b.type = 'button'; b.className = 'tag-chip'; b.dataset.tagSaran = n; b.textContent = '#' + n;
                        b.setAttribute('aria-pressed', ada.indexOf(n.toLowerCase()) > -1);
                        return b;
                    }));
                    lain.hidden = !isi.children.length;
                    kosong();
                }).catch(function () {});
            }, 200);
            kosong();
            function kosong() { panel.querySelector('.tag-panel-kosong').hidden = !!panel.querySelector('.tag-panel-grup:not([hidden])'); }
        }
        function buka(w) {
            var f = field(w), panel = w.querySelector('.tag-panel');
            if (document.querySelectorAll('[id="' + panel.id + '"]').length > 1) { panel.id = 'tagp-js' + (++seq); f.setAttribute('aria-controls', panel.id); }
            if (!panel.hidden) return;
            panel.hidden = false; f.setAttribute('aria-expanded', 'true');
            saring(w);
        }
        function tutup(w) {
            var panel = w && w.querySelector('.tag-panel');
            if (!panel || panel.hidden) return;
            panel.hidden = true; field(w).setAttribute('aria-expanded', 'false');
        }
        document.addEventListener('focusin', function (e) { if (e.target.matches('.tag-input-field')) buka(e.target.closest('[data-tag-input]')); });
        document.addEventListener('focusout', function (e) {
            var w = e.target.closest && e.target.closest('[data-tag-input]');
            if (w && !(e.relatedTarget && w.contains(e.relatedTarget))) tutup(w);
        });
        // tap chip di panel: jangan ambil fokus dari input, biar panel tetap terbuka
        document.addEventListener('pointerdown', function (e) {
            if (e.target.closest('.tag-panel')) e.preventDefault();
            document.querySelectorAll('[data-tag-input]').forEach(function (w) { if (!w.contains(e.target)) tutup(w); });
        });
        document.addEventListener('keydown', function (e) {
            if (!e.target.matches('.tag-input-field')) return;
            var w = e.target.closest('[data-tag-input]');
            if (e.key === 'Escape') { if (!w.querySelector('.tag-panel').hidden) { e.preventDefault(); e.stopPropagation(); tutup(w); } }
            else if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); tambah(w, e.target.value); e.target.value = ''; saring(w); }
            else if (e.key === 'Backspace' && !e.target.value) { var c = w.querySelectorAll('.tag-input-chip'); if (c.length) { c[c.length - 1].remove(); segarkan(w); } }
        }, true);
        document.addEventListener('input', function (e) {
            var f = e.target;
            if (!f.matches('.tag-input-field')) return;
            var w = f.closest('[data-tag-input]');
            if (f.value.indexOf(',') > -1) { var p = f.value.split(','); f.value = p.pop(); p.forEach(function (n) { tambah(w, n); }); }
            buka(w);
            saring(w);
        });
        document.addEventListener('click', function (e) {
            var b = e.target.closest('[data-tag-hapus], [data-tag-saran]');
            var w = b && b.closest('[data-tag-input]');
            if (!w) return;
            if (b.hasAttribute('data-tag-hapus')) { b.parentNode.remove(); segarkan(w); return; }
            if (b.getAttribute('aria-pressed') === 'true') hapus(w, b.dataset.tagSaran);
            else { tambah(w, b.dataset.tagSaran); if (field(w).value) { field(w).value = ''; saring(w); } }
        });
        document.addEventListener('submit', function (e) {
            e.target.querySelectorAll('.tag-input-field').forEach(function (f) { if (f.value.trim()) { tambah(f.closest('[data-tag-input]'), f.value); f.value = ''; } });
        }, true);
    }());
    </script>
    @stack('scripts')
</body>
</html>
