{{-- CE: tombol "Undang ke proyek…" khusus Admin/SA; isi dialog dimuat dari admin.undangan.form (layout memasang #undang-dialog). Param: user (Extras). AD-33: tidak untuk akun nonaktif/melanggar atau profil belum lengkap (termasuk mangkrak). --}}
@if (auth()->user()?->bisaSebagaiAdmin() && $user?->isExtras() && $user->extrasProfile && ! $user->diblokir() && $user->extrasProfile->profilLengkap())
    <button type="button" class="btn btn-sm" data-undang="{{ route('admin.undangan.form', $user) }}"><i class="ti ti-user-plus" aria-hidden="true"></i> Undang ke proyek…</button>
@endif
