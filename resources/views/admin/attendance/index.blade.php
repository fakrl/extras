@extends('layouts.app')

@section('title', 'Absensi Lapangan')

@section('content')
<div style="font-size: 16px; font-weight: 600; margin-bottom: 2px;">Absensi Lapangan</div>
<p style="color: var(--text-secondary); margin: 0 0 16px; font-size: 13.5px;">
    Tandai hadir/tidak hadir Extras per tanggal shooting.
</p>

<form method="GET" action="{{ route('admin.attendance.index') }}" class="form-row" style="margin-bottom: 8px;">
    <div>
        <label>Proyek</label>
        <select name="project" onchange="this.form.submit()">
            @foreach ($projects as $p)
                <option value="{{ $p->id }}" @selected($castingProject?->id === $p->id)>{{ $p->nama_produksi }} ({{ $p->client_ph }})</option>
            @endforeach
        </select>
    </div>
    @if ($castingProject && $castingProject->shootingDates->isNotEmpty())
        <div>
            <label>Tanggal Shooting</label>
            <select name="tanggal" onchange="this.form.submit()">
                @foreach ($castingProject->shootingDates as $tgl)
                    <option value="{{ $tgl->id }}" @selected($shootingDate?->id === $tgl->id)>{{ $tgl->tanggal->format('d M Y') }}</option>
                @endforeach
            </select>
        </div>
    @endif
</form>

@if (! $castingProject)
    <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">Belum ada proyek.</div>
@elseif (! $shootingDate)
    <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">Proyek ini belum punya tanggal shooting.</div>
