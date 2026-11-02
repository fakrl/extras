@extends('layouts.app')

@section('title', 'Dashboard Extras')

@section('content')
@php
    $sudahAbsenHariIni = $riwayatAbsensi->filter(fn ($a) => $a->eventShootingDate?->tanggal->isToday())->pluck('project_application_id');
    $tindakan = collect();
    if (! $extrasProfile?->profilLengkap()) {
        $tindakan->push(['badge' => 'badge-info', 'label' => 'Profil belum lengkap', 'teks' => 'Lengkapi foto, usia, gender & tinggi badan', 'url' => route('extras.profile.edit'), 'tombol' => 'Lengkapi']);
    }
    foreach ($pendaftaranAktif as $app) {
        $nama = e($app->castingProject->nama_produksi);
        $lompat = ['app' => $app->id];
        foreach ($bentrokPer[$app->id]->filter->isPasti() as $lawan) {
            $tindakan->push(['badge' => 'badge-urgent', 'label' => 'Jadwal bentrok', 'teks' => 'Jadwal bentrok: <em>'.e($lawan->castingProject->nama_produksi)."</em> sudah pasti, <em>{$nama}</em> tanggal sama. Batalkan <em>{$nama}</em>?"] + $lompat);
        }
        if ($app->status_partisipasi === 'diundang') {
            $peran = $app->castingProjectClass?->nama_kelas;
            $tindakan->push(['badge' => 'badge-info', 'label' => 'Undangan proyek', 'teks' => "Undangan proyek <em>{$nama}</em>".($peran ? ' ('.e($peran).')' : ''), 'undangan' => $app] + $lompat);
        } elseif ($app->status_partisipasi === 'nego_fee') {
            $tindakan->push(['badge' => 'badge-pending', 'label' => 'Nego fee', 'teks' => "Negosiasi fee <em>{$nama}</em> menunggu balasanmu"] + $lompat);
        } elseif ($app->status_partisipasi === 'lolos' && ! $extrasProfile->nik_hash) {
            $tindakan->push(['badge' => 'badge-pending', 'label' => 'Lengkapi KTP', 'teks' => "Lengkapi KTP untuk <em>{$nama}</em>, wajib sebelum TTD kontrak"] + $lompat);
        } elseif ($app->status_partisipasi === 'lolos') {
            $tindakan->push(['badge' => 'badge-aktif', 'label' => 'Kontrak siap TTD', 'teks' => "Kontrak <em>{$nama}</em> siap kamu tanda tangani"] + $lompat);
        }
        if ($app->payment?->status === 'ditransfer') {
            $tindakan->push(['badge' => 'badge-info', 'label' => 'Konfirmasi bayar', 'teks' => "Honor <em>{$nama}</em> sudah ditransfer, konfirmasi penerimaannya"] + $lompat);
        }
        if (in_array($app->status_partisipasi, \App\Models\ProjectApplication::STATUS_LOLOS_KE_ATAS) && $app->castingProject->shootingDates->contains(fn ($d) => $d->tanggal->isToday()) && ! $sudahAbsenHariIni->contains($app->id)) {
            $tindakan->push(['badge' => 'badge-urgent', 'label' => 'Absen hari ini', 'teks' => $nama, 'tombol' => 'Absen Selfie', 'dialog' => 'dialog-absen-'.$app->id]);
        }
    }
@endphp

@push('styles')
<style>
.exd-kanan { display: contents; }
@media (min-width: 1100px) {
    .dash-kolom.exd-grid { max-width: none; display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 16px; align-items: start; }
    .exd-grid > p, .exd-grid > .dash-perlu { grid-column: 1 / -1; }
    .exd-grid > section > .card { margin: 0 0 16px !important; }
    .exd-grid > section > .card:last-child { margin-bottom: 0 !important; }
    .exd-grid > section > .card-title { line-height: 17px; }
    .exd-kanan { display: flex; flex-direction: column; gap: 16px; min-width: 0; margin-top: 29px; }
    .exd-kanan > .card { margin: 0; }
    .exd-judul { font-weight: 500 !important; }
    .exd-cara { padding-inline: var(--space-4) !important; }
    .exd-cara .step-bar-item { width: 76px; }
}
</style>
@endpush

