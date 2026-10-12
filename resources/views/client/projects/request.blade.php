@extends('layouts.app')

@section('title', 'Ajukan Permintaan Proyek')

@section('content')
<div class="card-header-row">
    <div>
        <div style="font-size: 16px; font-weight: 600;">Ajukan Permintaan Proyek Baru</div>
        <p style="color: var(--text-muted); font-size: 13px; margin: 2px 0 0;">
            Kirimkan brief kebutuhan talent extras untuk ditinjau dan disetujui oleh tim JBTB.
        </p>
    </div>
</div>

<div class="card" style="margin-bottom: 24px; max-width: 720px;">
    <form method="POST" action="{{ route('client.projects.request.store') }}" enctype="multipart/form-data">
        @csrf
        <p class="wajib-ket"><span class="wajib">*</span> wajib diisi</p>
        <label>Nama Produksi / Judul Film / Iklan <span class="wajib" aria-hidden="true">*</span></label>
        <input type="text" name="nama_produksi" value="{{ old('nama_produksi') }}" placeholder="Contoh: Kado Untuk Ibu / Iklan Bank Mandiri" required>

        <p class="field-hint">Rumah Produksi: <strong>{{ auth()->user()->nama_perusahaan ?: auth()->user()->name }}</strong> (ubah di <a href="{{ route('client.profil') }}">Profil</a>).</p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div>
                <label>Estimasi Deadline Perekrutan <span class="wajib" aria-hidden="true">*</span></label>
                <input type="date" name="deadline" value="{{ old('deadline') }}" min="{{ date('Y-m-d') }}" required>
            </div>
            <div>
                <label>Perkiraan Total Kuota Extras <span class="wajib" aria-hidden="true">*</span></label>
                <input type="number" name="kuota" min="1" value="{{ old('kuota', 10) }}" required>
            </div>
        </div>

        <label>Brief Kebutuhan Karakter & Catatan Tambahan <span class="wajib" aria-hidden="true">*</span></label>
        <textarea name="brief_catatan" rows="4" placeholder="Jelaskan kebutuhan peran (misal: 10 ibu-ibu pasar look Jawa, 5 bapak-bapak pos ronda), estimasi tanggal take kamera, dan lokasi syuting..." required>{{ old('brief_catatan') }}</textarea>

        <label>Poster Acuan</label>
        <input type="file" name="poster_path" accept="image/*">

        <label for="req-files">Lampiran Dokumen (bisa pilih beberapa)</label>
        <input type="file" id="req-files" name="files[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
        <div style="font-size: var(--fs-xs); color: var(--text-muted);">PDF, Word, Excel, JPG, PNG · maks 10 MB per file</div>

        <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-brand">Ajukan Permintaan Proyek</button>
        </div>
    </form>
</div>

@if ($myRequests->isNotEmpty())
    <div class="card">
        <div class="card-title">Riwayat Pengajuan Permintaan Proyek Anda</div>
        @foreach ($myRequests as $req)
            <div style="padding: 12px 0; border-top: 1px solid var(--border-color);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; flex-wrap: wrap;">
                    <div style="font-weight: 600;">{{ $req->nama_produksi }}</div>
                    @if ($req->client_request_status === 'disetujui')
                        <span class="badge badge-aktif">Disetujui tim JBTB</span>
                    @elseif ($req->client_request_status === 'ditolak')
                        <span class="badge badge-tolak">Ditolak</span>
                    @else
                        <span class="badge badge-pending">Menunggu ACC</span>
                    @endif
                </div>
                <div style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">
                    {{ $req->namaClient() }} · {{ $req->kuota }} orang · Batas pendaftaran {{ $req->deadline?->translatedFormat('d M Y') }}
                </div>
                <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 2px;">Diajukan {{ $req->created_at->translatedFormat('d M Y H:i') }}</div>
                @if ($req->client_request_status === 'ditolak' && $req->alasan_tolak)
                    <div style="font-size: 13px; color: var(--danger); margin-top: 4px;">Alasan: {{ $req->alasan_tolak }}</div>
                @endif
            </div>
        @endforeach
    </div>
@endif
@endsection
