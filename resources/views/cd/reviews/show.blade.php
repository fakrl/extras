@extends('layouts.app')

@section('title', 'Greenlight — ' . $castingProject->nama_produksi)

@section('content')
<div style="margin-bottom: 12px;">
    <a href="{{ route('cd.reviews.index') }}" style="font-size: 13px; color: var(--text-secondary); text-decoration: none;">&larr; Kembali ke Greenlight</a>
</div>

<div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px; margin-bottom: 14px;">
    <div>
        <div style="font-size: 16px; font-weight: 600; margin-bottom: 2px;">{{ $castingProject->nama_produksi }}</div>
        <p style="color: var(--text-secondary); margin: 0; font-size: 13.5px;">Semua kandidat di proyek ini (pending dan sudah diputus).</p>
        @if ($castingProject->link_grup)
            <a href="{{ $castingProject->link_grup }}" target="_blank" style="font-size: 12.5px;">Link Grup Koordinasi</a>
        @endif
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('cd.riwayat.export.xlsx', $castingProject) }}" class="btn btn-sm">Ekspor Excel</a>
        <a href="{{ route('cd.riwayat.export.pdf', $castingProject) }}" class="btn btn-sm">Ekspor PDF</a>
    </div>
</div>

{{-- Filter status --}}
<div style="display: flex; gap: 8px; margin-bottom: 10px; align-items: center;">
    @foreach (['' => 'Semua', 'menunggu' => 'Menunggu', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label)
        <a href="{{ route('cd.reviews.show', array_filter(['castingProject' => $castingProject->id, 'status' => $val ?: null])) }}"
           class="btn btn-sm {{ ($statusFilter ?? '') === $val ? 'btn-brand' : '' }}">{{ $label }}</a>
    @endforeach
</div>

<form method="POST" action="{{ route('cd.reviews.review') }}" id="form-review">
    @csrf
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="check-all"></th>
                    <th>Alias</th>
                    <th>Karakter</th>
                    <th>Kriteria</th>
                    <th>Rek. Admin</th>
                    <th>Status</th>
                    <th>Keputusan CD</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($applications as $app)
                    @php
                        $review = $app->cdReviews->first();
                        $statusLabel = match ($app->status_partisipasi) {
                            'diajukan_ke_cd' => ['Menunggu', 'badge-pending'],
                            'lolos', 'kontrak_ditandatangani', 'selesai_produksi' => ['Approved', 'badge-aktif'],
                            'ditolak' => ['Rejected', 'badge-tolak'],
                            default => [$app->status_partisipasi, ''],
                        };
                    @endphp
                    <tr>
                        <td>
                            @if ($app->status_partisipasi === 'diajukan_ke_cd')
                                <input type="checkbox" name="application_ids[]" value="{{ $app->id }}" class="app-checkbox">
                            @endif
                        </td>
                        <td>{{ $app->extras->user->username ?? '-' }}</td>
                        <td>{{ $app->castingProjectClass->nama_kelas ?? '-' }}</td>
                        <td style="font-size: 12px; color: var(--text-secondary); max-width: 160px;">{{ $app->castingProjectClass->kriteria ?? '-' }}</td>
                        <td>{{ $app->grade ?? '-' }}</td>
                        <td><span class="badge {{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span></td>
                        <td style="font-size: 12.5px;">
                            @if ($review)
                                {{ ucfirst($review->keputusan) }}
                                @if ($review->grade_cd) &mdash; Grade {{ $review->grade_cd }} @endif
                                <span style="color: var(--text-secondary); font-size: 11px; display: block;">{{ $review->created_at->format('d M Y') }}</span>
                            @elseif ($app->status_partisipasi === 'diajukan_ke_cd')
                                <div style="display: flex; gap: 4px; align-items: center; flex-wrap: wrap;">
                                    <select class="grade-select" data-appid="{{ $app->id }}" style="width: auto; padding: 3px 6px; font-size: 12px; min-height: unset;">
                                        <option value="">Grade</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-brand btn-approve-single" data-appid="{{ $app->id }}">Approve</button>
                                    <button type="button" class="btn btn-sm btn-danger-outline btn-reject-single" data-appid="{{ $app->id }}">Reject</button>
                                </div>
                            @else
                                <span style="color: var(--text-muted);">—</span>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-icon btn-lihat-profil"
                                data-alias="{{ $app->extras->user->username ?? '-' }}"
                                data-foto="{{ $app->extras->foto_profil_path ? route('extras.media.foto', $app->extras) : '' }}"
                                data-video="{{ $app->extras->video_profil_path ? route('extras.media.video', $app->extras) : '' }}"
                                data-photos="{{ $app->extras->photos->map(fn ($p) => route('extras.media.foto-tambahan', [$app->extras, $p->urutan]))->toJson() }}"
                                data-usia="{{ $app->extras->usia ?? '' }}"
                                data-gender="{{ $app->extras->gender ?? '' }}"
                                data-tinggi="{{ $app->extras->tinggi_badan ?? '' }}"
                                data-ukuran-baju="{{ $app->extras->ukuran_baju ?? '' }}"
                                data-warna-kulit="{{ $app->extras->warna_kulit ?? '' }}"
                                data-pengalaman="{{ $app->extras->pengalaman ?? '' }}"
                                data-bahasa="{{ $app->extras->bahasa ?? '' }}"
                                title="Lihat Profil">
                                <i class="ti ti-eye"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 20px 0;">Tidak ada kandidat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <input type="hidden" name="grade_cd" id="bulk-grade-cd">
    <input type="hidden" name="keputusan" id="bulk-keputusan">

    <div style="margin-top: 12px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <select id="bulk-grade-select" style="min-height: unset; padding: 4px 8px; width: auto; font-size: 13px;">
            <option value="">— Grade Bulk —</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
        </select>
        <button type="button" id="btn-bulk-approve" class="btn btn-brand">Greenlight Terpilih</button>
        <button type="button" id="btn-bulk-reject" class="btn btn-danger-outline">Reject Terpilih</button>
    </div>
</form>

<dialog id="modal-profil">
    <div class="modal-body" style="padding: 20px; min-width: 260px; max-width: 440px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div id="modal-alias" style="font-size: 15px; font-weight: 600;"></div>
            <button type="button" id="modal-close" style="background: none; border: none; cursor: pointer; font-size: 18px; color: var(--text-muted);"><i class="ti ti-x"></i></button>
        </div>
        <div id="modal-foto-wrap" style="margin-bottom: 12px; text-align: center;">
            <img id="modal-foto" src="" alt="" style="max-width: 100%; max-height: 220px; border-radius: 8px; display: none;">
            <div id="modal-foto-empty" style="display: none; width: 80px; height: 80px; border-radius: 50%; background: var(--border-color); align-items: center; justify-content: center; margin: 0 auto;">
                <i class="ti ti-user" style="font-size: 32px; color: var(--text-muted);"></i>
            </div>
        </div>
        <div id="modal-photos-grid" style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;"></div>
        <div id="modal-video-wrap" style="display: none; margin-bottom: 12px;">
            <a id="modal-video-link" href="" target="_blank" class="btn btn-sm">Lihat Video</a>
        </div>
        <div id="modal-atribut" style="font-size: 13px; display: grid; grid-template-columns: 1fr 1fr; gap: 6px 12px;"></div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    document.getElementById('check-all')?.addEventListener('change', function (e) {
        document.querySelectorAll('.app-checkbox').forEach(function (cb) { cb.checked = e.target.checked; });
    });

    document.querySelectorAll('.btn-approve-single').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var appId = btn.dataset.appid;
            var grade = document.querySelector('.grade-select[data-appid="' + appId + '"]').value;
            if (!grade) { alert('Pilih Grade CD dulu sebelum Approve.'); return; }
            document.querySelectorAll('.app-checkbox').forEach(function (cb) { cb.checked = false; });
            var cb = document.querySelector('input[type="checkbox"][value="' + appId + '"]');
            if (cb) cb.checked = true;
            document.getElementById('bulk-grade-cd').value = grade;
            document.getElementById('bulk-keputusan').value = 'approve';
            document.getElementById('form-review').submit();
        });
    });

    document.querySelectorAll('.btn-reject-single').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var appId = btn.dataset.appid;
            document.querySelectorAll('.app-checkbox').forEach(function (cb) { cb.checked = false; });
            var cb = document.querySelector('input[type="checkbox"][value="' + appId + '"]');
            if (cb) cb.checked = true;
            document.getElementById('bulk-grade-cd').value = '';
            document.getElementById('bulk-keputusan').value = 'reject';
            document.getElementById('form-review').submit();
        });
    });

    document.getElementById('btn-bulk-approve')?.addEventListener('click', function () {
        var grade = document.getElementById('bulk-grade-select').value;
        if (!grade) { alert('Pilih Grade CD untuk bulk approve.'); return; }
        var anyChecked = Array.from(document.querySelectorAll('.app-checkbox')).some(function (cb) { return cb.checked; });
        if (!anyChecked) { alert('Centang minimal 1 kandidat.'); return; }
        document.getElementById('bulk-grade-cd').value = grade;
        document.getElementById('bulk-keputusan').value = 'approve';
        document.getElementById('form-review').submit();
    });

    document.getElementById('btn-bulk-reject')?.addEventListener('click', function () {
        var anyChecked = Array.from(document.querySelectorAll('.app-checkbox')).some(function (cb) { return cb.checked; });
        if (!anyChecked) { alert('Centang minimal 1 kandidat.'); return; }
        document.getElementById('bulk-grade-cd').value = '';
        document.getElementById('bulk-keputusan').value = 'reject';
        document.getElementById('form-review').submit();
    });

    var dlg = document.getElementById('modal-profil');
    document.querySelectorAll('.btn-lihat-profil').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var alias = btn.dataset.alias || '-';
            var foto = btn.dataset.foto || '';
            var video = btn.dataset.video || '';
            var photos = [];
            try { photos = JSON.parse(btn.dataset.photos || '[]'); } catch (e) {}

            document.getElementById('modal-alias').textContent = alias;
            var imgEl = document.getElementById('modal-foto');
            var emptyEl = document.getElementById('modal-foto-empty');
            if (foto) { imgEl.src = foto; imgEl.style.display = ''; emptyEl.style.display = 'none'; }
            else { imgEl.style.display = 'none'; emptyEl.style.display = 'flex'; }

            var grid = document.getElementById('modal-photos-grid');
            grid.innerHTML = '';
            photos.forEach(function (url) {
                var a = document.createElement('a'); a.href = url; a.target = '_blank';
                var img = document.createElement('img'); img.src = url;
                img.style.cssText = 'width: 56px; height: 56px; object-fit: cover; border-radius: 6px;';
                a.appendChild(img); grid.appendChild(a);
            });

            var videoWrap = document.getElementById('modal-video-wrap');
            if (video) { document.getElementById('modal-video-link').href = video; videoWrap.style.display = ''; }
            else { videoWrap.style.display = 'none'; }

            var atribut = [
                ['Usia', btn.dataset.usia ? btn.dataset.usia + ' tahun' : ''],
                ['Gender', btn.dataset.gender],
                ['Tinggi', btn.dataset.tinggi ? btn.dataset.tinggi + ' cm' : ''],
                ['Ukuran Baju', btn.dataset.ukuranBaju],
                ['Warna Kulit', btn.dataset.warnaKulit],
                ['Pengalaman', btn.dataset.pengalaman],
                ['Bahasa', btn.dataset.bahasa],
            ];
            var atEl = document.getElementById('modal-atribut');
            atEl.innerHTML = '';
            atribut.forEach(function (pair) {
                var label = document.createElement('div'); label.style.cssText = 'color: var(--text-secondary);'; label.textContent = pair[0];
                var val = document.createElement('div'); val.textContent = pair[1] || '—';
                atEl.appendChild(label); atEl.appendChild(val);
            });
            dlg.showModal();
        });
    });

    document.getElementById('modal-close').addEventListener('click', function () { dlg.close(); });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
})();
</script>
@endpush
