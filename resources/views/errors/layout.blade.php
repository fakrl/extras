@extends('layouts.auth')

@section('title', trim($__env->yieldContent('judul')).' | JBTB Casting')

@section('content')
@php($masuk = rescue(fn () => auth()->check(), false, false))
<h1 class="auth-title">@yield('judul')</h1>
<p class="auth-subtitle">@yield('isi')</p>

@hasSection('muat-ulang')
    <a href="{{ url()->current() }}" class="btn-brand">Muat Ulang Halaman</a>
@else
    <a href="{{ $masuk ? route('dashboard') : route('home') }}" class="btn-brand">{{ $masuk ? 'Kembali ke Dashboard' : 'Kembali ke Beranda' }}</a>
@endif
@endsection
