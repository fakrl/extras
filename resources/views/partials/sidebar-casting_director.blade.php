@php
    $route = request()->path();
@endphp

<div class="sidebar-group-label">Aplikasi</div>
<a href="{{ url('/cd/dashboard') }}" class="sidebar-link {{ str_starts_with($route, 'cd/dashboard') ? 'active' : '' }}">
    <i class="ti ti-layout-dashboard"></i> Dashboard
</a>

<div class="sidebar-group-label">Operasional</div>
<a href="{{ url('/cd/reviews') }}" class="sidebar-link {{ str_starts_with($route, 'cd/reviews') ? 'active' : '' }}">
    <i class="ti ti-clipboard-check"></i> Greenlight
</a>
