@php
    $route = request()->path();
@endphp

<div class="sidebar-group-label">Aplikasi</div>
<a href="{{ url('/admin/dashboard') }}" class="sidebar-link {{ str_starts_with($route, 'admin/dashboard') ? 'active' : '' }}">
    <i class="ti ti-layout-dashboard"></i> Dashboard
</a>
<a href="{{ url('/admin/riwayat-kerja') }}" class="sidebar-link {{ str_starts_with($route, 'admin/riwayat-kerja') ? 'active' : '' }}">
    <i class="ti ti-history"></i> Riwayat Kerja
</a>

<div class="sidebar-group-label">Operasional</div>
<a href="{{ url('/admin/users') }}" class="sidebar-link {{ str_starts_with($route, 'admin/users') ? 'active' : '' }}">
    <i class="ti ti-users"></i> Kelola Akun
</a>
<a href="{{ url('/admin/projects') }}" class="sidebar-link {{ str_starts_with($route, 'admin/projects') ? 'active' : '' }}">
    <i class="ti ti-movie"></i> Callsheet
</a>
<a href="{{ url('/admin/recap') }}" class="sidebar-link {{ str_starts_with($route, 'admin/recap') ? 'active' : '' }}">
    <i class="ti ti-report"></i> Rekap Extras
</a>
<a href="{{ url('/admin/absensi') }}" class="sidebar-link {{ str_starts_with($route, 'admin/absensi') ? 'active' : '' }}">
    <i class="ti ti-clipboard-check"></i> Absensi
</a>
