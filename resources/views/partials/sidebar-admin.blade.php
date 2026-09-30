@php
    $route = request()->path();
@endphp

<div class="sidebar-group-label">Operasional Proyek</div>
<a href="{{ url('/admin/dashboard') }}" class="sidebar-link {{ str_starts_with($route, 'admin/dashboard') ? 'active' : '' }}">
    <i class="ti ti-layout-dashboard"></i> Dashboard
</a>
<a href="{{ url('/admin/projects') }}" class="sidebar-link {{ str_starts_with($route, 'admin/projects') ? 'active' : '' }}">
    <i class="ti ti-movie"></i> Proyek &amp; Keuangan
</a>
<details class="sidebar-dropdown" {{ str_starts_with($route, 'admin/akun') ? 'open' : '' }}>
    <summary class="sidebar-dropdown-summary">
        <span><i class="ti ti-users" style="margin-right: 6px;"></i> Kelola Akun</span>
        <i class="ti ti-chevron-right chevron-icon"></i>
    </summary>
    <div class="sidebar-submenu">
        <a href="{{ route('admin.akun.extras') }}" class="sidebar-link {{ str_starts_with($route, 'admin/akun/extras') ? 'active' : '' }}">
            <i class="ti ti-user-star"></i> Extras
        </a>
    </div>
</details>
<a href="{{ url('/admin/riwayat-kerja') }}" class="sidebar-link {{ str_starts_with($route, 'admin/riwayat-kerja') ? 'active' : '' }}">
    <i class="ti ti-history"></i> Riwayat Kerja
</a>

<div class="sidebar-group-label">Lapangan</div>
<a href="{{ url('/admin/absensi') }}" class="sidebar-link {{ str_starts_with($route, 'admin/absensi') || str_starts_with($route, 'admin/attendances') ? 'active' : '' }}">
    <i class="ti ti-clipboard-check"></i> Absensi Lapangan
</a>
