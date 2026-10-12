@extends('layouts.auth')

@section('title', 'Lupa Kata Sandi | JBTB Casting')

@section('content')
<h1 class="auth-title">Lupa Kata Sandi</h1>
<p class="auth-subtitle">Masukkan email atau username akunmu, kami kirim link untuk atur ulang kata sandi.</p>

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
    <label for="forgot-email">Email atau username <span class="wajib" aria-hidden="true">*</span></label>
    <input type="text" id="forgot-email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">

    <button type="submit" class="btn-brand">Kirim Link Reset</button>
</form>

<hr>
<p class="auth-footer">
    <a href="{{ route('login') }}">Kembali ke halaman masuk</a>
</p>
@endsection
