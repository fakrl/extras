@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 18px;">
    <p style="color: var(--text-secondary); font-size: 13.5px; margin: 0;">
        Monitoring dan analitik sistem (read-only). Operasional harian dikelola oleh Admin.
    </p>
    <form method="GET" action="{{ route('super-admin.dashboard') }}" style="display: flex; gap: 6px;">
        @foreach (['7d' => '7 Hari', '30d' => '30 Hari', '1y' => '1 Tahun'] as $val => $label)
            <button type="submit" name="period" value="{{ $val }}"
                class="btn btn-sm {{ $period === $val ? 'btn-brand' : '' }}"
                style="min-height: 30px; padding: 0 12px; font-size: 12px; border-radius: 20px;">
                {{ $label }}
            </button>
        @endforeach
    </form>
</div>

{{-- 1. Proyek Perlu Ditindak --}}
<div class="card" style="border: 2px solid var(--accent-strong); margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <div class="card-title" style="color: var(--accent-strong); margin: 0;">
            <i class="ti ti-clipboard-list"></i> Proyek Perlu Ditindak
            @if ($pendingRequests->isNotEmpty())
                <span class="badge badge-pending" style="margin-left: 8px;">{{ $pendingRequests->count() }} menunggu ACC</span>
            @endif
        </div>
        <a href="{{ route('admin.projects.index') }}" style="font-size: 12px; color: var(--accent);">Lihat semua proyek &rarr;</a>
    </div>

    @forelse ($pendingRequests as $req)
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border-color); gap: 8px; flex-wrap: wrap;">
            <div>
                <div style="font-weight: 600; font-size: 13.5px;">{{ $req->nama_produksi }}</div>
                <div style="font-size: 12px; color: var(--text-muted);">{{ $req->client_ph }} &bull; {{ $req->diajukanOlehClient?->name }}</div>
            </div>
            <div style="display: flex; gap: 6px; flex-shrink: 0;">
                <form method="POST" action="{{ route('super-admin.projects.acc', $req) }}" style="display: inline-block;">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-brand" onclick="return confirm('Setujui permintaan proyek ini?')">ACC</button>
                </form>
                <form method="POST" action="{{ route('super-admin.projects.reject', $req) }}" style="display: inline-block;">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-secondary" onclick="return confirm('Tolak permintaan proyek ini?')">Tolak</button>
                </form>
            </div>
        </div>
    @empty
        @forelse ($ringkasanProyek as $p)
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-color); gap: 8px; flex-wrap: wrap;">
                <div>
                    <span style="font-weight: 500; font-size: 13.5px;">{{ $p->nama_produksi }}</span>
                    @if ($p->isUrgent()) <span class="badge badge-tolak" style="font-size: 10px; margin-left: 6px;">URGENT</span> @endif
                    <div style="font-size: 12px; color: var(--text-muted);">Deadline: {{ $p->deadline?->format('d M Y') ?? '-' }}</div>
                </div>
            </div>
        @empty
            <p style="color: var(--text-muted); font-size: 13px; padding: 8px 0;">Tidak ada proyek yang perlu ditindak saat ini.</p>
        @endforelse
    @endempty

    @if ($pendingRequests->isNotEmpty() && $ringkasanProyek->isNotEmpty())
        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border-color);">
            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">Proyek Berjalan:</div>
            @foreach ($ringkasanProyek->take(2) as $p)
                <div style="font-size: 13px; padding: 4px 0;">
                    {{ $p->nama_produksi }}
                    @if ($p->isUrgent()) <span class="badge badge-tolak" style="font-size: 10px;">URGENT</span> @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- 2. Metric cards --}}
