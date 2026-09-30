@extends('layouts.app')

@section('title', 'Absensi Lapangan')

@push('styles')
<style>
.absen-layout { display: grid; gap: 16px; align-items: start; grid-template-areas: "perlu" "main" "jadwal"; }
.absen-layout > * { min-width: 0; }
.absen-main { grid-area: main; }
.absen-perlu { grid-area: perlu; max-height: none; }
.absen-jadwal { grid-area: jadwal; }
@media (min-width: 1100px) {
    .absen-layout { grid-template-columns: minmax(0, 1fr) 360px; grid-template-rows: auto auto 1fr; grid-template-areas: "main perlu" "main jadwal" "main ."; }
}
</style>
@endpush

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
                <option value="{{ $p->id }}" @selected($castingProject?->id === $p->id)>{{ $p->kode_proyek }} · {{ $p->nama_produksi }} ({{ $p->namaClient() }})</option>
            @endforeach
        </select>
    </div>
    @if ($castingProject && $castingProject->shootingDates->isNotEmpty())
        <div>
            <label>Tanggal Shooting</label>
            <select name="tanggal" onchange="this.form.submit()">
                @foreach ($castingProject->shootingDates as $tgl)
                    <option value="{{ $tgl->id }}" @selected($shootingDate?->id === $tgl->id)>
                        {{ $tgl->tanggal->format('d M Y') }}{{ $tgl->tanggal->toDateString() === $today ? ' (Hari Ini)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif
</form>

<div class="absen-layout">
<div class="absen-main">
@if (! $castingProject)
    <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">Belum ada proyek dengan jadwal shooting mulai kemarin.</div>
@elseif (! $shootingDate)
    <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">Proyek ini belum punya tanggal shooting.</div>
@else
    @php
        $perluValidasi = $applicants->filter(fn ($a) => $a->absen?->status_validasi === 'menunggu');
        $belumDiabsen = $applicants->filter(fn ($a) => ! $a->absen);
    @endphp
    <label for="search-extras">Cari Extras</label>
    <input type="search" id="search-extras" placeholder="Nama atau username..."
        style="width: 100%; max-width: 320px; padding: 7px 12px; border: 1px solid var(--border-color); border-radius: 7px; margin-bottom: 10px; font-size: 16px; background: var(--bg-card); color: var(--text-primary); display: block;">
    @forelse ($applicants as $app)
        @php
            $absen = $app->absen;
            $karakter = $app->karakter;
            $callingan = $app->jam_callingan ?: $app->castingProjectClass?->jam_callingan;
            $scene = $app->keterangan_scene ?: $app->castingProjectClass?->keterangan_scene;
        @endphp
        <div class="entity-card extras-row" id="app-{{ $app->id }}" data-nama="{{ strtolower(($app->extras->user->name ?? '').' '.($app->extras->user->username ?? '')) }}" style="margin-bottom: 12px; scroll-margin-top: 80px;">
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
                                <span style="font-size: var(--fs-xs); color: var(--text-muted);">{{ $absen->created_at->format('H:i') }} WIB</span>
                            @else
                                <span class="badge badge-pending">Belum diabsen</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Kolom Foto Selfie Hybrid --}}
                @if ($absen && $absen->foto_path)
                    <div style="text-align: center; font-size: var(--fs-xs);">
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
                            <button class="btn btn-brand" title="Setujui selfie kehadiran extras"><i class="ti ti-check"></i> Setujui Hadir</button>
                        </form>
                        <button type="button" class="btn btn-danger-outline" style="margin-left: 12px;" onclick="document.getElementById('tolak-dialog-{{ $absen->id }}').showModal()"><i class="ti ti-x"></i> Tolak</button>
                        <dialog id="tolak-dialog-{{ $absen->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 360px; width: 90%;">
                            <form method="POST" action="{{ route('admin.absensi.tolak', $absen) }}" style="padding: 18px;">
                                @csrf
                                <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Tolak kehadiran?</div>
                                <label style="font-size: 12.5px;">Alasan penolakan <span class="wajib" aria-hidden="true">*</span></label>
                                <textarea name="alasan" rows="3" required placeholder="Tulis alasan penolakan..." style="width: 100%; margin-bottom: 12px;"></textarea>
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button>
                                    <button type="submit" class="btn btn-danger-outline">Tolak</button>
                                </div>
                            </form>
                        </dialog>
                    @else
                        <form method="POST" action="{{ route('admin.attendance.store', $app) }}">
                            @csrf
                            <input type="hidden" name="event_shooting_date_id" value="{{ $shootingDate->id }}">
                            <input type="hidden" name="status" value="hadir">
                            <button class="btn btn-brand">Hadir</button>
                        </form>
                        <x-confirm-form action="{{ route('admin.attendance.store', $app) }}" message="Tandai {{ $app->extras->user->username ?? 'extras ini' }} Tidak Hadir? Ini memengaruhi perhitungan honor." style="margin-left: 12px;">
                            <input type="hidden" name="event_shooting_date_id" value="{{ $shootingDate->id }}">
                            <input type="hidden" name="status" value="tidak_hadir">
                            <button type="submit" class="btn btn-danger-outline">Tidak Hadir</button>
                        </x-confirm-form>
                    @endif

                    <button type="button" class="btn" style="margin-left: 12px;" onclick="document.getElementById('foto-dialog-{{ $app->id }}').showModal()" title="Foto onsite langsung oleh Korlap">
                        <i class="ti ti-camera"></i> Foto Korlap
                    </button>
                    <button type="button" class="btn" onclick="document.getElementById('catatan-dialog-{{ $app->id }}').showModal()">Catatan</button>
                </div>
            </div>
        </div>

        {{-- Dialog Foto Korlap Onsite --}}
        <dialog id="foto-dialog-{{ $app->id }}" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 380px; width: 90%;">
            <form method="POST" action="{{ route('admin.attendance.store', $app) }}" enctype="multipart/form-data" style="padding: 18px;" onsubmit="var b = this.querySelector('[type=submit]'); b.disabled = true; b.textContent = 'Mengunggah…';">
                @csrf
                <input type="hidden" name="event_shooting_date_id" value="{{ $shootingDate->id }}">
                <input type="hidden" name="status" value="hadir">
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Ambil Foto On-Site: {{ $app->extras->user->username ?? 'Extras' }}</div>
                <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">Foto extras di lokasi syuting sebagai bukti kehadiran untuk Client / PH. Maks. 10MB.</p>
                <input type="file" name="foto" accept="image/*" capture="environment" required style="width: 100%; margin-bottom: 12px;">
                <textarea name="catatan" rows="2" placeholder="Catatan kehadiran..." style="width: 100%; margin-bottom: 12px;"></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-brand">Simpan & Tandai Hadir</button>
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
                    <button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button>
                    <button type="submit" class="btn btn-brand">Simpan</button>
                </div>
            </form>
        </dialog>
    @empty
        <div class="card" style="text-align:center; color: var(--text-muted); padding: 30px 0;">Tidak ada Extras aktif di proyek ini.</div>
    @endforelse
