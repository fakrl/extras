@extends('layouts.auth')

@section('title', 'Registrasi Casting Director — SIM Casting JBTB')

@section('content')
<h1 class="auth-title">Registrasi Casting Director</h1>
<p class="auth-subtitle">
    Halaman ini khusus untuk Casting Director yang diundang oleh PT. JBTB Casting Creative Group.
    Akun yang dibuat lewat halaman ini otomatis berperan sebagai Casting Director.
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

    <label>Password</label>
    <input type="password" name="password" required minlength="8">

    <label>Konfirmasi Password</label>
    <input type="password" name="password_confirmation" required minlength="8">

    <button type="submit" class="btn-brand">Daftar sebagai Casting Director</button>
</form>
@endsection
