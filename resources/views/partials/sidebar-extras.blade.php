@php
    $route = request()->path();
@endphp

<div class="sidebar-group-label">Aplikasi</div>
<a href="{{ url('/extras/dashboard') }}" class="sidebar-link {{ str_starts_with($route, 'extras/dashboard') ? 'active' : '' }}">
    <i class="ti ti-layout-dashboard"></i> Dashboard
</a>
<a href="{{ url('/extras/profil') }}" class="sidebar-link {{ str_starts_with($route, 'extras/profil') ? 'active' : '' }}">
    <i class="ti ti-user-circle"></i> Profil Saya
</a>

<div class="sidebar-group-label">Operasional</div>
<a href="{{ url('/extras/lowongan') }}" class="sidebar-link {{ str_starts_with($route, 'extras/lowongan') ? 'active' : '' }}">
    <i class="ti ti-briefcase"></i> Lowongan Casting
</a>
