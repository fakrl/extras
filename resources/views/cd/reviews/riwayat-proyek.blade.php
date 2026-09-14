@extends('layouts.app')

@section('title', 'Riwayat — ' . $castingProject->nama_produksi)

@section('content')
<div style="margin-bottom:12px;">
    <a href="{{ route('cd.riwayat') }}" style="font-size:13px; color:var(--text-secondary); text-decoration:none;">
        &larr; Kembali ke Riwayat
    </a>
</div>

<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px; margin-bottom:14px;">
    <div>
        <div style="font-size:16px; font-weight:600; margin-bottom:2px;">{{ $castingProject->nama_produksi }}</div>
        <p style="color:var(--text-secondary); margin:0; font-size:13.5px;">Kandidat yang kamu review di proyek ini.</p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="{{ route('cd.riwayat.export.xlsx', $castingProject) }}" class="btn btn-sm">Ekspor ke Excel</a>
        <a href="{{ route('cd.riwayat.export.pdf', $castingProject) }}" class="btn btn-sm">Ekspor ke PDF</a>
    </div>
</div>

<div style="display:flex; gap:8px; margin-bottom:10px; align-items:center;">
    <label style="margin:0; font-size:12.5px; color:var(--text-secondary);">Keputusan:</label>
    <select id="filter-keputusan" style="width:auto; min-height:unset; margin-bottom:0; padding:4px 8px; font-size:12.5px;">
        <option value="">Semua</option>
        <option value="approve">Approve</option>
        <option value="reject">Reject</option>
    </select>
</div>

