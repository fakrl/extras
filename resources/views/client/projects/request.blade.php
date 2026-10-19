@extends('layouts.app')

@section('title', $editing ? 'Ubah Pengajuan Proyek' : 'Ajukan Permintaan Proyek')

@section('content')
@php
    $kelasRows = old('kelas') ?: ($editing ? $editing->classes->map(fn ($k) => $k->only(['nama_kelas', 'kuota_kelas', 'kriteria']))->all() : [[]]);
    $tanggalRows = old('tanggal_shooting') ?: ($editing ? $editing->shootingDates->map(fn ($t) => ['tanggal' => $t->tanggal->format('Y-m-d'), 'lokasi' => $t->lokasi])->all() : [['tanggal' => '', 'lokasi' => '']]);
@endphp

<div class="card-header-row">
    <div>
        <div style="font-size: 16px; font-weight: 600;">{{ $editing ? 'Ubah Pengajuan Proyek' : 'Ajukan Permintaan Proyek Baru' }}</div>
        <p style="color: var(--text-muted); font-size: 13px; margin: 2px 0 0;">
            Isi lengkap kebutuhan proyek (peran, tanggal shooting + lokasi, dan budget) untuk ditinjau tim JBTB.
        </p>
    </div>
</div>

@if ($editing)
    <div style="background: #fef9c3; border: 1px solid #ca8a04; color: #713f12; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;">
        <strong>Catatan dari JBTB:</strong> {{ $editing->alasan_tolak }}
    </div>
@endif

