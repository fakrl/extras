@extends('layouts.app')

@section('title', 'Lengkapi Jadwal: ' . $project->nama_produksi)

@section('content')
<div style="margin-bottom: 16px;">
    <a href="{{ route('client.jadwal.index') }}" style="font-size: 13px; color: var(--text-muted);">&larr; Kembali</a>
</div>

<div style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">{{ $project->nama_produksi }}</div>
<div style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">Isi jadwal per tanggal shooting</div>

@if (session('success'))
    <div class="alert-success" style="margin-bottom: 16px;">{{ session('success') }}</div>
@endif

@forelse ($project->shootingDates->sortBy('tanggal') as $date)
    <div class="card" style="margin-bottom: 16px;">
        <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">
            {{ $date->tanggal->translatedFormat('l, d F Y') }}
        </div>

        <form method="POST" action="{{ route('client.jadwal.store', $project) }}">
            @csrf
            <input type="hidden" name="tanggal" value="{{ $date->tanggal->format('Y-m-d') }}">

            <div class="form-row">
                <div>
                    <label for="lokasi-{{ $date->id }}">Lokasi</label>
                    <input type="text" name="lokasi" id="lokasi-{{ $date->id }}" value="{{ old('lokasi_' . $date->id, $date->lokasi) }}" placeholder="Nama studio / lokasi shooting">
                </div>
                <div>
                    <label for="jm-{{ $date->id }}">Jam Mulai</label>
                    <input type="time" name="jam_mulai" id="jm-{{ $date->id }}" value="{{ old('jam_mulai_' . $date->id, $date->jam_mulai ? substr($date->jam_mulai, 0, 5) : '') }}">
                </div>
                <div>
                    <label for="js-{{ $date->id }}">Jam Selesai</label>
                    <input type="time" name="jam_selesai" id="js-{{ $date->id }}" value="{{ old('jam_selesai_' . $date->id, $date->jam_selesai ? substr($date->jam_selesai, 0, 5) : '') }}">
                </div>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Catatan</label>
                <textarea name="catatan" rows="2" placeholder="Catatan tambahan untuk hari ini">{{ old('catatan_' . $date->id, $date->catatan) }}</textarea>
            </div>

            <div style="font-size: 13px; font-weight: 500; margin-bottom: 8px;">
                Daftar Panggilan
                <span style="color: var(--text-muted); font-weight: 400; font-size: 12px;">(nama karakter/kategori + jam panggil)</span>
            </div>
            <div class="panggilan-wrap" id="panggilan-{{ $date->id }}" data-next="{{ count($date->panggilan ?: [1]) }}">
                @php $panggilanList = $date->panggilan ?: [['nama' => '', 'jam' => '']]; @endphp
                @foreach ($panggilanList as $i => $p)
                    <div style="display:flex; gap:8px; margin-bottom:8px; align-items:center;">
                        <input type="text" name="panggilan[{{ $i }}][nama]" value="{{ $p['nama'] ?? '' }}" placeholder="Nama karakter / kategori" style="flex:2;">
                        <input type="time" name="panggilan[{{ $i }}][jam]" value="{{ $p["jam"] ?? "" }}" aria-label="Jam panggil" style="flex:1; max-width:130px;">
                        <button type="button" class="btn-icon-danger btn-rm-panggilan" style="display:none;">&times;</button>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-sm btn-add-panggilan" data-target="panggilan-{{ $date->id }}" style="margin-bottom: 16px;">+ Tambah Panggilan</button>

            <button type="submit" class="btn btn-brand" style="width: 100%;">Simpan Jadwal Tanggal Ini</button>
        </form>

        @if ($date->attendances->isNotEmpty())
            <div style="margin-top: 16px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                <div style="font-size: 13px; font-weight: 600; margin-bottom: 10px; color: var(--text-secondary);">
                    <i class="ti ti-camera"></i> Bukti Kehadiran ({{ $date->attendances->count() }} peserta)
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    @foreach ($date->attendances as $absen)
                        @if ($absen->foto_path)
                            <div style="text-align: center;">
                                <a href="{{ route('client.absensi.foto', $absen) }}" target="_blank">
                                    <img src="{{ route('client.absensi.foto', $absen) }}" alt="Foto absensi"
                                         style="width: 72px; height: 72px; object-fit: cover; border-radius: 6px; border: 2px solid {{ $absen->status_validasi === 'tervalidasi' ? 'var(--accent)' : 'var(--border-color)' }};">
                                </a>
                                <div style="font-size: var(--fs-xs); color: var(--text-secondary); margin-top: 2px;">{{ $absen->projectApplication?->extras?->user?->username }}</div>
                                <div style="font-size: var(--fs-xs); color: var(--text-muted);">
                                    @if ($absen->status_validasi === 'tervalidasi' && $absen->status === 'hadir')
                                        <span style="color: var(--accent);">✓ Hadir</span>
                                    @elseif ($absen->status_validasi === 'tervalidasi' && $absen->status === 'tidak_hadir')
                                        <span style="color: var(--danger, #d9534f);">Tidak hadir</span>
                                    @else
                                        <span>Belum divalidasi</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@empty
    <div class="card" style="color: var(--text-muted);">Belum ada tanggal shooting untuk proyek ini.</div>
@endforelse

@include('partials.project-attachments')
@endsection

@push('scripts')
<script>
(function () {
    function updateRmButtons(wrap) {
        var rows = wrap.querySelectorAll('.btn-rm-panggilan');
        rows.forEach(function (btn) {
            btn.style.display = rows.length > 1 ? 'block' : 'none';
        });
    }

    document.querySelectorAll('.btn-add-panggilan').forEach(function (btn) {
        var wrap = document.getElementById(btn.dataset.target);
        updateRmButtons(wrap);

        btn.addEventListener('click', function () {
            var n = wrap.dataset.next = (parseInt(wrap.dataset.next, 10) || 0) + 1;
            var row = document.createElement('div');
            row.style.cssText = 'display:flex; gap:8px; margin-bottom:8px; align-items:center;';
            row.innerHTML = '<input type="text" name="panggilan[' + n + '][nama]" placeholder="Nama karakter / kategori" style="flex:2;">' +
                '<input type="time" name="panggilan[' + n + '][jam]" aria-label="Jam panggil" style="flex:1; max-width:130px;">' +
                '<button type="button" class="btn-icon-danger btn-rm-panggilan">&times;</button>';
            wrap.appendChild(row);
            updateRmButtons(wrap);
        });

        wrap.addEventListener('click', function (e) {
            if (e.target.classList.contains('btn-rm-panggilan')) {
                e.target.parentElement.remove();
                updateRmButtons(wrap);
            }
        });
    });
})();
</script>
@endpush
