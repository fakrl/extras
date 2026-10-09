{{-- CE: tombol "Undang ke proyek…" khusus Admin/SA; isi dialog dimuat dari admin.undangan.form (layout memasang #undang-dialog). Param: user (Extras) --}}
@if (auth()->user()?->bisaSebagaiAdmin() && $user?->isExtras() && $user->extrasProfile)
    <button type="button" class="btn btn-sm" data-undang="{{ route('admin.undangan.form', $user) }}"><i class="ti ti-user-plus" aria-hidden="true"></i> Undang ke proyek…</button>
@endif
