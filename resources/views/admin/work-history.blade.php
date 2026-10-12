@extends('layouts.app')

@section('title', 'Riwayat & Honor Saya')

@section('content')
@if (auth()->user()->isSuperAdmin() && ! auth()->user()->modeSa())
    <div class="card">
        <div class="alert-info" style="margin: 0 0 12px;">Super Admin tidak punya honor penugasan sendiri. Rekap honor dan riwayat kerja staf ada di halaman detail akun masing-masing.</div>
        <a href="{{ route('super-admin.akun.index') }}" class="btn btn-brand">Buka Manajemen Akun</a>
    </div>
@else
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Halaman ini hanya untuk dilihat. Nominal dan status ditentukan oleh Super Admin.
</p>

<div class="card" style="margin-bottom: 20px;">
    <div style="font-size: 15px; font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
        <i class="ti ti-briefcase" style="color: var(--accent);"></i> Riwayat Honor Pokok Penugasan Proyek
    </div>
    <div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Proyek</th>
                <th>Status Penugasan</th>
                <th>Honor Pokok</th>
                <th>Add-on / Reimburse</th>
                <th>Total Honor</th>
                <th>Status Bayar</th>
                <th>Slip</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assignments as $a)
                @php
                    $addonTotal = $a->payroll?->addons->sum('nominal') ?? 0;
                @endphp
                <tr>
                    <td style="font-weight: 600;">{{ $a->castingProject->nama_produksi }}</td>
                    <td>
                        <span class="badge {{ $a->status_log === 'selesai' ? 'badge-aktif' : 'badge-info' }}">
                            {{ ucfirst($a->status_log) }}
                        </span>
                    </td>
                    <td>
                        @if ($a->payroll)
                            Rp {{ number_format($a->payroll->nominal_pokok, 0, ',', '.') }}
                        @else
                            <span style="color: var(--text-muted);">Belum dihitung</span>
                        @endif
                    </td>
                    <td>
                        @if ($addonTotal > 0)
                            <span style="color: var(--accent-strong); font-weight: 500;">+Rp {{ number_format($addonTotal, 0, ',', '.') }}</span>
                        @else
                            <span style="color: var(--text-muted);">-</span>
                        @endif
                    </td>
                    <td style="font-weight: 600;">
                        @if ($a->payroll)
                            Rp {{ number_format($a->payroll->nominalTotal(), 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($a->payroll)
                            @if ($a->payroll->isDibayar())
                                <span class="badge badge-aktif">Sudah dibayar</span>
                                <div style="font-size: var(--fs-xs); color: var(--text-muted);">{{ $a->payroll->dibayar_at?->translatedFormat('d M Y') }}</div>
                            @else
                                <span class="badge badge-netral">Belum dibayar</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($a->payroll?->pdf_slip_path)
                            <span style="color: var(--text-muted);">Slip dicatat, belum bisa diunduh di sini</span>
                        @else
                            <span style="color: var(--text-muted);">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; color: var(--text-muted); padding: 20px 0;">Belum ada penugasan proyek.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="card">
    <div style="font-size: 15px; font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
        <i class="ti ti-receipt" style="color: var(--accent);"></i> Riwayat Reimbursement &amp; Add-on Operasional
    </div>
    <div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Proyek Terkait</th>
                <th>Keperluan / Label Reimburse</th>
                <th>Nominal</th>
                <th>Status Pembukuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reimbursements as $r)
                <tr>
                    <td>{{ $r->tanggal ? $r->tanggal->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $r->proyek }}</td>
                    <td style="font-weight: 500;">{{ $r->label }}</td>
                    <td style="font-weight: 600; color: var(--accent-strong);">Rp {{ number_format($r->nominal, 0, ',', '.') }}</td>
                    <td>
                        <span class="badge {{ $r->slip_status === 'Tercatat di Slip' ? 'badge-aktif' : 'badge-netral' }}">
                            {{ $r->slip_status }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; color: var(--text-muted); padding: 20px 0;">Belum ada riwayat reimbursement atau biaya tambahan yang dicatat.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endif
@endsection
