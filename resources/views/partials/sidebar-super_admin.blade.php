@php
    $route = request()->path();
    // BD.8: menu final SA. Menu lama (Monitoring Akun, Kelola Akun, Keuangan, dropdown Admin) sudah digabung,
    // route-nya redirect; menu operasional Admin/Korlap dibuka lewat Monitoring.
    $isAkunActive = str_starts_with($route, 'super-admin/akun') || str_starts_with($route, 'super-admin/admins');
    // CR.3.3: titik merah = masih ada yang perlu diputuskan/ditindak.
    $sbMenungguKeputusan = \App\Models\CastingProject::where('client_request_status', 'menunggu_acc')->count()
        + \App\Models\CastingProject::tanpaPicClient()->count()
        + \App\Models\User::where('role', 'client')->where('wajib_ganti_password', true)->where('updated_at', '<=', now()->subDays(7))->count();
    // Titik merah "ada tugas tertunda" harus pakai definisi sama dengan
    // pantauAdmin()/tahapan() (perlu=true), BUKAN AdminRingkasan::untuk() (dipakai role lain,
    // tidak boleh disentuh). 1 query agregat (tahapan(null) sekali, bukan loop per-Admin).
    $adminAktifIds = \App\Models\User::where('role', \App\Models\User::ROLE_ADMIN)->where('status', 'aktif')->pluck('id');
    $sbPantauTotal = collect(\App\Support\AdminRingkasan::tahapan(null, PHP_INT_MAX))
        ->flatMap(fn ($t) => $t['daftar'])
        ->contains(fn ($i) => $i['perlu'] && $adminAktifIds->contains($i['app']->castingProject?->admin_id));
@endphp

<a href="{{ route('super-admin.dashboard') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/dashboard') ? 'active' : '' }}">
    <i class="ti ti-layout-dashboard"></i> Dashboard
</a>
<a href="{{ route('super-admin.persetujuan') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/persetujuan') ? 'active' : '' }}">
    <i class="ti ti-circle-check"></i> Persetujuan
    @if ($sbMenungguKeputusan)<span class="sb-dot" aria-label="Ada yang perlu diputuskan"></span>@endif
</a>
<a href="{{ route('super-admin.pantau-admin.index') }}" class="sidebar-link {{ str_starts_with($route, 'super-admin/pantau-admin') ? 'active' : '' }}">
    <i class="ti ti-users"></i> Pantau Admin
    @if ($sbPantauTotal)<span class="sb-dot" aria-label="Ada tugas tertunda"></span>@endif
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
        <span><i class="ti ti-eye sb-ikon-mr"></i> Monitoring</span>
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