<div class="card" style="margin-bottom: 24px; max-width: 760px;">
    <form method="POST" action="{{ $editing ? route('client.projects.request.update', $editing) : route('client.projects.request.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif
        <p class="wajib-ket"><span class="wajib">*</span> wajib diisi</p>
        <label>Nama Produksi / Judul Film / Iklan <span class="wajib" aria-hidden="true">*</span></label>
        <input type="text" name="nama_produksi" value="{{ old('nama_produksi', $editing->nama_produksi ?? '') }}" placeholder="Contoh: Kado Untuk Ibu / Iklan Bank Mandiri" required>

        <p class="field-hint">Rumah Produksi: <strong>{{ auth()->user()->nama_perusahaan ?: auth()->user()->name }}</strong> (ubah di <a href="{{ route('client.profil') }}">Profil</a>).</p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div>
                <label>Estimasi Deadline Perekrutan <span class="wajib" aria-hidden="true">*</span></label>
                <input type="date" id="deadline" name="deadline" value="{{ old('deadline', $editing?->deadline?->format('Y-m-d')) }}" min="{{ date('Y-m-d') }}" required>
            </div>
            <div>
                <label>Budget Proyek (Total, Rp) <span class="wajib" aria-hidden="true">*</span></label>
                <input type="number" name="budget_total" min="1" step="1" value="{{ old('budget_total', $editing->budget_total ?? '') }}" required>
            </div>
        </div>

        <label>Brief Kebutuhan Karakter & Catatan Tambahan <span class="wajib" aria-hidden="true">*</span></label>
        <textarea name="brief_catatan" rows="3" placeholder="Jelaskan konsep produksi, gaya visual, atau catatan lain di luar rincian peran di bawah..." required>{{ old('brief_catatan', $editing->brief_catatan ?? '') }}</textarea>

        <hr>
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">
            Peran yang Dibutuhkan <span class="wajib" aria-hidden="true">*</span> <span style="color: var(--text-muted); font-weight: 400; font-size: 12.5px;">(minimal satu peran, budget per orang diatur Admin JBTB nanti)</span>
        </div>
        <div id="kelas-wrap">
            @foreach ($kelasRows as $i => $k)
                <div class="kelas-row" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 12px; margin-bottom: 10px;">
                    <div class="form-row" style="align-items: flex-end;">
                        <div>
                            <label>Nama Peran <span class="wajib" aria-hidden="true">*</span></label>
                            <input type="text" name="kelas[{{ $i }}][nama_kelas]" value="{{ $k['nama_kelas'] ?? '' }}" placeholder="misal: Ibu-ibu 29-50th" required>
                        </div>
                        <div>
                            <label>Jumlah Orang <span class="wajib" aria-hidden="true">*</span></label>
                            <input type="number" name="kelas[{{ $i }}][kuota_kelas]" value="{{ $k['kuota_kelas'] ?? '' }}" min="1" required>
                        </div>
                        <div style="flex: 0;">
                            <button type="button" class="btn-icon-danger btn-remove-kelas" aria-label="Hapus peran" style="display:none">&times;</button>
                        </div>
                    </div>
                    <div style="margin-top: 8px;">
                        <label>Kriteria yang dibutuhkan</label>
                        <textarea name="kelas[{{ $i }}][kriteria]" rows="2" placeholder="Contoh: wanita 25-35 th, ekspresi natural, look sederhana" maxlength="500">{{ $k['kriteria'] ?? '' }}</textarea>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" id="btn-add-kelas" class="btn btn-sm" style="margin-bottom: 20px;">+ Tambah Peran</button>

        <hr>
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">
            Tanggal Shooting & Lokasi <span class="wajib" aria-hidden="true">*</span> <span style="color: var(--text-muted); font-weight: 400; font-size: 12.5px;">(minimal satu, setelah deadline di atas)</span>
        </div>
        <div id="tanggal-wrap">
            @foreach ($tanggalRows as $i => $t)
                <div class="form-row tanggal-row" style="margin-bottom: 8px; align-items: flex-end;">
                    <div>
                        <label>Tanggal</label>
                        <input type="date" name="tanggal_shooting[{{ $i }}][tanggal]" value="{{ $t['tanggal'] ?? '' }}" required>
                    </div>
                    <div>
                        <label>Lokasi</label>
                        <input type="text" name="tanggal_shooting[{{ $i }}][lokasi]" value="{{ $t['lokasi'] ?? '' }}" placeholder="misal: Pasar Minggu, Jakarta Selatan">
                    </div>
                    <div style="flex: 0;">
                        <button type="button" class="btn-icon-danger btn-remove-tanggal" aria-label="Hapus tanggal" style="display:none">&times;</button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" id="btn-add-tanggal" class="btn btn-sm" style="margin-bottom: 20px;">+ Tambah Tanggal</button>

        <label>Poster Acuan</label>
        <input type="file" name="poster_path" accept="image/*">

        <label for="req-files">Lampiran Dokumen (bisa pilih beberapa)</label>
        <input type="file" id="req-files" name="files[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
        <div style="font-size: var(--fs-xs); color: var(--text-muted);">PDF, Word, Excel, JPG, PNG · maks 10 MB per file</div>

        <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-brand">{{ $editing ? 'Ajukan Ulang' : 'Ajukan Permintaan Proyek' }}</button>
        </div>
    </form>
</div>

