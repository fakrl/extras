@extends('layouts.auth')

@section('title', 'Lupa Password — SIM Casting JBTB')

@section('content')
<h1 class="auth-title">Lupa Password</h1>
<p class="auth-subtitle">Masukkan email akunmu, kami kirim link untuk atur ulang password.</p>

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

<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <label>Email</label>
    <input type="email" name="email" value="{{ old('email') }}" required autofocus>

    <button type="submit" class="btn-brand">Kirim Link Reset</button>
</form>

<hr>
<p class="auth-footer">
    <a href="{{ route('login') }}">Kembali ke halaman masuk</a>
</p>
@endsection
