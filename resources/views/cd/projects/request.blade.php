@extends('layouts.app')

@section('title', 'Ajukan Permintaan Proyek')

@section('content')
<div class="card-header-row">
    <div>
        <div style="font-size: 16px; font-weight: 600;">Ajukan Permintaan Proyek Baru</div>
        <p style="color: var(--text-muted); font-size: 13px; margin: 2px 0 0;">
            Kirimkan brief kebutuhan talent extras untuk ditinjau dan disetujui oleh Super Admin JBTB.
        </p>
    </div>
</div>

<div class="card" style="margin-bottom: 24px; max-width: 720px;">
    @if ($errors->any())
        <div class="alert-danger" style="margin-bottom: 14px;">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('cd.projects.request.store') }}" enctype="multipart/form-data">
        @csrf
        <label>Nama Produksi / Judul Film / Iklan <span style="color: red;">*</span></label>
        <input type="text" name="nama_produksi" value="{{ old('nama_produksi') }}" placeholder="Contoh: Kado Untuk Ibu / Iklan Bank Mandiri" required>

        <label>Rumah Produksi (Production House) <span style="color: red;">*</span></label>
        <input type="text" name="client_ph" value="{{ old('client_ph', auth()->user()->name) }}" placeholder="Contoh: Starvision Plus" required>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div>
                <label>Estimasi Deadline Perekrutan <span style="color: red;">*</span></label>
                <input type="date" name="deadline" value="{{ old('deadline') }}" min="{{ date('Y-m-d') }}" required>
            </div>
            <div>
                <label>Perkiraan Total Kuota Extras <span style="color: red;">*</span></label>
                <input type="number" name="kuota" min="1" value="{{ old('kuota', 10) }}" required>
            </div>
        </div>

        <label>Brief Kebutuhan Karakter & Catatan Tambahan <span style="color: red;">*</span></label>
        <textarea name="brief_catatan" rows="4" placeholder="Jelaskan kebutuhan peran (misal: 10 ibu-ibu pasar look Jawa, 5 bapak-bapak pos ronda), estimasi tanggal take kamera, dan lokasi syuting..." required>{{ old('brief_catatan') }}</textarea>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div>
                <label>Poster Acuan (Opsional)</label>
                <input type="file" name="poster_path" accept="image/*">
            </div>
            <div>
                <label>Cover Banner (Opsional)</label>
                <input type="file" name="cover_path" accept="image/*">
            </div>
        </div>

        <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-brand">Ajukan Permintaan Proyek</button>
        </div>
    </form>
</div>

@if ($myRequests->isNotEmpty())
    <div class="card">
        <div class="card-title">Riwayat Pengajuan Permintaan Proyek Anda</div>
        <div class="table-container">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); text-align: left;">
                        <th style="padding: 10px 8px;">Judul Produksi</th>
                        <th style="padding: 10px 8px;">PH</th>
                        <th style="padding: 10px 8px;">Kuota</th>
                        <th style="padding: 10px 8px;">Deadline</th>
                        <th style="padding: 10px 8px;">Status Pengajuan</th>
                        <th style="padding: 10px 8px;">Tanggal Diajukan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($myRequests as $req)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 10px 8px; font-weight: 600;">{{ $req->nama_produksi }}</td>
                            <td style="padding: 10px 8px;">{{ $req->client_ph }}</td>
                            <td style="padding: 10px 8px;">{{ $req->kuota }} orang</td>
                            <td style="padding: 10px 8px;">{{ $req->deadline?->format('d/m/Y') }}</td>
                            <td style="padding: 10px 8px;">
                                @if ($req->client_request_status === 'disetujui')
                                    <span class="badge badge-aktif">Disetujui Super Admin</span>
                                @elseif ($req->client_request_status === 'ditolak')
                                    <span class="badge badge-tolak">Ditolak</span>
                                @else
                                    <span class="badge badge-pending">Menunggu ACC</span>
                                @endif
                            </td>
                            <td style="padding: 10px 8px; font-size: 12.5px; color: var(--text-muted);">
                                {{ $req->created_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
