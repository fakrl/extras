@extends('layouts.app')

@section('title', 'Ubah Kata Sandi')

@section('content')
<div style="max-width:480px;">
    <h2 style="margin-bottom:20px; font-size:18px; font-weight:600;">Ubah Kata Sandi</h2>

    <div class="card" style="padding:24px;">
        <form method="POST" action="{{ route('ubah-password.update') }}">
            @csrf

            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:6px; font-size:13px; font-weight:500;">Kata Sandi Saat Ini</label>
                <input type="password" name="current_password"
                    class="{{ $errors->has('current_password') ? 'input-error' : '' }}"
                    style="width:100%; padding:9px 12px; border-radius:7px; border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:14px;">
                @error('current_password')
                    <span style="color:var(--danger); font-size:12px; margin-top:4px; display:block;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:6px; font-size:13px; font-weight:500;">Kata Sandi Baru</label>
                <input type="password" name="new_password"
                    class="{{ $errors->has('new_password') ? 'input-error' : '' }}"
                    style="width:100%; padding:9px 12px; border-radius:7px; border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:14px;">
                @error('new_password')
                    <span style="color:var(--danger); font-size:12px; margin-top:4px; display:block;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:6px; font-size:13px; font-weight:500;">Konfirmasi Kata Sandi Baru</label>
                <input type="password" name="new_password_confirmation"
                    style="width:100%; padding:9px 12px; border-radius:7px; border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:14px;">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; min-height:40px;">Simpan Kata Sandi</button>
        </form>
    </div>
</div>
@endsection
