@extends('layouts.app')

@section('title', 'Tagihan Saya')

@section('content')
<div style="font-size: 18px; font-weight: 700; margin-bottom: 4px;">Tagihan & Invoice</div>
<p style="color: var(--text-secondary); margin: 0 0 20px; font-size: 13.5px;">Daftar proyek dan dokumen invoice resmi terkait akun Rumah Produksi / Client Anda.</p>

@forelse ($projects as $project)
    @php
        $tInfo = $tagihan[$project->id];
        $hasInvoice = $project->invoices->isNotEmpty();
    @endphp
    <div class="card" style="margin-bottom: 14px; padding: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
            <div style="flex: 1; min-width: 240px;">
                <div style="font-size: 15px; font-weight: 700; color: var(--text-primary);">
                    {{ $project->nama_produksi }}
                    <span class="kode-proyek" style="margin-left: 6px;">{{ $project->kode_proyek }}</span>
                </div>
                <div style="font-size: 12.5px; color: var(--text-muted); display: flex; align-items: center; flex-wrap: wrap; gap: 6px; margin-top: 6px;">
                    <span>{{ $project->namaClient() }}</span>
                    <span>&bull;</span>
                    <span>Status Tagihan:
                        <span class="badge {{ $tInfo['badge'] }}">
                            @if ($tInfo['label'] === 'Belum TTD')
                                Belum TTD (Menunggu Tanda Tangan)
                            @else
                                {{ $tInfo['label'] }}
                            @endif
                        </span>
                    </span>
                    @if ($tInfo['nominal'] !== null)
                        <span>&bull;</span>
                        <strong style="color: var(--text-primary);">Rp {{ number_format($tInfo['nominal'], 0, ',', '.') }}</strong>
                    @endif
                </div>
                @if (! $hasInvoice)
                    <div style="font-size: var(--fs-xs); color: var(--text-muted); margin-top: 6px;">
                        <i class="ti ti-info-circle"></i> Diterbitkan setelah kontrak & produksi berjalan
                    </div>
                @endif
            </div>

            <div>
                @if ($hasInvoice)
                    <a href="{{ route('invoices.show', $project) }}" class="btn btn-brand" style="white-space: nowrap;">
                        <i class="ti ti-file-text"></i> Lihat Invoice
                    </a>
                @else
                    <button type="button" class="btn btn-tonal" disabled style="white-space: nowrap; opacity: 0.6; cursor: not-allowed;" title="Diterbitkan setelah kontrak & produksi berjalan">
                        <i class="ti ti-receipt-off"></i> Invoice Belum Terbit
                    </button>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="card" style="text-align: center; color: var(--text-muted); padding: 36px 0;">
        <i class="ti ti-receipt" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
        Belum ada proyek yang terkait dengan akun Anda.
    </div>
@endforelse
@endsection
