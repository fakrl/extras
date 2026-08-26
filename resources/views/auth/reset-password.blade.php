@extends('layouts.auth')

@section('title', 'Atur Ulang Password — SIM Casting JBTB')

@section('content')
<h1 class="auth-title">Atur Ulang Password</h1>
<p class="auth-subtitle">Masukkan password baru untuk akunmu.</p>

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

    <label>Email</label>
    <input type="email" name="email" value="{{ old('email', $email) }}" required autofocus>

    <x-password-input name="password" label="Password Baru" :minlength="8" />
    <x-password-input name="password_confirmation" label="Konfirmasi Password Baru" :minlength="8" />

    <button type="submit" class="btn-brand">Simpan Password Baru</button>
</form>
@endsection
