@extends('layouts.auth')

@section('title', 'Daftar Extras — SIM Casting JBTB')

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

<form method="POST" action="{{ route('register') }}">
    @csrf
    <label>Nama Lengkap</label>
    <input type="text" name="name" value="{{ old('name') }}" required>

    <label>Email</label>
    <input type="email" name="email" value="{{ old('email') }}" required>

    <label>Password</label>
    <input type="password" name="password" required minlength="8">

    <label>Konfirmasi Password</label>
    <input type="password" name="password_confirmation" required minlength="8">

    <button type="submit" class="btn-brand">Daftar</button>
</form>

<hr>
<p class="auth-footer">
    Sudah punya akun? <a href="{{ route('login') }}">Masuk</a>
</p>
@endsection
