{{-- BD.4: daftar aksi per akun, dipakai di kebab baris & dialog Kelola kartu Extras. Param: u --}}
@php $buka = fn ($id) => "document.getElementById('{$id}-{$u->id}').showModal()"; @endphp
@if ($u->is_protected)
    <span class="akun-menu-item is-muted"><i class="ti ti-lock"></i> Akun dilindungi</span>
@elseif ($u->trashed())
    <button type="button" class="akun-menu-item is-brand" onclick="{{ $buka('restore-dialog') }}"><i class="ti ti-restore"></i> Restore</button>
@else
    <a href="{{ route('super-admin.admins.show', $u) }}" class="akun-menu-item"><i class="ti ti-id"></i> Lihat detail</a>
    @if ($u->role === 'extras' && $u->extrasProfile)
        <a href="{{ route('admin.extras.profil', $u) }}" class="akun-menu-item" data-profil-modal data-aksi-url="{{ route('super-admin.admins.show', $u) }}" data-aksi-label="Lihat detail"><i class="ti ti-user"></i> Lihat profil</a>
    @endif
    <button type="button" class="akun-menu-item" onclick="{{ $buka('edit-user-dialog') }}"><i class="ti ti-edit"></i> Edit</button>
    <form method="POST" action="{{ route('super-admin.admins.reset-password', $u) }}" onsubmit="return confirm('Reset password akun {{ $u->name }}? Password baru tampil sekali.')">
        @csrf
        <button type="submit" class="akun-menu-item"><i class="ti ti-key"></i> Reset password</button>
    </form>
    <button type="button" class="akun-menu-item" onclick="{{ $buka('toggle-dialog') }}"><i class="ti ti-power"></i> {{ $u->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
    <button type="button" class="akun-menu-item is-danger" onclick="{{ $buka('hapus-dialog') }}"><i class="ti ti-trash"></i> Hapus</button>
@endif
