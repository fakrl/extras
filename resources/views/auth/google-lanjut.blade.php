@extends('layouts.auth')

@section('title', 'Daftar dengan Google | JBTB Casting')

@section('content')
<h1 class="auth-title">Satu langkah lagi</h1>
<p class="auth-subtitle">Kamu akan daftar sebagai Extras dengan akun Google <strong>{{ $google['email'] }}</strong>.</p>

@if ($errors->any())
    <div class="alert-danger">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('google.daftar') }}">
    @csrf
    <div class="checkbox-row">
        <label for="setuju_privasi">
            <input type="checkbox" name="setuju_privasi" id="setuju_privasi" required>
            <span>Saya sudah membaca dan menyetujui <a href="{{ route('privacy-policy') }}" target="_blank">Kebijakan Privasi</a>. <span class="wajib" aria-hidden="true">*</span></span>
        </label>
    </div>

    <button type="submit" class="btn-brand">Buat Akun Extras</button>
</form>

<hr>
<p class="auth-footer">
    Bukan akun ini? <a href="{{ route('login') }}">Kembali ke halaman masuk</a>
</p>
@endsection
