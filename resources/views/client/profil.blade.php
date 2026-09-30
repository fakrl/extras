@extends('layouts.app')

@section('title', 'Profil')

@section('content')
<div class="card" style="max-width: 520px;">
    <div class="card-title">Profil Client</div>
    <form method="POST" action="{{ route('client.profil.update') }}">
        @csrf @method('PUT')
        <label>Username</label>
        <input type="text" value="{{ $user->username }}" disabled>

        <label>Nama <span class="wajib" aria-hidden="true">*</span></label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="255">

        <label>Nama perusahaan / PH</label>
        <input type="text" name="nama_perusahaan" value="{{ old('nama_perusahaan', $user->nama_perusahaan) }}" maxlength="255">

        <label>Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="Untuk notifikasi & lupa password">

        <label>Nomor WA</label>
        <input type="tel" name="nomor_wa" value="{{ old('nomor_wa', $user->nomor_wa) }}" maxlength="20" placeholder="08xxxxxxxxxx">

        <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: space-between; align-items: center;">
            <a href="{{ route('ubah-password') }}">Ganti password</a>
            <button type="submit" class="btn btn-brand">Simpan</button>
        </div>
    </form>
</div>
@endsection
