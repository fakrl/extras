@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Monitoring dan analitik sistem (read-only). Operasional harian dikelola oleh Admin.
</p>

{{-- 1. Permintaan Proyek Baru - SELALU tampil --}}
<div class="card" style="border: 2px solid var(--accent-strong); margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <div class="card-title" style="color: var(--accent-strong); margin: 0;">
            <i class="ti ti-bell"></i> Permintaan Proyek Baru dari Client
            @if ($pendingRequests->isNotEmpty())
                (Menunggu ACC: {{ $pendingRequests->count() }})
            @endif
        </div>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Judul Produksi</th>
                    <th>Client / PH</th>
                    <th>Kuota</th>
                    <th>Deadline</th>
                    <th>Brief Kebutuhan</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendingRequests as $req)
                    <tr>
                        <td style="font-weight: 600;">{{ $req->nama_produksi }}</td>
                        <td>{{ $req->client_ph }} <br><small style="color: var(--text-muted);">{{ $req->diajukanOlehClient?->name }}</small></td>
                        <td>{{ $req->kuota }} orang</td>
                        <td>{{ $req->deadline?->format('d/m/Y') }}</td>
                        <td style="max-width: 280px; font-size: 12.5px;">{{ Str::limit($req->brief_catatan, 120) }}</td>
                        <td style="text-align: right; white-space: nowrap;">
                            <form method="POST" action="{{ route('super-admin.projects.acc', $req) }}" style="display: inline-block;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-brand" onclick="return confirm('Setujui permintaan proyek ini dan teruskan ke Admin?')">ACC Proyek</button>
                            </form>
                            <form method="POST" action="{{ route('super-admin.projects.reject', $req) }}" style="display: inline-block; margin-left: 4px;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-secondary" onclick="return confirm('Tolak permintaan proyek ini?')">Tolak</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 16px 0;">
                            Tidak ada permintaan menunggu ACC saat ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- 2. Metric cards --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="metric-card">
        <div class="metric-label">Proyek Berjalan</div>
        <div class="metric-value">{{ $proyekBerjalan }}</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Extras Aktif</div>
        <div class="metric-value">{{ $extrasAktif }}</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Total Akun Sistem</div>
        <div class="metric-value">{{ $totalAkun }}</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Honor Belum Diproses</div>
        <div class="metric-value">{{ $honorBelumDiproses }}</div>
    </div>
</div>

{{-- 3. Admin & Staff - Honor Berjalan Top 5 --}}
<div class="card" style="margin-bottom: 20px;">
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

{{-- 4. Ringkasan Proyek --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-title">Ringkasan Proyek</div>
    @forelse ($ringkasanProyek as $p)
        <div style="padding: 8px 0; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap;">
            <div>
                <span style="font-weight: 500; font-size: 13.5px;">{{ $p->nama_produksi }}</span>
                @if ($p->isUrgent())
                    <span class="badge badge-tolak" style="font-size: 10px; margin-left: 6px;">URGENT</span>
                @endif
            </div>
            <span style="font-size: 12px; color: var(--text-muted);">
                Deadline: {{ $p->deadline?->format('d M Y') ?? '-' }}
            </span>
        </div>
    @empty
        <p style="color: var(--text-muted); font-size: 13px;">Tidak ada proyek yang sedang berjalan.</p>
    @endforelse
    <div style="margin-top: 10px; text-align: right; font-size: 12.5px;">
        <a href="{{ route('super-admin.monitoring') }}" style="color: var(--accent);">Lihat semua &rarr;</a>
    </div>
</div>

{{-- 5. Jadwal Shooting Bulan Ini --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-title">Jadwal Shooting Bulan Ini</div>
    <x-jadwal-calendar :events="$jadwalBulanIni" :compact="true" />
</div>

{{-- 6. Ringkasan Keuangan --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-title">Rekap Margin Proyek</div>
    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px;">
        Lihat detail margin per proyek, breakdown fee client vs payout extras, dan rekap keuangan keseluruhan.
    </p>
    <a href="{{ $rekapMarginUrl }}" class="btn btn-brand">Buka Rekap Margin &rarr;</a>
</div>
@endsection