<div class="dash-kolom exd-grid">
<div style="grid-column: 1 / -1; margin: -8px 0 4px;">
    <span class="label-editorial-sm" style="display: block; margin-bottom: 2px;">Ruang Kerja Talenta &middot; Portofolio &amp; Panggilan</span>
    <p style="color: var(--text-secondary); margin: 0 0 14px; font-size: 13.5px;">
        Halo, <strong class="font-editorial" style="font-size: 16px;">{{ auth()->user()->name }}</strong>! Cek lowongan casting terbaru dan pantau status pendaftaran kamu di sini.
    </p>

    {{-- Strip 4 Kartu Ringkasan Karir & Honor Saya --}}
    <div class="dash-pulse-strip" style="margin-bottom: 16px;">
        <div class="dash-stat-card" tabindex="0">
            <div>
                <div style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    <i class="ti ti-movie"></i> Aktivitas &amp; Proyek
                </div>
                @php
                    $totAktif = max(1, $statKarirExtras['proyek_selesai'] + $statKarirExtras['lamaran_aktif']);
                    $lenSelesai = round(($statKarirExtras['proyek_selesai'] / $totAktif) * 188.5, 1);
                    $lenLamaran = max(0, round(188.5 - $lenSelesai, 1));
                @endphp
                <div class="dash-viz-body">
                    <div class="viz-donut-wrap">
                        <svg class="viz-donut-svg" viewBox="0 0 76 76" aria-hidden="true">
                            <circle cx="38" cy="38" r="30" stroke="var(--surface-2, rgba(0,0,0,0.06))" stroke-width="7" fill="none" />
                            <circle cx="38" cy="38" r="30" stroke="#15803D" stroke-width="7" stroke-dasharray="{{ $lenSelesai }} 188.5" stroke-dashoffset="0" fill="none" />
                            <circle cx="38" cy="38" r="30" stroke="#0284C7" stroke-width="7" stroke-dasharray="{{ $lenLamaran }} 188.5" stroke-dashoffset="{{ -$lenSelesai }}" fill="none" />
                        </svg>
                        <div class="viz-center-label">
                            <span class="viz-center-num">{{ $statKarirExtras['proyek_selesai'] }}</span>
                            <span class="viz-center-sub">Selesai</span>
                        </div>
                    </div>
                    <div class="viz-legend">
                        <div class="viz-legend-row">
                            <span class="viz-legend-label"><span class="viz-dot" style="background: #15803D;"></span>Selesai</span>
                            <strong class="viz-legend-val">{{ $statKarirExtras['proyek_selesai'] }}</strong>
                        </div>
                        <div class="viz-legend-row">
                            <span class="viz-legend-label"><span class="viz-dot" style="background: #0284C7;"></span>Lamaran</span>
                            <strong class="viz-legend-val">{{ $statKarirExtras['lamaran_aktif'] }}</strong>
                        </div>
                        <div class="viz-legend-row">
                            <span class="viz-legend-label"><span class="viz-dot" style="background: #D97706;"></span>Riwayat</span>
                            <strong class="viz-legend-val">{{ $statKarirExtras['total_melamar'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>
            <span class="info-hint" title="Klik untuk rincian / kunci pop-up"><i class="ti ti-info-circle"></i></span>
            <div class="dash-hover-popover">
                <div class="dash-popover-header">
                    <span class="dash-popover-title">Rincian Partisipasi Proyek</span>
                    <button type="button" class="dash-popover-close" aria-label="Tutup"><i class="ti ti-x"></i></button>
                </div>
                <div class="popover-row"><span>Proses Seleksi / Nego</span><strong>{{ $statKarirExtras['proses_seleksi'] }}</strong></div>
                <div class="popover-row"><span>Kontrak Aktif / Lolos</span><strong>{{ $statKarirExtras['kontrak_aktif'] }}</strong></div>
                <div class="popover-row"><span>Selesai Produksi</span><strong>{{ $statKarirExtras['proyek_selesai'] }}</strong></div>
                <div class="popover-row"><span>Total Riwayat Melamar</span><strong>{{ $statKarirExtras['total_melamar'] }}</strong></div>
            </div>
        </div>

        <div class="dash-stat-card" tabindex="0">
            <div>
                <div style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    <i class="ti ti-wallet"></i> Dompet Honor Saya
                </div>
                <div style="font-size: 19px; font-weight: 700; color: var(--text-primary); margin: 3px 0 2px;">
                    Rp {{ number_format($statKarirExtras['honor_cair'], 0, ',', '.') }}
                </div>
                @php
                    $pendingHonor = $statKarirExtras['honor_konfirmasi'] + $statKarirExtras['honor_proses'];
                    $totHonorSum = max(1, $statKarirExtras['honor_cair'] + $pendingHonor);
                    $pctCair = round(($statKarirExtras['honor_cair'] / $totHonorSum) * 100);
                    $pctPending = max(0, 100 - $pctCair);
                @endphp
                <div class="viz-fin-bars" style="margin-top: 6px;">
                    <div class="viz-fin-item">
                        <div class="viz-fin-head">
                            <span style="color: var(--text-secondary); font-size: 11px;">Sudah Cair</span>
                            <strong style="color: #15803D; font-size: 11px;">Rp {{ number_format($statKarirExtras['honor_cair'], 0, ',', '.') }}</strong>
                        </div>
                        <div class="viz-fin-track">
                            <div class="viz-fin-fill" style="width: {{ $pctCair }}%; background: #15803D;"></div>
                        </div>
                    </div>
                    @if ($pendingHonor > 0)
                        <div class="viz-fin-item">
                            <div class="viz-fin-head">
                                <span style="color: var(--text-secondary); font-size: 11px;">Proses/Konfirmasi</span>
                                <strong style="color: #D97706; font-size: 11px;">Rp {{ number_format($pendingHonor, 0, ',', '.') }}</strong>
                            </div>
                            <div class="viz-fin-track">
                                <div class="viz-fin-fill" style="width: {{ $pctPending }}%; background: #D97706;"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <span class="info-hint" title="Klik untuk rincian / kunci pop-up"><i class="ti ti-info-circle"></i></span>
            <div class="dash-hover-popover">
                <div class="dash-popover-header">
                    <span class="dash-popover-title">Status Pencairan Honor</span>
                    <button type="button" class="dash-popover-close" aria-label="Tutup"><i class="ti ti-x"></i></button>
                </div>
                <div class="popover-row"><span>Sudah Diterima (Cair)</span><strong style="color: #15803D;">Rp {{ number_format($statKarirExtras['honor_cair'], 0, ',', '.') }}</strong></div>
                <div class="popover-row"><span>Perlu Konfirmasi Anda</span><strong style="color: #D97706;">Rp {{ number_format($statKarirExtras['honor_konfirmasi'], 0, ',', '.') }}</strong></div>
                <div class="popover-row"><span>Dalam Proses Transfer</span><strong>Rp {{ number_format($statKarirExtras['honor_proses'], 0, ',', '.') }}</strong></div>
            </div>
        </div>

        <div class="dash-stat-card" tabindex="0">
            <div>
                <div style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    <i class="ti ti-award"></i> Skor Kehadiran
                </div>
                @php
                    $skorHadir = min(100, max(0, $statKarirExtras['skor_kehadiran']));
                    $ringHadirLen = round(($skorHadir / 100) * 188.5, 1);
                    $colorHadir = $skorHadir >= 80 ? '#15803D' : ($skorHadir >= 50 ? '#D97706' : '#DC2626');
                @endphp
                <div class="dash-viz-body">
                    <div class="viz-ring-wrap" title="Skor Kehadiran: {{ $skorHadir }}%">
                        <svg class="viz-ring-svg" viewBox="0 0 76 76" aria-hidden="true">
                            <circle cx="38" cy="38" r="30" stroke="var(--surface-2, rgba(0,0,0,0.06))" stroke-width="7" fill="none" />
                            <circle cx="38" cy="38" r="30" stroke="{{ $colorHadir }}" stroke-width="7" stroke-dasharray="{{ $ringHadirLen }} 188.5" stroke-linecap="round" fill="none" />
                        </svg>
                        <div class="viz-center-label">
                            <span class="viz-center-num">{{ $skorHadir }}%</span>
                            <span class="viz-center-sub">Hadir</span>
                        </div>
                    </div>
                    <div class="viz-legend">
                        <div class="viz-legend-row">
                            <span class="viz-legend-label"><span class="viz-dot" style="background: #15803D;"></span>Hadir</span>
                            <strong class="viz-legend-val">{{ $statKarirExtras['hadir_count'] }}/{{ $statKarirExtras['total_absen'] }}</strong>
                        </div>
                        <div class="viz-legend-row">
                            <span class="viz-legend-label"><span class="viz-dot" style="background: #DC2626;"></span>Batal</span>
                            <strong class="viz-legend-val">{{ $statKarirExtras['batal_mendadak'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>
            <span class="info-hint" title="Klik untuk rincian / kunci pop-up"><i class="ti ti-info-circle"></i></span>
            <div class="dash-hover-popover">
                <div class="dash-popover-header">
                    <span class="dash-popover-title">Catatan Kedisiplinan &amp; Absensi</span>
                    <button type="button" class="dash-popover-close" aria-label="Tutup"><i class="ti ti-x"></i></button>
                </div>
                <div class="popover-row"><span>Kehadiran Terverifikasi</span><strong>{{ $statKarirExtras['hadir_count'] }} kali</strong></div>
                <div class="popover-row"><span>Total Panggilan Absen</span><strong>{{ $statKarirExtras['total_absen'] }} jadwal</strong></div>
                <div class="popover-row"><span>Batal Mendadak</span><strong>{{ $statKarirExtras['batal_mendadak'] }} kali</strong></div>
            </div>
        </div>

        <div class="dash-stat-card is-right-aligned" tabindex="0">
            <div>
                <div style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600;">
                    <i class="ti ti-user-check"></i> Kesiapan Profil &amp; Kontrak
                </div>
                @php
                    $persenProfilVal = min(100, max(0, $extrasProfile?->persenKelengkapan() ?? 0));
                    $ringProfilLen = round(($persenProfilVal / 100) * 188.5, 1);
                    $colorProfil = $persenProfilVal >= 80 ? '#15803D' : ($persenProfilVal >= 50 ? '#D97706' : '#DC2626');
                @endphp
                <div class="dash-viz-body">
                    <div class="viz-ring-wrap" title="Kelengkapan Profil: {{ $persenProfilVal }}%">
                        <svg class="viz-ring-svg" viewBox="0 0 76 76" aria-hidden="true">
                            <circle cx="38" cy="38" r="30" stroke="var(--surface-2, rgba(0,0,0,0.06))" stroke-width="7" fill="none" />
                            <circle cx="38" cy="38" r="30" stroke="{{ $colorProfil }}" stroke-width="7" stroke-dasharray="{{ $ringProfilLen }} 188.5" stroke-linecap="round" fill="none" />
                        </svg>
                        <div class="viz-center-label">
                            <span class="viz-center-num">{{ $persenProfilVal }}%</span>
                            <span class="viz-center-sub">Profil</span>
                        </div>
                    </div>
                    <div class="viz-legend">
                        <div class="viz-legend-row">
                            <span class="viz-legend-label"><span class="viz-dot" style="background: {{ $statKarirExtras['siap_kontrak'] ? '#15803D' : '#D97706' }};"></span>NIK</span>
                            <strong class="viz-legend-val">{{ $statKarirExtras['siap_kontrak'] ? 'Siap' : 'Belum' }}</strong>
                        </div>
                        <div style="margin-top: 4px;">
                            <a href="{{ route('extras.profile.edit') }}" class="btn btn-sm btn-tonal" style="width: 100%; justify-content: center; font-size: 11px; padding: 2px 6px; gap: 4px;"><i class="ti ti-user-edit"></i> Edit Profil</a>
                        </div>
                    </div>
                </div>
            </div>
            <span class="info-hint" title="Klik untuk rincian / kunci pop-up"><i class="ti ti-info-circle"></i></span>
            <div class="dash-hover-popover">
                <div class="dash-popover-header">
                    <span class="dash-popover-title">Kelengkapan Data Talent</span>
                    <button type="button" class="dash-popover-close" aria-label="Tutup"><i class="ti ti-x"></i></button>
                </div>
                <div class="popover-row"><span>Data Terisi</span><strong>{{ $extrasProfile?->persenKelengkapan() ?? 0 }}%</strong></div>
                <div class="popover-row"><span>Status Verifikasi NIK</span><strong>{{ $statKarirExtras['siap_kontrak'] ? 'Lengkap' : 'Belum Terisi' }}</strong></div>
                <div style="margin-top: 8px; text-align: right; padding-top: 6px; border-top: 1px dashed var(--border-color);">
                    <a href="{{ route('extras.profile.edit') }}" class="btn btn-sm btn-tonal" style="width: 100%; justify-content: center; font-size: 11px; padding: 4px 8px; gap: 4px;"><i class="ti ti-user-edit"></i> Edit Profil <i class="ti ti-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

@php $persenProfil = $extrasProfile?->persenKelengkapan(); @endphp
@if ($persenProfil !== null && $persenProfil < 100)
    <a href="{{ route('extras.profile.edit') }}" class="dash-row" style="margin-bottom: 12px; grid-column: 1 / -1;">
        <div style="flex: 1; min-width: 0;">
            <div class="dash-sub" style="margin-bottom: 4px;">Profil {{ $persenProfil }}% lengkap</div>
            <progress value="{{ $persenProfil }}" max="100" class="profil-persen-bar"></progress>
        </div>
        <span class="dash-sub">Lengkapi <i class="ti ti-arrow-right"></i></span>
    </a>
@endif

@if ($callinganCard)
    @php
        $ca = $callinganCard['app'];
        $ct = $callinganCard['tanggal'];
        $caJamKumpul = $ca->jam_callingan;
        $caKorlap = $ca->castingProject->adminAssignments->first(fn ($a) => $a->user?->isKorlap())?->user;
        $caMapsUrl = $ct->lokasi ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($ct->lokasi) : null;
        $caWaKorlap = $caKorlap?->tautanWa("Halo {$caKorlap->name}, saya ".auth()->user()->name." mau tanya soal shooting {$ca->castingProject->nama_produksi}.");
    @endphp
    <div class="card boarding-pass-card" data-dash="callingan" style="border: 2px solid var(--accent); margin-bottom: 16px;">
        <div class="bp-header {{ $callinganCard['hari_ini'] ? 'is-urgent' : '' }}">
            <span><i class="ti ti-ticket"></i> JADWAL CALLINGAN SYUTING</span>
            <span class="badge badge-urgent">{{ $callinganCard['hari_ini'] ? 'Hari ini' : 'Besok' }}</span>
        </div>
        <div class="bp-body">
            <div style="font-size: 16px; font-weight: 700;">{{ $ca->castingProject->nama_produksi }}</div>
            <div class="dash-sub">{{ $ca->castingProjectClass?->nama_kelas ?? $ca->karakter ?? 'Talent' }} · {{ $ct->tanggal->translatedFormat('l, d F Y') }}</div>

            <div class="bp-ticket-grid">
                <div class="bp-ticket-cell">
                    <span class="bp-lbl">Jam Kumpul</span>
                    <span class="bp-val" style="color: var(--accent-strong); font-size: 20px;">{{ $caJamKumpul ?: '(belum diisi)' }}</span>
                </div>
                <div class="bp-ticket-cell">
                    <span class="bp-lbl">Lokasi Venue</span>
                    <span class="bp-val">{{ $ct->lokasi ?: 'Lokasi syuting belum diisi' }}</span>
                </div>
                <div class="bp-ticket-cell">
                    <span class="bp-lbl">Korlap Lapangan</span>
                    <span class="bp-val">{{ $caKorlap?->name ?? 'Belum ada Korlap' }}</span>
                </div>
            </div>

            @if ($ca->keterangan_scene)
                <div style="font-size: var(--fs-sm); margin-top: 10px; background: var(--bg-card); padding: 8px 10px; border-radius: var(--radius-sm); border: 1px dashed var(--border-color);">
                    <strong>Kostum/Scene:</strong> {{ $ca->keterangan_scene }}
                </div>
            @endif
        </div>

        <div class="bp-perforated"></div>

        <div class="bp-footer">
            @if ($caMapsUrl)
                <a href="{{ $caMapsUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-tonal"><i class="ti ti-map-pin"></i> Buka Maps</a>
            @endif
            @if ($caWaKorlap)
                <a href="{{ $caWaKorlap }}" target="_blank" rel="noopener" class="btn btn-sm btn-tonal"><i class="ti ti-brand-whatsapp"></i> WA Korlap ({{ $caKorlap->name }})</a>
            @endif
            @if ($callinganCard['hari_ini'])
                <button type="button" class="btn btn-brand" onclick="document.getElementById('dialog-absen-{{ $ca->id }}').showModal()"><i class="ti ti-camera"></i> Absen Selfie</button>
            @endif
        </div>
    </div>
@endif

@if ($tindakan->isNotEmpty())
    <div class="card dash-perlu" data-dash="perlu-tindakan">
        <div class="card-title">
            <i class="ti ti-clipboard-list"></i> Perlu Tindakan
            <span class="badge badge-pending" style="margin-left: 8px;">{{ $tindakan->count() }}</span>
        </div>
        @foreach ($tindakan as $t)
            <div class="dash-row">
                <div style="min-width: 0; flex: 1;">
                    <span class="badge {{ $t['badge'] }}">{{ $t['label'] }}</span>
                    @isset($t['app'])
                        <a href="#pendaftaran-{{ $t['app'] }}" class="dash-sub" style="display: block; margin-top: 4px; color: inherit;">{!! $t['teks'] !!} <i class="ti ti-arrow-down" style="color: var(--accent);"></i></a>
                    @else
                        <div class="dash-sub" style="margin-top: 4px;">{!! $t['teks'] !!}</div>
                    @endisset
                </div>
                @isset($t['undangan'])
                    @include('partials.undangan-aksi', ['app' => $t['undangan']])
                @elseif (isset($t['dialog']))
                    <button type="button" class="btn btn-brand" onclick="document.getElementById('{{ $t['dialog'] }}').showModal()"><i class="ti ti-camera"></i> {{ $t['tombol'] }}</button>
                @elseif (isset($t['url']))
                    <a href="{{ $t['url'] }}" class="btn btn-brand">{{ $t['tombol'] }}</a>
                @endisset
            </div>
        @endforeach
    </div>
@endif

<section data-dash="pendaftaran">
<div class="card-title">Pendaftaran Saya</div>

@forelse ($pendaftaranAktif as $app)
    <div class="card" id="pendaftaran-{{ $app->id }}" style="margin-bottom: 14px; scroll-margin-top: 80px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 12px; flex-wrap: wrap;">
            <div>
                <div style="font-size: 15px; font-weight: 700; color: var(--text-primary);">{{ $app->castingProject->nama_produksi }}</div>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px; flex-wrap: wrap;">
                    <x-status-badge :model="$app" style="display: inline-block;" />
                    @if ($app->castingProjectClass)
                        <span class="badge badge-netral">Peran: {{ $app->castingProjectClass->nama_kelas }}</span>
                    @endif
                </div>
            </div>
        </div>

        @php $bentrokPasti = $bentrokPer[$app->id]->filter->isPasti(); @endphp
        @if ($bentrokPer[$app->id]->isNotEmpty())
            <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin: -6px 0 12px;">
                @foreach ($bentrokPer[$app->id] as $lawan)
                    <span class="badge badge-urgent">Bentrok jadwal dengan {{ $lawan->castingProject->nama_produksi }}</span>
                @endforeach
                @if ($bentrokPasti->isNotEmpty())
                    <button type="button" class="btn btn-danger-outline" onclick="document.getElementById('dialog-batal-bentrok-{{ $app->id }}').showModal()">Batalkan {{ $app->castingProject->nama_produksi }}</button>
                @endif
            </div>
            @if ($bentrokPasti->isNotEmpty())
                <dialog id="dialog-batal-bentrok-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 380px; width: 90%;">
                    <form method="POST" action="{{ route('extras.negotiations.batalkan', $app) }}" style="padding: 18px;">
                        @csrf
                        <input type="hidden" name="alasan" value="Bentrok jadwal">
                        <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Batalkan {{ $app->castingProject->nama_produksi }}?</div>
                        <p style="font-size: 13px; margin: 0 0 8px; line-height: 1.5;">Jadwalnya bentrok dengan {{ $bentrokPasti->map(fn ($b) => $b->castingProject->nama_produksi)->join(', ') }} yang sudah pasti. Alasan: Bentrok jadwal.</p>
                        <p style="font-size: 13px; margin: 0 0 14px; line-height: 1.5; font-weight: 600; color: var(--danger);">
                            {{ $app->batalMendadak() ? 'Syuting kurang dari 2 hari lagi, jadi ini dihitung batal mendadak ('.$jumlahBatalMendadak.' dari 3 sebelum akunmu ditandai melanggar).' : 'Syuting masih lebih dari 2 hari lagi, jadi tidak dihitung batal mendadak.' }}
                        </p>
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button type="button" class="btn" onclick="this.closest('dialog').close()">Tutup</button>
                            <button type="submit" class="btn btn-danger-outline">Ya, batalkan</button>
                        </div>
                    </form>
                </dialog>
            @endif
        @endif

        @include('partials.application-progress', ['app' => $app])

        @if (in_array($app->status_partisipasi, ['lolos', 'kontrak_ditandatangani']))
            <div style="margin-top: 12px;">@include('partials.batal-dialog', ['app' => $app])</div>
        @endif

        @if (in_array($app->status_partisipasi, \App\Models\ProjectApplication::STATUS_LOLOS_KE_ATAS))
            @php
                $callingan = $app->jam_callingan ?: $app->castingProjectClass?->jam_callingan;
                $karakter = $app->karakter;
                $scene = $app->keterangan_scene ?: $app->castingProjectClass?->keterangan_scene;
                $continuity = ($app->tipe_continuity ?: $app->castingProjectClass?->tipe_continuity) === 'continuity' ? 'Berlanjut (beberapa hari)' : 'Sehari saja';
                $shootingDates = $app->castingProject->shootingDates->sortBy('tanggal');
            @endphp

            <div style="margin-top: 12px; border-top: 1px solid var(--border-color); padding-top: 12px;">
                {{-- Status Absensi & Tombol Selfie --}}
                @if ($shootingDates->contains(fn ($d) => $d->tanggal->isToday()))
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 8px; padding: 10px 12px;">
                    <div>
                        <div style="font-size: 12.5px; font-weight: 600;"><i class="ti ti-camera"></i> Absen di Lokasi</div>
                        <div style="font-size: var(--fs-xs); color: var(--text-muted);">Ambil selfie langsung di lokasi syuting menggunakan kamera ponsel.</div>
                    </div>
                    <button type="button" class="btn btn-brand" onclick="document.getElementById('dialog-absen-{{ $app->id }}').showModal()">
                        <i class="ti ti-camera"></i> Absen Selfie di Lokasi
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

                        <div style="font-size: 12px; margin-bottom: 12px; color: var(--text-secondary);">
                            Absen untuk hari ini: <strong>{{ today()->translatedFormat('l, d F Y') }}</strong>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label>Foto Selfie di Lokasi <span class="wajib" aria-hidden="true">*</span></label>
                            <input type="file" name="foto" accept="image/*" capture="user" required style="width: 100%;">
                            <span style="font-size: var(--fs-xs); color: var(--text-muted); display: block; margin-top: 4px;">Ambil foto langsung di lokasi. Di sebagian ponsel kamera akan terbuka otomatis.</span>
                        </div>

                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button>
                            <button type="submit" class="btn btn-brand">Kirim Selfie Kehadiran</button>
                        </div>
                    </form>
                </dialog>
                @endif

                {{-- CS.3.2: dipindah ke details supaya tidak dobel dengan kartu callingan di atas. --}}
                <details>
                    <summary style="cursor: pointer; font-size: 12.5px; font-weight: 600; color: var(--accent-strong);">Lihat jam kumpul, lokasi & kostum</summary>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; font-size: 12.5px; background: var(--bg-secondary, rgba(0,0,0,.03)); border-radius: 8px; padding: 12px; margin: 8px 0 12px;">
                        @if ($callingan)
                            <div>
                                <div style="color: var(--text-muted); font-size: var(--fs-xs); text-transform: uppercase;">Jam Kumpul</div>
                                <div style="margin-top: 2px;"><span class="badge badge-urgent" style="font-size: 16px; font-weight: 700;">{{ $callingan }} WIB</span></div>
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
                </details>
            </div>
        @endif
    </div>
@empty
    @if ($riwayatPendaftaran->isEmpty())
    <div class="card exd-cara" style="padding: 20px 0 24px;">
        <div style="font-size: 14px; font-weight: 600; margin-bottom: 16px;">Cara Kerja buat Calon Extras</div>
        <div class="step-bar-wrap">
            <div class="step-bar">
                @foreach (['Daftar akun', 'Isi profil', 'Daftar lowongan', 'Seleksi', 'Tanda tangan', 'Kerja & dibayar'] as $i => $step)
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
    @endif
@endforelse

@if ($riwayatPendaftaran->isNotEmpty())
    <details class="card" data-dash="riwayat" style="margin-bottom: 14px;">
        <summary style="list-style: none; cursor: pointer; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; justify-content: space-between;"><span>Riwayat ({{ $riwayatPendaftaran->count() }})</span> <i class="ti ti-chevron-down"></i></summary>
        @foreach ($riwayatPendaftaran as $app)
            <div class="dash-row" id="pendaftaran-{{ $app->id }}">
                <div style="min-width: 0; flex: 1;">
                    <div style="font-weight: 600;">{{ $app->castingProject->nama_produksi }}</div>
                    <x-status-badge :model="$app" style="margin-top: 4px; display: inline-block;" />
                </div>
                @if ($app->status_partisipasi === 'selesai_produksi')
                    <a href="{{ route('payments.show', $app) }}" style="font-size: 12.5px; color: var(--accent);">Pembayaran <i class="ti ti-arrow-right"></i></a>
                @else
                    <a href="{{ route('extras.projects.show', $app->castingProject) }}" style="font-size: 12.5px; color: var(--accent);">Lihat proyek <i class="ti ti-arrow-right"></i></a>
                @endif
            </div>
        @endforeach
    </details>
@endif
</section>

<div class="exd-kanan">
<div class="card" data-dash="casting-call">
    <div class="card-header-row" style="margin-bottom: 4px;">
        <div class="card-title" style="margin: 0;"><i class="ti ti-microphone"></i> Lowongan Terbuka</div>
        <a href="{{ route('extras.projects.index') }}" style="font-size: 12.5px; color: var(--accent);">Lihat semua <i class="ti ti-arrow-right"></i></a>
    </div>
    @forelse ($castingCallTerbuka as $project)
        @php $peran = $project->peranCocok; @endphp
        <div class="dash-row">
            <div style="min-width: 0; flex: 1;">
                <div style="font-weight: 600;">
                    {{ $project->nama_produksi }}
                    @if ($project->isUrgent()) <span class="badge badge-urgent">Dadakan</span> @endif
                </div>
                @if ($peran)
                    <div style="margin-top: 4px; font-size: 13px;">
                        {{ $peran->nama_kelas }}
                        @if ($project->persenCocok !== null && $project->persenCocok >= 50) <span class="badge badge-aktif">{{ $project->persenCocok }}% cocok</span> @endif
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
            <a href="{{ route('extras.projects.show', $project) }}" class="btn btn-brand">Daftar</a>
        </div>
    @empty
        <div class="dash-sub" style="text-align: center; padding: 12px 0;">Belum ada lowongan terbuka. Nanti kami kabari kalau ada yang baru.</div>
    @endforelse
</div>

<div class="card" data-dash="status-talenta">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
        <div class="exd-judul" style="font-size: 14px; font-weight: 600;">
            <i class="ti ti-activity"></i> Status Talenta & Linimasa Aktivitas
        </div>
    </div>
    <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 14px; line-height: 1.45;">
        Riwayat aktivitas dan status akun kamu tercatat di bawah ini.
    </p>

    @if ($aktivitasSaya->isNotEmpty())
        <div style="display: flex; flex-direction: column; gap: 8px;">
            @foreach ($aktivitasSaya as $log)
                <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 8px 10px; background: var(--bg-secondary, rgba(0,0,0,.03)); border-radius: 6px; font-size: 12.5px; gap: 10px;">
                    <div>
                        <div style="font-weight: 500;">
                            <i class="ti ti-point"></i> <span title="{{ $log->action }}">{{ \App\Models\ActivityLog::actionLabel($log->action) }}</span>
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
    <div class="exd-judul" style="font-size: 14px; font-weight: 600; margin-bottom: 12px;"><i class="ti ti-clipboard-check"></i> Status Absensi Saya</div>
    @foreach ($riwayatAbsensi as $absen)
        @php
            $namaProyek = $absen->projectApplication->castingProject->nama_produksi ?? '-';
            $tanggal = $absen->eventShootingDate->tanggal->translatedFormat('d M Y') ?? '-';
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
    <div class="card-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <span>Jadwal Shooting</span>
    </div>
    <x-navigasi-timeline :dari="$dari" :sampai="$sampai" :skala="$skala" :action="route('extras.dashboard')" />
    <x-agenda :events="$agendaEvents" />
</div>
</div>
</div>
@endsection
