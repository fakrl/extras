@extends('layouts.auth')

@section('title', 'Atur Ulang Kata Sandi | JBTB Casting')

@section('content')
<h1 class="auth-title">Atur Ulang Kata Sandi</h1>
<p class="auth-subtitle">Masukkan kata sandi baru untuk akunmu.</p>

@if ($errors->any())
    <div class="alert-danger">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <label for="reset-email">Email <span class="wajib" aria-hidden="true">*</span></label>
    <input type="email" id="reset-email" name="email" value="{{ old('email', $email) }}" required autofocus autocomplete="email">

    <x-password-input name="password" label="Kata Sandi Baru" :minlength="8" hint="Minimal 8 karakter." autocomplete="new-password" />
    <x-password-input name="password_confirmation" label="Konfirmasi Kata Sandi Baru" :minlength="8" autocomplete="new-password" />

    <button type="submit" class="btn-brand">Simpan Kata Sandi Baru</button>
</form>
@endsection
