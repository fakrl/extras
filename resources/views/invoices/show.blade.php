@extends('layouts.app')

@section('title', 'Invoice')

@php
    $isClient = auth()->user()->isClient();
    $sudahTtd = $isClient ? $invoice?->ttd_client_signature_path : $invoice?->ttd_admin_signature_path;
@endphp

@section('content')
<div style="font-size: 16px; font-weight: 600; margin-bottom: 2px;">Invoice Produksi: {{ $castingProject->nama_produksi }} <span class="kode-proyek">{{ $castingProject->kode_proyek }}</span></div>
<p style="color: var(--text-secondary); margin: 0 0 20px; font-size: 13.5px;">
    Client / PH: <strong>{{ $castingProject->namaClient() }}</strong>
</p>

@if (! $invoice)
    <div class="card">
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 6px;">Invoice belum tersedia</div>
        <p style="margin: 0; font-size: 13.5px; color: var(--text-secondary);">Invoice dibuat otomatis setelah ada kandidat yang dipilih Client.</p>
    </div>
@else
{{-- Model 1: Format Resmi JBTB --}}
<div class="card" style="margin-bottom: 16px;">
    <div style="font-size: 14.5px; font-weight: 600; margin-bottom: 10px;">
        <i class="ti ti-file-text"></i> Model 1: Invoice Resmi JBTB (Digital PDF)
    </div>
    <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 12px;">
        Invoice resmi ter-generate otomatis dengan kalkulasi kelas karakter, fee, dan tanda tangan digital sah dari kedua belah pihak.
    </p>

    <div style="display: flex; gap: 14px; margin-bottom: 14px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 180px; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-secondary);">Tanda Tangan Admin JBTB</div>
            <div style="font-weight: 600; margin-top: 4px;">
                @if ($invoice->ttd_admin_signature_path)
                    <span class="badge badge-aktif">Sudah Ditandatangani</span>
                @else
                    <span class="badge badge-pending">Menunggu TTD Admin</span>
                @endif
            </div>
        </div>
        <div style="flex: 1; min-width: 180px; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-secondary);">Tanda Tangan Client / PH</div>
            <div style="font-weight: 600; margin-top: 4px;">
                @if ($invoice->ttd_client_signature_path)
                    <span class="badge badge-aktif">Sudah Ditandatangani</span>
                @else
                    <span class="badge badge-pending">Menunggu TTD Client</span>
                @endif
            </div>
        </div>
    </div>

    @if ($invoice->pdf_path)
        <div style="background: rgba(34, 197, 94, 0.08); border: 1px solid var(--accent); border-radius: 8px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <div style="font-size: 13px; font-weight: 600;">
                <i class="ti ti-circle-check"></i> Invoice PDF Siap Diunduh
            </div>
            <a href="{{ route('invoices.download-pdf', $castingProject) }}" class="btn btn-brand btn-sm">
                <i class="ti ti-download"></i> Unduh Invoice PDF Resmi JBTB
            </a>
        </div>
    @endif
</div>

{{-- Model 2: Template Dokumen / Voucher Khusus Client PH --}}
<div class="card" style="margin-bottom: 16px;">
    <div style="font-size: 14.5px; font-weight: 600; margin-bottom: 10px;">
        <i class="ti ti-upload"></i> Model 2: Template / Voucher Dokumen Khusus PH
    </div>
    <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 12px;">
        Jika Production House memiliki format template invoice, voucher talent, atau purchase order (PO) sendiri, upload atau download dokumennya di sini.
    </p>

    @if ($invoice->custom_doc_path)
        <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 16px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <div>
                <div style="font-weight: 600; font-size: 13px;">Dokumen Khusus PH Tersedia</div>
                <div style="font-size: 12px; color: var(--text-muted);">Diperbarui: {{ $invoice->updated_at->translatedFormat('d F Y H:i') }}</div>
            </div>
            <a href="{{ route('invoices.download-custom', $castingProject) }}" class="btn btn-sm">
                <i class="ti ti-download"></i> Unduh Dokumen PH
            </a>
        </div>
    @endif

    <form method="POST" action="{{ route('invoices.upload-custom', $castingProject) }}" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
        @csrf
        <div style="flex: 1; min-width: 220px;">
            <label style="font-size: 12px; margin-bottom: 4px; display: block;">Upload Dokumen PH (PDF, DOCX, XLSX, max 10MB)</label>
            <input type="file" name="custom_doc" required accept=".pdf,.docx,.xlsx,.jpg,.jpeg,.png">
        </div>
        <button type="submit" class="btn btn-brand btn-sm">Upload Dokumen</button>
    </form>
</div>

{{-- Rincian Invoice --}}
<div class="card" style="margin-bottom: 16px;">
    <div style="font-size: 14.5px; font-weight: 600; margin-bottom: 10px;">Rincian Invoice</div>
    <div class="table-container">
    <table>
        <thead><tr><th>Peran / Kelas</th><th>Jumlah</th><th>Fee per Orang</th><th>Subtotal</th></tr></thead>
        <tbody>
            @forelse ($rincian->rows as $row)
                <tr>
                    <td>{{ $row->nama_kelas }}</td>
                    <td>{{ $row->kuota_kelas }}</td>
                    <td>Rp {{ number_format($row->budget_client, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($row->subtotal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center; color: var(--text-muted);">Belum ada kelas karakter.</td></tr>
            @endforelse
            <tr>
                <td colspan="3" style="text-align: right; font-weight: 600;">Total</td>
                <td style="font-weight: 600;">Rp {{ number_format($rincian->total, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    </div>
    <p style="font-size: 12px; color: var(--text-muted); margin: 10px 0 0;">Rincian final mengikuti kesepakatan.</p>
</div>

{{-- Form Tanda Tangan Digital --}}
@if (! $sudahTtd)
    <div class="card">
        <div style="font-size: 14.5px; font-weight: 600; margin-bottom: 10px;">
            Bubuhi Tanda Tangan Digital ({{ $isClient ? 'Client / PH' : 'Admin JBTB' }})
        </div>
        <form method="POST" action="{{ route('invoices.sign', $castingProject) }}">
            @csrf
            <x-signature-pad name="signature" />
            <button type="submit" class="btn btn-brand" style="margin-top: 10px;">Simpan Tanda Tangan</button>
        </form>
    </div>
@else
    <div class="alert-success">Kamu sudah menandatangani invoice ini.</div>
@endif
@endif
@endsection
