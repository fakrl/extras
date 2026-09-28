@extends('layouts.app')

@section('title', 'Greenlight: ' . $castingProject->nama_produksi)

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

<div class="xfilter" aria-label="Filter status">
    @foreach (['' => 'Semua', 'menunggu' => 'Menunggu', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label)
        <a href="{{ route('cd.reviews.show', array_filter(['castingProject' => $castingProject->id, 'status' => $val ?: null])) }}"
           class="btn btn-sm {{ ($statusFilter ?? '') === $val ? 'btn-brand' : '' }}">{{ $label }}</a>
    @endforeach
</div>

{{-- AU.7: Filter demografis (server-side: gender, usia) + Live Search & filter klien (ukuran baju, warna kulit) --}}
<form method="GET" action="{{ route('cd.reviews.show', $castingProject) }}" class="xtoolbar">
    @if($statusFilter) <input type="hidden" name="status" value="{{ $statusFilter }}"> @endif
    <input type="search" id="filter-candidate-search" class="xtoolbar-cari" placeholder="Cari username atau peran…" aria-label="Cari kandidat">
    <details class="xtoolbar-more" @if($genderFilter || $usiaMin || $usiaMax) open @endif>
    <summary class="btn btn-sm"><i class="ti ti-adjustments-horizontal"></i> Filter</summary>
    <div class="xtoolbar-more-isi">
    <select name="gender" id="filter-gender" aria-label="Gender">
        <option value="">Semua gender</option>
        <option value="laki-laki" @selected(($genderFilter ?? '') === 'laki-laki')>Laki-laki</option>
        <option value="perempuan" @selected(($genderFilter ?? '') === 'perempuan')>Perempuan</option>
    </select>
    <label for="filter-usia-min">Usia</label>
    <input type="number" name="usia_min" id="filter-usia-min" placeholder="Min" value="{{ $usiaMin ?? '' }}" style="width: 70px;">
    <input type="number" name="usia_max" id="filter-usia-max" placeholder="Max" value="{{ $usiaMax ?? '' }}" style="width: 70px;" aria-label="Usia maksimal">
    <button type="submit" class="btn btn-sm">Terapkan</button>
    @if($genderFilter || $usiaMin || $usiaMax)
        <a href="{{ route('cd.reviews.show', array_filter(['castingProject' => $castingProject->id, 'status' => $statusFilter ?: null])) }}" class="btn btn-sm">Reset</a>
    @endif
    <select id="filter-ukuran-baju" aria-label="Ukuran baju">
        <option value="">Semua ukuran</option>
        <option value="S">S</option><option value="M">M</option>
        <option value="L">L</option><option value="XL">XL</option><option value="XXL">XXL</option>
    </select>
    <select id="filter-warna-kulit" aria-label="Warna kulit">
        <option value="">Semua warna kulit</option>
        <option value="sawo matang">Sawo Matang</option>
        <option value="kuning langsat">Kuning Langsat</option>
        <option value="hitam">Hitam</option>
        <option value="putih">Putih</option>
    </select>
    </div>
    </details>
</form>

{{-- BA.5: filter tag (client-side, cocok salah satu) + urutkan --}}
<div class="xfilter">
    @if ($tagDicari->isNotEmpty())
        <span class="xfilter-label">Tag dicari</span>
        @foreach ($tagDicari as $tag)
            <button type="button" class="btn btn-sm gl-tag" data-tag="{{ $tag->id }}" aria-pressed="false">#{{ $tag->nama }}</button>
        @endforeach
    @endif
    <label for="gl-urut" class="xfilter-label" style="margin: 0 0 0 auto;">Urutkan</label>
    <select id="gl-urut" style="width: auto; min-height: 36px; margin: 0; padding: 4px 10px; font-size: var(--fs-sm);">
        <option value="">Terbaru</option>
        <option value="cocok">Paling cocok</option>
    </select>
</div>

<label style="margin: 0 0 10px; display: inline-flex; align-items: center; gap: 8px; min-height: 36px; cursor: pointer;">
    <input type="checkbox" id="check-all-outer"> Pilih Semua Pending
</label>
<div id="gl-kosong" class="card" style="display: none; text-align: center; color: var(--text-muted); padding: 24px; margin-bottom: 14px;">Tidak ada kandidat yang cocok dengan filter.</div>

<form method="POST" action="{{ route('cd.reviews.review') }}" id="form-review">
    @csrf
    <input type="hidden" name="grade_cd" id="hidden-grade-cd">
    <input type="hidden" name="keputusan" id="hidden-keputusan">

    <div class="xgrid">
        @forelse ($applications as $app)
            @php
                $review = $app->cdReviews->first();
                $isPending = $app->status_partisipasi === 'diajukan_ke_cd';
                $badge = match ($app->status_partisipasi) {
                    'diajukan_ke_cd' => ['Menunggu', 'badge-pending'],
                    'lolos' => ['Approved · Kontrak', $app->badgeClass()],
                    'kontrak_ditandatangani' => ['Approved · Syuting', $app->badgeClass()],
                    'selesai_produksi' => ['Selesai', $app->badgeClass()],
                    'ditolak' => ['Rejected', 'badge-tolak'],
                    default => [$app->label(), $app->badgeClass()],
                };
                $riwayatApprove = \App\Models\CdReview::where('cd_id', auth()->id())
                    ->whereHas('projectApplication', fn($q) => $q->where('extras_id', $app->extras->id))
                    ->where('keputusan', 'approve')
                    ->count();
                $riwayatReject = \App\Models\CdReview::where('cd_id', auth()->id())
                    ->whereHas('projectApplication', fn($q) => $q->where('extras_id', $app->extras->id))
                    ->where('keputusan', 'reject')
                    ->count();
            @endphp
            @include('partials.extras-card', [
                'profile' => $app->extras,
                'aplikasi' => $app,
                'badge' => $badge,
                'highlight' => $isPending,
                'check' => $isPending ? ['name' => 'application_ids[]', 'class' => 'app-checkbox'] : null,
                'lihat' => ['onclick' => "bukaModalKandidat({$app->id})"],
                'aksi' => $isPending ? ['label' => 'Pilih', 'onclick' => "bukaModalKandidat({$app->id}, true)"] : null,
                'attrs' => [
                    'class' => 'kandidat-card',
                    'data-appid' => $app->id,
                    'data-tags' => implode(' ', $app->extras->categories->modelKeys()),
                    'data-cocok' => $app->persenCocok() ?? -1,
                    'data-urut' => $loop->index,
                    'data-alias' => $app->extras->user->username ?? '-',
                    'data-foto' => $app->extras->foto_profil_path ? route('extras.media.foto', $app->extras) : '',
                    'data-video' => $app->extras->video_profil_path ? route('extras.media.video', $app->extras) : '',
                    'data-usia' => $app->extras->usia ?? '',
                    'data-gender' => $app->extras->gender ?? '',
                    'data-tinggi' => $app->extras->tinggi_badan ?? '',
                    'data-ukuran-baju' => $app->extras->ukuran_baju ?? '',
                    'data-warna-kulit' => $app->extras->warna_kulit ?? '',
                    'data-pengalaman' => $app->extras->pengalaman ?? '',
                    'data-bahasa' => $app->extras->bahasa ?? '',
                    'data-karakter' => $app->castingProjectClass->nama_kelas ?? '-',
                    'data-kriteria' => $app->castingProjectClass->kriteria ?? '',
                    'data-is-pending' => $isPending ? '1' : '0',
                    'data-status' => $badge[0],
                    'data-status-class' => $badge[1],
                    'data-review-keputusan' => $review?->keputusan ?? '',
                    'data-review-grade' => $review?->grade_cd ?? '',
                    'data-review-tgl' => $review ? $review->created_at->format('d M Y') : '',
                    'data-riwayat-approve' => $riwayatApprove,
                    'data-riwayat-reject' => $riwayatReject,
                    'data-fotos' => json_encode($app->extras->photos->map(fn ($p) => route('extras.media.foto-tambahan', [$app->extras, $p->urutan]))->values()),
                ],
            ])
            <template id="mk-tags-{{ $app->id }}">@include('partials.tag-cocok', ['profile' => $app->extras, 'aplikasi' => $app])</template>
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

<dialog id="modal-kandidat" class="xmodal" aria-labelledby="mk-alias">
    <div class="xmodal-ph" id="mk-foto-wrap">
        <img id="mk-foto" src="" alt="" style="display: none;">
        <span id="mk-foto-empty" class="xcard-inisial" aria-hidden="true"></span>
        <button type="button" class="xmodal-x" onclick="document.getElementById('modal-kandidat').close()" aria-label="Tutup"><i class="ti ti-x"></i></button>
    </div>
    <div class="xmodal-body">
        <div class="xmodal-head">
            <div style="min-width: 0;">
                <div id="mk-alias" class="xmodal-name"></div>
                <div class="xmodal-sub" style="font-style: italic; color: var(--text-muted);">Nama asli & kontak dipegang JBTB</div>
            </div>
            <span id="mk-status" class="badge"></span>
        </div>

        <div id="mk-lightbox-slot" style="margin-top: 14px;"></div>

        <div id="mk-video-wrap" style="margin-top: 12px; display: none;">
            <video id="mk-video" controls style="width: 100%; border-radius: 10px; background: #000; aspect-ratio: 16/9;"></video>
        </div>

        <div class="xsec">Karakter dilamar</div>
        <div id="mk-karakter" style="font-size: var(--fs-base); font-weight: 600;"></div>
        <div id="mk-kriteria" style="color: var(--text-secondary); font-size: var(--fs-sm); margin-top: 4px;"></div>

        <div id="mk-tags-slot"></div>

        <div class="xsec">Fisik & kemampuan</div>
        <div id="mk-atribut" class="xkv"></div>

        <div class="xsec">Riwayat Anda dengan talent ini</div>
        <div id="mk-riwayat" style="font-size: var(--fs-sm);"></div>
    </div>
    <div id="mk-form-area"></div>
</dialog>

{{-- Shared lightbox (sibling modal-kandidat, BUKAN nested di dalamnya) --}}
<dialog id="mk-lb-dialog"
    style="border:1px solid var(--border-color); border-radius:12px; padding:0; background:#000; max-width:95vw; position:relative;"
    data-fotos="[]" data-current="0">
    <img id="mk-lb-img" src="" alt="" style="max-width:90vw; max-height:90vh; object-fit:contain; display:block;">
    <button type="button" onclick="document.getElementById('mk-lb-dialog').close()" aria-label="Tutup"
        style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,.6); color:#fff; border:none; border-radius:50%; width:28px; height:28px; cursor:pointer; font-size:16px; line-height:1;">×</button>
    <button type="button" id="mk-lb-prev" aria-label="Foto sebelumnya"
        style="position:absolute; top:50%; left:8px; transform:translateY(-50%); background:rgba(0,0,0,.6); color:#fff; border:none; border-radius:50%; width:32px; height:32px; cursor:pointer; font-size:20px; line-height:1; display:none;">‹</button>
    <button type="button" id="mk-lb-next" aria-label="Foto berikutnya"
        style="position:absolute; top:50%; right:8px; transform:translateY(-50%); background:rgba(0,0,0,.6); color:#fff; border:none; border-radius:50%; width:32px; height:32px; cursor:pointer; font-size:20px; line-height:1; display:none;">›</button>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    document.getElementById('check-all-outer')?.addEventListener('change', function (e) {
        document.querySelectorAll('.app-checkbox').forEach(function (cb) { if (cb.offsetParent !== null) cb.checked = e.target.checked; });
    });

    document.getElementById('btn-bulk-reject')?.addEventListener('click', function () {
        var jumlah = Array.from(document.querySelectorAll('.app-checkbox')).filter(function (cb) { return cb.checked; }).length;
        if (!jumlah) { alert('Centang minimal 1 kandidat.'); return; }
        if (!confirm('Reject ' + jumlah + ' kandidat terpilih? Kandidat yang direject tidak bisa diajukan ulang ke proyek ini.')) return;
        this.disabled = true;
        this.textContent = 'Memproses…';
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

    window.bukaModalKandidat = function (appId, fokusPilih) {
        var kartu = document.querySelector('[data-appid="' + appId + '"]');
        if (!kartu) return;

        document.getElementById('mk-alias').textContent = kartu.dataset.alias ? '@' + kartu.dataset.alias : '-';
        var mkStatus = document.getElementById('mk-status');
        mkStatus.className = 'badge ' + (kartu.dataset.statusClass || '');
        mkStatus.textContent = kartu.dataset.status || '';

        var foto = kartu.dataset.foto;
        var mkFoto = document.getElementById('mk-foto');
        var mkEmpty = document.getElementById('mk-foto-empty');
        var ph = kartu.querySelector('.xcard-ph');
        document.getElementById('mk-foto-wrap').style.setProperty('--h', ph ? ph.style.getPropertyValue('--h') : '140');
        mkEmpty.textContent = (kartu.querySelector('.xcard-inisial') || {}).textContent || '';
        if (foto) { mkFoto.src = foto; mkFoto.alt = 'Foto ' + (kartu.dataset.alias || ''); mkFoto.style.display = ''; mkEmpty.style.display = 'none'; }
        else { mkFoto.removeAttribute('src'); mkFoto.style.display = 'none'; mkEmpty.style.display = ''; }

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
        }

        var video = kartu.dataset.video;
        var vWrap = document.getElementById('mk-video-wrap');
        var vEl = document.getElementById('mk-video');
        if (video) { vEl.src = video; vWrap.style.display = ''; } else { vEl.src = ''; vWrap.style.display = 'none'; }

        var attrs = [
            ['Usia', kartu.dataset.usia ? kartu.dataset.usia + ' tahun' : ''],
            ['Gender', kartu.dataset.gender],
            ['Tinggi', kartu.dataset.tinggi ? kartu.dataset.tinggi + ' cm' : ''],
            ['Ukuran Baju', kartu.dataset.ukuranBaju],
            ['Warna Kulit', kartu.dataset.warnaKulit],
            ['Bahasa', kartu.dataset.bahasa],
            ['Pengalaman', kartu.dataset.pengalaman, true],
        ];
        var atEl = document.getElementById('mk-atribut');
        atEl.innerHTML = '';
        attrs.forEach(function (pair) {
            var box = document.createElement('div');
            if (pair[2]) box.className = 'full';
            var lbl = document.createElement('span'); lbl.className = 'xkv-l'; lbl.textContent = pair[0];
            var val = document.createElement('b'); val.textContent = pair[1] || '-';
            box.appendChild(lbl); box.appendChild(val); atEl.appendChild(box);
        });

        var tpl = document.getElementById('mk-tags-' + appId);
        document.getElementById('mk-tags-slot').innerHTML = tpl ? tpl.innerHTML : '';
        document.getElementById('mk-karakter').textContent = kartu.dataset.karakter || '-';
        document.getElementById('mk-kriteria').textContent = kartu.dataset.kriteria || '';
        document.getElementById('mk-riwayat').textContent =
            'Approve: ' + (kartu.dataset.riwayatApprove || '0') + ', Reject: ' + (kartu.dataset.riwayatReject || '0');

        var formArea = document.getElementById('mk-form-area');
        formArea.innerHTML = '';
        formArea.className = '';
        if (kartu.dataset.isPending === '1') {
            formArea.className = 'xmodal-foot';
            formArea.innerHTML =
                '<label for="mk-grade-select" style="flex-basis: 100%; margin: 0; font-weight: 600;">Grade Client (wajib untuk Approve)</label>' +
                '<select id="mk-grade-select" style="flex-basis: 100%; margin-bottom: 0;">' +
                '<option value="">Pilih Grade</option>' +
                '<option value="A">A</option>' +
                '<option value="B">B</option>' +
                '<option value="C">C</option>' +
                '</select>' +
                '<div style="flex-basis: 100%; font-size: var(--fs-xs); color: var(--text-muted);">A = terbaik/paling sesuai, B = sesuai, C = cukup (cadangan).</div>' +
                '<button type="button" class="btn btn-danger-outline" onclick="submitSingle(' + appId + ', \'reject\')">Reject</button>' +
                '<button type="button" class="btn btn-brand" onclick="submitSingle(' + appId + ', \'approve\')">Approve</button>';
        } else if (kartu.dataset.reviewKeputusan) {
            var kep = kartu.dataset.reviewKeputusan;
            var grCd = kartu.dataset.reviewGrade ? ' (Grade ' + kartu.dataset.reviewGrade + ')' : '';
            formArea.className = 'xmodal-foot';
            formArea.innerHTML =
                '<div style="font-size: 13px; color: var(--text-secondary);">' +
                'Keputusan: <strong>' + kep.charAt(0).toUpperCase() + kep.slice(1) + grCd + '</strong><br>' +
                '<span style="font-size: var(--fs-xs);">' + (kartu.dataset.reviewTgl || '') + '</span></div>';
        }

        dlg.showModal();
        dlg.scrollTop = 0;
        if (fokusPilih) document.getElementById('mk-grade-select')?.focus();
    };

    (function () {
        function applyDemoFilter() {
            var search = (document.getElementById('filter-candidate-search')?.value || '').toLowerCase().trim();
            var ukuran = document.getElementById('filter-ukuran-baju').value.toUpperCase();
            var warna = document.getElementById('filter-warna-kulit').value.toLowerCase();
            var tags = Array.from(document.querySelectorAll('.gl-tag[aria-pressed="true"]')).map(function (b) { return b.dataset.tag; });
            var tampil = 0;
            document.querySelectorAll('.kandidat-card').forEach(function (card) {
                var u = (card.dataset.ukuranBaju || '').toUpperCase();
                var w = (card.dataset.warnaKulit || '').toLowerCase();
                var text = (card.textContent || '').toLowerCase();
                var matchSearch = !search || text.includes(search);
                var punya = (card.dataset.tags || '').split(' ');
                var matchTag = !tags.length || tags.some(function (t) { return punya.includes(t); });
                var visible = matchSearch && matchTag && (!ukuran || u === ukuran) && (!warna || w === warna);
                card.style.display = visible ? '' : 'none';
                if (visible) tampil++;
            });
            document.getElementById('gl-kosong').style.display = tampil || !document.querySelector('.kandidat-card') ? 'none' : '';
        }
        document.querySelectorAll('.gl-tag').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var on = btn.getAttribute('aria-pressed') !== 'true';
                btn.setAttribute('aria-pressed', on);
                btn.classList.toggle('btn-brand', on);
                applyDemoFilter();
            });
        });
        document.getElementById('gl-urut').addEventListener('change', function () {
            var key = this.value === 'cocok' ? 'cocok' : 'urut';
            var grid = document.querySelector('.xgrid');
            Array.from(grid.querySelectorAll('.kandidat-card'))
                .sort(function (a, b) { return key === 'cocok' ? (b.dataset.cocok - a.dataset.cocok) || (a.dataset.urut - b.dataset.urut) : a.dataset.urut - b.dataset.urut; })
                .forEach(function (card) { grid.appendChild(card); });
        });
        ['filter-candidate-search', 'filter-ukuran-baju', 'filter-warna-kulit'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) { el.addEventListener('change', applyDemoFilter); el.addEventListener('input', applyDemoFilter); }
        });
    }());

    window.submitSingle = function (appId, keputusan) {
        if (keputusan === 'approve') {
            var grade = document.getElementById('mk-grade-select')?.value;
            if (!grade) { alert('Pilih Grade Client dulu sebelum Approve.'); return; }
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
