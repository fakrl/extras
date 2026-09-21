@extends('layouts.app')

@section('title', 'Greenlight')

@section('content')
<div style="font-size: 16px; font-weight: 600; margin-bottom: 2px;">Greenlight</div>
<p style="color: var(--text-secondary); margin: 0 0 16px; font-size: 13.5px;">
    Daftar proyek casting. Pilih proyek untuk melihat dan meninjau kandidat.
</p>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Proyek</th>
                <th>Menunggu</th>
                <th>Approved</th>
                <th>Rejected</th>
                <th>Total</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($proyek as $item)
                <tr>
                    <td>{{ $item['proyek']->nama_produksi }}</td>
                    <td><span class="badge badge-pending">{{ $item['menunggu'] }}</span></td>
                    <td><span class="badge badge-aktif">{{ $item['approved'] }}</span></td>
                    <td><span class="badge badge-tolak">{{ $item['rejected'] }}</span></td>
                    <td>{{ $item['total'] }}</td>
                    <td>
                        <a href="{{ route('cd.reviews.show', $item['proyek']) }}" class="btn btn-sm">Lihat Kandidat</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px 0;">
                        Belum ada proyek yang kamu handle.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
