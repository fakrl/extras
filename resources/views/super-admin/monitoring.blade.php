@extends('layouts.app')

@section('title', 'Monitoring — Semua Akun')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Tampilan ini read-only. Aksi nonaktifkan/kelola akun tetap dilakukan Admin Default.
</p>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="metric-card" style="text-align: center;">
        <div class="metric-value">{{ $extrasAktif }}/{{ $extrasTotal }}</div>
        <div class="metric-label">Extras Aktif</div>
    </div>
    <div class="metric-card" style="text-align: center;">
        <div class="metric-value">{{ $cdTotal }}</div>
        <div class="metric-label">Casting Director</div>
    </div>
    <div class="metric-card" style="text-align: center;">
        <div class="metric-value">{{ $adminTotal }}</div>
        <div class="metric-label">Admin (Default + Sub-role)</div>
    </div>
</div>

<div class="card" style="margin-bottom: 16px;">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Extras</div>
    <table>
        <thead><tr><th>Nama</th><th>Alias</th><th>Email</th><th>Status</th></tr></thead>
        <tbody>
            @foreach ($extrasList as $ex)
                <tr>
                    <td>{{ $ex->name }}</td>
                    <td>{{ $ex->username ?? '-' }}</td>
                    <td>{{ $ex->email }}</td>
                    <td>
                        <span class="badge {{ $ex->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">
                            {{ $ex->status }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="card" style="margin-bottom: 16px;">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Jadwal Shooting (Read-only)</div>
    @if ($jadwalProjects->isEmpty())
        <div style="color: var(--text-muted); font-size: 13px;">Belum ada jadwal yang diisi CD.</div>
    @else
        @foreach ($jadwalProjects as $project)
            <div style="margin-bottom: 12px;">
                <div style="font-weight: 600; font-size: 13.5px; margin-bottom: 6px;">{{ $project->nama_produksi }}</div>
                @foreach ($project->shootingDates as $date)
                    <div style="font-size: 12.5px; border-left: 3px solid var(--accent); padding-left: 10px; margin-bottom: 6px;">
                        <strong>{{ $date->tanggal->format('d M Y') }}</strong>
                        @if ($date->lokasi) &mdash; {{ $date->lokasi }} @endif
                        @if ($date->jam_mulai) | {{ substr($date->jam_mulai, 0, 5) }}{{ $date->jam_selesai ? '–' . substr($date->jam_selesai, 0, 5) : '' }} @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif
</div>

<div class="card">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Casting Director</div>
    <table>
        <thead><tr><th>Nama</th><th>Email</th><th>Status</th></tr></thead>
        <tbody>
            @foreach ($cdList as $cd)
                <tr>
                    <td>{{ $cd->name }}</td>
                    <td>{{ $cd->email }}</td>
                    <td>
                        <span class="badge {{ $cd->status === 'aktif' ? 'badge-aktif' : 'badge-tolak' }}">
                            {{ $cd->status }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
