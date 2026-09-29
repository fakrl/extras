@php
    $route = request()->path();
    $isKelolaAkunActive = str_starts_with($route, 'super-admin/admins')
        || str_starts_with($route, 'super-admin/casting-directors');
    $isAdminMenuActive = str_starts_with($route, 'admin/') && ! str_starts_with($route, 'admin/projects');
@endphp

<div class="sidebar-group-label">Aplikasi & Monitoring</div>
<a href="{{ url('/super-admin/dashboard') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/dashboard') ? 'active' : '' }}">
    <i class="ti ti-layout-dashboard"></i> Dashboard
</a>
<a href="{{ url('/super-admin/monitoring') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/monitoring') ? 'active' : '' }}">
    <i class="ti ti-eye"></i> Monitoring Akun
</a>
<a href="{{ route('super-admin.activity-logs') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/activity-logs') ? 'active' : '' }}">
    <i class="ti ti-activity"></i> Log Aktivitas
</a>
<a href="{{ route('admin.projects.index') }}" class="sidebar-link {{ str_starts_with($route, 'admin/projects') ? 'active' : '' }}">
    <i class="ti ti-wallet"></i> Proyek &amp; Keuangan
</a>

<div class="sidebar-group-label">Manajemen Akun</div>
<details class="sidebar-dropdown" {{ $isKelolaAkunActive ? 'open' : '' }}>
    <summary class="sidebar-dropdown-summary">
        <span><i class="ti ti-users-group" style="margin-right: 6px;"></i> Kelola Akun</span>
        <i class="ti ti-chevron-right chevron-icon"></i>
    </summary>
    <div class="sidebar-submenu">
        <a href="{{ route('super-admin.admins.index', ['role' => 'admin']) }}" class="sidebar-link {{ request()->is('super-admin/admins*') && request()->get('role') === 'admin' ? 'active' : '' }}">
            <i class="ti ti-shield"></i> Admin
        </a>
        <a href="{{ route('super-admin.admins.index', ['role' => 'korlap']) }}" class="sidebar-link {{ request()->is('super-admin/admins*') && request()->get('role') === 'korlap' ? 'active' : '' }}">
            <i class="ti ti-map-pin"></i> Korlap
        </a>
        <a href="{{ route('super-admin.admins.index', ['role' => 'client']) }}" class="sidebar-link {{ request()->is('super-admin/admins*') && request()->get('role') === 'client' ? 'active' : '' }}">
            <i class="ti ti-building"></i> Client
        </a>
        <a href="{{ route('super-admin.admins.index', ['role' => 'extras']) }}" class="sidebar-link {{ request()->is('super-admin/admins*') && request()->get('role') === 'extras' ? 'active' : '' }}">
            <i class="ti ti-user-star"></i> Extras
        </a>
    </div>
</details>

<div class="sidebar-group-label">Operasional</div>
<details class="sidebar-dropdown" {{ $isAdminMenuActive ? 'open' : '' }}>
    <summary class="sidebar-dropdown-summary">
        <span><i class="ti ti-settings" style="margin-right: 6px;"></i> Admin</span>
        <i class="ti ti-chevron-right chevron-icon"></i>
    </summary>
    <div class="sidebar-submenu">
        <a href="{{ route('admin.recap.index') }}" class="sidebar-link {{ str_starts_with($route, 'admin/recap') ? 'active' : '' }}">
            <i class="ti ti-report"></i> Rekap Extras
        </a>
        <a href="{{ route('admin.work-history') }}" class="sidebar-link {{ str_starts_with($route, 'admin/riwayat-kerja') ? 'active' : '' }}">
            <i class="ti ti-history"></i> Riwayat Kerja
        </a>
        <a href="{{ route('admin.attendance.index') }}" class="sidebar-link {{ str_starts_with($route, 'admin/absensi') || str_starts_with($route, 'admin/attendances') ? 'active' : '' }}">
            <i class="ti ti-clipboard-check"></i> Absensi Lapangan
        </a>
    </div>
</details>

<div class="sidebar-group-label">Monitoring</div>
<details class="sidebar-dropdown" {{ str_starts_with($route, 'super-admin/sebagai') ? 'open' : '' }}>
    <summary class="sidebar-dropdown-summary">
        <span><i class="ti ti-eye" style="margin-right: 6px;"></i> Monitoring</span>
        <i class="ti ti-chevron-right chevron-icon"></i>
    </summary>
    <div class="sidebar-submenu">
        @foreach (['admin' => 'ti-shield', 'korlap' => 'ti-map-pin', 'client' => 'ti-building', 'extras' => 'ti-user-star'] as $m => $ikon)
            <a href="{{ route('super-admin.mode.pilih', $m) }}" class="sidebar-link {{ $route === 'super-admin/sebagai/'.$m ? 'active' : '' }}">
                <i class="ti {{ $ikon }}"></i> {{ \App\Models\User::LABELS[$m] }}
            </a>
        @endforeach
    </div>
</details>
