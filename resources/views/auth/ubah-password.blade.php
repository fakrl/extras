@extends('layouts.app')

@section('title', 'Ubah Kata Sandi')

@section('content')
<div style="max-width:480px;">
    <h2 style="margin-bottom:20px; font-size:18px; font-weight:600;">Ubah Kata Sandi</h2>

    <div class="card" style="padding:24px;">
        <form method="POST" action="{{ route('ubah-password.update') }}">
            @csrf

            @if (auth()->user()->password)
            <div style="margin-bottom:16px;">
                <label for="current-password-field">Kata Sandi Saat Ini <span class="wajib" aria-hidden="true">*</span></label>
                <input type="password" name="current_password" id="current-password-field" required autocomplete="current-password"
                    class="{{ $errors->has('current_password') ? 'input-error' : '' }}" style="margin-bottom:0;">
                <span id="cp-hint" style="font-size:12px; margin-top:4px; display:block;"></span>
                @error('current_password')
                    <span style="color:var(--danger); font-size:12px; margin-top:4px; display:block;">{{ $message }}</span>
                @enderror
            </div>
            @else
            <p style="font-size:13px; color:var(--text-secondary); margin:0 0 16px;">Akunmu dibuat lewat Google dan belum punya kata sandi. Buat kata sandi supaya bisa masuk tanpa Google juga.</p>
            @endif

            <div style="margin-bottom:16px;">
                <label for="new-password-field">Kata Sandi Baru <span class="wajib" aria-hidden="true">*</span></label>
                <input type="password" name="new_password" id="new-password-field" required minlength="8" autocomplete="new-password" aria-describedby="new-password-hint"
                    class="{{ $errors->has('new_password') ? 'input-error' : '' }}" style="margin-bottom:0;">
                <span id="new-password-hint" class="field-hint" style="display:block; margin:4px 0 0;">Minimal 8 karakter.</span>
                @error('new_password')
                    <span style="color:var(--danger); font-size:12px; margin-top:4px; display:block;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom:20px;">
                <label for="new-password-confirm-field">Konfirmasi Kata Sandi Baru <span class="wajib" aria-hidden="true">*</span></label>
                <input type="password" name="new_password_confirmation" id="new-password-confirm-field" required autocomplete="new-password" style="margin-bottom:0;">
            </div>

            <button type="submit" class="btn btn-brand" style="width:100%;">Simpan Kata Sandi</button>
        </form>
    </div>
    @include('partials.google-akun')
</div>
@push('scripts')
<script>
(function () {
    var timer;
    var hint = document.getElementById('cp-hint');
    var field = document.getElementById('current-password-field');
    if (!field) return;
    field.addEventListener('input', function () {
        var val = this.value;
        clearTimeout(timer);
        if (!val) { hint.textContent = ''; return; }
        timer = setTimeout(function () {
            fetch('{{ route('ubah-password.validate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ password: val }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.valid) {
                    hint.textContent = '';
                } else {
                    hint.style.color = 'var(--danger)';
                    hint.textContent = 'Kata sandi saat ini tidak cocok';
                }
            });
        }, 500);
    });
}());
</script>
@endpush

@endsection
