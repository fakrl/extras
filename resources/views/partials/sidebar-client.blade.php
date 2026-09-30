@php
    $route = request()->path();
@endphp

<div class="sidebar-group-label">Aplikasi</div>
<a href="{{ url('/client/dashboard') }}" class="sidebar-link {{ str_starts_with($route, 'client/dashboard') ? 'active' : '' }}">
    <i class="ti ti-layout-dashboard"></i> Dashboard
</a>

<div class="sidebar-group-label">Operasional</div>
<a href="{{ route('client.projects.request') }}" class="sidebar-link {{ request()->routeIs('client.projects.request*') ? 'active' : '' }}">
    <i class="ti ti-folder-plus"></i> Ajukan Proyek
</a>
<a href="{{ url('/client/reviews') }}" class="sidebar-link {{ str_starts_with($route, 'client/reviews') ? 'active' : '' }}">
    <i class="ti ti-clipboard-check"></i> Greenlight
</a>
<a href="{{ url('/client/jadwal') }}" class="sidebar-link {{ str_starts_with($route, 'client/jadwal') ? 'active' : '' }}">
    <i class="ti ti-calendar-event"></i> Jadwal
</a>
<a href="{{ route('invoices.index-client') }}" class="sidebar-link {{ str_starts_with($route, 'invoice') ? 'active' : '' }}">
    <i class="ti ti-receipt"></i> Tagihan
</a>
<a href="{{ route('client.profil') }}" class="sidebar-link {{ request()->routeIs('client.profil*') ? 'active' : '' }}">
    <i class="ti ti-user"></i> Profil
</a>
