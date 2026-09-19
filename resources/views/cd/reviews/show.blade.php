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
<div style="display: flex; gap: 8px; margin-bottom: 10px; align-items: center; flex-wrap: wrap;">
    @foreach (['' => 'Semua', 'menunggu' => 'Menunggu', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label)
        <a href="{{ route('cd.reviews.show', array_filter(['castingProject' => $castingProject->id, 'status' => $val ?: null])) }}"
           class="btn btn-sm {{ ($statusFilter ?? '') === $val ? 'btn-brand' : '' }}">{{ $label }}</a>
    @endforeach
</div>

{{-- Select-all pending --}}
<div style="margin-bottom: 8px; display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-secondary);">
    <input type="checkbox" id="check-all-outer">
    <label for="check-all-outer" style="cursor: pointer;">Pilih Semua Pending</label>
</div>

<form method="POST" action="{{ route('cd.reviews.review') }}" id="form-review">
    @csrf
    <input type="hidden" name="grade_cd" id="hidden-grade-cd">
    <input type="hidden" name="keputusan" id="hidden-keputusan">

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; margin-bottom: 16px;">
        @forelse ($applications as $app)
            @php
                $review = $app->cdReviews->first();
                $isPending = $app->status_partisipasi === 'diajukan_ke_cd';
                [$statusLabel, $statusBadge] = match ($app->status_partisipasi) {
                    'diajukan_ke_cd' => ['Menunggu', 'badge-pending'],
                    'lolos', 'kontrak_ditandatangani', 'selesai_produksi' => ['Approved', 'badge-aktif'],
                    'ditolak' => ['Rejected', 'badge-tolak'],
                    default => [$app->status_partisipasi, ''],
                };
                $riwayatApprove = \App\Models\CdReview::where('cd_id', auth()->id())
                    ->whereHas('projectApplication', fn($q) => $q->where('extras_id', $app->extras->id))
                    ->where('keputusan', 'approve')
                    ->count();
                $riwayatReject = \App\Models\CdReview::where('cd_id', auth()->id())
                    ->whereHas('projectApplication', fn($q) => $q->where('extras_id', $app->extras->id))
                    ->where('keputusan', 'reject')
                    ->count();
                $fotosArr = $app->extras->photos->map(fn($p) => [
                    'url' => route('extras.media.foto-tambahan', [$app->extras, $p->urutan]),
                    'alt' => 'Foto ' . $p->urutan,
                ])->values()->all();
            @endphp

            <div class="kandidat-card"
                style="position: relative; border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; cursor: pointer; background: var(--bg-card);"
                onclick="bukaModalKandidat({{ $app->id }})"
                data-appid="{{ $app->id }}"
                data-alias="{{ $app->extras->user->username ?? '-' }}"
                data-foto="{{ $app->extras->foto_profil_path ? route('extras.media.foto', $app->extras) : '' }}"
                data-video="{{ $app->extras->video_profil_path ? route('extras.media.video', $app->extras) : '' }}"
                data-usia="{{ $app->extras->usia ?? '' }}"
                data-gender="{{ $app->extras->gender ?? '' }}"
                data-tinggi="{{ $app->extras->tinggi_badan ?? '' }}"
                data-ukuran-baju="{{ $app->extras->ukuran_baju ?? '' }}"
                data-warna-kulit="{{ $app->extras->warna_kulit ?? '' }}"
                data-pengalaman="{{ e($app->extras->pengalaman ?? '') }}"
                data-bahasa="{{ $app->extras->bahasa ?? '' }}"
                data-karakter="{{ $app->castingProjectClass->nama_kelas ?? '-' }}"
                data-kriteria="{{ e($app->castingProjectClass->kriteria ?? '') }}"
                data-grade-admin="{{ $app->grade ?? '' }}"
                data-is-pending="{{ $isPending ? '1' : '0' }}"
                data-review-keputusan="{{ $review?->keputusan ?? '' }}"
                data-review-grade="{{ $review?->grade_cd ?? '' }}"
                data-review-tgl="{{ $review ? $review->created_at->format('d M Y') : '' }}"
                data-riwayat-approve="{{ $riwayatApprove }}"
                data-riwayat-reject="{{ $riwayatReject }}"
                data-fotos="{{ json_encode(array_column($fotosArr, 'url')) }}"
            >
                <div style="position: absolute; top: 6px; right: 6px; z-index: 1;">
                    <span class="badge {{ $statusBadge }}" style="font-size: 10px;">{{ $statusLabel }}</span>
                </div>

                @if ($isPending)
                    <div style="position: absolute; top: 6px; left: 6px; z-index: 2;" onclick="event.stopPropagation()">
                        <input type="checkbox" name="application_ids[]" value="{{ $app->id }}" class="app-checkbox">
                    </div>
                @endif

                <div style="aspect-ratio: 3/4; background: var(--bg-nav-active); overflow: hidden;">
                    @if ($app->extras->foto_profil_path)
                        <img src="{{ route('extras.media.foto', $app->extras) }}" alt="{{ $app->extras->user->username ?? '' }}"
                             style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                            <i class="ti ti-user" style="font-size: 36px;"></i>
                        </div>
                    @endif
                </div>

                <div style="padding: 8px; font-size: 12.5px; font-weight: 600; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    {{ $app->extras->user->username ?? '-' }}
                </div>
            </div>
        @empty
            <div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 30px 0;">
                Tidak ada kandidat.
            </div>
        @endforelse
    </div>

    <div style="margin: 12px 0; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <button type="button" id="btn-bulk-reject" class="btn btn-danger-outline">Reject Terpilih</button>
    </div>
</form>

<style>
.lightbox-thumbs { display:grid; grid-template-columns: repeat(auto-fill, minmax(72px, 1fr)); gap: 6px; }
.lightbox-thumbs img { width:100%; aspect-ratio:1/1; object-fit:cover; border-radius:8px; cursor:pointer; }
</style>

<dialog id="modal-kandidat" style="border: 1px solid var(--border-color); border-radius: 14px; padding: 0; max-width: 500px; width: 95%; max-height: 90vh; overflow-y: auto;">
    <div style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div id="mk-alias" style="font-size: 16px; font-weight: 700;"></div>
            <button type="button" onclick="document.getElementById('modal-kandidat').close()" style="background: none; border: none; cursor: pointer; font-size: 20px; color: var(--text-muted);"><i class="ti ti-x"></i></button>
        </div>

        <div style="text-align: center; margin-bottom: 12px;">
            <div id="mk-foto-wrap" style="width: 120px; aspect-ratio: 3/4; margin: 0 auto 8px; border-radius: 12px; overflow: hidden; background: var(--bg-nav-active); display: flex; align-items: center; justify-content: center;">
                <img id="mk-foto" src="" alt="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                <i id="mk-foto-empty" class="ti ti-user" style="font-size: 32px; color: var(--text-muted);"></i>
            </div>
        </div>

        <div id="mk-lightbox-slot" style="margin-bottom: 12px;"></div>

        <div id="mk-video-wrap" style="margin-bottom: 12px; display: none;">
            <video id="mk-video" controls style="width: 100%; border-radius: 10px; background: #000; aspect-ratio: 16/9;"></video>
        </div>

        <div id="mk-atribut" style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px 14px; font-size: 13px; margin-bottom: 14px;"></div>

        <div style="margin-bottom: 12px; font-size: 13px;">
            <div style="font-weight: 600; margin-bottom: 4px;">Karakter Dilamar</div>
            <div id="mk-karakter"></div>
            <div id="mk-kriteria" style="color: var(--text-secondary); font-size: 12px; margin-top: 4px;"></div>
        </div>

        <div style="margin-bottom: 14px; font-size: 13px;">
            <span style="color: var(--text-secondary);">Rekomendasi Admin:</span>
            <strong id="mk-grade-admin"></strong>
        </div>

        <div style="margin-bottom: 14px; font-size: 13px; padding: 10px; border-radius: 8px; background: var(--bg-nav-active);">
            Riwayat dengan CD ini: <span id="mk-riwayat"></span>
        </div>

        <div id="mk-form-area"></div>
    </div>
</dialog>

{{-- Shared lightbox (sibling modal-kandidat, BUKAN nested di dalamnya) --}}
<dialog id="mk-lb-dialog"
    style="border:1px solid var(--border-color); border-radius:12px; padding:0; background:#000; max-width:95vw; position:relative;"
    data-fotos="[]" data-current="0">
    <img id="mk-lb-img" src="" alt="" style="max-width:90vw; max-height:90vh; object-fit:contain; display:block;">
    <button type="button" onclick="document.getElementById('mk-lb-dialog').close()"
        style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,.6); color:#fff; border:none; border-radius:50%; width:28px; height:28px; cursor:pointer; font-size:16px; line-height:1;">×</button>
    <button type="button" id="mk-lb-prev"
        style="position:absolute; top:50%; left:8px; transform:translateY(-50%); background:rgba(0,0,0,.6); color:#fff; border:none; border-radius:50%; width:32px; height:32px; cursor:pointer; font-size:20px; line-height:1; display:none;">‹</button>
    <button type="button" id="mk-lb-next"
        style="position:absolute; top:50%; right:8px; transform:translateY(-50%); background:rgba(0,0,0,.6); color:#fff; border:none; border-radius:50%; width:32px; height:32px; cursor:pointer; font-size:20px; line-height:1; display:none;">›</button>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    document.getElementById('check-all-outer')?.addEventListener('change', function (e) {
        document.querySelectorAll('.app-checkbox').forEach(function (cb) { cb.checked = e.target.checked; });
    });

    document.getElementById('btn-bulk-reject')?.addEventListener('click', function () {
        var any = Array.from(document.querySelectorAll('.app-checkbox')).some(function (cb) { return cb.checked; });
        if (!any) { alert('Centang minimal 1 kandidat.'); return; }
        document.getElementById('hidden-grade-cd').value = '';
        document.getElementById('hidden-keputusan').value = 'reject';
        document.getElementById('form-review').submit();
    });

    var dlg = document.getElementById('modal-kandidat');
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });

    var lbDlg = document.getElementById('mk-lb-dialog');
    var lbImg = document.getElementById('mk-lb-img');
    lbDlg.addEventListener('click', function (e) { if (e.target === lbDlg) lbDlg.close(); });
    document.getElementById('mk-lb-prev').addEventListener('click', function () {
        var fotos = JSON.parse(lbDlg.dataset.fotos);
        var idx = (parseInt(lbDlg.dataset.current) - 1 + fotos.length) % fotos.length;
        lbDlg.dataset.current = idx; lbImg.src = fotos[idx];
    });
    document.getElementById('mk-lb-next').addEventListener('click', function () {
        var fotos = JSON.parse(lbDlg.dataset.fotos);
        var idx = (parseInt(lbDlg.dataset.current) + 1) % fotos.length;
        lbDlg.dataset.current = idx; lbImg.src = fotos[idx];
    });

    function openMkLb(idx) {
        var fotos = JSON.parse(lbDlg.dataset.fotos);
        lbDlg.dataset.current = idx; lbImg.src = fotos[idx]; lbDlg.showModal();
    }

    window.bukaModalKandidat = function (appId) {
        var kartu = document.querySelector('[data-appid="' + appId + '"]');
        if (!kartu) return;

        document.getElementById('mk-alias').textContent = kartu.dataset.alias || '-';

        var foto = kartu.dataset.foto;
        var mkFoto = document.getElementById('mk-foto');
        var mkEmpty = document.getElementById('mk-foto-empty');
        if (foto) { mkFoto.src = foto; mkFoto.style.display = ''; mkEmpty.style.display = 'none'; }
        else { mkFoto.style.display = 'none'; mkEmpty.style.display = ''; }

        var slot = document.getElementById('mk-lightbox-slot');
        slot.innerHTML = '';
        var fotosData = [];
        try { fotosData = JSON.parse(kartu.dataset.fotos || '[]'); } catch (e) { fotosData = []; }
        lbDlg.dataset.fotos = JSON.stringify(fotosData);
        lbDlg.dataset.current = '0';
        if (fotosData.length > 0) {
            var thumbGrid = document.createElement('div');
            thumbGrid.className = 'lightbox-thumbs';
            fotosData.forEach(function (url, i) {
                var img = document.createElement('img');
                img.src = url; img.alt = 'Foto ' + (i + 1);
                img.addEventListener('click', (function (idx) { return function () { openMkLb(idx); }; })(i));
                thumbGrid.appendChild(img);
            });
            slot.appendChild(thumbGrid);
            document.getElementById('mk-lb-prev').style.display = fotosData.length > 1 ? '' : 'none';
            document.getElementById('mk-lb-next').style.display = fotosData.length > 1 ? '' : 'none';
        } else {
            var p = document.createElement('p');
            p.style.cssText = 'color:var(--text-muted);font-size:12px;margin:0;';
            p.textContent = 'Gallery masih kosong.';
            slot.appendChild(p);
        }

        var video = kartu.dataset.video;
        var vWrap = document.getElementById('mk-video-wrap');
        var vEl = document.getElementById('mk-video');
        if (video) { vEl.src = video; vWrap.style.display = ''; } else { vEl.src = ''; vWrap.style.display = 'none'; }

        var attrs = [
            ['Alias', kartu.dataset.alias],
            ['Usia', kartu.dataset.usia ? kartu.dataset.usia + ' tahun' : ''],
            ['Gender', kartu.dataset.gender],
            ['Tinggi', kartu.dataset.tinggi ? kartu.dataset.tinggi + ' cm' : ''],
            ['Ukuran Baju', kartu.dataset.ukuranBaju],
            ['Warna Kulit', kartu.dataset.warnaKulit],
            ['Pengalaman', kartu.dataset.pengalaman],
            ['Bahasa', kartu.dataset.bahasa],
        ];
        var atEl = document.getElementById('mk-atribut');
        atEl.innerHTML = '';
        attrs.forEach(function (pair) {
            var lbl = document.createElement('div'); lbl.style.color = 'var(--text-secondary)'; lbl.textContent = pair[0];
            var val = document.createElement('div'); val.textContent = pair[1] || '—';
            atEl.appendChild(lbl); atEl.appendChild(val);
        });

        document.getElementById('mk-karakter').textContent = kartu.dataset.karakter || '-';
        document.getElementById('mk-kriteria').textContent = kartu.dataset.kriteria || '';
        document.getElementById('mk-grade-admin').textContent = kartu.dataset.gradeAdmin || '—';
        document.getElementById('mk-riwayat').textContent =
            'Approve: ' + (kartu.dataset.riwayatApprove || '0') + ', Reject: ' + (kartu.dataset.riwayatReject || '0');

        var formArea = document.getElementById('mk-form-area');
        formArea.innerHTML = '';
        if (kartu.dataset.isPending === '1') {
            formArea.innerHTML =
                '<div style="border-top: 1px solid var(--border-color); padding-top: 12px; display: flex; flex-direction: column; gap: 8px;">' +
                '<label style="font-size: 13px; font-weight: 600;">Grade CD (wajib untuk Approve)</label>' +
                '<select id="mk-grade-select" style="width: 100%; min-height: 36px; padding: 4px 8px; margin-bottom: 0;">' +
                '<option value="">— Pilih Grade —</option>' +
                '<option value="A">A</option>' +
                '<option value="B">B</option>' +
                '<option value="C">C</option>' +
                '</select>' +
                '<div style="display: flex; gap: 8px;">' +
                '<button type="button" class="btn btn-brand" style="flex: 1;" onclick="submitSingle(' + appId + ', \'approve\')">Approve</button>' +
                '<button type="button" class="btn btn-danger-outline" style="flex: 1;" onclick="submitSingle(' + appId + ', \'reject\')">Reject</button>' +
                '</div></div>';
        } else if (kartu.dataset.reviewKeputusan) {
            var kep = kartu.dataset.reviewKeputusan;
            var grCd = kartu.dataset.reviewGrade ? ' — Grade ' + kartu.dataset.reviewGrade : '';
            formArea.innerHTML =
                '<div style="border-top: 1px solid var(--border-color); padding-top: 12px; font-size: 13px; color: var(--text-secondary);">' +
                'Keputusan: <strong>' + kep.charAt(0).toUpperCase() + kep.slice(1) + grCd + '</strong><br>' +
                '<span style="font-size: 11px;">' + (kartu.dataset.reviewTgl || '') + '</span></div>';
        }

        dlg.showModal();
    };

    window.submitSingle = function (appId, keputusan) {
        if (keputusan === 'approve') {
            var grade = document.getElementById('mk-grade-select')?.value;
            if (!grade) { alert('Pilih Grade CD dulu sebelum Approve.'); return; }
            document.getElementById('hidden-grade-cd').value = grade;
        } else {
            document.getElementById('hidden-grade-cd').value = '';
        }
        document.querySelectorAll('.app-checkbox').forEach(function (cb) { cb.checked = false; });
        var cb = document.querySelector('input[type="checkbox"][value="' + appId + '"]');
        if (cb) {
            cb.checked = true;
        } else {
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'application_ids[]';
            inp.value = appId;
            document.getElementById('form-review').appendChild(inp);
        }
        document.getElementById('hidden-keputusan').value = keputusan;
        document.getElementById('modal-kandidat').close();
        document.getElementById('form-review').submit();
    };
})();
</script>
@endpush
