@extends('layouts.app')

@section('title', 'Monitoring — Semua Akun')

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Tampilan ini read-only. Aksi nonaktifkan/kelola akun tetap dilakukan Admin Default.
</p>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="metric-card" style="text-align: center;">
        <div class="metric-value">{{ $extrasAktif }}/{{ $extrasTotal }}</div>
        <div class="metric-label">Extras Aktif</div>
    </div>
    <div class="metric-card" style="text-align: center;">
        <div class="metric-value">{{ $cdTotal }}</div>
        <div class="metric-label">Client / PH</div>
    </div>
    <div class="metric-card" style="text-align: center;">
        <div class="metric-value">{{ $adminTotal }}</div>
        <div class="metric-label">Admin & Korlap</div>
    </div>
    <div class="metric-card" style="text-align: center;">
        <div class="metric-value">{{ $attendanceStats['total_hadir'] }}</div>
        <div class="metric-label">Total Kehadiran</div>
    </div>
    <div class="metric-card" style="text-align: center;">
        <div class="metric-value" style="color: {{ $attendanceStats['menunggu_validasi'] > 0 ? 'var(--warning, #e67e22)' : 'inherit' }};">{{ $attendanceStats['menunggu_validasi'] }}</div>
        <div class="metric-label">Menunggu Validasi</div>
    </div>
</div>

<div class="card" style="margin-bottom: 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
        <div style="font-size: 14px; font-weight: 600;"><i class="ti ti-camera"></i> Monitoring Absensi Lapangan (Real-time)</div>
        <a href="{{ route('admin.attendance.index') }}" class="btn btn-sm btn-brand">Kelola / Validasi Absensi &rarr;</a>
    </div>

    @if ($recentAttendances->isEmpty())
        <div style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 20px 0;">Belum ada riwayat absensi lapangan.</div>
    @else
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Extras</th>
                        <th>Proyek & Scene</th>
                        <th>Tgl Shooting</th>
                        <th>Status</th>
                        <th>Validasi Korlap</th>
                        <th>Foto Selfie</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentAttendances as $att)
                        <tr>
                            <td style="font-size: 12px; color: var(--text-muted); white-space: nowrap;">
                                {{ $att->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                <strong>{{ $att->projectApplication?->extras?->user?->username ?? $att->projectApplication?->extras?->user?->name ?? 'Extras' }}</strong>
                            </td>
                            <td style="font-size: 12.5px;">
                                <div>{{ $att->projectApplication?->castingProject?->nama_produksi ?? '-' }}</div>
                                @if ($att->projectApplication?->karakter || $att->projectApplication?->castingProjectClass?->karakter)
                                    <span style="color: var(--text-muted); font-size: 11px;">
                                        Peran: {{ $att->projectApplication->karakter ?: $att->projectApplication->castingProjectClass->karakter }}
                                    </span>
                                @endif
                            </td>
                            <td style="font-size: 12px; white-space: nowrap;">
                                {{ $att->eventShootingDate?->tanggal?->format('d M Y') ?? '-' }}
                            </td>
                            <td>
                                <span class="badge {{ $att->status === 'hadir' ? 'badge-aktif' : 'badge-tolak' }}">
                                    {{ $att->status === 'hadir' ? 'Hadir' : 'Tidak Hadir' }}
                                </span>
                            </td>
                            <td>
                                @if ($att->status_validasi === 'tervalidasi')
                                    <span class="badge badge-aktif" title="Divalidasi oleh: {{ $att->divalidasiOleh?->name ?? 'Staf' }}">
                                        Tervalidasi
                                    </span>
                                @else
                                    <span class="badge badge-pending">Menunggu Korlap</span>
                                @endif
                            </td>
                            <td>
                                @if ($att->foto_path)
                                    <a href="{{ route('admin.absensi.foto', $att) }}" target="_blank" class="btn btn-sm" style="font-size: 11px; padding: 2px 8px;">
                                        <i class="ti ti-photo"></i> Lihat Foto
                                    </a>
                                @else
                                    <span style="color: var(--text-muted); font-size: 11px;">(Tanpa Foto)</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
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
