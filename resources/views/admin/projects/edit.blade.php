@extends('layouts.app')

@section('title', 'Edit Proyek Casting')

@section('content')
<div style="margin-bottom: 12px;">
    <a href="{{ route('admin.projects.show', $castingProject) }}" class="btn btn-sm btn-tonal" style="display: inline-flex; align-items: center; gap: 6px;">
        <i class="ti ti-arrow-left"></i> Kembali ke Proyek
    </a>
</div>

@if ($castingProject->brief_catatan)
    <div style="background: color-mix(in srgb, var(--accent, #15803d) 8%, var(--bg-card, #fff)); border-left: 4px solid var(--accent, #15803d); border-radius: 8px; padding: 14px 16px; margin-bottom: 16px;">
        <div style="font-size: 12px; font-weight: 600; color: var(--accent, #15803d); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px;">Brief dari Client (pengajuan asli)</div>
        <div style="font-size: 13.5px; white-space: pre-line;">{{ $castingProject->brief_catatan }}</div>
    </div>
@endif

@if ($castingProject->brief_catatan && $castingProject->classes->isEmpty())
    <div style="background: #fef9c3; border: 1px solid #ca8a04; color: #713f12; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;">
        <strong>Proyek dari Client · Breakdown belum lengkap.</strong> Peran &amp; jadwal syuting belum diisi. Lengkapi di bagian bawah halaman ini sebelum mulai buka pendaftaran.
    </div>
@endif

