@extends('layouts.app')

@section('title', 'Lengkapi Profil')

@push('head')
<style>
    .field-error { color: var(--danger); font-size: 12px; margin-top: 4px; display: block; }
    .input-error { border-color: var(--danger) !important; }
    .upload-spinner {
        display: none; width: 22px; height: 22px; margin-top: 8px;
        border: 3px solid var(--border-color); border-top-color: var(--accent-strong);
        border-radius: 50%; animation: spin 0.7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .upload-error-msg { color: var(--danger); font-size: 12px; margin-top: 6px; display: none; }
</style>
@endpush

@section('content')
<div class="card" style="max-width: 560px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
        <div style="font-size: 17px; font-weight: 600;">Lengkapi Profil Kamu</div>
        <a href="{{ route('extras.profile.show') }}" class="btn btn-sm">Lihat Profil</a>
    </div>
    <p style="color: var(--text-secondary); font-size: 13px; margin-bottom: 20px; line-height: 1.5;">
        Data ini yang dilihat Admin & Casting Director saat memilih pemain. Isi sesuai kondisi kamu sekarang — nggak perlu sempurna, bisa diubah kapan saja.
    </p>

    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- ===== Foto Profil ===== --}}
    <div class="profile-section">
        <div class="profile-section-title">Foto Profil</div>
        <p class="field-hint" style="margin-top: -4px;">Ini yang pertama dilihat Casting Director. Pakai foto wajah yang jelas & terang.</p>

        <label for="upload-foto" class="media-upload-box" id="box-foto">
            @if ($profile->foto_profil_path)
                <img id="preview-foto" src="{{ route('extras.media.foto', $profile) }}" alt="Foto profil" class="media-upload-preview">
                <span class="media-upload-overlay">Ketuk untuk ganti foto</span>
            @else
                <span class="media-upload-empty" id="empty-foto">
                    <i class="ti ti-camera"></i>
                    Ketuk untuk pilih foto
                </span>
                <img id="preview-foto" src="" alt="Foto profil" class="media-upload-preview" style="display:none">
            @endif
        </label>
        <input type="file" name="foto" id="upload-foto" accept="image/jpeg,image/png" style="display: none;"
               data-endpoint="{{ route('extras.profile.foto.ajax') }}"
               data-progress="progress-foto"
               data-preview="preview-foto"
               data-error="err-foto">
        <div id="progress-foto" class="upload-spinner"></div>
        <span id="err-foto" class="upload-error-msg"></span>
        <p class="field-hint">Format JPG/PNG, maksimal 5MB.</p>
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
               data-preview="preview-video"
               data-error="err-video"
               data-type="video">
        <div id="progress-video" class="upload-spinner"></div>
        <span id="err-video" class="upload-error-msg"></span>
        @if ($profile->video_profil_path)
            <label for="upload-video" class="btn btn-sm" style="margin-top: 8px; cursor: pointer;">Ganti Video</label>
        @endif
        <p class="field-hint">Format MP4/MOV, maksimal 50MB.</p>
    </div>

    {{-- ===== Gallery ===== --}}
    <div class="profile-section">
        <div class="profile-section-title">Gallery</div>
        <p class="field-hint" style="margin-top: -4px;">Foto lain buat Admin menilai — misal dari sisi samping, badan penuh, atau gaya lain. Boleh diisi sebagian, boleh diganti kapan saja.</p>

        <div class="photo-slot-grid">
            @foreach ($fotoTambahan as $slot => $foto)
                <div @if($slot === 1) style="grid-column: span 2;" @endif>
                    @if($slot === 1)
                        <p style="font-size:11px; color:var(--accent-strong); font-weight:600; margin:0 0 4px; text-transform:uppercase; letter-spacing:.5px;">Foto Grid (kolase gaya Instagram)</p>
                    @endif
                    <label for="upload-slot-{{ $slot }}" class="media-upload-box photo-slot-box" id="box-slot-{{ $slot }}"
                           @if($slot === 1) style="aspect-ratio:2/1;" @endif>
                        @if ($foto)
                            <img id="preview-slot-{{ $slot }}" src="{{ route('extras.media.foto-tambahan', [$profile, $slot]) }}" alt="Foto tambahan {{ $slot }}" class="media-upload-preview">
                            <span class="media-upload-overlay">Ketuk untuk ganti</span>
                        @else
                            <span class="media-upload-empty" id="empty-slot-{{ $slot }}">
                                <i class="ti ti-plus"></i>
                                Slot {{ $slot }}
                            </span>
                            <img id="preview-slot-{{ $slot }}" src="" alt="Foto tambahan {{ $slot }}" class="media-upload-preview" style="display:none">
                        @endif
                    </label>
                    <input type="file" name="foto" id="upload-slot-{{ $slot }}" accept="image/jpeg,image/png"
                           style="display: none;"
                           data-endpoint="{{ route('extras.profile.foto-tambahan.ajax', $slot) }}"
                           data-progress="progress-slot-{{ $slot }}"
                           data-preview="preview-slot-{{ $slot }}"
                           data-error="err-slot-{{ $slot }}"
                           data-empty="empty-slot-{{ $slot }}">
                    <div id="progress-slot-{{ $slot }}" class="upload-spinner"></div>
                    <span id="err-slot-{{ $slot }}" class="upload-error-msg"></span>
                    @if ($foto)
                        <form method="POST" action="{{ route('extras.profile.foto-tambahan.hapus', $slot) }}" style="margin-top: 4px;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger-outline" style="width: 100%;">Hapus</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
        <p class="field-hint">Format JPG/PNG, maksimal 5MB per foto.</p>
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
            <p class="field-hint">Nama panggung yang dilihat Casting Director. Huruf, angka, garis bawah, dan strip saja (tanpa spasi). Bisa dipakai untuk masuk selain email.</p>

            <label>Nomor WhatsApp</label>
            <input type="text" name="nomor_wa" value="{{ old('nomor_wa', $profile->user->nomor_wa) }}"
                   placeholder="Contoh: 08123456789" inputmode="tel"
                   @class(['input-error' => $errors->has('nomor_wa')])>
            @error('nomor_wa')<span class="field-error">{{ $message }}</span>@enderror
            <p class="field-hint">Buat notifikasi WhatsApp (apply, hasil seleksi, kontrak, pengingat jadwal).</p>
        </div>

        <div class="profile-section">
            <div class="profile-section-title">Data Diri & Ciri Fisik</div>
            <p class="field-hint" style="margin-top: -4px;">Membantu Casting Director mencocokkan kamu dengan kebutuhan peran.</p>

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
            <p class="field-hint">Kosongkan saja kalau belum pernah punya pengalaman — nggak masalah.</p>

            <label>Bahasa yang Kamu Kuasai</label>
            <input type="text" name="bahasa" value="{{ old('bahasa', $profile->bahasa) }}"
                   placeholder="Contoh: Indonesia, Jawa, Inggris"
                   @class(['input-error' => $errors->has('bahasa')])>
            @error('bahasa')<span class="field-error">{{ $message }}</span>@enderror
        </div>

        <div class="profile-section">
            <div class="profile-section-title">Tautan Tambahan</div>
            <p class="field-hint" style="margin-top: -4px;">Instagram, TikTok, portofolio, atau link lain — opsional, boleh tambah lebih dari satu.</p>

            @php $existingTautan = old('tautan_label') ? [] : ($profile->tautan_tambahan ?? []); @endphp
            <div id="tautan-wrap">
                @forelse ($existingTautan as $i => $tautan)
                    <div class="tautan-row">
                        <input type="text" name="tautan_label[]" value="{{ $tautan['label'] }}" placeholder="Nama (contoh: Instagram)" class="input-inline" style="flex: 0 0 130px;">
                        <input type="url" name="tautan_url[]" value="{{ $tautan['url'] }}" placeholder="https://..." class="input-inline">
                        <button type="button" class="btn-icon-danger btn-remove-tautan">&times;</button>
                    </div>
                @empty
                    <div class="tautan-row">
                        <input type="text" name="tautan_label[]" placeholder="Nama (contoh: Instagram)" class="input-inline" style="flex: 0 0 130px;">
                        <input type="url" name="tautan_url[]" placeholder="https://..." class="input-inline">
                        <button type="button" class="btn-icon-danger btn-remove-tautan" style="display:none">&times;</button>
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
            <p class="field-hint">Ini cuma harapan awal kamu — nanti masih akan dibicarakan lagi sama Admin sebelum deal.</p>
        </div>

        <button type="submit" class="btn btn-brand" style="width: 100%; margin-top: 8px;">Simpan Profil</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function uploadWithProgress(input, progressEl, previewEl, onSuccess, onError) {
        var file = input.files[0];
        if (!file) return;

        var form = new FormData();
        form.append(input.name, file);
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

        xhr.onerror = function () {
            progressEl.style.display = 'none';
            onError('Koneksi bermasalah, coba lagi.');
        };

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

        input.addEventListener('change', function () {
            errorEl.style.display = 'none';
            uploadWithProgress(input, progressEl, previewEl,
                function (data) {
                    previewEl.src = data.url;
                    previewEl.style.display = 'block';
                    if (emptyEl) emptyEl.style.display = 'none';
                    if (!isVideo) previewEl.onload = null;
                },
                function (msg) {
                    errorEl.textContent = msg;
                    errorEl.style.display = 'block';
                }
            );
        });
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
            '<button type="button" class="btn-icon-danger btn-remove-tautan">&times;</button>';
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
