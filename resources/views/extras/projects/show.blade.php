@extends('layouts.app')

@section('title', $castingProject->nama_produksi)

@section('content')
<div class="project-context-header">
    <div class="pch-main">
        <a href="{{ route('extras.projects.index') }}" class="pch-back"><i class="ti ti-arrow-left"></i> Lowongan</a>
        <div>
            <div class="pch-title">{{ $castingProject->nama_produksi }}</div>
            <div class="pch-meta">
                @if ($castingProject->namaClientUntukExtras())
                    <span>Client: {{ $castingProject->namaClientUntukExtras() }}</span>
                    <span>&bull;</span>
                @endif
                <span>Deadline: {{ $castingProject->deadline->translatedFormat('d M Y') }}</span>
                <span>&bull;</span>
                <span>Fee yang kamu terima sudah setelah potongan jasa agensi 25%.</span>
                @if ($castingProject->kode_proyek)
                    <span class="badge badge-netral">{{ $castingProject->kode_proyek }}</span>
                @endif
            </div>
        </div>
    </div>
    <div>
        @if ($castingProject->isUrgent())
            <span class="badge badge-urgent">Butuh Dadakan</span>
        @endif
        @include('partials.label-nego', ['project' => $castingProject])
    </div>
</div>

<div class="project-apply-split">
    {{-- Kolom Kiri: Informasi & Rincian Proyek --}}
    <div style="display: flex; flex-direction: column; gap: 16px; min-width: 0;">
        <div class="card" style="margin: 0;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;"><i class="ti ti-calendar-event"></i> Tanggal Shooting</div>
            <ul style="margin: 0; padding-left: 18px; font-size: 13.5px;">
                @foreach ($castingProject->shootingDates as $date)
                    @php($kena = $bentrok->filter(fn ($b) => $b->tanggalBentrok->contains($date->tanggal->toDateString())))
                    <li style="margin-bottom: 6px;">
                        <strong>{{ $date->tanggal->translatedFormat('l, d F Y') }}</strong>
                        @if ($kena->isNotEmpty())
                            <span class="badge badge-urgent" style="margin-left: 4px;">Bentrok dengan jadwalmu</span>
                            <span style="font-size: var(--fs-xs); color: var(--text-muted); display: block; margin-top: 2px;">{{ $kena->map(fn ($b) => $b->castingProject->nama_produksi.' ('.($b->isPasti() ? 'sudah pasti' : 'masih diproses').')')->join(', ') }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card" style="margin: 0;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;"><i class="ti ti-users"></i> Peran yang Dibutuhkan</div>
            @if ($castingProject->classes->isEmpty())
                <p style="margin: 0; font-size: 13.5px; color: var(--text-muted);">Belum ada rincian peran.</p>
            @else
                @include('partials.peran-lowongan', ['classes' => $castingProject->classes])
            @endif
        </div>
    </div>

    {{-- Kolom Kanan: Pendaftaran & Pilihan Peran (Sticky Desktop) --}}
    <div class="pas-sticky-card">
        @if ($undangan)
        <div class="card" style="margin: 0; border-color: var(--accent);">
            <span class="badge badge-info">Diundang</span>
            <div style="font-size: 14px; font-weight: 600; margin: 8px 0 4px;">Kamu diundang ke proyek ini @if ($undangan->castingProjectClass)sebagai {{ $undangan->castingProjectClass->nama_kelas }}@endif</div>
            <p style="margin: 0 0 12px; font-size: 13.5px; color: var(--text-secondary);">Terima kalau kamu bisa ikut, atau tolak kalau tidak. Setelah diterima, Admin akan mereview profilmu seperti pendaftar lain.</p>
            @if (session('konfirmasi_bentrok'))
                <div class="alert-danger" style="margin: 0 0 12px;"><span class="badge badge-urgent">Bentrok jadwal</span> {{ session('konfirmasi_bentrok') }} Klik Terima lagi untuk tetap menerima.</div>
            @endif
            @include('partials.undangan-aksi', ['app' => $undangan, 'alasan' => true, 'konfirmasi' => session()->has('konfirmasi_bentrok')])
        </div>
        @elseif ($lamaranAktif)
        <div class="card" style="margin: 0;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Kamu sudah mendaftar ke proyek ini</div>
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                <x-status-badge :model="$lamaranAktif" />
                @if ($lamaranAktif->castingProjectClass)
                    <span class="badge badge-netral">Peran: {{ $lamaranAktif->castingProjectClass->nama_kelas }}</span>
                @endif
            </div>
            <p style="margin: 0 0 12px; font-size: 12.5px; color: var(--text-muted);">Mendaftar {{ $lamaranAktif->created_at->translatedFormat('d M Y') }}</p>
            <a href="{{ route('extras.dashboard') }}#pendaftaran-{{ $lamaranAktif->id }}" class="btn btn-brand">Lihat Status Pendaftaran</a>
        </div>
        @elseif (! $castingProject->menerimaPendaftaran())
        <div class="alert-info" style="margin: 0;"><i class="ti ti-info-circle"></i> Proyek ini sudah tidak menerima pendaftaran (ditutup, kuota penuh, atau lewat deadline).</div>
        @else
        <form method="POST" action="{{ route('extras.projects.apply', $castingProject) }}" style="margin: 0;">
            @csrf
            @if ($castingProject->classes->isNotEmpty())
                <div class="card" id="kartu-pilih-peran" style="margin-bottom: 16px; border: 2px solid var(--accent);">
                    <div style="font-size: 15px; font-weight: 700; margin-bottom: 4px; color: var(--accent-strong);">Pilih Peran &amp; Kirim Lamaran</div>
                    <p style="font-size: 12.5px; color: var(--text-muted); margin: 0 0 12px;">Pilih salah satu peran yang sesuai dengan kesiapan dan kriteriamu.</p>
                    <div style="display: grid; gap: 8px;">
                        @foreach ($castingProject->classes as $class)
                            @php($penuh = $class->sisaKuota() === 0)
                            <label style="display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; border-radius: var(--radius-md); border: 1px solid var(--border-color); background: var(--bg-card); cursor: {{ $penuh ? 'not-allowed' : 'pointer' }};{{ $penuh ? ' color: var(--text-muted); opacity: 0.7;' : '' }}">
                                <input type="radio" name="casting_project_class_id" value="{{ $class->id }}" required @disabled($penuh) style="margin-top: 3px;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; font-size: 13.5px;">{{ $class->nama_kelas }}</div>
                                    @if ($penuh)
                                        <span style="font-size: var(--fs-xs); color: var(--text-muted);">· Penuh, slot bisa terbuka lagi</span>
                                    @else
                                        <span style="font-size: var(--fs-xs); color: var(--accent); font-weight: 600;">Tersedia (Sisa kuota: {{ $class->sisaKuota() }})</span>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>

                    @if ($bentrok->isNotEmpty())
                        <div style="margin-top: 12px; padding: 10px 12px; border-radius: var(--radius-md); background: var(--md-custom-warning-container, rgba(245,158,11,0.12)); border: 1px solid color-mix(in srgb, var(--warning) 40%, transparent);">
                            <div style="font-size: 12.5px; font-weight: 600; color: var(--warning);"><i class="ti ti-alert-triangle"></i> Peringatan Jadwal Beririsan</div>
                            <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">Jadwal syuting proyek ini beririsan dengan proyekmu yang lain ({{ $bentrok->map(fn ($b) => $b->castingProject->nama_produksi)->unique()->join(', ') }}).</div>
                        </div>
                    @endif

                    <button type="submit" class="btn btn-brand" style="width: 100%; justify-content: center; margin-top: 14px; min-height: 44px; font-weight: 600;"><i class="ti ti-check"></i> Daftar ke Proyek Ini</button>
                </div>
            @endif
        </form>
        @endif
    </div>
</div>

{{-- Sticky CTA bar untuk mobile screen --}}
<div class="sticky-mobile-cta">
    <div>
        <div style="font-size: 11px; color: var(--text-muted);">Deadline: {{ $castingProject->deadline->translatedFormat('d M') }}</div>
        <div style="font-size: 13px; font-weight: 700;">{{ $castingProject->nama_produksi }}</div>
    </div>
    <a href="#kartu-pilih-peran" class="btn btn-brand" onclick="document.getElementById('kartu-pilih-peran')?.scrollIntoView({behavior:'smooth'}); return false;">Pilih Peran <i class="ti ti-arrow-down"></i></a>
</div>

@if (session('konfirmasi_bentrok'))
    <dialog id="dialog-bentrok" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 380px; width: 90%;">
        <form method="POST" action="{{ route('extras.projects.apply', $castingProject) }}" style="padding: 18px;">
            @csrf
            <input type="hidden" name="konfirmasi_bentrok" value="1">
            @if (old('casting_project_class_id'))
                <input type="hidden" name="casting_project_class_id" value="{{ old('casting_project_class_id') }}">
            @endif
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;"><span class="badge badge-urgent">Bentrok jadwal</span></div>
            <p style="font-size: 13px; margin: 0 0 14px; line-height: 1.5;">{{ session('konfirmasi_bentrok') }}</p>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button>
                <button type="submit" class="btn btn-brand">Tetap daftar</button>
            </div>
        </form>
    </dialog>
    <script>document.getElementById('dialog-bentrok').showModal();</script>
@endif
@endsection
