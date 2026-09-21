@extends('layouts.app')

@section('title', 'Dashboard Extras')

@php
    $badgeClass = [
        'diajukan' => 'badge-pending',
        'direview_admin' => 'badge-pending',
        'nego_fee' => 'badge-pending',
        'deal' => 'badge-aktif',
        'diajukan_ke_cd' => 'badge-pending',
        'direview_cd' => 'badge-pending',
        'lolos' => 'badge-aktif',
        'ditolak' => 'badge-tolak',
        'kontrak_ditandatangani' => 'badge-aktif',
        'selesai_produksi' => 'badge-aktif',
        'dibatalkan' => 'badge-tolak',
    ];
    $statusLabel = [
        'diajukan' => 'Diajukan',
        'direview_admin' => 'Direview Admin',
        'nego_fee' => 'Nego Fee',
        'deal' => 'Deal',
        'diajukan_ke_cd' => 'Diajukan ke CD',
        'direview_cd' => 'Direview CD',
        'lolos' => 'Lolos',
        'ditolak' => 'Ditolak',
        'kontrak_ditandatangani' => 'Kontrak TTD',
        'selesai_produksi' => 'Selesai Produksi',
        'dibatalkan' => 'Dibatalkan',
    ];
@endphp

@section('content')
<p style="color: var(--text-secondary); margin: -8px 0 20px; font-size: 13.5px;">
    Halo, {{ auth()->user()->name }}! Cek lowongan casting terbaru dan pantau status pendaftaran kamu di sini.
</p>

<div style="display: flex; gap: 8px; margin-bottom: 20px;">
    <a href="{{ route('extras.profile.show') }}" class="btn">Lihat Profil Saya</a>
    <a href="{{ route('extras.projects.index') }}" class="btn btn-brand">Lihat Lowongan Casting</a>
</div>

{{-- Status Akun, Grade, & Linimasa Aktivitas --}}
<div class="card" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
        <div style="font-size: 14px; font-weight: 600;">
            <i class="ti ti-activity"></i> Status Talenta & Linimasa Aktivitas
        </div>
        <div>
            @if ($extrasProfile?->grade_saat_ini)
                <span class="badge badge-aktif" style="font-weight: 600; font-size: 12px;">Grade {{ $extrasProfile->grade_saat_ini }}</span>
            @else
                <span class="badge badge-pending" style="font-size: 11.5px;">Grade: Belum Dinilai</span>
            @endif
        </div>
    </div>
    <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 14px; line-height: 1.4;">
        Klasifikasi Grade (A/B/C) ditentukan oleh Admin Casting berdasarkan penampilan/look fisik talenta dan divalidasi oleh Client. Riwayat aktivitas dan perubahan status akun Anda tercatat secara transparan di bawah ini.
    </p>

    @if ($aktivitasSaya->isNotEmpty())
        <div style="display: flex; flex-direction: column; gap: 8px;">
            @foreach ($aktivitasSaya as $log)
                <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 8px 10px; background: var(--bg-secondary, rgba(0,0,0,.03)); border-radius: 6px; font-size: 12.5px; gap: 10px;">
                    <div>
                        <div style="font-weight: 500;">
                            @if ($log->action === 'SET_EXTRAS_GRADE')
                                <span style="color: var(--accent);"><i class="ti ti-star"></i> Perubahan Grade Talenta</span>
                            @else
                                <i class="ti ti-point"></i> {{ $log->action }}
                            @endif
                        </div>
                        <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">{{ $log->description }}</div>
                    </div>
                    <div style="font-size: 11px; color: var(--text-muted); white-space: nowrap;">
                        {{ $log->created_at?->diffForHumans() }}
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div style="font-size: 12px; color: var(--text-muted); text-align: center; padding: 12px 0;">
            Belum ada catatan aktivitas akun.
        </div>
    @endif
</div>

<div class="card" style="margin-bottom: 20px;">
    <div class="card-title">Jadwal Shooting Bulan Ini</div>
    <x-jadwal-calendar :events="$jadwalBulanIni" :compact="true" />
</div>

<div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Pendaftaran Saya</div>

