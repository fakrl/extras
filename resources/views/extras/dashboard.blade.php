@extends('layouts.app')

@section('title', 'Dashboard Extras')

@section('content')
@php
    $sudahAbsenHariIni = $riwayatAbsensi->filter(fn ($a) => $a->eventShootingDate?->tanggal->isToday())->pluck('project_application_id');
    $tindakan = collect();
    if (! $extrasProfile?->profilLengkap()) {
        $tindakan->push(['badge-info', 'Profil belum lengkap', 'Lengkapi foto, usia, gender & tinggi badan', route('extras.profile.edit'), 'Lengkapi', null]);
    }
    foreach ($pendaftaranSaya as $app) {
        $nama = $app->castingProject->nama_produksi;
        if ($app->status_partisipasi === 'nego_fee') {
            $tindakan->push(['badge-pending', 'Nego fee', $nama, route('extras.negotiations.show', $app), 'Lanjut Nego Fee', null]);
        } elseif ($app->status_partisipasi === 'lolos' && ! $extrasProfile->nik_hash) {
            $tindakan->push(['badge-pending', 'Lengkapi KTP', $nama.' · wajib sebelum TTD kontrak', route('extras.kontrak.lengkapi-ktp', $app), 'Lengkapi KTP', null]);
        } elseif ($app->status_partisipasi === 'lolos') {
            $tindakan->push(['badge-aktif', 'Kontrak siap TTD', $nama, route('contracts.show', $app), 'Tanda Tangan', null]);
        }
        if ($app->payment?->status === 'ditransfer') {
            $tindakan->push(['badge-info', 'Konfirmasi bayar', $nama.' · honor sudah ditransfer', route('payments.show', $app), 'Konfirmasi', null]);
        }
        if (in_array($app->status_partisipasi, \App\Models\ProjectApplication::STATUS_LOLOS_KE_ATAS) && $app->castingProject->shootingDates->contains(fn ($d) => $d->tanggal->isToday()) && ! $sudahAbsenHariIni->contains($app->id)) {
            $tindakan->push(['badge-tolak', 'Absen hari ini', $nama, null, 'Absen Selfie', 'dialog-absen-'.$app->id]);
        }
    }
@endphp

<div class="dash-kolom">
<p style="color: var(--text-secondary); margin: -8px 0 0; font-size: 13.5px;">
    Halo, {{ auth()->user()->name }}! Cek lowongan casting terbaru dan pantau status pendaftaran kamu di sini.
</p>


@if ($tindakan->isNotEmpty())
    <div class="card dash-perlu" data-dash="perlu-tindakan">
        <div class="card-title">
            <i class="ti ti-clipboard-list"></i> Perlu Tindakan
            <span class="badge badge-pending" style="margin-left: 8px;">{{ $tindakan->count() }}</span>
        </div>
        @foreach ($tindakan as [$badge, $label, $sub, $url, $tombol, $dialog])
            <div class="dash-row">
                <div style="min-width: 0; flex: 1;">
                    <span class="badge {{ $badge }}">{{ $label }}</span>
                    <div class="dash-sub" style="margin-top: 4px;">{{ $sub }}</div>
                </div>
                @if ($dialog)
                    <button type="button" class="btn btn-sm btn-brand" onclick="document.getElementById('{{ $dialog }}').showModal()"><i class="ti ti-camera"></i> {{ $tombol }}</button>
                @else
                    <a href="{{ $url }}" class="btn btn-sm btn-brand">{{ $tombol }}</a>
                @endif
            </div>
        @endforeach
    </div>
@endif

