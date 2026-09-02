@php
    $route = request()->path();
@endphp

<div class="sidebar-group-label">Aplikasi</div>
<a href="{{ url('/super-admin/dashboard') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/dashboard') ? 'active' : '' }}">
    <i class="ti ti-layout-dashboard"></i> Dashboard
</a>
<a href="{{ url('/super-admin/monitoring') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/monitoring') ? 'active' : '' }}">
    <i class="ti ti-eye"></i> Monitoring Akun
</a>

<div class="sidebar-group-label">Operasional</div>
<a href="{{ url('/super-admin/admins') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/admins') ? 'active' : '' }}">
    <i class="ti ti-users-group"></i> Kelola Admin
</a>
<a href="{{ url('/super-admin/casting-directors') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/casting-directors') ? 'active' : '' }}">
    <i class="ti ti-movie"></i> Kelola Casting Director
</a>