@else
    @forelse ($applicants as $app)
        @php
            $absen = $app->attendances->firstWhere('event_shooting_date_id', $shootingDate->id);
            $karakter = $app->karakter ?: $app->castingProjectClass?->karakter;
            $callingan = $app->jam_callingan ?: $app->castingProjectClass?->jam_callingan;
            $scene = $app->keterangan_scene ?: $app->castingProjectClass?->keterangan_scene;
        @endphp
        <div class="entity-card" style="margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
                <div style="display: flex; gap: 12px; align-items: center;">
                    @if ($app->extras->foto_profil_path)
                        <img src="{{ route('extras.media.foto', $app->extras) }}" alt="Foto" style="width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border-color);">
                    @else
                        <div style="width: 48px; height: 48px; border-radius: 8px; background: var(--bg-secondary, #eee); display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                            <i class="ti ti-user" style="font-size: 20px;"></i>
                        </div>
                    @endif

                    <div>
                        <div class="entity-card-title">{{ $app->extras->user->username ?? '(belum isi username)' }}</div>
                        <div style="font-size: 12.5px; color: var(--text-secondary); margin-top: 2px;">
                            Peran: <strong>{{ $karakter ?: ($app->castingProjectClass->nama_kelas ?? 'Umum') }}</strong>
                            @if ($callingan)
                                · Callingan: <strong style="color: var(--danger, #d9534f);">{{ $callingan }} WIB</strong>
                            @endif
                            @if ($scene)
                                · Scene: <em>{{ $scene }}</em>
                            @endif
                        </div>

                        <div style="display: flex; gap: 6px; margin-top: 6px; flex-wrap: wrap; align-items: center;">
                            @if ($absen)
                                <span class="badge {{ $absen->status === 'hadir' ? 'badge-aktif' : 'badge-tolak' }}">
                                    {{ $absen->status === 'hadir' ? 'Hadir' : 'Tidak Hadir' }}
                                </span>
                                @if ($absen->status_validasi === 'menunggu')
                                    <span class="badge badge-pending">Menunggu Validasi Korlap</span>
                                @elseif ($absen->status_validasi === 'tervalidasi')
                                    <span class="badge badge-aktif">Tervalidasi ({{ $absen->divalidasiOleh?->name ?? 'Staf' }})</span>
                                @endif
                                <span style="font-size: 11.5px; color: var(--text-muted);">{{ $absen->created_at->format('H:i') }} WIB</span>
                            @else
                                <span class="badge badge-pending">Belum diabsen</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Kolom Foto Selfie Hybrid --}}
                @if ($absen && $absen->foto_path)
                    <div style="text-align: center; font-size: 11px;">
                        <a href="{{ route('admin.absensi.foto', $absen) }}" target="_blank" style="display: block;">
                            <img src="{{ route('admin.absensi.foto', $absen) }}" alt="Selfie" style="width: 52px; height: 52px; border-radius: 8px; object-fit: cover; border: 2px solid var(--accent, #3b82f6);">
                        </a>
                        <span style="color: var(--text-muted);">Foto Onsite</span>
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                    @if ($absen && $absen->status_validasi === 'menunggu')
                        <form method="POST" action="{{ route('admin.absensi.validasi', $absen) }}">
                            @csrf
                            <button class="btn btn-brand btn-sm" title="Setujui selfie kehadiran extras"><i class="ti ti-check"></i> Setujui Hadir</button>
                        </form>
                        <form method="POST" action="{{ route('admin.absensi.tolak', $absen) }}">
                            @csrf
                            <button class="btn btn-danger-outline btn-sm" title="Tolak selfie kehadiran extras"><i class="ti ti-x"></i> Tolak</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.attendance.store', $app) }}">
                            @csrf
                            <input type="hidden" name="event_shooting_date_id" value="{{ $shootingDate->id }}">
                            <input type="hidden" name="status" value="hadir">
                            <button class="btn btn-brand btn-sm">Hadir</button>
                        </form>
                        <form method="POST" action="{{ route('admin.attendance.store', $app) }}">
                            @csrf
                            <input type="hidden" name="event_shooting_date_id" value="{{ $shootingDate->id }}">
                            <input type="hidden" name="status" value="tidak_hadir">
                            <button class="btn btn-danger-outline btn-sm">Tidak Hadir</button>
                        </form>
                    @endif

                    <button type="button" class="btn btn-sm" onclick="document.getElementById('foto-dialog-{{ $app->id }}').showModal()" title="Foto onsite langsung oleh Korlap">
                        <i class="ti ti-camera"></i> Foto Korlap
                    </button>
                    <button type="button" class="btn btn-sm" onclick="document.getElementById('catatan-dialog-{{ $app->id }}').showModal()">Catatan</button>
                </div>
            </div>
        </div>

        {{-- Dialog Foto Korlap Onsite --}}
        <dialog id="foto-dialog-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 380px; width: 90%;">
            <form method="POST" action="{{ route('admin.attendance.store', $app) }}" enctype="multipart/form-data" style="padding: 18px;">
                @csrf
                <input type="hidden" name="event_shooting_date_id" value="{{ $shootingDate->id }}">
                <input type="hidden" name="status" value="hadir">
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Ambil Foto On-Site: {{ $app->extras->user->username ?? 'Extras' }}</div>
                <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">Foto extras di lokasi syuting sebagai bukti kehadiran untuk Client / PH.</p>
                <input type="file" name="foto" accept="image/*" capture="environment" required style="width: 100%; margin-bottom: 12px;">
                <textarea name="catatan" rows="2" placeholder="Catatan kehadiran (opsional)..." style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand">Simpan & Tandai Hadir</button>
                </div>
            </form>
        </dialog>

        {{-- Dialog Catatan Lapangan --}}
        <dialog id="catatan-dialog-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
            <form method="POST" action="{{ route('admin.applications.catatan', $app) }}" style="padding: 18px;">
                @csrf
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Catatan Lapangan: {{ $app->extras->user->username ?? 'kandidat' }}</div>
                <select name="jenis" required style="width: 100%; margin-bottom: 10px;">
                    <option value="catatan">Catatan</option>
                    <option value="sanksi">Sanksi</option>
                </select>
                <textarea name="isi" rows="3" required placeholder="Isi catatan/sanksi" style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn btn-sm" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-sm btn-brand">Simpan</button>
                </div>
            </form>
        </dialog>
    @empty
        <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">Tidak ada Extras aktif di proyek ini.</div>
    @endforelse
@endif
@endsection