@endif
</div>

@if ($rekap)
    <div class="card dash-perlu absen-perlu {{ $rekap['menunggu'] ? '' : 'is-aman' }}">
        <div class="card-title">
            <i class="ti ti-clipboard-list"></i> Perlu Tindakan
            @if ($rekap['menunggu'])
                <span class="badge badge-pending" style="margin-left: 8px;">{{ $rekap['menunggu'] }}</span>
            @endif
        </div>
        <div style="font-size: var(--fs-md); font-weight: 600; margin-bottom: 6px;">{{ $rekap['hadir'] }}/{{ $rekap['total'] }} hadir · {{ $rekap['menunggu'] }} menunggu</div>
        <div class="dash-sub" style="margin-bottom: 4px;">{{ $shootingDate->tanggal->translatedFormat('l, d M Y') }}</div>
        @if ($perluValidasi->isNotEmpty())
            <a href="#app-{{ $perluValidasi->first()->id }}" class="dash-row">
                <div><span class="badge badge-pending">Menunggu validasi</span> <strong>{{ $perluValidasi->count() }} selfie</strong></div>
                <span class="dash-sub">Cek &rarr;</span>
            </a>
        @endif
        @if ($belumDiabsen->isNotEmpty())
            <a href="#app-{{ $belumDiabsen->first()->id }}" class="dash-row">
                <div><span class="badge badge-netral">Belum diabsen</span> <strong>{{ $belumDiabsen->count() }} Extras</strong></div>
                <span class="dash-sub">Cek &rarr;</span>
            </a>
        @endif
        @unless ($rekap['menunggu'])
            <div class="dash-aman" role="status"><i class="ti ti-circle-check"></i> Semua sudah diabsen &amp; divalidasi.</div>
        @endunless
    </div>
@endif

<div class="card absen-jadwal">
    <div class="card-title">Jadwal Shooting Bulan Ini</div>
    <x-jadwal-calendar :events="$jadwalBulanIni" compact />
</div>
</div>

@push('scripts')
<script>
document.getElementById('search-extras')?.addEventListener('input', function () {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.extras-row').forEach(function (el) {
        el.style.display = el.dataset.nama.includes(q) ? '' : 'none';
    });
});
</script>
@endpush
@endsection
