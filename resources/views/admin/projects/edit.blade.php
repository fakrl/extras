@extends('layouts.app')

@section('title', 'Edit Proyek Casting')

@section('content')

@if ($castingProject->brief_catatan)
    <div style="background: color-mix(in srgb, var(--accent, #15803d) 8%, var(--bg-card, #fff)); border-left: 4px solid var(--accent, #15803d); border-radius: 8px; padding: 14px 16px; margin-bottom: 16px;">
        <div style="font-size: 12px; font-weight: 600; color: var(--accent, #15803d); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px;">Brief dari Client (pengajuan asli)</div>
        <div style="font-size: 13.5px; white-space: pre-line;">{{ $castingProject->brief_catatan }}</div>
    </div>
@endif

@if ($castingProject->brief_catatan && $castingProject->classes->isEmpty())
    <div style="background: #fef9c3; border: 1px solid #ca8a04; color: #713f12; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;">
        <strong>Proyek dari Client — Breakdown belum lengkap.</strong> Kelas karakter &amp; jadwal syuting belum diisi. Lengkapi di bagian bawah halaman ini sebelum mulai buka pendaftaran.
    </div>
@endif

<div class="card">
    <div style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Edit Proyek Casting</div>

    @if ($applicantsCount > 0)
        <div class="alert-info">
            Proyek ini sudah ada {{ $applicantsCount }} pendaftar. Mengubah budget/kuota kelas TIDAK mengubah fee yang sudah di-nego dengan pendaftar. Kelas yang sudah ada tidak bisa dihapus, hanya bisa diubah atau ditambah kelas baru.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.projects.update', $castingProject) }}" enctype="multipart/form-data"
        data-client-lama="{{ $castingProject->client_id }}" data-client-lama-nama="{{ $castingProject->client?->username ? '@'.$castingProject->client->username : $castingProject->client?->name }}"
        onsubmit="var d = this.dataset; return !d.clientLama || this.client_id.value === d.clientLama || confirm('Client lama (' + d.clientLamaNama + ') nggak bisa lihat proyek ini lagi. Lanjut?')">
        @csrf
        <p class="wajib-ket"><span class="wajib">*</span> wajib diisi</p>
        @method('PATCH')

        <div class="form-row">
            <div>
                <label>Nama Produksi <span class="wajib" aria-hidden="true">*</span></label>
                <input type="text" name="nama_produksi" value="{{ old('nama_produksi', $castingProject->nama_produksi) }}" required>
            </div>
        </div>

        @if (! $castingProject->client_id)
            <div class="alert-info">Proyek ini belum punya akun Client. Pilih Client di bawah supaya bisa disimpan.</div>
        @endif
        @include('partials.proyek-pic-client', ['adminId' => $castingProject->admin_id, 'clientId' => $castingProject->client_id, 'adminKosong' => auth()->user()->isSuperAdmin() ? '- Pilih Admin -' : '- Tetap seperti sekarang -', 'adminWajib' => auth()->user()->isSuperAdmin()])

        <div class="form-row">
            <div>
                <label>Link Grup Koordinasi <span style="color: var(--text-muted); font-weight: 400;">(WA/Telegram, dapat diisi menyusul)</span></label>
                <input type="url" name="link_grup" value="{{ old('link_grup', $castingProject->link_grup) }}" placeholder="https://chat.whatsapp.com/... atau https://t.me/...">
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>Poster Produksi <span style="color: var(--text-muted); font-weight: 400;">(maks. 2MB)</span></label>
                @if ($castingProject->poster_path)
                    <div style="margin-bottom: 8px;">
                        <img src="{{ Storage::url($castingProject->poster_path) }}" alt="Poster" style="height: 80px; border-radius: 6px; object-fit: cover;">
                        <span style="font-size: 12px; color: var(--text-muted); margin-left: 8px;">Upload baru untuk mengganti</span>
                    </div>
                @endif
                <input type="file" name="poster_path" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>Deadline Pendaftaran <span class="wajib" aria-hidden="true">*</span></label>
                <input type="date" name="deadline" value="{{ old('deadline', $castingProject->deadline->format('Y-m-d')) }}" required>
            </div>
            <div>
                <label>Kuota Total <span class="wajib" aria-hidden="true">*</span></label>
                <input type="number" name="kuota" value="{{ old('kuota', $castingProject->kuota) }}" min="1" required>
            </div>
            <div style="display: flex; align-items: center; padding-top: 22px;">
                <div class="form-check">
                    <input type="checkbox" name="is_urgent" value="1" id="is_urgent" @checked(old('is_urgent', $castingProject->is_urgent))>
                    <label for="is_urgent" style="margin-bottom:0;">Butuh Dadakan / Urgent</label>
                </div>
            </div>
            <div style="padding-top: 22px;">
                <div class="form-check">
                    <input type="checkbox" name="nego_terbuka" value="1" id="nego_terbuka" @checked(old('nego_terbuka', $castingProject->nego_terbuka)) @disabled($castingProject->sudahAdaPenawaran())>
                    <label for="nego_terbuka" style="margin-bottom:0;">Fee bisa dinego</label>
                </div>
                <div style="font-size: var(--fs-xs); color: var(--text-muted);">{{ $castingProject->sudahAdaPenawaran() ? 'Sudah ada penawaran, tidak bisa diubah' : 'Matikan bila fee proyek ini tetap dan tidak bisa ditawar' }}</div>
            </div>
        </div>

        <hr>
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">
            Tanggal Shooting <span class="wajib" aria-hidden="true">*</span> <span style="color: var(--text-muted); font-weight: 400; font-size: 12.5px;">(bisa lebih dari satu, tidak harus berurutan)</span>
        </div>
        <div id="tanggal-wrap">
            @foreach ($castingProject->shootingDates as $tanggal)
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                    <input type="date" name="tanggal_shooting[]" class="input-inline" value="{{ $tanggal->tanggal->format('Y-m-d') }}" required>
                    <button type="button" class="btn-icon-danger btn-remove-row" title="Hapus" style="display:none">&times;</button>
                </div>
            @endforeach
        </div>
        <button type="button" id="btn-add-tanggal" class="btn btn-sm" style="margin-bottom: 20px;">+ Tambah Tanggal</button>

        <hr>
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">
            Karakter yang Dibutuhkan <span style="color: var(--text-muted); font-weight: 400; font-size: 12.5px;">(minimal satu karakter)</span>
        </div>
        <div id="kelas-wrap">
            @foreach ($castingProject->classes as $kelas)
                <div class="kelas-row" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 12px; margin-bottom: 10px;">
                    <input type="hidden" name="kelas[{{ $loop->index }}][id]" value="{{ $kelas->id }}">
                    <div class="form-row" style="align-items: flex-end;">
                        <div>
                            <label>Nama Karakter <span class="wajib" aria-hidden="true">*</span></label>
                            <input type="text" name="kelas[{{ $loop->index }}][nama_kelas]" value="{{ $kelas->nama_kelas }}" placeholder="misal: Ibu-ibu 29-50th" required>
                        </div>
                        <div>
                            <label>Budget Client (Rp) <span class="wajib" aria-hidden="true">*</span></label>
                            <input type="number" name="kelas[{{ $loop->index }}][budget_client]" value="{{ $kelas->budget_client }}" min="0" required>
                        </div>
                        <div>
                            <label>Kuota Kelas <span class="wajib" aria-hidden="true">*</span></label>
                            <input type="number" name="kelas[{{ $loop->index }}][kuota_kelas]" value="{{ $kelas->kuota_kelas }}" min="1" required>
                        </div>
                        <div style="flex: 0;">
                            <button type="button" class="btn-icon-danger btn-remove-kelas" @if ($applicantsCount > 0) style="display:none" title="Kelas ini tidak bisa dihapus, proyek sudah punya pendaftar" @endif>&times;</button>
                        </div>
                    </div>
                    <div class="form-row" style="margin-top: 8px;">
                        <div>
                            <label>Jam Callsheet Client <span style="color: var(--text-muted); font-weight: 400;">(waktu rundown PH)</span></label>
                            <input type="time" name="kelas[{{ $loop->index }}][jam_callsheet]" value="{{ $kelas->jam_callsheet }}">
                        </div>
                        <div>
                            <label>Jam Callingan Extras <span style="color: var(--text-muted); font-weight: 400;">(otomatis -1 jam jika kosong)</span></label>
                            <input type="time" name="kelas[{{ $loop->index }}][jam_callingan]" value="{{ $kelas->jam_callingan }}">
                        </div>
                        <div>
                            <label>Tipe Kontinuitas</label>
                            <select name="kelas[{{ $loop->index }}][tipe_continuity]">
                                <option value="free" @selected(($kelas->tipe_continuity ?? 'free') === 'free')>Bebas (Single Day)</option>
                                <option value="continuity" @selected(($kelas->tipe_continuity ?? '') === 'continuity')>Continuity (Multi-day)</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top: 8px;">
                        <label>Keterangan Scene <span style="color: var(--text-muted); font-weight: 400;">(misal: Scene 12-14 warung kopi)</span></label>
                        <input type="text" name="kelas[{{ $loop->index }}][keterangan_scene]" value="{{ $kelas->keterangan_scene }}" placeholder="Scene 12-14 di warung kopi...">
                    </div>
                    <div style="margin-top: 8px;">
                        <label>Kriteria yang dibutuhkan</label>
                        <textarea name="kelas[{{ $loop->index }}][kriteria]" rows="2" placeholder="Contoh: wanita 25-35 th, ekspresi natural" maxlength="500">{{ $kelas->kriteria }}</textarea>
                    </div>
                    <div style="margin-top: 8px;">
                        <label>Tag yang dicari <span style="color: var(--text-muted); font-weight: 400;">(dipakai untuk % cocok)</span></label>
                        @include('partials.tag-input', ['name' => 'kelas['.$loop->index.'][tag_nama]', 'selected' => $kelas->categories])
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" id="btn-add-kelas" class="btn btn-sm" style="margin-bottom: 24px;">+ Tambah Karakter</button>

        <button type="submit" class="btn btn-brand" style="width: 100%;">Simpan Perubahan</button>
    </form>
    @if (auth()->user()->isSuperAdmin())
        @include('partials.client-baru-modal')
    @endif