@forelse ($pendaftaranSaya as $app)
    <div class="card" style="margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 14px; flex-wrap: wrap;">
            <div>
                <div style="font-size: 14.5px; font-weight: 600;">{{ $app->castingProject->nama_produksi }}</div>
                <span class="badge {{ $badgeClass[$app->status_partisipasi] ?? 'badge-pending' }}" style="margin-top: 4px; display: inline-block;">
                    {{ $statusLabel[$app->status_partisipasi] ?? $app->status_partisipasi }}
                </span>
            </div>
            @if ($app->status_partisipasi === 'nego_fee')
                <a href="{{ route('extras.negotiations.show', $app) }}" class="btn btn-brand" style="min-height:32px; padding:0 12px; font-size:12.5px;">Lanjut Nego Fee</a>
            @elseif ($app->status_partisipasi === 'lolos')
                <a href="{{ route('contracts.show', $app) }}" class="btn btn-brand" style="min-height:32px; padding:0 12px; font-size:12.5px;">Kontrak</a>
            @elseif (in_array($app->status_partisipasi, ['kontrak_ditandatangani', 'selesai_produksi']))
                <a href="{{ route('payments.show', $app) }}" class="btn btn-brand" style="min-height:32px; padding:0 12px; font-size:12.5px;">Pembayaran</a>
            @endif
        </div>

        @include('partials.application-progress', ['app' => $app])

        @if (in_array($app->status_partisipasi, \App\Models\ProjectApplication::STATUS_LOLOS_KE_ATAS))
            @php
                $callingan = $app->jam_callingan ?: $app->castingProjectClass?->jam_callingan;
                $karakter = $app->karakter ?: $app->castingProjectClass?->karakter;
                $scene = $app->keterangan_scene ?: $app->castingProjectClass?->keterangan_scene;
                $continuity = ($app->tipe_continuity ?: $app->castingProjectClass?->tipe_continuity) === 'continuity' ? 'Continuity (Multi-day)' : 'Bebas (Single Day)';
                $shootingDates = $app->castingProject->shootingDates->sortBy('tanggal');
            @endphp

            <div style="margin-top: 12px; border-top: 1px solid var(--border-color); padding-top: 12px;">
                <div style="font-size: 13px; font-weight: 600; color: var(--accent-strong); margin-bottom: 8px;">
                    <i class="ti ti-clock"></i> Callingan & Info On-Site
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; font-size: 12.5px; background: var(--bg-secondary, rgba(0,0,0,.03)); border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                    @if ($callingan)
                        <div>
                            <div style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Jam Callingan</div>
                            <div style="font-size: 16px; font-weight: 700; color: var(--danger, #d9534f);">{{ $callingan }} WIB</div>
                            <div style="font-size: 11px; color: var(--text-muted);">(Wajib tiba di lokasi)</div>
                        </div>
                    @endif
                    <div>
                        <div style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Peran / Tokoh</div>
                        <div style="font-weight: 600;">{{ $karakter ?: ($app->castingProjectClass->nama_kelas ?? 'Umum') }}</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Kelas: {{ $app->castingProjectClass->nama_kelas ?? 'Umum' }}</div>
                    </div>
                    @if ($scene)
                        <div>
                            <div style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Scene & Catatan Kostum</div>
                            <div>{{ $scene }}</div>
                        </div>
                    @endif
                    <div>
                        <div style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Kontinuitas</div>
                        <div>{{ $continuity }}</div>
                    </div>
                </div>

                {{-- Status Absensi & Tombol Selfie --}}
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 8px; padding: 10px 12px;">
                    <div>
                        <div style="font-size: 12.5px; font-weight: 600;"><i class="ti ti-camera"></i> Absensi Lapangan Hybrid</div>
                        <div style="font-size: 11.5px; color: var(--text-muted);">Ambil selfie langsung di lokasi syuting menggunakan kamera ponsel.</div>
                    </div>
                    <button type="button" class="btn btn-brand btn-sm" onclick="document.getElementById('dialog-absen-{{ $app->id }}').showModal()">
                        <i class="ti ti-camera"></i> Absen Selfie On-Site
                    </button>
                </div>

                {{-- Dialog Absen Selfie On-site --}}
                <dialog id="dialog-absen-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 12px; padding: 0; max-width: 400px; width: 92%;">
                    <form method="POST" action="{{ route('extras.absensi.selfie', $app) }}" enctype="multipart/form-data" style="padding: 20px;">
                        @csrf
                        <div style="font-size: 15px; font-weight: 600; margin-bottom: 8px;">Absen Selfie di Lokasi Syuting</div>
                        <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 14px; line-height: 1.4;">
                            Buka kamera dan ambil foto selfie kamu di lokasi syuting. Tanggal dan waktu pengiriman akan tercatat otomatis dan divalidasi oleh Korlap di lapangan.
                        </p>

                        @if ($shootingDates->count() > 1)
                            <div style="margin-bottom: 12px;">
                                <label>Pilih Tanggal Shooting</label>
                                <select name="event_shooting_date_id" required style="width: 100%;">
                                    @foreach ($shootingDates as $sd)
                                        <option value="{{ $sd->id }}" @selected($sd->tanggal->isToday())>
                                            {{ $sd->tanggal->translatedFormat('l, d F Y') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif ($shootingDates->isNotEmpty())
                            <input type="hidden" name="event_shooting_date_id" value="{{ $shootingDates->first()->id }}">
                            <div style="font-size: 12px; margin-bottom: 12px; color: var(--text-secondary);">
                                Tanggal Shooting: <strong>{{ $shootingDates->first()->tanggal->translatedFormat('l, d F Y') }}</strong>
                            </div>
                        @endif

                        <div style="margin-bottom: 16px;">
                            <label>Foto Selfie di Lokasi (Kamera Saja)</label>
                            <input type="file" name="foto" accept="image/*" capture="user" required style="width: 100%;">
                            <span style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 4px;">Hanya kamera langsung (tidak bisa pilih dari galeri).</span>
                        </div>

                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                            <button type="submit" class="btn btn-brand btn-sm">Kirim Selfie Kehadiran</button>
                        </div>
                    </form>
                </dialog>

                @php $jadwalTerisi = $shootingDates->where('lokasi', '!=', null); @endphp
                @if ($jadwalTerisi->isNotEmpty())
                    <div style="font-size: 12.5px; font-weight: 600; margin-bottom: 6px;">Jadwal Shooting Detail</div>
                    @foreach ($jadwalTerisi as $date)
                        <div style="font-size: 12.5px; margin-bottom: 6px; padding: 8px; background: var(--bg-secondary, rgba(0,0,0,.04)); border-radius: 6px;">
                            <div style="font-weight: 500;">{{ $date->tanggal->translatedFormat('l, d F Y') }}</div>
                            @if ($date->lokasi) <div>Lokasi: {{ $date->lokasi }}</div> @endif
                            @if ($date->jam_mulai) <div>Waktu: {{ substr($date->jam_mulai, 0, 5) }}{{ $date->jam_selesai ? ' – ' . substr($date->jam_selesai, 0, 5) : '' }}</div> @endif
                            @if ($date->catatan) <div>Catatan: {{ $date->catatan }}</div> @endif
                            @if ($date->panggilan)
                                <div style="margin-top: 4px;"><strong>Panggilan:</strong></div>
                                <ul style="margin: 2px 0 0 14px;">
                                    @foreach ($date->panggilan as $p)
                                        <li>{{ $p['nama'] }} ({{ $p['jam'] }})</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        @endif
    </div>
@empty
    <div class="card" style="padding: 20px 0 24px;">
        <div style="font-size: 14px; font-weight: 600; margin-bottom: 16px;">Cara Kerja buat Calon Extras</div>
        <div class="step-bar-wrap">
            <div class="step-bar">
                @foreach (['Daftar akun', 'Lengkapi profil', 'Apply proyek casting terbuka', 'Seleksi Admin & CD', 'Tanda tangan kontrak digital', 'Kerja & dibayar'] as $i => $step)
                    <div class="step-bar-item">
                        <div class="step-bar-circle">{{ $i + 1 }}</div>
                        <div class="step-bar-label">{{ $step }}</div>
                    </div>
                    @if (! $loop->last)
                        <div class="step-bar-line"></div>
                    @endif
                @endforeach
            </div>
        </div>
        <p style="text-align:center; color: var(--text-muted); font-size: 13px; margin: 20px 0 0;">
            Belum ada pendaftaran. <a href="{{ route('extras.projects.index') }}" style="color: var(--accent);">Lihat lowongan casting</a> yang tersedia.
        </p>
    </div>
@endforelse
@endsection