<div class="card" data-dash="casting-call">
    <div class="card-header-row" style="margin-bottom: 4px;">
        <div class="card-title" style="margin: 0;"><i class="ti ti-microphone"></i> Casting Call Terbuka</div>
        <a href="{{ route('extras.projects.index') }}" style="font-size: 12.5px; color: var(--accent);">Lihat semua &rarr;</a>
    </div>
    @forelse ($castingCallTerbuka as $project)
        @php $peran = $project->peranCocok; @endphp
        <div class="dash-row">
            <div style="min-width: 0; flex: 1;">
                <div style="font-weight: 600;">
                    {{ $project->nama_produksi }}
                    @if ($project->isUrgent()) <span class="badge badge-tolak">Dadakan</span> @endif
                </div>
                @if ($peran)
                    <div style="margin-top: 4px; font-size: 13px;">
                        {{ $peran->nama_kelas }}
                        @if ($project->persenCocok !== null) <span class="badge {{ $project->persenCocok >= 50 ? 'badge-aktif' : 'badge-netral' }}">{{ $project->persenCocok }}% cocok</span> @endif
                        @if ($project->classes->count() > 1) <span class="dash-sub">+{{ $project->classes->count() - 1 }} peran lain</span> @endif
                    </div>
                @endif
                <div class="dash-sub" style="margin-top: 2px;">
                    @if ($peran)
                        {{ $peran->sisaKuota() === 0 ? 'Penuh' : 'Sisa '.$peran->sisaKuota().' dari '.$peran->kuota_kelas }}
                    @else
                        Kuota {{ $project->kuota }}
                    @endif
                    · Deadline {{ $project->deadline->translatedFormat('d M Y') }}
                </div>
            </div>
            <a href="{{ route('extras.projects.show', $project) }}" class="btn btn-sm btn-brand">Daftar</a>
        </div>
    @empty
        <div class="dash-sub" style="text-align: center; padding: 12px 0;">Belum ada lowongan terbuka. Nanti kami kabari kalau ada yang baru.</div>
    @endforelse
</div>

<section data-dash="pendaftaran">
<div style="font-size: 14px; font-weight: 500; margin-bottom: 12px;">Pendaftaran Saya</div>

@forelse ($pendaftaranSaya as $app)
    <div class="card" style="margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 14px; flex-wrap: wrap;">
            <div>
                <div style="font-size: 14.5px; font-weight: 600;">{{ $app->castingProject->nama_produksi }}</div>
                <x-status-badge :model="$app" style="margin-top: 4px; display: inline-block;" />
            </div>
            @if ($app->status_partisipasi === 'nego_fee')
                <a href="{{ route('extras.negotiations.show', $app) }}" class="btn btn-brand">Lanjut Nego Fee</a>
            @elseif ($app->status_partisipasi === 'lolos')
                <a href="{{ route('contracts.show', $app) }}" class="btn btn-brand">Kontrak</a>
            @elseif (in_array($app->status_partisipasi, ['kontrak_ditandatangani', 'selesai_produksi']))
                <a href="{{ route('payments.show', $app) }}" class="btn btn-brand">Pembayaran</a>
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
                            <div style="color: var(--text-muted); font-size: var(--fs-xs); text-transform: uppercase;">Jam Callingan</div>
                            <div style="font-size: 16px; font-weight: 700; color: var(--danger, #d9534f);">{{ $callingan }} WIB</div>
                            <div style="font-size: var(--fs-xs); color: var(--text-muted);">(Wajib tiba di lokasi)</div>
                        </div>
                    @endif
                    <div>
                        <div style="color: var(--text-muted); font-size: var(--fs-xs); text-transform: uppercase;">Peran / Tokoh</div>
                        <div style="font-weight: 600;">{{ $karakter ?: ($app->castingProjectClass->nama_kelas ?? 'Umum') }}</div>
                        <div style="font-size: var(--fs-xs); color: var(--text-muted);">Kelas: {{ $app->castingProjectClass->nama_kelas ?? 'Umum' }}</div>
                    </div>
                    @if ($scene)
                        <div>
                            <div style="color: var(--text-muted); font-size: var(--fs-xs); text-transform: uppercase;">Scene & Catatan Kostum</div>
                            <div>{{ $scene }}</div>
                        </div>
                    @endif
                    <div>
                        <div style="color: var(--text-muted); font-size: var(--fs-xs); text-transform: uppercase;">Kontinuitas</div>
                        <div>{{ $continuity }}</div>
                    </div>
                </div>

                {{-- Status Absensi & Tombol Selfie --}}
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 8px; padding: 10px 12px;">
                    <div>
                        <div style="font-size: 12.5px; font-weight: 600;"><i class="ti ti-camera"></i> Absensi Lapangan Hybrid</div>
                        <div style="font-size: var(--fs-xs); color: var(--text-muted);">Ambil selfie langsung di lokasi syuting menggunakan kamera ponsel.</div>
                    </div>
                    <button type="button" class="btn btn-brand" onclick="document.getElementById('dialog-absen-{{ $app->id }}').showModal()">
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
                            <span style="font-size: var(--fs-xs); color: var(--text-muted); display: block; margin-top: 4px;">Hanya kamera langsung (tidak bisa pilih dari galeri).</span>
                        </div>

                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button>
                            <button type="submit" class="btn btn-brand">Kirim Selfie Kehadiran</button>
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
                @foreach (['Daftar akun', 'Lengkapi profil', 'Apply proyek casting terbuka', 'Seleksi Admin & Client', 'Tanda tangan kontrak digital', 'Kerja & dibayar'] as $i => $step)
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
</section>

