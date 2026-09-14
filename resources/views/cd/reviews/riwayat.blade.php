@extends('layouts.app')

@section('title', 'Riwayat Keputusan')

@section('content')
<div style="font-size: 16px; font-weight: 600; margin-bottom: 2px;">Riwayat Keputusan</div>
<p style="color: var(--text-secondary); margin: 0 0 16px; font-size: 13.5px;">
    Proyek yang pernah kamu review kandidatnya.
</p>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Proyek</th>
                <th>Approve</th>
                <th>Reject</th>
                <th>Review Terakhir</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($byProyek as $item)
                <tr>
                    <td>{{ $item['proyek']->nama_produksi }}</td>
                    <td>
                        <span class="badge badge-aktif">{{ $item['jumlah_approve'] }}</span>
                    </td>
                    <td>
                        <span class="badge badge-tolak">{{ $item['jumlah_reject'] }}</span>
                    </td>
                    <td style="color:var(--text-secondary); font-size:13px;">
                        {{ \Carbon\Carbon::parse($item['tanggal_terakhir'])->format('d M Y') }}
                    </td>
                    <td>
                        <a href="{{ route('cd.riwayat.show', $item['proyek']) }}" class="btn btn-sm">Lihat Kandidat</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:var(--text-muted); padding:20px 0;">
                        Belum ada riwayat keputusan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
