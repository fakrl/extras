@extends('layouts.app')

@section('title', 'Lengkapi KTP & Rekening')

@section('content')
<div class="card" style="max-width: 480px; margin: 0 auto;">
    <div style="font-size: 17px; font-weight: 600; margin-bottom: 4px;">Lengkapi Data untuk Kontrak</div>
    <p style="color: var(--text-secondary); font-size: 13px; margin-bottom: 20px; line-height: 1.5;">
        Selamat, kamu lolos untuk proyek {{ $application->castingProject->nama_produksi }}! Data ini dibutuhkan
        untuk kontrak & transfer pembayaran, dan hanya dilihat Admin — tidak ditampilkan ke pihak lain.
    </p>

    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif
    @if (session('info'))
        <div class="alert-info">{{ session('info') }}</div>
    @endif
    @if (session('error'))
        <div class="alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('extras.kontrak.simpan-ktp', $application) }}">
        @csrf

        <div class="form-row">
            <div>
                <label>NIK (16 digit) <span class="required-mark">*</span></label>
                <input type="text" name="nik" value="{{ old('nik') }}" required
                       inputmode="numeric" pattern="\d{16}" maxlength="16" placeholder="Sesuai KTP">
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>Nomor Rekening Penerima</label>
                <input type="text" name="rekening" value="{{ old('rekening') }}"
                       placeholder="Contoh: BCA 1234567890 a.n Nama Kamu">
                <p class="field-hint">Kosongkan kalau sudah pernah diisi sebelumnya dan tidak berubah.</p>
            </div>
        </div>

        <button type="submit" class="btn btn-brand" style="width: 100%; margin-top: 8px;">Simpan & Lanjut ke Kontrak</button>
    </form>
</div>
@endsection
