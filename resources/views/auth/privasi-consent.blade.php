@extends('layouts.app')

@section('title', 'Setujui Kebijakan Privasi & Syarat Ketentuan')

@section('content')
<div style="max-width:480px;">
    <h2 style="margin-bottom:20px; font-size:18px; font-weight:600;">Satu langkah lagi</h2>
    <p style="font-size:13px; color:var(--text-secondary); margin:0 0 16px;">Sebelum lanjut, baca dan setujui dulu Kebijakan Privasi dan Syarat & Ketentuan.</p>

    <div class="card" style="padding:24px;">
        @if ($errors->any())
            <div class="alert-danger" style="margin-bottom:16px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('privasi.consent.store') }}">
            @csrf
            <div style="margin-bottom:20px;">
                <label style="display:flex; gap:8px; align-items:flex-start; font-weight:400;">
                    <input type="checkbox" name="setuju_privasi" id="setuju_privasi" required style="margin-top:3px;">
                    <span>Saya sudah membaca dan menyetujui <a href="{{ route('privacy-policy') }}" target="_blank">Kebijakan Privasi</a> dan <a href="{{ route('syarat-ketentuan') }}" target="_blank">Syarat & Ketentuan</a>. <span class="wajib" aria-hidden="true">*</span></span>
                </label>
            </div>
            <button type="submit" class="btn btn-brand" style="width:100%;">Lanjut</button>
        </form>
    </div>
</div>
@endsection
