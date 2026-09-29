{{-- BH.2: toggle tampil di beranda (Admin/SA). Param: profile --}}
@if (auth()->user()?->bisaSebagaiAdmin() && $profile)
    @php $bisa = $profile->izin_tampil_publik || $profile->tampil_di_beranda; @endphp
    <form method="POST" action="{{ route('admin.extras.beranda', $profile->user_id) }}" style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        @csrf @method('PATCH')
        <span @unless ($bisa) title="Extras belum mengizinkan foto & profilnya tampil di website" @endunless>
            <button type="submit" @class(['btn btn-sm', 'btn-brand' => ! $profile->tampil_di_beranda]) @disabled(! $bisa) aria-pressed="{{ $profile->tampil_di_beranda ? 'true' : 'false' }}">
                <i class="ti {{ $profile->tampil_di_beranda ? 'ti-eye-off' : 'ti-home-star' }}" aria-hidden="true"></i>
                {{ $profile->tampil_di_beranda ? 'Sembunyikan dari beranda' : 'Tampilkan di beranda' }}
            </button>
        </span>
        <span style="font-size: var(--fs-xs); color: var(--text-muted);">
            {{ $profile->tampil_di_beranda ? 'Sedang tampil di beranda' : ($profile->izin_tampil_publik ? 'Extras sudah mengizinkan' : 'Belum ada izin dari Extras') }}
        </span>
    </form>
@endif
