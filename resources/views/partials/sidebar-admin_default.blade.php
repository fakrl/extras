@php
    $route = request()->path();
    $isOperasionalActive = str_starts_with($route, 'admin/dashboard') 
        || str_starts_with($route, 'admin/projects') 
        || str_starts_with($route, 'admin/users') 
        || str_starts_with($route, 'admin/extras')
        || str_starts_with($route, 'admin/recap')
        || str_starts_with($route, 'admin/rekap-margin')
        || str_starts_with($route, 'admin/riwayat-kerja');
        
    $isPresensiActive = str_starts_with($route, 'admin/absensi') || str_starts_with($route, 'admin/attendances');
@endphp

<details class="sidebar-dropdown" {{ $isOperasionalActive ? 'open' : '' }}>
    <summary class="sidebar-dropdown-summary">
        <span><i class="ti ti-briefcase" style="margin-right: 6px;"></i> Operasional Proyek</span>
        <i class="ti ti-chevron-right chevron-icon"></i>
    </summary>
    <div class="sidebar-submenu">
        <a href="{{ url('/admin/dashboard') }}" class="sidebar-link {{ str_starts_with($route, 'admin/dashboard') ? 'active' : '' }}">
            <i class="ti ti-layout-dashboard"></i> Dashboard
        </a>
        <a href="{{ url('/admin/projects') }}" class="sidebar-link {{ str_starts_with($route, 'admin/projects') ? 'active' : '' }}">
            <i class="ti ti-movie"></i> Manajemen Proyek
        </a>
        <a href="{{ url('/admin/users') }}" class="sidebar-link {{ str_starts_with($route, 'admin/users') ? 'active' : '' }}">
            <i class="ti ti-users"></i> Kelola Akun
        </a>
        <a href="{{ url('/admin/recap') }}" class="sidebar-link {{ str_starts_with($route, 'admin/recap') ? 'active' : '' }}">
            <i class="ti ti-report"></i> Rekap Extras
        </a>
        <a href="{{ url('/admin/riwayat-kerja') }}" class="sidebar-link {{ str_starts_with($route, 'admin/riwayat-kerja') ? 'active' : '' }}">
            <i class="ti ti-history"></i> Riwayat Kerja
        </a>
    </div>
</details>

<details class="sidebar-dropdown" {{ $isPresensiActive ? 'open' : '' }}>
    <summary class="sidebar-dropdown-summary">
        <span><i class="ti ti-calendar-event" style="margin-right: 6px;"></i> Presensi & Jadwal</span>
        <i class="ti ti-chevron-right chevron-icon"></i>
    </summary>
    <div class="sidebar-submenu">
        <a href="{{ url('/admin/absensi') }}" class="sidebar-link {{ str_starts_with($route, 'admin/absensi') ? 'active' : '' }}">
            <i class="ti ti-clipboard-check"></i> Presensi Lapangan
        </a>
    </div>
</details>