@if ($myRequests->isNotEmpty())
    <div class="card">
        <div class="card-title">Riwayat Pengajuan Permintaan Proyek Anda</div>
        @foreach ($myRequests as $req)
            <div style="padding: 12px 0; border-top: 1px solid var(--border-color);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; flex-wrap: wrap;">
                    <div style="font-weight: 600;">{{ $req->nama_produksi }}</div>
                    @if ($req->client_request_status === 'disetujui')
                        <span class="badge badge-aktif">Disetujui tim JBTB</span>
                    @elseif ($req->client_request_status === 'ditolak')
                        <span class="badge badge-tolak">Ditolak</span>
                    @elseif ($req->client_request_status === 'perlu_revisi')
                        <span class="badge badge-pending">Perlu Revisi</span>
                    @else
                        <span class="badge badge-pending">Menunggu ACC</span>
                    @endif
                </div>
                <div style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">
                    {{ $req->namaClient() }} · {{ $req->kuota }} orang · Batas pendaftaran {{ $req->deadline?->translatedFormat('d M Y') }}
                </div>
                <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 2px;">Diajukan {{ $req->created_at->translatedFormat('d M Y H:i') }}</div>
                @if ($req->client_request_status === 'ditolak' && $req->alasan_tolak)
                    <div style="font-size: 13px; color: var(--danger); margin-top: 4px;">Alasan: {{ $req->alasan_tolak }}</div>
                @elseif ($req->client_request_status === 'perlu_revisi')
                    <div style="font-size: 13px; color: var(--danger); margin-top: 4px;">Catatan dari JBTB: {{ $req->alasan_tolak }}</div>
                    <a href="{{ route('client.projects.request.edit', $req) }}" class="btn btn-sm btn-brand" style="margin-top: 6px;">Ubah & Ajukan Ulang</a>
                @endif
            </div>
        @endforeach
    </div>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        var kelasWrap = document.getElementById('kelas-wrap');
        var kelasIndex = {{ collect($kelasRows)->keys()->max() + 1 }};
        function updateRemove(wrap, selector) {
            var rows = wrap.querySelectorAll(selector);
            rows.forEach(function (btn) { btn.style.display = rows.length > 1 ? 'block' : 'none'; });
        }
        document.getElementById('btn-add-kelas').addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'kelas-row';
            row.style.cssText = 'border:1px solid var(--border-color); border-radius:10px; padding:12px; margin-bottom:10px;';
            row.innerHTML =
                '<div class="form-row" style="align-items:flex-end;">' +
                '<div><label>Nama Peran <span class="wajib" aria-hidden="true">*</span></label>' +
                '<input type="text" name="kelas[' + kelasIndex + '][nama_kelas]" required></div>' +
                '<div><label>Jumlah Orang <span class="wajib" aria-hidden="true">*</span></label>' +
                '<input type="number" name="kelas[' + kelasIndex + '][kuota_kelas]" min="1" required></div>' +
                '<div style="flex:0;"><button type="button" class="btn-icon-danger btn-remove-kelas" aria-label="Hapus peran">&times;</button></div></div>' +
                '<div style="margin-top:8px;"><label>Kriteria yang dibutuhkan</label>' +
                '<textarea name="kelas[' + kelasIndex + '][kriteria]" rows="2" maxlength="500" placeholder="Contoh: wanita 25-35 th, ekspresi natural"></textarea></div>';
            kelasWrap.appendChild(row);
            kelasIndex++;
            updateRemove(kelasWrap, '.btn-remove-kelas');
        });
        kelasWrap.addEventListener('click', function (e) {
            if (e.target.classList.contains('btn-remove-kelas')) {
                e.target.closest('.kelas-row').remove();
                updateRemove(kelasWrap, '.btn-remove-kelas');
            }
        });
        updateRemove(kelasWrap, '.btn-remove-kelas');

        var tanggalWrap = document.getElementById('tanggal-wrap');
        var tanggalIndex = {{ collect($tanggalRows)->keys()->max() + 1 }};
        document.getElementById('btn-add-tanggal').addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'form-row tanggal-row';
            row.style.cssText = 'margin-bottom:8px; align-items:flex-end;';
            row.innerHTML =
                '<div><label>Tanggal</label><input type="date" name="tanggal_shooting[' + tanggalIndex + '][tanggal]" required></div>' +
                '<div><label>Lokasi</label><input type="text" name="tanggal_shooting[' + tanggalIndex + '][lokasi]" placeholder="misal: Pasar Minggu, Jakarta Selatan"></div>' +
                '<div style="flex:0;"><button type="button" class="btn-icon-danger btn-remove-tanggal" aria-label="Hapus tanggal">&times;</button></div>';
            tanggalWrap.appendChild(row);
            tanggalIndex++;
            updateRemove(tanggalWrap, '.btn-remove-tanggal');
        });
        tanggalWrap.addEventListener('click', function (e) {
            if (e.target.classList.contains('btn-remove-tanggal')) {
                e.target.closest('.tanggal-row').remove();
                updateRemove(tanggalWrap, '.btn-remove-tanggal');
            }
        });
        updateRemove(tanggalWrap, '.btn-remove-tanggal');
    })();
</script>
@endpush
