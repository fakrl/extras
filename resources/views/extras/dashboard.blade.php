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
<p style="color: var(--text-secondary); margin: -8px 0 0; font-size: 13.5px;">
    Halo, {{ auth()->user()->name }}! Cek lowongan casting terbaru dan pantau status pendaftaran kamu di sini.
</p>


@php $persenProfil = $extrasProfile?->persenKelengkapan(); @endphp
@if ($persenProfil !== null && $persenProfil < 100)
    <a href="{{ route('extras.profile.edit') }}" class="dash-row" style="margin-bottom: 12px;">
        <div style="flex: 1; min-width: 0;">
            <div class="dash-sub" style="margin-bottom: 4px;">Profil {{ $persenProfil }}% lengkap</div>
            <progress value="{{ $persenProfil }}" max="100" class="profil-persen-bar"></progress>
        </div>
        <span class="dash-sub">Lengkapi &rarr;</span>
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
    <div class="card" data-dash="callingan" style="border: 2px solid var(--accent); margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap;">
            <div>
                <span class="badge badge-urgent">{{ $callinganCard['hari_ini'] ? 'Hari ini' : 'Besok' }}</span>
                <div style="font-size: 15px; font-weight: 700; margin-top: 6px;">{{ $ca->castingProject->nama_produksi }}</div>
            </div>
            @if ($callinganCard['hari_ini'])
                <button type="button" class="btn btn-brand" onclick="document.getElementById('dialog-absen-{{ $ca->id }}').showModal()"><i class="ti ti-camera"></i> Absen Selfie</button>
            @endif
        </div>
        <div style="margin: 12px 0;">
            <div style="color: var(--text-muted); font-size: var(--fs-xs); text-transform: uppercase;">Jam Kumpul</div>
            <div style="font-size: 26px; font-weight: 800; color: var(--accent-strong);">{{ $caJamKumpul ?: '(belum diisi)' }}</div>
        </div>
        @if ($ca->keterangan_scene)
            <div style="font-size: var(--fs-sm); margin-bottom: 10px;"><strong>Kostum/Scene:</strong> {{ $ca->keterangan_scene }}</div>
        @endif
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            @if ($caMapsUrl)
                <a href="{{ $caMapsUrl }}" target="_blank" rel="noopener" class="btn btn-sm"><i class="ti ti-map-pin"></i> Buka Maps</a>
            @endif
            @if ($caWaKorlap)
                <a href="{{ $caWaKorlap }}" target="_blank" rel="noopener" class="btn btn-sm"><i class="ti ti-brand-whatsapp"></i> WA Korlap ({{ $caKorlap->name }})</a>
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
                        <a href="#pendaftaran-{{ $t['app'] }}" class="dash-sub" style="display: block; margin-top: 4px; color: inherit;">{!! $t['teks'] !!} <span style="color: var(--accent);">&darr;</span></a>
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
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 14px; flex-wrap: wrap;">
            <div>
                <div style="font-size: 14.5px; font-weight: 600;">{{ $app->castingProject->nama_produksi }}</div>
                <x-status-badge :model="$app" style="margin-top: 4px; display: inline-block;" />
            </div>
            @if ($app->status_partisipasi === 'diundang')
                <a href="{{ route('extras.projects.show', $app->castingProject) }}" class="btn btn-brand">Lihat Undangan</a>
            @elseif ($app->status_partisipasi === 'nego_fee')
                <a href="{{ route('extras.negotiations.show', $app) }}" class="btn btn-brand">Lanjut Nego Fee</a>
            @elseif ($app->status_partisipasi === 'lolos' && ! $extrasProfile->nik_hash)
                <a href="{{ route('extras.kontrak.lengkapi-ktp', $app) }}" class="btn btn-brand">Lengkapi KTP</a>
            @elseif ($app->status_partisipasi === 'lolos')
                <a href="{{ route('contracts.show', $app) }}" class="btn btn-brand">Tanda Tangan Kontrak</a>
            @elseif ($app->payment?->status === 'ditransfer')
                <a href="{{ route('payments.show', $app) }}" class="btn btn-brand">Konfirmasi Bayar</a>
            @elseif (in_array($app->status_partisipasi, ['kontrak_ditandatangani', 'selesai_produksi']))
                <a href="{{ route('payments.show', $app) }}" class="btn btn-brand">Pembayaran</a>
            @endif
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
        <summary style="list-style: none; cursor: pointer; font-size: 13.5px; font-weight: 600;">Riwayat ({{ $riwayatPendaftaran->count() }}) &#9662;</summary>
        @foreach ($riwayatPendaftaran as $app)
            <div class="dash-row" id="pendaftaran-{{ $app->id }}">
                <div style="min-width: 0; flex: 1;">
                    <div style="font-weight: 600;">{{ $app->castingProject->nama_produksi }}</div>
                    <x-status-badge :model="$app" style="margin-top: 4px; display: inline-block;" />
                </div>
                @if ($app->status_partisipasi === 'selesai_produksi')
                    <a href="{{ route('payments.show', $app) }}" style="font-size: 12.5px; color: var(--accent);">Pembayaran &rarr;</a>
                @else
                    <a href="{{ route('extras.projects.show', $app->castingProject) }}" style="font-size: 12.5px; color: var(--accent);">Lihat proyek &rarr;</a>
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
        <a href="{{ route('extras.projects.index') }}" style="font-size: 12.5px; color: var(--accent);">Lihat semua &rarr;</a>
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
        <div>
            @if ($extrasProfile?->grade_saat_ini)
                <span class="badge badge-aktif" style="font-weight: 600; font-size: 12px;">Grade {{ $extrasProfile->grade_saat_ini }}</span>
            @else
                <span class="badge badge-netral" style="font-size: var(--fs-xs);">Grade: Belum Dinilai</span>
            @endif
        </div>
    </div>
    <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 14px; line-height: 1.4;">
        Klasifikasi Grade (A/B/C) ditentukan oleh Admin Casting berdasarkan penampilan/look fisik talenta dan divalidasi oleh Client. Riwayat aktivitas dan perubahan status akun kamu tercatat secara transparan di bawah ini.
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
