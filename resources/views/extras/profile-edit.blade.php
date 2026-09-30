@extends('layouts.app')

@section('title', 'Lengkapi Profil')

@push('styles')
<style>
    .field-error { color: var(--danger); font-size: 12px; margin-top: 4px; display: block; }
    .input-error { border-color: var(--danger) !important; }
    .upload-progress { display: none; width: 100%; height: 10px; margin-top: 8px; accent-color: var(--accent-strong); }
    .upload-error-msg { color: var(--danger); font-size: 12px; margin-top: 6px; display: none; }
    .foto-hint { font-size: 12px; color: var(--text-muted); line-height: 1.4; margin: -4px 0 12px; }
    .foto-hint.is-kosong { color: var(--text-primary); background: color-mix(in srgb, var(--warning) 14%, transparent); border: 1px solid color-mix(in srgb, var(--warning) 40%, transparent); border-radius: var(--radius-md); padding: 10px 12px; margin-top: 0; }
    .foto-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .foto-tile { position: relative; min-width: 0; }
    .foto-tile[hidden] { display: none; }
    .foto-box { position: relative; display: flex; align-items: center; justify-content: center; aspect-ratio: 3 / 4; margin: 0; border-radius: var(--radius-md); overflow: hidden; background: var(--bg-nav-active); border: 2px dashed var(--border-color); cursor: pointer; }
    .foto-box:has(img:not([hidden])) { border-style: solid; }
    .foto-box img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .foto-box img[hidden], .foto-kosong[hidden] { display: none; }
    .foto-kosong { display: flex; flex-direction: column; align-items: center; gap: 6px; font-size: 12px; color: var(--text-secondary); text-align: center; padding: 8px; }
    .foto-kosong i { font-size: 26px; color: var(--accent-strong); }
    .foto-wajib { position: absolute; left: 6px; top: 6px; padding: 2px 8px; border-radius: 999px; background: var(--accent); color: var(--accent-on); font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .foto-hapus { position: absolute; right: 4px; top: 4px; margin: 0; }
    .foto-hapus button { width: 36px; height: 36px; border-radius: 999px; border: 0; background: rgba(0,0,0,.6); color: #fff; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
</style>
@endpush

@section('content')
<div class="card" style="max-width: 560px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
        <div style="font-size: 17px; font-weight: 600;">Lengkapi Profil Kamu</div>
        <a href="{{ route('extras.profile.show') }}" class="btn btn-sm">Lihat Profil</a>
    </div>
    <p style="color: var(--text-secondary); font-size: 13px; margin-bottom: 20px; line-height: 1.5;">
        Data profil talenta untuk penilaian Admin dan Client. Isi sesuai kondisi saat ini (dapat diubah sewaktu-waktu).
    </p>

    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif

    {{-- ===== Foto (BJ.2: utama + galeri satu section) ===== --}}
    @php
        $jumlahGaleri = count(array_filter($fotoTambahan));
        $slotKosong = array_keys(array_filter($fotoTambahan, fn ($f) => ! $f));
    @endphp
    <div class="profile-section">
        <div class="profile-section-title">Foto</div>
        <p id="foto-hint" @class(['foto-hint', 'is-kosong' => ! $jumlahGaleri])>💡 Profil dengan 3+ foto (close-up, setengah badan, seluruh badan) lebih sering dipilih Client.</p>

        <div class="foto-grid">
            <div class="foto-tile">
                <label for="upload-foto" class="foto-box" aria-label="{{ $profile->foto_profil_path ? 'Ganti foto utama' : 'Pilih foto utama' }}">
                    <img id="preview-foto" src="{{ $profile->foto_profil_path ? route('extras.media.foto', $profile) : '' }}" alt="Foto utama" @if (! $profile->foto_profil_path) hidden @endif>
                    <span class="foto-kosong" id="empty-foto" @if ($profile->foto_profil_path) hidden @endif><i class="ti ti-camera" aria-hidden="true"></i> Foto utama</span>
                    <span class="foto-wajib">Wajib</span>
                </label>
            </div>
            @foreach ($fotoTambahan as $slot => $foto)
                <div class="foto-tile" id="tile-slot-{{ $slot }}" @if (! $foto) hidden @endif>
                    <label for="upload-slot-{{ $slot }}" class="foto-box" aria-label="Ganti foto galeri {{ $slot }}">
                        <img id="preview-slot-{{ $slot }}" src="{{ $foto ? route('extras.media.foto-tambahan', [$profile, $slot]) : '' }}" alt="Foto galeri {{ $slot }}">
                    </label>
                    <x-confirm-form action="{{ route('extras.profile.foto-tambahan.hapus', $slot) }}" method="DELETE" class="foto-hapus" message="Hapus foto galeri ini? Foto yang sudah dihapus tidak bisa dikembalikan.">
                        <button type="submit" aria-label="Hapus foto galeri {{ $slot }}"><i class="ti ti-trash" aria-hidden="true"></i></button>
                    </x-confirm-form>
                </div>
            @endforeach
            @if ($slotKosong)
                <label for="upload-slot-{{ $slotKosong[0] }}" class="foto-box foto-tambah" id="foto-tambah" aria-label="Tambah foto galeri">
                    <span class="foto-kosong"><i class="ti ti-plus" aria-hidden="true"></i> Tambah foto</span>
                </label>
            @endif
        </div>

        <input type="file" name="foto" id="upload-foto" accept="image/jpeg,image/png" hidden
               data-endpoint="{{ route('extras.profile.foto.ajax') }}" data-progress="progress-foto" data-max="5120"
               data-preview="preview-foto" data-error="err-foto" data-empty="empty-foto">
        <progress id="progress-foto" class="upload-progress" max="100" value="0"></progress>
        <span id="err-foto" class="upload-error-msg"></span>
        @foreach ($fotoTambahan as $slot => $foto)
            <input type="file" name="foto" id="upload-slot-{{ $slot }}" accept="image/jpeg,image/png" hidden
                   data-endpoint="{{ route('extras.profile.foto-tambahan.ajax', $slot) }}" data-progress="progress-slot-{{ $slot }}" data-max="5120"
                   data-preview="preview-slot-{{ $slot }}" data-error="err-slot-{{ $slot }}" data-tile="tile-slot-{{ $slot }}">
            <progress id="progress-slot-{{ $slot }}" class="upload-progress" max="100" value="0"></progress>
            <span id="err-slot-{{ $slot }}" class="upload-error-msg"></span>
        @endforeach
        <p class="field-hint" style="margin: 8px 0 0;">Ketuk foto untuk ganti. Foto utama = yang pertama dilihat Client, pakai wajah yang jelas &amp; terang. Maks 4 foto galeri, JPG/PNG maks 5MB.</p>
    </div>

    {{-- ===== Video Perkenalan ===== --}}
    <div class="profile-section">
        <div class="profile-section-title">Video Perkenalan</div>
        <p class="field-hint" style="margin-top: -4px;">Video singkat (30-60 detik) memperkenalkan diri. Boleh direkam pakai HP.</p>

        <label for="upload-video" class="media-upload-box media-upload-box-video" id="box-video">
            @if ($profile->video_profil_path)
                <video id="preview-video" src="{{ route('extras.media.video', $profile) }}" class="media-upload-preview" controls></video>
            @else
                <span class="media-upload-empty" id="empty-video">
                    <i class="ti ti-video"></i>
                    Ketuk untuk pilih video
                </span>
                <video id="preview-video" src="" class="media-upload-preview" controls style="display:none"></video>
            @endif
        </label>
        <input type="file" name="video" id="upload-video" accept="video/mp4,video/quicktime,video/webm" style="display: none;"
               data-endpoint="{{ route('extras.profile.video.ajax') }}"
               data-progress="progress-video"
               data-max="51200"
               data-preview="preview-video"
               data-error="err-video"
               data-type="video">
        <progress id="progress-video" class="upload-progress" max="100" value="0"></progress>
        <span id="err-video" class="upload-error-msg"></span>
        @if ($profile->video_profil_path)
            <label for="upload-video" class="btn btn-sm" style="margin-top: 8px; cursor: pointer;">Ganti Video</label>
        @endif
        <p class="field-hint" style="margin-top: 8px;">Format MP4/MOV, maksimal 50MB.</p>
    </div>

    <form method="POST" action="{{ route('extras.profile.update') }}">
        @csrf
        @method('PUT')

        <div class="profile-section">
            <div class="profile-section-title">Nama & Kontak</div>

            <label>Nama Asli (sesuai KTP) <span class="required-mark">*</span></label>
            <input type="text" name="nama_asli" value="{{ old('nama_asli', $profile->nama_asli) }}" required
                   placeholder="Contoh: Rina Wulandari" inputmode="text"
                   @class(['input-error' => $errors->has('nama_asli')])>
            @error('nama_asli')<span class="field-error">{{ $message }}</span>@enderror
            <p class="field-hint">Dipakai di dokumen kontrak resmi, bukan yang tampil ke publik.</p>

            <label>Nama Panggung / Username <span class="required-mark">*</span></label>
            <input type="text" name="username" value="{{ old('username', $profile->user->username) }}" required
                   placeholder="Contoh: rina_wulan" maxlength="50"
                   @class(['input-error' => $errors->has('username')])>
            @error('username')<span class="field-error">{{ $message }}</span>@enderror
            <p class="field-hint">Nama panggung yang dilihat Client. Huruf, angka, garis bawah, dan strip saja (tanpa spasi). Bisa dipakai untuk masuk selain email.</p>

            <label>Nomor WhatsApp</label>
            <input type="text" name="nomor_wa" value="{{ old('nomor_wa', $profile->user->nomor_wa) }}"
                   placeholder="Contoh: 08123456789" inputmode="tel"
                   @class(['input-error' => $errors->has('nomor_wa')])>
            @error('nomor_wa')<span class="field-error">{{ $message }}</span>@enderror
            <p class="field-hint">Buat notifikasi WhatsApp (apply, hasil seleksi, kontrak, pengingat jadwal).</p>
        </div>

        <div class="profile-section">
            <div class="profile-section-title">Data Diri & Ciri Fisik</div>
            <p class="field-hint" style="margin-top: -4px;">Membantu Client mencocokkan kamu dengan kebutuhan peran.</p>

            <label>Usia</label>
            <input type="number" name="usia" value="{{ old('usia', $profile->usia) }}"
                   placeholder="Contoh: 28" inputmode="numeric" min="1" max="120"
                   @class(['input-error' => $errors->has('usia')])>
            @error('usia')<span class="field-error">{{ $message }}</span>@enderror

            <label>Jenis Kelamin</label>
            <select name="gender" @class(['input-error' => $errors->has('gender')])>
                <option value="">Pilih salah satu</option>
                <option value="pria" @selected(old('gender', $profile->gender) === 'pria')>Laki-laki</option>
                <option value="wanita" @selected(old('gender', $profile->gender) === 'wanita')>Perempuan</option>
            </select>
            @error('gender')<span class="field-error">{{ $message }}</span>@enderror

            <label>Tinggi Badan (cm)</label>
            <input type="number" name="tinggi_badan" value="{{ old('tinggi_badan', $profile->tinggi_badan) }}"
                   placeholder="Contoh: 165" inputmode="numeric"
                   @class(['input-error' => $errors->has('tinggi_badan')])>
            @error('tinggi_badan')<span class="field-error">{{ $message }}</span>@enderror

            <label>Ukuran Baju</label>
            <input type="text" name="ukuran_baju" value="{{ old('ukuran_baju', $profile->ukuran_baju) }}"
                   placeholder="Contoh: M, L, XL"
                   @class(['input-error' => $errors->has('ukuran_baju')])>
            @error('ukuran_baju')<span class="field-error">{{ $message }}</span>@enderror

            <label>Warna Kulit</label>
            <input type="text" name="warna_kulit" value="{{ old('warna_kulit', $profile->warna_kulit) }}"
                   placeholder="Contoh: Sawo matang, Kuning langsat"
                   @class(['input-error' => $errors->has('warna_kulit')])>
            @error('warna_kulit')<span class="field-error">{{ $message }}</span>@enderror
        </div>

        <div class="profile-section">
            <div class="profile-section-title">Pengalaman & Kemampuan</div>

            <label>Pengalaman Main / Kerja Sebelumnya</label>
            <textarea name="pengalaman" rows="3" placeholder="Contoh: Pernah jadi figuran di iklan A, sinetron B..."
                      @class(['input-error' => $errors->has('pengalaman')])>{{ old('pengalaman', $profile->pengalaman) }}</textarea>
            @error('pengalaman')<span class="field-error">{{ $message }}</span>@enderror
            <p class="field-hint">Kosongkan bila belum memiliki pengalaman kerja.</p>

            <label>Bahasa yang Kamu Kuasai</label>
            <input type="text" name="bahasa" value="{{ old('bahasa', $profile->bahasa) }}"
                   placeholder="Contoh: Indonesia, Jawa, Inggris"
                   @class(['input-error' => $errors->has('bahasa')])>
            @error('bahasa')<span class="field-error">{{ $message }}</span>@enderror
        </div>

        <div class="profile-section">
            <div class="profile-section-title">Tentang Kamu</div>
            <p class="field-hint">Tulis tag yang menggambarkan kamu (usia tampilan, tipe, kemampuan). Pilih dari saran atau ketik sendiri. Dipakai Admin untuk mencocokkan peran.</p>
            <input type="hidden" name="categories_present" value="1">
            @include('partials.tag-input', ['name' => 'tag_nama', 'selected' => old('categories_present') ? old('tag_nama', []) : $profile->categories])
            @error('tag_nama')<span class="field-error">{{ $message }}</span>@enderror
            @error('categories.*')<span class="field-error">{{ $message }}</span>@enderror
        </div>

        <div class="profile-section">
            <div class="profile-section-title">Tautan Tambahan</div>
            <p class="field-hint" style="margin-top: -4px;">Instagram, TikTok, portofolio, atau tautan lain (opsional, dapat lebih dari satu).</p>

            @php
                if (old('tautan_label')) {
                    $existingTautan = collect(old('tautan_label'))->map(fn ($label, $i) => [
                        'label' => $label,
                        'url' => old('tautan_url')[$i] ?? '',
                    ])->all();
                } else {
                    $existingTautan = $profile->tautan_tambahan ?? [];
                }
            @endphp
            <div id="tautan-wrap">
                @forelse ($existingTautan as $i => $tautan)
                    <div class="tautan-row">
                        <label for="tautan_label_{{ $i }}" class="sr-only">Nama tautan</label>
                        <input type="text" name="tautan_label[]" id="tautan_label_{{ $i }}" value="{{ $tautan['label'] }}" placeholder="Nama (contoh: Instagram)" class="input-inline" style="flex: 0 0 130px;">
                        <label for="tautan_url_{{ $i }}" class="sr-only">URL tautan</label>
                        <input type="url" name="tautan_url[]" id="tautan_url_{{ $i }}" value="{{ $tautan['url'] }}" placeholder="https://..." class="input-inline">
                        <button type="button" class="btn-icon-danger btn-remove-tautan" aria-label="Hapus tautan ini">&times;</button>
                    </div>
                @empty
                    <div class="tautan-row">
                        <label for="tautan_label_0" class="sr-only">Nama tautan</label>
                        <input type="text" name="tautan_label[]" id="tautan_label_0" placeholder="Nama (contoh: Instagram)" class="input-inline" style="flex: 0 0 130px;">
                        <label for="tautan_url_0" class="sr-only">URL tautan</label>
                        <input type="url" name="tautan_url[]" id="tautan_url_0" placeholder="https://..." class="input-inline">
                        <button type="button" class="btn-icon-danger btn-remove-tautan" aria-label="Hapus tautan ini" style="display:none">&times;</button>
                    </div>
                @endforelse
            </div>
            <button type="button" id="btn-add-tautan" class="btn btn-sm" style="margin-top: 4px;">+ Tambah Tautan</button>
        </div>

        <div class="profile-section">
            <div class="profile-section-title">Tarif</div>

            <label>Tarif yang Kamu Harapkan (Rp)</label>
            <input type="number" name="rate_card" value="{{ old('rate_card', $profile->rate_card) }}"
                   placeholder="Contoh: 300000" inputmode="numeric" min="0"
                   @class(['input-error' => $errors->has('rate_card')])>
            @error('rate_card')<span class="field-error">{{ $message }}</span>@enderror
            <p class="field-hint">Tarif harapan awal. Nominal akhir akan didiskusikan bersama Admin.</p>
        </div>

        <div class="profile-section">
            <div class="profile-section-title">Tampil di Website</div>
            <input type="hidden" name="izin_present" value="1">
            <label style="display: flex; gap: 10px; align-items: flex-start; font-weight: 500;">
                <input type="checkbox" name="izin_tampil_publik" value="1" style="width: auto; min-height: auto; margin: 3px 0 0;"
                       @checked(old('izin_present') ? old('izin_tampil_publik') : $profile->izin_tampil_publik)>
                <span>Izinkan foto &amp; profil saya ditampilkan di website JBTB</span>
            </label>
            <p class="field-hint">Yang tampil hanya foto utama, username, dan tag usia. Nama asli, usia pasti, dan kontak tidak pernah ditampilkan. Admin JBTB yang memilih siapa yang tampil. Hapus centang kapan saja untuk langsung disembunyikan.</p>
        </div>

        <button type="submit" class="btn btn-brand" style="width: 100%; margin-top: 8px;">Simpan Profil</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function resizeFoto(file, done) {
        var img = new Image();
        img.onload = function () {
            URL.revokeObjectURL(img.src);
            var skala = 2000 / Math.max(img.width, img.height);
            if (skala >= 1) return done(file);
            var canvas = document.createElement('canvas');
            canvas.width = Math.round(img.width * skala);
            canvas.height = Math.round(img.height * skala);
            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
            canvas.toBlob(function (blob) {
                done(blob ? new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : file);
            }, 'image/jpeg', 0.85);
        };
        img.onerror = function () { done(file); };
        img.src = URL.createObjectURL(file);
    }

    function uploadWithProgress(input, file, progressEl, previewEl, onSuccess, onError) {
        var form = new FormData();
        form.append(input.name, file, file.name);
        form.append('_token', csrfToken);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', input.dataset.endpoint);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function () {
            progressEl.style.display = 'none';
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    onSuccess(data);
                } catch (e) {
                    onError('Upload gagal, coba lagi.');
                }
            } else {
                var msg = 'Upload gagal.';
                try {
                    var err = JSON.parse(xhr.responseText);
                    var first = err.errors ? Object.values(err.errors)[0] : null;
                    if (first) msg = Array.isArray(first) ? first[0] : first;
                } catch (e) {}
                onError(msg);
            }
        };

        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) progressEl.value = Math.round(e.loaded / e.total * 100);
            progressEl.textContent = progressEl.value + '%';
            progressEl.title = progressEl.value + '%';
        };

        xhr.onerror = function () {
            progressEl.style.display = 'none';
            onError('Koneksi bermasalah, coba lagi.');
        };

        progressEl.value = 0;
        progressEl.style.display = 'block';
        xhr.send(form);
    }

    function wireUpload(inputId) {
        var input = document.getElementById(inputId);
        if (!input) return;
        var progressEl = document.getElementById(input.dataset.progress);
        var previewEl  = document.getElementById(input.dataset.preview);
        var errorEl    = document.getElementById(input.dataset.error);
        var emptyEl    = input.dataset.empty ? document.getElementById(input.dataset.empty) : null;
        var isVideo    = input.dataset.type === 'video';
        var tileEl     = input.dataset.tile ? document.getElementById(input.dataset.tile) : null;

        function tampilError(msg) {
            errorEl.textContent = msg;
            errorEl.style.display = 'block';
        }

        input.addEventListener('change', function () {
            errorEl.style.display = 'none';
            if (!input.files[0]) return;
            (isVideo ? function (f, done) { done(f); } : resizeFoto)(input.files[0], function (file) {
                var maxKb = parseInt(input.dataset.max, 10);
                if (file.size > maxKb * 1024) {
                    return tampilError('Ukuran file ' + (file.size / 1048576).toFixed(1) + 'MB, maksimal ' + (maxKb / 1024) + 'MB.');
                }
                uploadWithProgress(input, file, progressEl, previewEl,
                    function (data) {
                        previewEl.src = data.url;
                        previewEl.style.display = 'block';
                        previewEl.hidden = false;
                        if (emptyEl) emptyEl.hidden = true;
                        if (tileEl) { tileEl.hidden = false; aturTambah(); }
                        if (!isVideo) previewEl.onload = null;
                    },
                    tampilError
                );
            });
        });
    }

    // BJ.2: kotak "+" menunjuk slot galeri kosong pertama, hilang kalau penuh
    function aturTambah() {
        var tambah = document.getElementById('foto-tambah');
        var kosong = document.querySelector('.foto-tile[hidden]');
        document.getElementById('foto-hint').classList.remove('is-kosong');
        if (!tambah) return;
        if (kosong) tambah.htmlFor = kosong.id.replace('tile-', 'upload-');
        else tambah.remove();
    }

    wireUpload('upload-foto');
    wireUpload('upload-video');
    wireUpload('upload-slot-1');
    wireUpload('upload-slot-2');
    wireUpload('upload-slot-3');
    wireUpload('upload-slot-4');

    // Tautan tambahan
    var wrap = document.getElementById('tautan-wrap');

    document.getElementById('btn-add-tautan').addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'tautan-row';
        row.innerHTML =
            '<input type="text" name="tautan_label[]" placeholder="Nama (contoh: Instagram)" class="input-inline" style="flex: 0 0 130px;">' +
            '<input type="url" name="tautan_url[]" placeholder="https://..." class="input-inline">' +
            '<button type="button" class="btn-icon-danger btn-remove-tautan" aria-label="Hapus tautan ini">&times;</button>';
        wrap.appendChild(row);
        updateRemoveButtons();
    });

    wrap.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-remove-tautan')) {
            e.target.closest('.tautan-row').remove();
            updateRemoveButtons();
        }
    });

    function updateRemoveButtons() {
        var rows = wrap.querySelectorAll('.btn-remove-tautan');
        rows.forEach(function (btn) {
            btn.style.display = rows.length > 1 ? 'block' : 'none';
        });
    }

    // Auto-scroll ke field error pertama
    document.addEventListener('DOMContentLoaded', function () {
        var first = document.querySelector('.input-error, .field-error');
        if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
})();
</script>
@endpush