<div class="card">
    <div style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Edit Proyek Casting</div>

    @if ($applicantsCount > 0)
        <div class="alert-info">
            Proyek ini sudah ada {{ $applicantsCount }} pendaftar. Mengubah budget/kuota peran TIDAK mengubah fee yang sudah di-nego dengan pendaftar. Peran yang sudah ada tidak bisa dihapus, hanya bisa diubah atau ditambah kelas baru.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.projects.update', $castingProject) }}" enctype="multipart/form-data"
        data-client-lama="{{ $castingProject->client_id }}" data-client-lama-nama="{{ $castingProject->client?->username ? '@'.$castingProject->client->username : $castingProject->client?->name }}"
        onsubmit="var d = this.dataset; return !d.clientLama || this.client_id.value === d.clientLama || confirm('Client lama (' + d.clientLamaNama + ') nggak bisa lihat proyek ini lagi. Lanjut?')">
        @csrf
        <p class="wajib-ket"><span class="wajib">*</span> wajib diisi</p>
        @method('PATCH')

        {{-- 1. Identitas Produksi & Penugasan --}}
        <div class="form-row" style="margin-bottom: 16px;">
            <div>
                <label for="nama_produksi" style="font-weight: 600;">Nama Produksi <span class="wajib" aria-hidden="true">*</span></label>
                <input type="text" name="nama_produksi" id="nama_produksi" value="{{ old('nama_produksi', $castingProject->nama_produksi) }}" placeholder="misal: Iklan TVC Bank Digital" required>
            </div>
            <div>
                <label for="link_grup" style="font-weight: 600;">Link Grup Koordinasi <span style="color: var(--text-muted); font-weight: 400; font-size: 12px;">(WA/Telegram, dapat diisi menyusul)</span></label>
                <input type="url" name="link_grup" id="link_grup" value="{{ old('link_grup', $castingProject->link_grup) }}" placeholder="https://chat.whatsapp.com/... atau https://t.me/...">
            </div>
        </div>

        @if (! $castingProject->client_id)
            <div class="alert-info" style="margin-bottom: 16px;">
                <i class="ti ti-info-circle"></i> Proyek ini belum memiliki akun Client yang terhubung. Pilih Client di bawah agar proyek dapat disimpan.
            </div>
        @endif

        @include('partials.proyek-pic-client', ['adminId' => $castingProject->admin_id, 'clientId' => $castingProject->client_id, 'adminKosong' => auth()->user()->isSuperAdmin() ? '- Pilih Admin -' : '- Tetap seperti sekarang -', 'adminWajib' => auth()->user()->isSuperAdmin()])

        {{-- 2. Poster Produksi --}}
        <div style="background: var(--bg-card-hover, rgba(255,255,255,0.02)); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 14px; margin-bottom: 16px;">
            <label for="poster_path" style="font-weight: 600; display: block; margin-bottom: 8px;">
                Poster Produksi <span style="color: var(--text-muted); font-weight: 400; font-size: 12px;">(format JPG/PNG/WebP, maks. 2MB)</span>
            </label>
            <div style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
                @if ($castingProject->poster_path)
                    <div style="position: relative; flex-shrink: 0;">
                        <img src="{{ Storage::url($castingProject->poster_path) }}" alt="Poster Saat Ini" style="width: 70px; height: 95px; border-radius: 6px; object-fit: cover; border: 1px solid var(--border-color);">
                    </div>
                @endif
                <div style="flex: 1; min-width: 220px;">
                    <input type="file" name="poster_path" id="poster_path" accept="image/jpeg,image/png,image/webp" style="margin: 0;">
                    @if ($castingProject->poster_path)
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Pilih file baru jika ingin mengganti poster yang lama.</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 3. Parameter Pendaftaran & Kuota --}}
        <div class="form-row" style="margin-bottom: 14px;">
            <div>
                <label for="deadline" style="font-weight: 600;">Deadline Pendaftaran <span class="wajib" aria-hidden="true">*</span></label>
                <input type="date" name="deadline" id="deadline" value="{{ old('deadline', $castingProject->deadline->format('Y-m-d')) }}" required>
            </div>
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label for="kuota" style="font-weight: 600; margin: 0;">Kuota Total Proyek <span class="wajib" aria-hidden="true">*</span></label>
                    <span id="kuota-hint" style="font-size: 11.5px; color: var(--accent); font-weight: 600;"></span>
                </div>
                <input type="number" name="kuota" id="kuota" value="{{ old('kuota', $castingProject->kuota) }}" min="1" required style="margin-top: 6px;">
                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 3px;">Total kebutuhan extras (otomatis sinkron saat kuota peran diisi).</div>
            </div>
        </div>

        {{-- Opsi Status Pendaftaran (Urgent & Nego) --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; margin-bottom: 16px;">
            <label style="display: flex; gap: 10px; align-items: flex-start; background: var(--bg-card-hover, rgba(255,255,255,0.02)); border: 1px solid var(--border-color); border-radius: var(--radius-sm, 8px); padding: 12px; margin: 0; cursor: pointer;">
                <input type="checkbox" name="is_urgent" value="1" id="is_urgent" @checked(old('is_urgent', $castingProject->is_urgent)) style="margin-top: 3px;">
                <div>
                    <div style="font-weight: 600; font-size: 13px;">Butuh Dadakan / Urgent</div>
                    <div style="font-size: 11.5px; color: var(--text-muted); line-height: 1.35; margin-top: 2px;">Tandai proyek dengan badge urgent agar diprioritaskan oleh Extras.</div>
                </div>
            </label>

            <label style="display: flex; gap: 10px; align-items: flex-start; background: var(--bg-card-hover, rgba(255,255,255,0.02)); border: 1px solid var(--border-color); border-radius: var(--radius-sm, 8px); padding: 12px; margin: 0; cursor: pointer; @if($castingProject->sudahAdaPenawaran()) opacity: 0.7; @endif">
                <input type="checkbox" name="nego_terbuka" value="1" id="nego_terbuka" @checked(old('nego_terbuka', $castingProject->nego_terbuka)) @disabled($castingProject->sudahAdaPenawaran()) style="margin-top: 3px;">
                <div>
                    <div style="font-weight: 600; font-size: 13px;">Fee Bisa Dinego</div>
                    <div style="font-size: 11.5px; color: var(--text-muted); line-height: 1.35; margin-top: 2px;">
                        {{ $castingProject->sudahAdaPenawaran() ? 'Sudah ada penawaran, tidak bisa diubah' : 'Bila aktif, extras dapat menawar nominal honor yang ditawarkan' }}
                    </div>
                </div>
            </label>
        </div>

        <hr>
        <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">
            Tanggal Shooting <span class="wajib" aria-hidden="true">*</span> <span style="color: var(--text-muted); font-weight: 400; font-size: 12.5px;">(bisa lebih dari satu, tidak harus berurutan)</span>
        </div>
        <div id="tanggal-wrap">
            @php
                $shootingDateModels = $castingProject->shootingDates->keyBy(fn ($t) => $t->tanggal->format('Y-m-d'));
            @endphp
            @foreach (old('tanggal_shooting', $castingProject->shootingDates->map(fn ($t) => $t->tanggal->format('Y-m-d'))->all()) as $tgl)
                @php $sd = $shootingDateModels->get($tgl); @endphp
                <div class="tanggal-row-box" style="background: var(--bg-card-hover, rgba(255,255,255,0.02)); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 12px; margin-bottom: 8px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between;">
                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                            <input type="date" name="tanggal_shooting[]" class="input-inline" value="{{ $tgl }}" required style="margin: 0;">
                            @if ($sd && ($sd->lokasi || $sd->jam_mulai))
                                <span class="badge badge-aktif" style="font-size: 11px;">Rundown terisi</span>
                            @endif
                        </div>
                        <button type="button" class="btn-icon-danger btn-remove-row" title="Hapus tanggal" aria-label="Hapus tanggal" style="display:none; padding: 2px 8px; font-size: 12px;">
                            <i class="ti ti-trash"></i> Hapus
                        </button>
                    </div>
                    @if ($sd && ($sd->lokasi || $sd->jam_mulai || $sd->catatan))
                        <div style="margin-top: 6px; font-size: 12px; color: var(--text-muted); line-height: 1.4; border-top: 1px dashed var(--border-color); padding-top: 6px;">
                            @if ($sd->lokasi) <div><i class="ti ti-map-pin"></i> <strong>Lokasi:</strong> {{ $sd->lokasi }}</div> @endif
                            @if ($sd->jam_mulai) <div><i class="ti ti-clock"></i> <strong>Waktu:</strong> {{ substr($sd->jam_mulai, 0, 5) }}{{ $sd->jam_selesai ? ' – ' . substr($sd->jam_selesai, 0, 5) : '' }}</div> @endif
                            @if ($sd->catatan) <div><i class="ti ti-notes"></i> <strong>Catatan:</strong> {{ $sd->catatan }}</div> @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        <button type="button" id="btn-add-tanggal" class="btn btn-sm btn-tonal" style="margin-bottom: 20px;">
            <i class="ti ti-plus"></i> Tambah Tanggal Shooting
        </button>

        <hr>
        @php
            $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
            $alokasi = $castingProject->totalAlokasiBudget();
            $bisaLihatBudget = auth()->user()->isSuperAdmin() || auth()->user()->can('kelola', $castingProject);
        @endphp
        @if ($bisaLihatBudget)
            @if ($castingProject->budget_total !== null)
                <div style="background: var(--bg-card-hover, rgba(255,255,255,0.02)); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 14px; margin-bottom: 14px; font-size: 13px; display: flex; flex-wrap: wrap; gap: 16px;">
                    <div><strong>Budget Proyek (Client):</strong> {{ $rp($castingProject->budget_total) }}</div>
                    <div><strong>Total Alokasi Peran:</strong> {{ $rp($alokasi) }}</div>
                    <div><strong>Sisa (Margin Agensi):</strong> {{ $rp($castingProject->budget_total - $alokasi) }}</div>
                </div>
                @if ($alokasi > $castingProject->budget_total)
                    <div style="background: #fef9c3; border: 1px solid #ca8a04; color: #713f12; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 13px;">
                        Total alokasi melebihi budget proyek dari Client. Proyek tetap bisa disimpan, tapi cek ulang nego fee per peran.
                    </div>
                @endif
            @else
                <div style="background: var(--bg-card-hover, rgba(255,255,255,0.02)); border: 1px dashed var(--border-color); border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 12.5px; color: var(--text-muted);">
                    <i class="ti ti-info-circle"></i> Budget total belum diisi oleh Client pada pengajuan awal. Total alokasi peran saat ini: <strong>{{ $rp($alokasi) }}</strong>.
                </div>
            @endif
        @endif

        <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">
            Peran yang Dibutuhkan <span style="color: var(--text-muted); font-weight: 400; font-size: 12.5px;">(minimal satu peran)</span>
        </div>
        <div id="kelas-wrap">
            @php
                $kelasRows = old('kelas') ?: $castingProject->classes->map(fn ($k) => $k->only(['id', 'nama_kelas', 'budget_client', 'kuota_kelas', 'jam_callsheet', 'jam_callingan', 'tipe_continuity', 'keterangan_scene', 'kriteria']) + ['tag_nama' => $k->categories->pluck('nama')->all()])->all();
            @endphp
            @foreach ($kelasRows as $i => $k)
                @include('admin.projects._kelas-row', ['i' => $i, 'k' => $k, 'kunci' => $applicantsCount > 0])
            @endforeach
        </div>
        <button type="button" id="btn-add-kelas" class="btn btn-sm btn-tonal" style="margin-bottom: 24px;">
            <i class="ti ti-plus"></i> Tambah Peran
        </button>

        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.projects.show', $castingProject) }}" class="btn">Batal</a>
            <button type="submit" class="btn btn-brand" style="flex: 1;">Simpan Perubahan</button>
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
        var hasApplicants = @json($applicantsCount > 0);

        var tanggalWrap = document.getElementById('tanggal-wrap');
        document.getElementById('btn-add-tanggal').addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'tanggal-row-box';
            row.style.cssText = 'background:var(--bg-card-hover, rgba(255,255,255,0.02)); border:1px solid var(--border-color); border-radius:8px; padding:10px 12px; margin-bottom:8px;';
            row.innerHTML =
                '<div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center; justify-content:space-between;">' +
                '<input type="date" name="tanggal_shooting[]" class="input-inline" required style="margin:0;">' +
                '<button type="button" class="btn-icon-danger btn-remove-row" aria-label="Hapus tanggal" title="Hapus tanggal" style="padding:2px 8px; font-size:12px;"><i class="ti ti-trash"></i> Hapus</button>' +
                '</div>';
            tanggalWrap.appendChild(row);
            updateRemoveButtons(tanggalWrap, '.btn-remove-row');
        });
        tanggalWrap.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-remove-row');
            if (btn) {
                btn.closest('.tanggal-row-box').remove();
                updateRemoveButtons(tanggalWrap, '.btn-remove-row');
            }
        });
        updateRemoveButtons(tanggalWrap, '.btn-remove-row');

        var kelasWrap = document.getElementById('kelas-wrap');
        updateRemoveButtons(kelasWrap, '.btn-remove-kelas');
        var kelasIndex = {{ collect($kelasRows)->keys()->max() + 1 }};

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
                '<div style="flex:1.5; min-width:170px;"><label style="font-weight:600;">Budget / Orang (Rp)</label>' +
                '<input type="number" name="kelas[' + kelasIndex + '][budget_client]" min="0" placeholder="Rp 0"></div>' +
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
                var row = btn.closest('.kelas-row');
                if (hasApplicants && row.querySelector('input[name$="[id]"]')) {
                    return;
                }
                row.remove();
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
                var isExistingClass = hasApplicants && btn.closest('.kelas-row') && btn.closest('.kelas-row').querySelector('input[name$="[id]"]');
                btn.style.display = (rows.length > 1 && !isExistingClass) ? 'block' : 'none';
            });
        }
    })();
</script>
@endpush