@php
$periodLabel = match($period) { '7d' => '7 hari ini', '1y' => 'tahun ini', default => '30 hari ini' };
@endphp
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="metric-card" title="Proyek casting yang sedang dalam proses (status dibuka)">
        <div class="metric-label">Proyek Berjalan</div>
        <div class="metric-value">{{ $proyekBerjalan }}</div>
        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">status aktif saat ini</div>
    </div>
    <div class="metric-card" title="Jumlah akun Extras dengan status aktif di sistem">
        <div class="metric-label">Extras Aktif</div>
        <div class="metric-value">{{ $extrasAktif }}</div>
        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: flex; align-items: center; gap: 6px;">
            <span>bergabung {{ $periodLabel }}</span>
            @if ($trendExtrasAktif)
                <span style="font-size: 10.5px; font-weight: 600; color: {{ $trendExtrasAktif['up'] ? 'var(--accent-strong)' : 'var(--danger)' }};">
                    {{ $trendExtrasAktif['up'] ? '↑' : '↓' }} {{ $trendExtrasAktif['label'] }}
                </span>
            @endif
        </div>
    </div>
    <div class="metric-card" title="Total seluruh akun terdaftar di sistem (semua role)">
        <div class="metric-label">Total Akun Sistem</div>
        <div class="metric-value">{{ $totalAkun }}</div>
        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: flex; align-items: center; gap: 6px;">
            <span>daftar {{ $periodLabel }}</span>
            @if ($trendTotalAkun)
                <span style="font-size: 10.5px; font-weight: 600; color: {{ $trendTotalAkun['up'] ? 'var(--accent-strong)' : 'var(--danger)' }};">
                    {{ $trendTotalAkun['up'] ? '↑' : '↓' }} {{ $trendTotalAkun['label'] }}
                </span>
            @endif
        </div>
    </div>
    <div class="metric-card" title="Jumlah staf/admin yang honornya belum diproses untuk proyek yang sudah selesai">
        <div class="metric-label">Honor Belum Diproses</div>
        <div class="metric-value">{{ $honorBelumDiproses }}</div>
        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">perlu tindak lanjut</div>
    </div>
</div>

{{-- AT.2: Card Margin Bulan Ini --}}
<div class="card" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <div class="metric-label">Margin Bulan Ini</div>
            @if ($marginBulanIni->ada_data)
                <div class="metric-value" style="font-size: 18px;">
                    Rp {{ number_format($marginBulanIni->margin, 0, ',', '.') }}
                </div>
            @else
                <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Belum ada data bulan ini</div>
            @endif
        </div>
        <a href="{{ route('super-admin.recap-margin') }}" style="font-size: 12.5px; color: var(--accent); white-space: nowrap;">
            &rarr; Lihat Detail
        </a>
    </div>
</div>

{{-- AU.5: Charts --}}
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px;">
    <div class="card">
        <div class="card-title">Margin per Bulan (6 bulan terakhir)</div>
        <div style="height: 200px;"><canvas id="chartMarginBulanan"></canvas></div>
    </div>
    <div class="card">
        <div class="card-title">Status Proyek</div>
        <div style="height: 200px;"><canvas id="chartStatusProyek"></canvas></div>
    </div>
</div>

{{-- AU.10.1: Honor + Kalender dalam 2 kolom --}}
<div class="dashboard-grid-2col is-wide-narrow">
    <div class="card">
        <div class="card-title">Admin & Staff — Honor Berjalan (Top 5)</div>
        <table>
            <thead>
                <tr><th>Nama Admin</th><th>Role</th><th>Total Honor</th><th>Proyek Selesai</th><th>Proyek Berjalan</th></tr>
            </thead>
            <tbody>
                @forelse ($rekapHonorAdmin as $admin)
                    <tr>
                        <td>{{ $admin->nama }}</td>
                        <td>{{ $admin->role }}</td>
                        <td>Rp {{ number_format($admin->total_honor, 0, ',', '.') }}</td>
                        <td>{{ $admin->proyek_selesai }}</td>
                        <td>{{ $admin->proyek_berjalan }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center; color: var(--text-muted);">Belum ada Admin.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top: 10px; text-align: right; font-size: 12.5px;">
            <a href="{{ route('super-admin.admins.index') }}" style="color: var(--accent);">Lihat semua &rarr;</a>
        </div>
    </div>
    <div class="card">
        <div class="card-title">Jadwal Shooting Bulan Ini</div>
        <x-jadwal-calendar :events="$jadwalBulanIni" :compact="true" />
        <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Klik tanggal bertanda untuk lihat detail acara.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var saColor = getComputedStyle(document.documentElement).getPropertyValue('--accent-strong').trim() || '#15803D';
    new Chart(document.getElementById('chartMarginBulanan'), {
        type: 'bar',
        data: {
            labels: @json($chartMarginBulanan['labels']),
            datasets: [{ label: 'Margin (Rp)', data: @json($chartMarginBulanan['data']), backgroundColor: saColor }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
    new Chart(document.getElementById('chartStatusProyek'), {
        type: 'doughnut',
        data: {
            labels: @json($chartStatusProyek['labels']),
            datasets: [{ data: @json($chartStatusProyek['data']), backgroundColor: [saColor, '#9CA3AF'] }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}());
</script>
@endpush
