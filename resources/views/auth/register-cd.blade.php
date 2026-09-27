@extends('layouts.auth')

@section('title', 'Registrasi Client | SIM Casting JBTB')

@section('content')
<h1 class="auth-title">Registrasi Client</h1>
<p class="auth-subtitle">
    Halaman ini khusus untuk Client yang diundang oleh PT. JBTB Casting Creative Group.
    Akun yang dibuat lewat halaman ini otomatis berperan sebagai Client.
</p>

@if ($errors->any())
    <div class="alert-danger">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('register.cd') }}">
    @csrf
    <label>Nama Lengkap</label>
    <input type="text" name="name" value="{{ old('name') }}" required>

    <label>Email</label>
    <input type="email" name="email" value="{{ old('email') }}" required>

    <x-password-input name="password" label="Password" :minlength="8" />
    <x-password-input name="password_confirmation" label="Konfirmasi Password" :minlength="8" />

    <div class="checkbox-row">
        <input type="checkbox" name="setuju_privasi" id="setuju_privasi" required>
        <label for="setuju_privasi">
            Saya sudah membaca dan menyetujui <a href="{{ route('privacy-policy') }}" target="_blank">Kebijakan Privasi</a>.
        </label>
    </div>

    <button type="submit" class="btn-brand">Daftar sebagai Client</button>
</form>
@endsection
