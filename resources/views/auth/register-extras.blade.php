@extends('layouts.auth')

@section('title', 'Daftar Extras | JBTB Casting')

@section('content')
<h1 class="auth-title">Daftar sebagai Extras</h1>
<p class="auth-subtitle">Setelah daftar, kamu bisa melengkapi profil dan mulai apply lowongan casting.</p>

@if ($errors->any())
    <div class="alert-danger">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

@include('partials.google-tombol')

<form method="POST" action="{{ route('register') }}">
    @csrf
    <label for="reg-name">Nama Lengkap <span class="wajib" aria-hidden="true">*</span></label>
    <input type="text" id="reg-name" name="name" value="{{ old('name') }}" required autocomplete="name">

    <label for="reg-email">Email <span class="wajib" aria-hidden="true">*</span></label>
    <input type="email" id="reg-email" name="email" value="{{ old('email') }}" required autocomplete="email">

    <x-password-input name="password" label="Kata Sandi" :minlength="8" hint="Minimal 8 karakter." autocomplete="new-password" />
    <x-password-input name="password_confirmation" label="Konfirmasi Kata Sandi" :minlength="8" autocomplete="new-password" />

    <div class="checkbox-row">
        <label for="setuju_privasi">
            <input type="checkbox" name="setuju_privasi" id="setuju_privasi" required>
            <span>Saya sudah membaca dan menyetujui <a href="{{ route('privacy-policy') }}" target="_blank">Kebijakan Privasi</a>. <span class="wajib" aria-hidden="true">*</span></span>
        </label>
    </div>

    <button type="submit" class="btn-brand">Daftar</button>
</form>

<hr>
<p class="auth-footer">
    Sudah punya akun? <a href="{{ route('login') }}">Masuk</a>
</p>
@endsection
