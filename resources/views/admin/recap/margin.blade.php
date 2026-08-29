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
                    <td>Rp {{ number_format($row->margin, 0, ',', '.') }}</td>
                    <td>{{ number_format($row->margin_persen, 1) }}%</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; color: var(--text-muted);">Belum ada proyek casting.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
