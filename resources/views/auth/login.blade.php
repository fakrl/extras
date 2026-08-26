@extends('layouts.auth')

@section('title', 'Masuk — SIM Casting JBTB')

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

<form method="POST" action="{{ route('login') }}">
    @csrf
    <label>Email</label>
    <input type="email" name="email" value="{{ old('email') }}" required autofocus>

    <x-password-input name="password" label="Password" />

    <div style="display: flex; justify-content: space-between; align-items: center; margin: -8px 0 16px;">
        <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 0; font-weight: 400;">
            <input type="checkbox" name="remember" style="width: auto; min-height: auto; margin-bottom: 0;">
            Ingat saya
        </label>
        <a href="{{ route('password.request') }}" style="font-size: 13px;">Lupa password?</a>
    </div>

    <button type="submit" class="btn-brand">Masuk</button>
</form>

<hr>
<p class="auth-footer">
    Belum punya akun Extras? <a href="{{ route('register') }}">Daftar di sini</a>
</p>
<p class="auth-footer" style="margin-top: 8px; font-size: 12px;">
    <a href="{{ route('privacy-policy') }}">Kebijakan Privasi</a>
</p>
@endsection
