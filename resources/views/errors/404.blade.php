@extends('layouts.auth')

@section('title', 'Halaman Tidak Ditemukan | SIM Casting JBTB')

@section('content')
<h1 class="auth-title">Halaman Tidak Ditemukan</h1>
<p class="auth-subtitle">URL yang kamu buka tidak tersedia atau sudah dipindahkan.</p>

<a href="{{ auth()->check() ? url('/') : route('login') }}" class="btn-brand" style="display: block; text-align: center; text-decoration: none; line-height: 46px;">Kembali ke Beranda</a>
@endsection
