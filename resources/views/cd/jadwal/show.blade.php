@extends('layouts.app')

@section('title', 'Input Jadwal: ' . $project->nama_produksi)

@section('content')
<div style="margin-bottom: 16px;">
    <a href="{{ route('cd.jadwal.index') }}" style="font-size: 13px; color: var(--text-muted);">&larr; Kembali</a>
</div>

<div style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">{{ $project->nama_produksi }}</div>
<div style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">Input jadwal per tanggal shooting</div>

@if (session('success'))
    <div class="alert-success" style="margin-bottom: 16px;">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert-danger" style="margin-bottom: 16px;">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

@forelse ($project->shootingDates->sortBy('tanggal') as $date)
    <div class="card" style="margin-bottom: 16px;">
        <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">
            {{ $date->tanggal->translatedFormat('l, d F Y') }}
        </div>

        <form method="POST" action="{{ route('cd.jadwal.store', $project) }}">
            @csrf
            <input type="hidden" name="tanggal" value="{{ $date->tanggal->format('Y-m-d') }}">

            <div class="form-row">
                <div>
                    <label>Lokasi</label>
                    <input type="text" name="lokasi" value="{{ old('lokasi_' . $date->id, $date->lokasi) }}" placeholder="Nama studio / lokasi shooting">
                </div>
                <div>
                    <label>Jam Mulai</label>
                    <input type="time" name="jam_mulai" value="{{ old('jam_mulai_' . $date->id, $date->jam_mulai ? substr($date->jam_mulai, 0, 5) : '') }}">
                </div>
                <div>
                    <label>Jam Selesai</label>
                    <input type="time" name="jam_selesai" value="{{ old('jam_selesai_' . $date->id, $date->jam_selesai ? substr($date->jam_selesai, 0, 5) : '') }}">
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
            <div class="panggilan-wrap" id="panggilan-{{ $date->id }}">
                @php $panggilanList = $date->panggilan ?: [['nama' => '', 'jam' => '']]; @endphp
                @foreach ($panggilanList as $p)
                    <div style="display:flex; gap:8px; margin-bottom:8px; align-items:center;">
                        <input type="text" name="panggilan[][nama]" value="{{ $p['nama'] ?? '' }}" placeholder="Nama karakter / kategori" style="flex:2;">
                        <input type="text" name="panggilan[][jam]" value="{{ $p['jam'] ?? '' }}" placeholder="06:30" style="flex:1; max-width:100px;">
                        <button type="button" class="btn-icon-danger btn-rm-panggilan" style="display:none;">&times;</button>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-sm btn-add-panggilan" data-target="panggilan-{{ $date->id }}" style="margin-bottom: 16px;">+ Tambah Panggilan</button>

            <button type="submit" class="btn btn-brand" style="width: 100%;">Simpan Jadwal Tanggal Ini</button>
        </form>
    </div>
@empty
    <div class="card" style="color: var(--text-muted);">Belum ada tanggal shooting untuk proyek ini.</div>
@endforelse
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
            var row = document.createElement('div');
            row.style.cssText = 'display:flex; gap:8px; margin-bottom:8px; align-items:center;';
            row.innerHTML = '<input type="text" name="panggilan[][nama]" placeholder="Nama karakter / kategori" style="flex:2;">' +
                '<input type="text" name="panggilan[][jam]" placeholder="06:30" style="flex:1; max-width:100px;">' +
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
