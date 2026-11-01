@extends('layouts.app')

@section('title', 'Buka Lowongan Casting')

@section('content')
<div style="margin-bottom: 12px;">
    <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-tonal" style="display: inline-flex; align-items: center; gap: 6px;">
        <i class="ti ti-arrow-left"></i> Kembali ke Daftar Proyek
    </a>
</div>
<div class="card">
    <div style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Buka Lowongan Casting Baru</div>

    <form method="POST" action="{{ route('admin.projects.store') }}" enctype="multipart/form-data">
        @csrf
        <p class="wajib-ket"><span class="wajib">*</span> wajib diisi</p>

        {{-- 1. Identitas Produksi & Penugasan --}}
        <div class="form-row" style="margin-bottom: 16px;">
            <div>
                <label for="nama_produksi" style="font-weight: 600;">Nama Produksi <span class="wajib" aria-hidden="true">*</span></label>
                <input type="text" name="nama_produksi" id="nama_produksi" value="{{ old('nama_produksi') }}" placeholder="misal: Iklan TVC Bank Digital" required>
            </div>
            <div>
                <label for="link_grup" style="font-weight: 600;">Link Grup Koordinasi <span style="color: var(--text-muted); font-weight: 400; font-size: 12px;">(WA/Telegram, dapat diisi menyusul)</span></label>
                <input type="url" name="link_grup" id="link_grup" value="{{ old('link_grup') }}" placeholder="https://chat.whatsapp.com/... atau https://t.me/...">
            </div>
        </div>

        @php $isSa = auth()->user()->isSuperAdmin(); @endphp
        @include('partials.proyek-pic-client', ['adminId' => $isSa ? '' : auth()->id(), 'clientId' => null, 'adminKosong' => $isSa ? '- Pilih Admin -' : 'Saya sendiri', 'adminWajib' => $isSa])

        {{-- 2. Poster Produksi --}}
        <div style="background: var(--bg-card-hover, rgba(255,255,255,0.02)); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 14px; margin-bottom: 16px;">
            <label for="poster_path" style="font-weight: 600; display: block; margin-bottom: 8px;">
                Poster Produksi <span style="color: var(--text-muted); font-weight: 400; font-size: 12px;">(format JPG/PNG/WebP, maks. 2MB)</span>
            </label>
            <input type="file" name="poster_path" id="poster_path" accept="image/jpeg,image/png,image/webp">
        </div>

        {{-- 3. Parameter Pendaftaran & Kuota --}}
        <div class="form-row" style="margin-bottom: 14px;">
            <div>
                <label for="deadline" style="font-weight: 600;">Deadline Pendaftaran <span class="wajib" aria-hidden="true">*</span></label>
                <input type="date" name="deadline" id="deadline" value="{{ old('deadline') }}" required>
            </div>
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label for="kuota" style="font-weight: 600; margin: 0;">Kuota Total Proyek <span class="wajib" aria-hidden="true">*</span></label>
                    <span id="kuota-hint" style="font-size: 11.5px; color: var(--accent); font-weight: 600;"></span>
                </div>
                <input type="number" name="kuota" id="kuota" value="{{ old('kuota') }}" min="1" required style="margin-top: 6px;">
                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 3px;">Total kebutuhan extras (otomatis sinkron saat kuota peran diisi).</div>
            </div>
        </div>

        {{-- Opsi Status Pendaftaran (Urgent & Nego) --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; margin-bottom: 16px;">
            <label style="display: flex; gap: 10px; align-items: flex-start; background: var(--bg-card-hover, rgba(255,255,255,0.02)); border: 1px solid var(--border-color); border-radius: var(--radius-sm, 8px); padding: 12px; margin: 0; cursor: pointer;">
                <input type="checkbox" name="is_urgent" value="1" id="is_urgent" @checked(old('is_urgent')) style="margin-top: 3px;">
                <div>
                    <div style="font-weight: 600; font-size: 13px;">Butuh Dadakan / Urgent</div>
                    <div style="font-size: 11.5px; color: var(--text-muted); line-height: 1.35; margin-top: 2px;">Tandai proyek dengan badge urgent agar diprioritaskan oleh Extras.</div>
                </div>
            </label>

            <label style="display: flex; gap: 10px; align-items: flex-start; background: var(--bg-card-hover, rgba(255,255,255,0.02)); border: 1px solid var(--border-color); border-radius: var(--radius-sm, 8px); padding: 12px; margin: 0; cursor: pointer;">
                <input type="checkbox" name="nego_terbuka" value="1" id="nego_terbuka" @checked(old() ? old('nego_terbuka') : true) style="margin-top: 3px;">
                <div>
                    <div style="font-weight: 600; font-size: 13px;">Fee Bisa Dinego</div>
                    <div style="font-size: 11.5px; color: var(--text-muted); line-height: 1.35; margin-top: 2px;">Bila aktif, extras dapat menawar nominal honor yang ditawarkan.</div>
                </div>
            </label>
        </div>

        <hr>
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">
            Tanggal Shooting <span class="wajib" aria-hidden="true">*</span> <span style="color: var(--text-muted); font-weight: 400; font-size: 12.5px;">(bisa lebih dari satu, tidak harus berurutan)</span>
        </div>
        <div id="tanggal-wrap">
            @foreach (old('tanggal_shooting', ['']) as $tgl)
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                    <input type="date" name="tanggal_shooting[]" class="input-inline" value="{{ $tgl }}" required>
                    <button type="button" class="btn-icon-danger btn-remove-row" title="Hapus tanggal" aria-label="Hapus tanggal" style="display:none">&times;</button>
                </div>
            @endforeach
        </div>
        <button type="button" id="btn-add-tanggal" class="btn btn-sm" style="margin-bottom: 20px;">+ Tambah Tanggal</button>

        <hr>
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">
            Peran yang Dibutuhkan & Breakdown <span style="color: var(--text-muted); font-weight: 400; font-size: 12.5px;">(minimal satu peran)</span>
        </div>
        <div id="kelas-wrap">
            @php $kelasRows = old('kelas') ?: [[]]; @endphp
            @foreach ($kelasRows as $i => $k)
                @include('admin.projects._kelas-row', ['i' => $i, 'k' => $k, 'kunci' => false])
            @endforeach
        </div>
        <button type="button" id="btn-add-kelas" class="btn btn-sm" style="margin-bottom: 24px;">+ Tambah Peran</button>

        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.projects.index') }}" class="btn">Batal</a>
            <button type="submit" class="btn btn-brand" style="flex: 1;">Simpan Proyek Casting</button>
        </div>
    </form>
    @if (auth()->user()->isSuperAdmin())
        @include('partials.admin-baru-modal')
        @include('partials.client-baru-modal')
    @endif
</div>
@endsection

@push('scripts')
<template id="tag-chips-tpl">@include('partials.tag-input', ['name' => 'kelas[__i__][tag_nama]', 'selected' => []])</template>
<script>
    (function () {
        var tanggalWrap = document.getElementById('tanggal-wrap');
        document.getElementById('btn-add-tanggal').addEventListener('click', function () {
            var row = document.createElement('div');
            row.style.cssText = 'display:flex; gap:8px; margin-bottom:8px; align-items:center;';
            row.innerHTML = '<input type="date" name="tanggal_shooting[]" class="input-inline" required>' +
                '<button type="button" class="btn-icon-danger btn-remove-row" aria-label="Hapus tanggal" title="Hapus tanggal">&times;</button>';
            tanggalWrap.appendChild(row);
            updateRemoveButtons(tanggalWrap, '.btn-remove-row');
        });
        tanggalWrap.addEventListener('click', function (e) {
            if (e.target.classList.contains('btn-remove-row')) {
                e.target.parentElement.remove();
                updateRemoveButtons(tanggalWrap, '.btn-remove-row');
            }
        });

        var kelasWrap = document.getElementById('kelas-wrap');
        var kelasIndex = {{ collect($kelasRows)->keys()->max() + 1 }};
        updateRemoveButtons(tanggalWrap, '.btn-remove-row');
        updateRemoveButtons(kelasWrap, '.btn-remove-kelas');

        function reindexKelasBadges() {
            var rows = kelasWrap.querySelectorAll('.kelas-row');
            rows.forEach(function (row, idx) {
                var badge = row.querySelector('.kelas-label-badge');
                if (badge) {
                    badge.innerHTML = '<i class="ti ti-user-star"></i> Peran #' + (idx + 1);
                }
            });
        }

        function syncKuotaTotal() {
            var kuotaInputs = kelasWrap.querySelectorAll('input[name$="[kuota_kelas]"]');
            var total = 0;
            var count = 0;
            kuotaInputs.forEach(function (inp) {
                var val = parseInt(inp.value, 10);
                if (!isNaN(val) && val > 0) {
                    total += val;
                    count++;
                }
            });
            var kuotaInput = document.getElementById('kuota');
            var hintEl = document.getElementById('kuota-hint');
            if (count > 0 && kuotaInput) {
                kuotaInput.value = total;
                if (hintEl) hintEl.textContent = 'Auto-sinkron: ' + total + ' orang';
            }
        }

        document.getElementById('btn-add-kelas').addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'kelas-row';
            row.style.cssText = 'background:var(--bg-card-hover, rgba(255,255,255,0.02)); border:1px solid var(--border-color); border-radius:10px; padding:14px; margin-bottom:14px;';
            row.innerHTML =
                '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px dashed var(--border-color); padding-bottom:8px;">' +
                '<span class="kelas-label-badge" style="font-weight:600; font-size:13px; color:var(--accent); display:flex; align-items:center; gap:6px;"><i class="ti ti-user-star"></i> Peran Baru</span>' +
                '<button type="button" class="btn-icon-danger btn-remove-kelas" aria-label="Hapus peran" style="padding:2px 8px; font-size:12px;"><i class="ti ti-trash"></i> Hapus</button>' +
                '</div>' +
                '<div class="form-row">' +
                '<div style="flex:2; min-width:200px;"><label style="font-weight:600;">Nama Peran <span class="wajib" aria-hidden="true">*</span></label>' +
                '<input type="text" name="kelas[' + kelasIndex + '][nama_kelas]" placeholder="misal: Extras Utama Pria 25th" required></div>' +
                '<div style="flex:1.5; min-width:170px;"><label style="font-weight:600;">Budget Client (Rp) <span class="wajib" aria-hidden="true">*</span></label>' +
                '<input type="number" name="kelas[' + kelasIndex + '][budget_client]" min="0" required placeholder="Rp 0"></div>' +
                '<div style="flex:1; min-width:120px;"><label style="font-weight:600;">Kuota Peran <span class="wajib" aria-hidden="true">*</span></label>' +
                '<input type="number" name="kelas[' + kelasIndex + '][kuota_kelas]" class="input-kuota-kelas" min="1" required placeholder="Jml orang"></div>' +
                '</div>' +
                '<div class="form-row" style="margin-top:10px;">' +
                '<div><label style="font-size:12px;">Jam Callsheet Client <span style="color:var(--text-muted); font-weight:400;">(rundown PH)</span></label><input type="time" name="kelas[' + kelasIndex + '][jam_callsheet]"></div>' +
                '<div><label style="font-size:12px;">Jam Panggil Extras <span style="color:var(--text-muted); font-weight:400;">(standby talent)</span></label><input type="time" name="kelas[' + kelasIndex + '][jam_callingan]"></div>' +
                '<div><label style="font-size:12px;">Tipe Kontinuitas</label><select name="kelas[' + kelasIndex + '][tipe_continuity]"><option value="free">Sehari (bebas)</option><option value="continuity">Berlanjut (beberapa hari)</option></select></div>' +
                '</div>' +
                '<div style="margin-top:10px;"><label style="font-size:12px;">Keterangan Scene <span style="color:var(--text-muted); font-weight:400;">(lokasi scene, wardrobe, dll)</span></label><input type="text" name="kelas[' + kelasIndex + '][keterangan_scene]" placeholder="Scene 12-14 di warung kopi..."></div>' +
                '<div style="margin-top:10px;"><label style="font-size:12px;">Kriteria / Karakter Khusus</label>' +
                '<textarea name="kelas[' + kelasIndex + '][kriteria]" rows="2" placeholder="Contoh: wanita 25-35 th, ekspresi natural" maxlength="500"></textarea></div>' +
                '<div style="margin-top:10px;"><label style="font-size:12px;">Tag yang dicari <span style="color:var(--text-muted); font-weight:400;">(dipakai untuk filter profil extras)</span></label>' + document.getElementById('tag-chips-tpl').innerHTML.replaceAll('__i__', kelasIndex) + '</div>';
            kelasWrap.appendChild(row);
            kelasIndex++;
            updateRemoveButtons(kelasWrap, '.btn-remove-kelas');
            reindexKelasBadges();
            syncKuotaTotal();
        });

        kelasWrap.addEventListener('input', function (e) {
            if (e.target.matches('input[name$="[kuota_kelas]"]')) {
                syncKuotaTotal();
            }
        });

        kelasWrap.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-remove-kelas');
            if (btn) {
                btn.closest('.kelas-row').remove();
                updateRemoveButtons(kelasWrap, '.btn-remove-kelas');
                reindexKelasBadges();
                syncKuotaTotal();
            }
        });

        // Jalankan saat init
        reindexKelasBadges();
        syncKuotaTotal();

        function updateRemoveButtons(wrap, selector) {
            var rows = wrap.querySelectorAll(selector);
            rows.forEach(function (btn) {
                btn.style.display = rows.length > 1 ? 'block' : 'none';
            });
        }
    })();
</script>
@endpush
