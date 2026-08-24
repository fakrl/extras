@extends('layouts.app')

@section('title', 'Riwayat Kerja & Status Gaji Saya')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Halaman ini read-only — nominal dan status ditentukan oleh Super Admin.
</p>

<div class="card">
    <table>
        <thead><tr><th>Proyek</th><th>Status Penugasan</th><th>Honor</th><th>Slip</th></tr></thead>
        <tbody>
            @forelse ($assignments as $a)
                <tr>
                    <td>{{ $a->castingProject->nama_produksi }}</td>
                    <td>
                        <span class="badge {{ $a->status_log === 'selesai' ? 'badge-aktif' : 'badge-pending' }}">
                            {{ $a->status_log }}
                        </span>
                    </td>
                    <td>
                        @if ($a->payroll)
                            Rp {{ number_format($a->payroll->nominalTotal(), 0, ',', '.') }}
                        @else
                            Belum dihitung
                        @endif
                    </td>
                    <td>
                        @if ($a->payroll?->pdf_slip_path)
                            <span style="color: var(--accent-strong);">Slip tersedia</span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center; color: var(--text-muted); padding: 20px 0;">Belum ada penugasan proyek.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
