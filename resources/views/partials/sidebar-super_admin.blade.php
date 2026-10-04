@php
    $route = request()->path();
    // BD.8: menu final SA. Menu lama (Monitoring Akun, Kelola Akun, Keuangan, dropdown Admin) sudah digabung,
    // route-nya redirect; menu operasional Admin/Korlap dibuka lewat Monitoring.
    $isAkunActive = str_starts_with($route, 'super-admin/akun') || str_starts_with($route, 'super-admin/admins');
@endphp

<a href="{{ route('super-admin.dashboard') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/dashboard') ? 'active' : '' }}">
    <i class="ti ti-layout-dashboard"></i> Dashboard
</a>
<a href="{{ route('super-admin.akun.index') }}" class="sidebar-link {{ $isAkunActive ? 'active' : '' }}">
    <i class="ti ti-users-group"></i> Manajemen Akun
</a>
<a href="{{ route('admin.projects.index') }}" class="sidebar-link {{ str_starts_with($route, 'admin/projects') ? 'active' : '' }}">
    <i class="ti ti-movie"></i> Proyek
</a>
<a href="{{ route('super-admin.activity-logs') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/activity-logs') ? 'active' : '' }}">
    <i class="ti ti-activity"></i> Log Aktivitas
</a>

<details class="sidebar-dropdown" {{ str_starts_with($route, 'super-admin/sebagai') || str_starts_with($route, 'super-admin/monitoring') ? 'open' : '' }}>
    <summary class="sidebar-dropdown-summary">
        <span><i class="ti ti-eye" style="margin-right: 6px;"></i> Monitoring</span>
        <i class="ti ti-chevron-right chevron-icon"></i>
    </summary>
    <div class="sidebar-submenu">
        {{-- BL.3: Admin/Korlap ke pratinjau, Client/Extras ke pemilih akun --}}
        @foreach (['admin' => 'ti-shield', 'korlap' => 'ti-map-pin', 'client' => 'ti-building', 'extras' => 'ti-user-star'] as $m => $ikon)
            @php $pratinjau = in_array($m, ['admin', 'korlap'], true); @endphp
            <a href="{{ $pratinjau ? route('super-admin.monitoring.'.$m) : route('super-admin.mode.pilih', $m) }}" class="sidebar-link {{ $route === ($pratinjau ? 'super-admin/monitoring/' : 'super-admin/sebagai/').$m ? 'active' : '' }}">
                <i class="ti {{ $ikon }}"></i> {{ \App\Models\User::LABELS[$m] }}
            </a>
        @endforeach
    </div>
</details>
