@extends('layouts.app')

@section('title', 'Rekap Margin')

@section('content')
<div class="card-header-row">
    <div style="font-size: 16px; font-weight: 600;">Rekap Margin per Proyek</div>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Proyek</th>
                <th>Fee Client</th>
                <th>Payout Extras</th>
                <th>Margin</th>
                <th>Margin %</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($projects as $row)
                <tr>
                    <td>{{ $row->project->nama_produksi }}</td>
                    <td>Rp {{ number_format($row->total_fee_client, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($row->total_payout, 0, ',', '.') }}</td>
                    <td>
                        Rp {{ number_format($row->margin, 0, ',', '.') }}
                        @if ($row->belum_terklasifikasi)
                            <span class="badge badge-pending" title="Termasuk data belum terklasifikasi, lihat rincian di bawah">⚠</span>
                        @endif
                    </td>
                    <td>{{ number_format($row->margin_persen, 1) }}%</td>
                </tr>
                @if ($row->breakdown->count() > 1 || $row->belum_terklasifikasi)
                    @foreach ($row->breakdown as $kelas)
                        <tr style="color: var(--text-secondary); font-size: 12.5px;">
                            <td style="padding-left: 24px;">&mdash; {{ $kelas->kelas->nama_kelas }} ({{ $kelas->jumlah_aplikasi }} orang)</td>
                            <td>Rp {{ number_format($kelas->total_fee_client, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($kelas->total_payout, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($kelas->margin, 0, ',', '.') }}</td>
                            <td>&mdash;</td>
                        </tr>
                    @endforeach
                    @if ($row->belum_terklasifikasi)
                        <tr style="color: var(--warning); background: rgba(240,185,11,0.1); font-size: 12.5px;">
                            <td style="padding-left: 24px;">
                                &mdash; Belum terklasifikasi ({{ $row->belum_terklasifikasi->jumlah_aplikasi }} orang)
                                <span class="badge badge-pending">⚠ Data belum lengkap</span>
                            </td>
                            <td>Rp 0</td>
                            <td>Rp {{ number_format($row->belum_terklasifikasi->total_payout, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format(-$row->belum_terklasifikasi->total_payout, 0, ',', '.') }}</td>
                            <td>&mdash;</td>
                        </tr>
                    @endif
                @endif
            @empty
                <tr><td colspan="5" style="text-align:center; color: var(--text-muted);">Belum ada proyek casting.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
