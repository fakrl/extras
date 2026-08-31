@include('partials.sidebar-admin-sub-role')

@php $route = request()->path(); @endphp
<div class="sidebar-group-label">Lapangan</div>
<a href="{{ url('/admin/absensi') }}" class="sidebar-link {{ str_starts_with($route, 'admin/absensi') ? 'active' : '' }}">
    <i class="ti ti-clipboard-check"></i> Absensi
</a>
