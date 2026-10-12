@extends('layouts.app')

@section('title', 'Buka Lowongan Casting')

@section('content')
<div class="card">
    <div style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Buka Lowongan Casting Baru</div>

    <form method="POST" action="{{ route('admin.projects.store') }}" enctype="multipart/form-data">
        @csrf
        <p class="wajib-ket"><span class="wajib">*</span> wajib diisi</p>

        <div class="form-row">
            <div>
                <label>Nama Produksi <span class="wajib" aria-hidden="true">*</span></label>
                <input type="text" name="nama_produksi" value="{{ old('nama_produksi') }}" required>
            </div>
        </div>

        @php $isSa = auth()->user()->isSuperAdmin(); @endphp
        @include('partials.proyek-pic-client', ['adminId' => $isSa ? '' : auth()->id(), 'clientId' => null, 'adminKosong' => $isSa ? '- Pilih Admin -' : 'Saya sendiri', 'adminWajib' => $isSa])

        <div class="form-row">
            <div>
                <label>Link Grup Koordinasi <span style="color: var(--text-muted); font-weight: 400;">(WA/Telegram, dapat diisi menyusul)</span></label>
                <input type="url" name="link_grup" value="{{ old('link_grup') }}" placeholder="https://chat.whatsapp.com/... atau https://t.me/...">
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>Deadline Pendaftaran <span class="wajib" aria-hidden="true">*</span></label>
                <input type="date" name="deadline" value="{{ old('deadline') }}" required>
            </div>
            <div>
                <label>Kuota Total <span class="wajib" aria-hidden="true">*</span></label>
                <input type="number" name="kuota" value="{{ old('kuota') }}" min="1" required>
            </div>
            <div style="display: flex; align-items: center; padding-top: 22px;">
                <div class="form-check">
                    <input type="checkbox" name="is_urgent" value="1" id="is_urgent" @checked(old('is_urgent'))>
                    <label for="is_urgent" style="margin-bottom:0;">Butuh Dadakan / Urgent</label>
                </div>
            </div>
            <div style="padding-top: 22px;">
                <div class="form-check">
                    <input type="checkbox" name="nego_terbuka" value="1" id="nego_terbuka" @checked(old() ? old('nego_terbuka') : true)>
                    <label for="nego_terbuka" style="margin-bottom:0;">Fee bisa dinego</label>
                </div>
                <div style="font-size: var(--fs-xs); color: var(--text-muted);">Matikan bila fee proyek ini tetap dan tidak bisa ditawar</div>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>Poster Produksi <span style="color: var(--text-muted); font-weight: 400;">(maks. 2MB)</span></label>
                <input type="file" name="poster_path" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>

        <hr>
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">
            Tanggal Shooting <span class="wajib" aria-hidden="true">*</span> <span style="color: var(--text-muted); font-weight: 400; font-size: 12.5px;">(bisa lebih dari satu, tidak harus berurutan)</span>
        </div>
        <div id="tanggal-wrap">
            @foreach (old('tanggal_shooting', ['']) as $tgl)
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                    <input type="date" name="tanggal_shooting[]" class="input-inline" value="{{ $tgl }}" required>
                    <button type="button" class="btn-icon-danger btn-remove-row" title="Hapus" style="display:none">&times;</button>
                </div>
            @endforeach
        </div>
        <button type="button" id="btn-add-tanggal" class="btn btn-sm" style="margin-bottom: 20px;">+ Tambah Tanggal</button>

        <hr>
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">
            Karakter yang Dibutuhkan & Breakdown <span style="color: var(--text-muted); font-weight: 400; font-size: 12.5px;">(minimal satu karakter)</span>
        </div>
        <div id="kelas-wrap">
            @php $kelasRows = old('kelas') ?: [[]]; @endphp
            @foreach ($kelasRows as $i => $k)
                @include('admin.projects._kelas-row', ['i' => $i, 'k' => $k, 'kunci' => false])
            @endforeach
        </div>
        <button type="button" id="btn-add-kelas" class="btn btn-sm" style="margin-bottom: 24px;">+ Tambah Karakter</button>

        <button type="submit" class="btn btn-brand" style="width: 100%;">Simpan Proyek Casting</button>
    </form>
    @if (auth()->user()->isSuperAdmin())
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
                '<button type="button" class="btn-icon-danger btn-remove-row">&times;</button>';
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
        document.getElementById('btn-add-kelas').addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'kelas-row';
            row.style.cssText = 'border:1px solid var(--border-color); border-radius:10px; padding:12px; margin-bottom:10px;';
            row.innerHTML =
                '<div class="form-row" style="align-items:flex-end;">' +
                '<div><label>Nama Karakter <span class="wajib" aria-hidden="true">*</span></label>' +
                '<input type="text" name="kelas[' + kelasIndex + '][nama_kelas]" required></div>' +
                '<div><label>Budget Client (Rp) <span class="wajib" aria-hidden="true">*</span></label>' +
                '<input type="number" name="kelas[' + kelasIndex + '][budget_client]" min="0" required></div>' +
                '<div><label>Kuota Kelas <span class="wajib" aria-hidden="true">*</span></label>' +
                '<input type="number" name="kelas[' + kelasIndex + '][kuota_kelas]" min="1" required></div>' +
                '<div style="flex:0;"><button type="button" class="btn-icon-danger btn-remove-kelas">&times;</button></div>' +
                '</div>' +
                '<div class="form-row" style="margin-top:8px;">' +
                '<div><label>Jam Callsheet Client</label><input type="time" name="kelas[' + kelasIndex + '][jam_callsheet]"></div>' +
                '<div><label>Jam Callingan Extras</label><input type="time" name="kelas[' + kelasIndex + '][jam_callingan]"></div>' +
                '<div><label>Tipe Kontinuitas</label><select name="kelas[' + kelasIndex + '][tipe_continuity]"><option value="free">Bebas (Single Day)</option><option value="continuity">Continuity (Multi-day)</option></select></div>' +
                '</div>' +
                '<div style="margin-top:8px;"><label>Keterangan Scene</label><input type="text" name="kelas[' + kelasIndex + '][keterangan_scene]" placeholder="Scene 12-14 di warung kopi..."></div>' +
                '<div style="margin-top:8px;"><label>Kriteria yang dibutuhkan</label>' +
                '<textarea name="kelas[' + kelasIndex + '][kriteria]" rows="2" placeholder="Contoh: wanita 25-35 th, ekspresi natural" maxlength="500"></textarea></div>' +
                '<div style="margin-top:8px;"><label>Tag yang dicari <span style="color: var(--text-muted); font-weight: 400;">(dipakai untuk % cocok)</span></label>' + document.getElementById('tag-chips-tpl').innerHTML.replaceAll('__i__', kelasIndex) + '</div>';
            kelasWrap.appendChild(row);
            kelasIndex++;
            updateRemoveButtons(kelasWrap, '.btn-remove-kelas');
        });
        kelasWrap.addEventListener('click', function (e) {
            if (e.target.classList.contains('btn-remove-kelas')) {
                e.target.closest('.kelas-row').remove();
                updateRemoveButtons(kelasWrap, '.btn-remove-kelas');
            }
        });

        function updateRemoveButtons(wrap, selector) {
            var rows = wrap.querySelectorAll(selector);
            rows.forEach(function (btn) {
                btn.style.display = rows.length > 1 ? 'block' : 'none';
            });
        }
    })();
</script>
@endpush