<div class="card">
    <table id="tabel-riwayat">
        <thead>
            <tr><th>Alias</th><th>Keputusan</th><th>Tanggal</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($reviews as $review)
                @php
                    $extras = $review->projectApplication->extras;
                    $fotoUrl  = $extras?->foto_profil_path  ? route('extras.media.foto', $extras)  : '';
                    $videoUrl = $extras?->video_profil_path ? route('extras.media.video', $extras) : '';
                    $photos   = $extras?->photos
                        ? $extras->photos->map(fn ($p) => route('extras.media.foto-tambahan', [$extras, $p->urutan]))->values()->toJson()
                        : '[]';
                @endphp
                <tr data-keputusan="{{ $review->keputusan }}"
                    data-alias="{{ $extras?->user?->username ?? '-' }}"
                    data-foto="{{ $fotoUrl }}"
                    data-video="{{ $videoUrl }}"
                    data-photos="{{ $photos }}"
                    data-usia="{{ $extras?->usia ?? '' }}"
                    data-gender="{{ $extras?->gender ?? '' }}"
                    data-tinggi="{{ $extras?->tinggi_badan ?? '' }}"
                    data-ukuran-baju="{{ $extras?->ukuran_baju ?? '' }}"
                    data-warna-kulit="{{ $extras?->warna_kulit ?? '' }}"
                    data-pengalaman="{{ $extras?->pengalaman ?? '' }}"
                    data-bahasa="{{ $extras?->bahasa ?? '' }}">
                    <td>{{ $extras?->user?->username ?? '-' }}</td>
                    <td>
                        <span class="badge {{ $review->keputusan === 'approve' ? 'badge-aktif' : 'badge-tolak' }}">
                            {{ ucfirst($review->keputusan) }}
                        </span>
                    </td>
                    <td style="color:var(--text-secondary); font-size:13px;">
                        {{ $review->created_at->format('d M Y') }}
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-icon btn-lihat-profil" title="Lihat Profil">
                            <i class="ti ti-eye"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center; color:var(--text-muted); padding:20px 0;">
                        Belum ada kandidat yang direview di proyek ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<dialog id="modal-profil">
    <div class="modal-body" style="padding:20px; min-width:260px; max-width:440px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <div id="modal-alias" style="font-size:15px; font-weight:600;"></div>
            <button type="button" id="modal-close" style="background:none;border:none;cursor:pointer;font-size:18px;color:var(--text-muted);">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <div id="modal-foto-wrap" style="margin-bottom:12px; text-align:center;">
            <img id="modal-foto" src="" alt="" style="max-width:100%; max-height:220px; border-radius:8px; display:none;">
            <div id="modal-foto-empty" style="display:none; width:80px; height:80px; border-radius:50%; background:var(--border-color); align-items:center; justify-content:center; margin:0 auto;">
                <i class="ti ti-user" style="font-size:32px; color:var(--text-muted);"></i>
            </div>
        </div>
        <div id="modal-photos-grid" style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px;"></div>
        <div id="modal-video-wrap" style="display:none; margin-bottom:12px;">
            <a id="modal-video-link" href="" target="_blank" class="btn btn-sm">Lihat Video</a>
        </div>
        <div id="modal-atribut" style="font-size:13px; display:grid; grid-template-columns:1fr 1fr; gap:6px 12px;"></div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    document.getElementById('filter-keputusan')?.addEventListener('change', function () {
        var val = this.value;
        document.querySelectorAll('#tabel-riwayat tbody tr[data-keputusan]').forEach(function (tr) {
            tr.style.display = (!val || tr.dataset.keputusan === val) ? '' : 'none';
        });
    });

    var dlg = document.getElementById('modal-profil');

    document.querySelectorAll('.btn-lihat-profil').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tr = btn.closest('tr');
            var alias = tr.dataset.alias || '-';
            var foto = tr.dataset.foto || '';
            var video = tr.dataset.video || '';
            var photos = [];
            try { photos = JSON.parse(tr.dataset.photos || '[]'); } catch (e) {}

            document.getElementById('modal-alias').textContent = alias;

            var imgEl = document.getElementById('modal-foto');
            var emptyEl = document.getElementById('modal-foto-empty');
            if (foto) {
                imgEl.src = foto;
                imgEl.style.display = '';
                emptyEl.style.display = 'none';
            } else {
                imgEl.style.display = 'none';
                emptyEl.style.display = 'flex';
            }

            var grid = document.getElementById('modal-photos-grid');
            grid.innerHTML = '';
            photos.forEach(function (url) {
                var a = document.createElement('a');
                a.href = url;
                a.target = '_blank';
                var img = document.createElement('img');
                img.src = url;
                img.style.cssText = 'width:56px;height:56px;object-fit:cover;border-radius:6px;';
                a.appendChild(img);
                grid.appendChild(a);
            });

            var videoWrap = document.getElementById('modal-video-wrap');
            if (video) {
                document.getElementById('modal-video-link').href = video;
                videoWrap.style.display = '';
            } else {
                videoWrap.style.display = 'none';
            }

            var atribut = [
                ['Usia', tr.dataset.usia ? tr.dataset.usia + ' tahun' : ''],
                ['Gender', tr.dataset.gender],
                ['Tinggi', tr.dataset.tinggi ? tr.dataset.tinggi + ' cm' : ''],
                ['Ukuran Baju', tr.dataset.ukuranBaju],
                ['Warna Kulit', tr.dataset.warnaKulit],
                ['Pengalaman', tr.dataset.pengalaman],
                ['Bahasa', tr.dataset.bahasa],
            ];
            var atEl = document.getElementById('modal-atribut');
            atEl.innerHTML = '';
            atribut.forEach(function (pair) {
                var label = document.createElement('div');
                label.style.cssText = 'color:var(--text-secondary);';
                label.textContent = pair[0];
                var val = document.createElement('div');
                val.textContent = pair[1] || '—';
                atEl.appendChild(label);
                atEl.appendChild(val);
            });

            dlg.showModal();
        });
    });

    document.getElementById('modal-close').addEventListener('click', function () {
        dlg.close();
    });

    dlg.addEventListener('click', function (e) {
        if (e.target === dlg) dlg.close();
    });
})();
</script>
@endpush