<div class="card" data-dash="status-talenta">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
        <div style="font-size: 14px; font-weight: 600;">
            <i class="ti ti-activity"></i> Status Talenta & Linimasa Aktivitas
        </div>
        <div>
            @if ($extrasProfile?->grade_saat_ini)
                <span class="badge badge-aktif" style="font-weight: 600; font-size: 12px;">Grade {{ $extrasProfile->grade_saat_ini }}</span>
            @else
                <span class="badge badge-pending" style="font-size: var(--fs-xs);">Grade: Belum Dinilai</span>
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
                                <i class="ti ti-point"></i> <span title="{{ $log->action }}">{{ \App\Models\ActivityLog::actionLabel($log->action) }}</span>
                            @endif
                        </div>
                        <div style="font-size: var(--fs-xs); color: var(--text-secondary); margin-top: 2px;">{{ $log->description }}</div>
                    </div>
                    <div style="font-size: var(--fs-xs); color: var(--text-muted); white-space: nowrap;">
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

@if ($riwayatAbsensi->isNotEmpty())
<div class="card">
    <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;"><i class="ti ti-clipboard-check"></i> Status Absensi Saya</div>
    @foreach ($riwayatAbsensi as $absen)
        @php
            $namaProyek = $absen->projectApplication->castingProject->nama_produksi ?? '-';
            $tanggal = $absen->eventShootingDate->tanggal->format('d M Y') ?? '-';
        @endphp
        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 8px 0; border-bottom: 1px solid var(--border-color); font-size: 12.5px; gap: 8px;">
            <div>
                <div style="font-weight: 500;">{{ $namaProyek }}</div>
                <div style="color: var(--text-muted);">{{ $tanggal }}</div>
                @if ($absen->catatan && $absen->status_validasi === 'tervalidasi' && $absen->status === 'tidak_hadir')
                    <div style="color: var(--danger, #d9534f); margin-top: 2px;">Alasan: {{ $absen->catatan }}</div>
                @endif
            </div>
            <div style="white-space: nowrap;">
                @if ($absen->status_validasi === 'tervalidasi' && $absen->status === 'hadir')
                    <span class="badge badge-aktif">Hadir Tervalidasi</span>
                @elseif ($absen->status_validasi === 'tervalidasi' && $absen->status === 'tidak_hadir')
                    <span class="badge badge-tolak">Tidak Hadir</span>
                @else
                    <span class="badge badge-pending">Menunggu Validasi</span>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endif

<div class="card" data-dash="jadwal">
    <div class="card-title">Jadwal Shooting Bulan Ini</div>
    <x-jadwal-calendar :events="$jadwalBulanIni" :compact="true" />
</div>
</div>
@endsection
