@extends('layouts.app')

@section('title', 'Pilih Kandidat')

@push('styles')
<style>
    @media (max-width: 640px) {
        .pk-tabel, .pk-tabel tbody, .pk-tabel tr, .pk-tabel td { display: block; width: 100%; }
        .pk-tabel thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
        .pk-tabel tr { padding: 12px 14px; border-bottom: 1px solid var(--border-color); }
        .pk-tabel td { border: 0; padding: 4px 0; white-space: normal !important; }
        .pk-tabel td[data-label]::before { content: attr(data-label) ": "; color: var(--text-muted); font-size: var(--fs-xs); }
        .pk-tabel td:last-child { padding-top: 8px; }
        .pk-tabel td:last-child .btn { width: 100%; }
    }
</style>
@endpush

@section('content')
<div style="font-size: 16px; font-weight: 600; margin-bottom: 2px;">Pilih Kandidat</div>
<p style="color: var(--text-secondary); margin: 0 0 16px; font-size: 13.5px;">
    Daftar proyek casting. Pilih proyek untuk melihat dan meninjau kandidat.
</p>

@if ($statusFilter)
    @php($labelFilter = ['menunggu' => 'ada kandidat menunggu dipilih', 'approved' => 'ada kandidat dipilih', 'rejected' => 'ada kandidat ditolak'][$statusFilter])
    <p style="margin: -8px 0 16px; font-size: 13px;">Menampilkan proyek yang {{ $labelFilter }}. <a href="{{ route('client.reviews.index') }}">Hapus filter</a></p>
@endif

<div class="card">
    <div class="table-container">
    <table class="pk-tabel">
        <thead>
            <tr>
                <th>Proyek</th>
                <th>Menunggu</th>
                <th>Dipilih</th>
                <th>Ditolak</th>
                <th>Total</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($proyek as $item)
                <tr>
                    <td>{{ $item['proyek']->nama_produksi }}</td>
                    <td data-label="Menunggu"><span class="badge badge-netral">{{ $item['menunggu'] }}</span></td>
                    <td data-label="Dipilih"><span class="badge badge-aktif">{{ $item['approved'] }}</span></td>
                    <td data-label="Ditolak"><span class="badge badge-tolak">{{ $item['rejected'] }}</span></td>
                    <td data-label="Total">{{ $item['total'] }}</td>
                    <td>
                        <a href="{{ route('client.reviews.show', $item['proyek']) }}" class="btn btn-sm">Lihat Kandidat</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px 0;">
                        Belum ada proyek yang Anda tangani.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
