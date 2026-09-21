@php
    $route = request()->path();
    $isAppMonitoringActive = str_starts_with($route, 'super-admin/dashboard') || str_starts_with($route, 'super-admin/monitoring') || str_starts_with($route, 'super-admin/activity-logs');
    $isSettingsUsersActive = str_starts_with($route, 'super-admin/admins') || str_starts_with($route, 'super-admin/casting-directors');
@endphp

<details class="sidebar-dropdown" {{ $isAppMonitoringActive ? 'open' : '' }}>
    <summary class="sidebar-dropdown-summary">
        <span><i class="ti ti-layout-dashboard" style="margin-right: 6px;"></i> Aplikasi & Monitoring</span>
        <i class="ti ti-chevron-right chevron-icon"></i>
    </summary>
    <div class="sidebar-submenu">
        <a href="{{ url('/super-admin/dashboard') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/dashboard') ? 'active' : '' }}">
            <i class="ti ti-layout-dashboard"></i> Dashboard
        </a>
        <a href="{{ url('/super-admin/monitoring') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/monitoring') ? 'active' : '' }}">
            <i class="ti ti-eye"></i> Monitoring Akun
        </a>
        <a href="{{ route('super-admin.activity-logs') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/activity-logs') ? 'active' : '' }}">
            <i class="ti ti-activity"></i> Log Aktivitas (Audit)
        </a>
    </div>
</details>

<details class="sidebar-dropdown" {{ $isSettingsUsersActive ? 'open' : '' }}>
    <summary class="sidebar-dropdown-summary">
        <span><i class="ti ti-settings" style="margin-right: 6px;"></i> Pengaturan & Pengguna</span>
        <i class="ti ti-chevron-right chevron-icon"></i>
    </summary>
    <div class="sidebar-submenu">
        <a href="{{ url('/super-admin/admins') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/admins') ? 'active' : '' }}">
            <i class="ti ti-users-group"></i> Kelola Staf & Client
        </a>
    </div>
</details>
