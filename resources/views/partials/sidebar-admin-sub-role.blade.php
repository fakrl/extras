@php
    $route = request()->path();
@endphp

<div class="sidebar-group-label">Aplikasi</div>
<a href="{{ url('/admin/dashboard') }}" class="sidebar-link {{ str_starts_with($route, 'admin/dashboard') ? 'active' : '' }}">
    <i class="ti ti-layout-dashboard"></i> Dashboard
</a>

<div class="sidebar-group-label">Operasional</div>
<a href="{{ url('/admin/riwayat-kerja') }}" class="sidebar-link {{ str_starts_with($route, 'admin/riwayat-kerja') ? 'active' : '' }}">
    <i class="ti ti-history"></i> Riwayat Kerja
</a>
