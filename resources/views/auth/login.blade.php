@extends('layouts.auth')

@section('title', 'Masuk | JBTB Casting')

@section('content')
<h1 class="auth-title">Masuk</h1>
<p class="auth-subtitle">Masuk ke akunmu untuk melanjutkan.</p>

@if (session('status'))
    <div class="alert-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="alert-danger">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

@include('partials.google-tombol')

<form method="POST" action="{{ route('login') }}">
    @csrf
    <label for="login-email">Email atau Username <span class="wajib" aria-hidden="true">*</span></label>
    <input type="text" id="login-email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
           placeholder="email@contoh.com atau username">

    <x-password-input name="password" label="Kata Sandi" autocomplete="current-password" />

    <div class="auth-opsi">
        <label for="login-remember">
            <input type="checkbox" id="login-remember" name="remember">
            Ingat saya
        </label>
        <a href="{{ route('password.request') }}">Lupa kata sandi?</a>
    </div>

    <button type="submit" class="btn-brand">Masuk</button>
</form>

<hr>
<p class="auth-footer">
    Belum punya akun Extras? <a href="{{ route('register') }}">Daftar di sini</a>
</p>
<p class="auth-footer" style="margin-top: 8px; font-size: var(--fs-xs);">
    <a href="{{ route('privacy-policy') }}">Kebijakan Privasi</a>
</p>
@endsection
