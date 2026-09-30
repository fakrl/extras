@php
    $route = request()->path();
@endphp

<div class="sidebar-group-label">Menu</div>
<a href="{{ url('/extras/lowongan') }}" class="sidebar-link {{ str_starts_with($route, 'extras/lowongan') ? 'active' : '' }}">
    <i class="ti ti-microphone"></i> Casting Call
</a>
<a href="{{ url('/extras/dashboard') }}" class="sidebar-link is-utama {{ str_starts_with($route, 'extras/dashboard') ? 'active' : '' }}" aria-label="Beranda">
    <i class="ti ti-home"></i> Beranda
</a>
<a href="{{ url('/extras/profil') }}" class="sidebar-link {{ str_starts_with($route, 'extras/profil') ? 'active' : '' }}">
    <i class="ti ti-user-circle"></i> Profil Saya
</a>
