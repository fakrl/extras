@php $label = \App\Models\User::LABELS[$mode]; @endphp
<div class="mon-kepala">
    <p class="dash-sub" style="margin: 0; font-size: 13.5px;"><i class="ti ti-eye"></i> {{ $teks }}</p>
    <x-confirm-form action="{{ route('super-admin.mode.mulai') }}" :message="'Ambil alih sebagai '.$label.'? Aksi yang kamu lakukan setelah ini nyata dan tercatat atas nama Anda.'" id="mon-masuk" style="margin: 0;">
        <input type="hidden" name="mode" value="{{ $mode }}">
        <input type="hidden" name="kembali" value="/{{ request()->path() }}">
        <button type="submit" class="btn btn-brand"><i class="ti ti-login-2"></i> Ambil alih sebagai {{ $label }}</button>
    </x-confirm-form>
</div>
