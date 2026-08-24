@extends('layouts.auth')

@section('title', 'Masuk — SIM Casting JBTB')

@section('content')
<h1 class="auth-title">Masuk</h1>
<p class="auth-subtitle">Masuk ke akunmu untuk melanjutkan.</p>

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

    <label>Password</label>
    <input type="password" name="password" required>

    <button type="submit" class="btn-brand">Masuk</button>
</form>

<hr>
<p class="auth-footer">
    Belum punya akun Extras? <a href="{{ route('register') }}">Daftar di sini</a>
</p>
@endsection