</div>

<div class="card">
    <div style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Jadwal Shooting (Read-only)</div>
    @if ($castingProject->shootingDates->whereNotNull('lokasi')->isEmpty())
        <div style="color: var(--text-muted);">Client belum mengisi jadwal detail.</div>
    @else
        @foreach ($castingProject->shootingDates->sortBy('tanggal') as $date)
            @if ($date->lokasi || $date->jam_mulai || $date->catatan || $date->panggilan)
                <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; margin-bottom: 10px;">
                    <div style="font-weight: 600; margin-bottom: 6px;">{{ $date->tanggal->translatedFormat('l, d F Y') }}</div>
                    @if ($date->lokasi) <div style="font-size: 13px;"><strong>Lokasi:</strong> {{ $date->lokasi }}</div> @endif
                    @if ($date->jam_mulai) <div style="font-size: 13px;"><strong>Waktu:</strong> {{ substr($date->jam_mulai, 0, 5) }}{{ $date->jam_selesai ? ' – ' . substr($date->jam_selesai, 0, 5) : '' }}</div> @endif
                    @if ($date->catatan) <div style="font-size: 13px;"><strong>Catatan:</strong> {{ $date->catatan }}</div> @endif
                    @if ($date->panggilan)
                        <div style="font-size: 13px; margin-top: 6px;"><strong>Daftar Panggilan:</strong></div>
                        <ul style="margin: 4px 0 0 16px; font-size: 13px;">
                            @foreach ($date->panggilan as $p)
                                <li>{{ $p['nama'] }} ({{ $p['jam'] }})</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        @endforeach
    @endif
</div>
@endsection

@push('scripts')
<template id="tag-chips-tpl">@include('partials.tag-input', ['name' => 'kelas[__i__][tag_nama]', 'selected' => []])</template>
<script>
    (function () {
        var hasApplicants = @json($applicantsCount > 0);

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
        updateRemoveButtons(tanggalWrap, '.btn-remove-row');

        var kelasWrap = document.getElementById('kelas-wrap');
        updateRemoveButtons(kelasWrap, '.btn-remove-kelas');
        var kelasIndex = {{ $castingProject->classes->count() }};
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
                var row = e.target.closest('.kelas-row');
                if (hasApplicants && row.querySelector('input[name$="[id]"]')) {
                    return;
                }
                row.remove();
                updateRemoveButtons(kelasWrap, '.btn-remove-kelas');
            }
        });

        function updateRemoveButtons(wrap, selector) {
            var rows = wrap.querySelectorAll(selector);
            rows.forEach(function (btn) {
                var isExistingClass = hasApplicants && btn.closest('.kelas-row') && btn.closest('.kelas-row').querySelector('input[name$="[id]"]');
                btn.style.display = (rows.length > 1 && !isExistingClass) ? 'block' : 'none';
            });
        }
    })();
</script>
@endpush
