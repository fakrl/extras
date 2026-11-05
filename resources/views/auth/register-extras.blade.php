@extends('layouts.auth')

@section('title', 'Daftar Extras | JBTB Casting')

@section('content')
<h1 class="auth-title">Daftar sebagai Extras</h1>
<p class="auth-subtitle">Setelah daftar, kamu bisa melengkapi profil dan mulai apply lowongan casting.</p>

@include('partials.google-tombol')

<form method="POST" action="{{ route('register') }}" novalidate>
    @csrf
    <label for="reg-name">Nama Lengkap <span class="wajib" aria-hidden="true">*</span></label>
    <input type="text" id="reg-name" name="name" value="{{ old('name') }}" required autocomplete="name" @class(['input-error' => $errors->has('name')])>
    @error('name')<span class="field-error">{{ $message }}</span>@enderror

    <label for="reg-email">Email <span class="wajib" aria-hidden="true">*</span></label>
    <input type="email" id="reg-email" name="email" value="{{ old('email') }}" required autocomplete="email" @class(['input-error' => $errors->has('email')])>
    @error('email')<span class="field-error">{{ $message }}</span>@enderror

    <x-password-input name="password" label="Kata Sandi" :minlength="8" hint="Minimal 8 karakter." autocomplete="new-password" />
    <x-password-input name="password_confirmation" label="Konfirmasi Kata Sandi" :minlength="8" autocomplete="new-password" />

    <div class="checkbox-row">
        <label for="setuju_privasi">
            <input type="checkbox" name="setuju_privasi" id="setuju_privasi" required>
            <span>Saya sudah membaca dan menyetujui <a href="{{ route('privacy-policy') }}" target="_blank">Kebijakan Privasi</a> dan <a href="{{ route('syarat-ketentuan') }}" target="_blank">Syarat &amp; Ketentuan</a>. <span class="wajib" aria-hidden="true">*</span></span>
        </label>
        @error('setuju_privasi')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <button type="submit" class="btn-brand">Daftar</button>
</form>

<hr>
<p class="auth-footer">
    Sudah punya akun? <a href="{{ route('login') }}">Masuk</a>
</p>
@endsection
