@extends('layouts.app')

@section('title', 'Profil')

@push('styles')
<style>
    .field-error { color: var(--danger); font-size: 12px; margin-top: 4px; display: block; }
    .input-error { border-color: var(--danger) !important; }
    .upload-progress { display: none; width: 100%; height: 10px; margin-top: 8px; accent-color: var(--accent-strong); }
    .upload-error-msg { color: var(--danger); font-size: 12px; margin-top: 6px; display: none; }
    .tautan-row .btn-icon-danger { width: 44px; height: 44px; flex-shrink: 0; }
    .pengalaman-row { align-items: flex-start; padding-bottom: 8px; border-bottom: 1px dashed var(--border-color); }
    .pengalaman-isi { flex: 1; min-width: 0; display: grid; grid-template-columns: minmax(0, 1fr); gap: 6px; }
    .pengalaman-sub { display: flex; gap: 6px; }
    .pengalaman-tahun { flex: 0 0 92px; }
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

    /* CL.1: kepala halaman (status kelengkapan) */
    .profil-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
    .profil-head-id { display: flex; gap: 12px; align-items: center; min-width: 0; }
    .profil-head-foto, .profil-head-foto-kosong { width: 56px; height: 56px; border-radius: 50%; flex-shrink: 0; }
    .profil-head-foto { object-fit: cover; background: var(--bg-nav-active); }
    .profil-head-foto-kosong { background: var(--bg-nav-active); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 22px; }
    .profil-head-nama { font-weight: 600; font-size: 16px; word-break: break-word; }
    .profil-status { font-size: 13px; margin-top: 4px; }
    .profil-status.is-lengkap { color: var(--accent-strong); font-weight: 600; }
    .profil-status.is-kurang { color: var(--danger); }
    .profil-status a { color: inherit; text-decoration: underline; }
    .profil-updated { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

    /* CL.1: kartu per bagian, mode baca <-> mode ubah lewat <details> */
    .bagian-card { border: 1px solid var(--border-color); border-radius: var(--radius-lg); margin-bottom: 12px; }
    .bagian-head { list-style: none; display: flex; justify-content: space-between; align-items: center; gap: 10px; min-height: 44px; padding: 14px 16px; cursor: pointer; }
    .bagian-head::-webkit-details-marker { display: none; }
    .bagian-title { font-size: 14px; font-weight: 600; }
    .bagian-aksi { font-size: 13px; font-weight: 600; color: var(--accent-strong); flex-shrink: 0; }
    .bagian-aksi::after { content: 'Ubah'; }
    .bagian-card[open] .bagian-aksi::after { content: 'Tutup'; }
    .bagian-baca { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 16px; padding: 0 16px 16px; }
    .bagian-card[open] .bagian-baca { display: none; }
    .bagian-form { display: none; padding: 16px; border-top: 1px solid var(--border-color); }
    .bagian-card[open] .bagian-form { display: block; }
    .baca-item { min-width: 0; }
    .baca-item.full { grid-column: 1 / -1; }
    .baca-label { font-size: 12px; color: var(--text-muted); margin-bottom: 2px; }
    .baca-value { font-size: 14px; color: var(--text-primary); font-weight: 500; word-break: break-word; }
    .baca-value.is-kosong { color: var(--text-muted); font-weight: 400; font-style: italic; }
    .baca-wajib { color: var(--danger); font-weight: 600; font-size: 11px; margin-left: 4px; }
    .bagian-sub { font-size: 13px; font-weight: 600; margin: 14px 0 8px; }
    .bagian-sub:first-child { margin-top: 0; }
    .bagian-foot { display: flex; gap: 8px; margin-top: 4px; }
    @media (max-width: 640px) {
        .bagian-baca { grid-template-columns: 1fr; }
        .bagian-foot { position: sticky; bottom: 0; background: var(--bg-card); margin: 12px -16px 0; padding: 10px 16px calc(10px + env(safe-area-inset-bottom)); border-top: 1px solid var(--border-color); }
    }

    /* CL.3: jenis kelamin jadi 2 tombol segmen */
    .gender-segmen { display: flex; gap: 8px; margin-bottom: 14px; }
    .gender-opsi { flex: 1; }
    .gender-opsi input { position: absolute; opacity: 0; pointer-events: none; }
    .gender-opsi label { display: flex; align-items: center; justify-content: center; min-height: 44px; margin: 0; border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer; font-weight: 500; color: var(--text-primary); background: var(--bg-card); }
    .gender-opsi input:checked + label { background: var(--accent); border-color: var(--accent); color: var(--accent-on); }
    .gender-opsi input:focus-visible + label { outline: 2px solid var(--accent); outline-offset: 2px; }
</style>
@endpush

@section('content')
@php
    $bukaBagian = old('bagian') ?: ($dataDiriBelumLengkap ? 'data_diri' : null);
    $belum = fn ($v) => $v === null || $v === '' || $v === [];
@endphp
<div class="card" style="max-width: 560px; margin: 0 auto;">
    <div class="profil-head">
        <div class="profil-head-id">
            @if ($profile->exists && $profile->foto_profil_path)
                <img src="{{ route('extras.media.foto', $profile) }}" class="profil-head-foto" alt="">
            @else
                <span class="profil-head-foto-kosong"><i class="ti ti-user" aria-hidden="true"></i></span>
            @endif
            <div>
                <div class="profil-head-nama">{{ $profile->user->username ? '@'.$profile->user->username : '(belum ada nama panggung)' }}</div>
                @if ($profile->profilLengkap())
                    <div class="profil-status is-lengkap">Profil siap melamar <i class="ti ti-circle-check" aria-hidden="true"></i></div>
                @else
                    <div class="profil-status is-kurang">Belum bisa melamar: lengkapi
                        @foreach ($kurangDaftar as $i => $item)
                            <a href="#{{ $item['anchor'] }}">{{ $item['label'] }}</a>{{ $loop->last ? '' : ', ' }}
                        @endforeach
                    </div>
                @endif
                <div class="profil-updated" style="margin-top: 6px;">Profil {{ $profile->persenKelengkapan() }}% lengkap</div>
                <progress value="{{ $profile->persenKelengkapan() }}" max="100" class="profil-persen-bar"></progress>
                @if ($profile->updated_at)
                    <div class="profil-updated">Terakhir diperbarui {{ $profile->updated_at->translatedFormat('d M Y, H:i') }}</div>
                @endif
            </div>
        </div>
        <a href="{{ route('extras.profile.show') }}" class="btn">Lihat seperti Client</a>
    </div>

    {{-- ===== 1. Foto & Video (upload AJAX, tidak lewat tombol Ubah) ===== --}}
    @php
        $jumlahGaleri = count(array_filter($fotoTambahan));
        $slotKosong = array_keys(array_filter($fotoTambahan, fn ($f) => ! $f));
    @endphp
    <div class="profile-section" id="bagian-foto">
        <div class="profile-section-title">Foto</div>
        <button type="button" class="btn btn-sm" style="margin-bottom: 12px;" onclick="document.getElementById('dialog-contoh-foto').showModal()">Contoh foto</button>
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

    <dialog class="xmodal" id="dialog-contoh-foto" aria-label="Contoh foto yang bagus" onclick="if (event.target === this) this.close()">
        <div class="xmodal-body">
            <div class="xmodal-head">
                <div class="xmodal-name" style="font-size: 16px;">Contoh foto yang bagus</div>
                <button type="button" class="xmodal-x" style="position: static; flex-shrink: 0; background: var(--bg-card-hover); color: var(--text-primary);" aria-label="Tutup" onclick="this.closest('dialog').close()"><i class="ti ti-x" aria-hidden="true"></i></button>
            </div>
            <ul style="list-style: none; padding: 0; margin: 14px 0 0; display: flex; flex-direction: column; gap: 12px; font-size: 13.5px;">
                <li style="display: flex; gap: 10px; align-items: flex-start;"><i class="ti ti-sun" style="font-size: 20px; color: var(--accent-strong);" aria-hidden="true"></i> Wajah jelas &amp; cahaya terang</li>
                <li style="display: flex; gap: 10px; align-items: flex-start;"><i class="ti ti-forbid-2" style="font-size: 20px; color: var(--accent-strong);" aria-hidden="true"></i> Tanpa filter/kacamata hitam</li>
                <li style="display: flex; gap: 10px; align-items: flex-start;"><i class="ti ti-crop" style="font-size: 20px; color: var(--accent-strong);" aria-hidden="true"></i> Close-up, setengah badan, dan seluruh badan</li>
                <li style="display: flex; gap: 10px; align-items: flex-start;"><i class="ti ti-square" style="font-size: 20px; color: var(--accent-strong);" aria-hidden="true"></i> Latar polos</li>
            </ul>
        </div>
    </dialog>

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

    {{-- ===== 2. Data Diri ===== --}}
    <details id="bagian-data_diri" class="bagian-card" @if ($bukaBagian === 'data_diri') open @endif>
        <summary class="bagian-head"><span class="bagian-title">Data Diri</span><span class="bagian-aksi"></span></summary>
        <div class="bagian-baca">
            <div class="baca-item">
                <div class="baca-label">Nama Asli</div>
                <div class="baca-value @if ($belum($profile->nama_asli)) is-kosong @endif">{{ $profile->nama_asli ?: '–' }}@if ($belum($profile->nama_asli))<span class="baca-wajib">Wajib</span>@endif</div>
            </div>
            <div class="baca-item">
                <div class="baca-label">Nama Panggung</div>
                <div class="baca-value @if ($belum($profile->user->username)) is-kosong @endif">{{ $profile->user->username ? '@'.$profile->user->username : '–' }}@if ($belum($profile->user->username))<span class="baca-wajib">Wajib</span>@endif</div>
            </div>
            <div class="baca-item">
                <div class="baca-label">Usia</div>
                <div class="baca-value @if ($belum($profile->usia)) is-kosong @endif">{{ $profile->usia ? $profile->usia.' th' : '–' }}@if ($belum($profile->usia))<span class="baca-wajib">Wajib</span>@endif</div>
            </div>
            <div class="baca-item">
                <div class="baca-label">Jenis Kelamin</div>
                <div class="baca-value @if ($belum($profile->gender)) is-kosong @endif">{{ $profile->genderLabel() ?: '–' }}@if ($belum($profile->gender))<span class="baca-wajib">Wajib</span>@endif</div>
            </div>
            <div class="baca-item">
                <div class="baca-label">Tinggi Badan</div>
                <div class="baca-value @if ($belum($profile->tinggi_badan)) is-kosong @endif">{{ $profile->tinggi_badan ? $profile->tinggi_badan.' cm' : '–' }}@if ($belum($profile->tinggi_badan))<span class="baca-wajib">Wajib</span>@endif</div>
            </div>
            <div class="baca-item">
                <div class="baca-label">Berat Badan</div>
                <div class="baca-value @if ($belum($profile->berat_badan)) is-kosong @endif">{{ $profile->berat_badan ? $profile->berat_badan.' kg' : '–' }}</div>
            </div>
            <div class="baca-item">
                <div class="baca-label">Ukuran Baju</div>
                <div class="baca-value @if ($belum($profile->ukuran_baju)) is-kosong @endif">{{ $profile->ukuran_baju ?: '–' }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('extras.profile.update') }}" class="bagian-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="bagian" value="data_diri">

            <label for="nama_asli">Nama Asli (sesuai KTP) <span class="wajib" aria-hidden="true">*</span></label>
            <input type="text" name="nama_asli" id="nama_asli" value="{{ old('nama_asli', $profile->nama_asli) }}" required
                   autocomplete="name" placeholder="Contoh: Rina Wulandari"
                   @class(['input-error' => $errors->has('nama_asli')])>
            @error('nama_asli')<span class="field-error">{{ $message }}</span>@enderror
            <p class="field-hint">Dipakai di kontrak, tidak tampil ke publik</p>

            <label for="username">Nama Panggung / Username <span class="wajib" aria-hidden="true">*</span></label>
            <input type="text" name="username" id="username" value="{{ old('username', $profile->user->username) }}" required
                   placeholder="Contoh: rina_wulan" maxlength="50"
                   @class(['input-error' => $errors->has('username')])>
            @error('username')<span class="field-error">{{ $message }}</span>@enderror
            <p class="field-hint">Huruf, angka, _ dan - tanpa spasi</p>

            <label for="usia">Usia</label>
            <input type="number" name="usia" id="usia" value="{{ old('usia', $profile->usia) }}"
                   placeholder="Contoh: 28" inputmode="numeric" min="1" max="120"
                   @class(['input-error' => $errors->has('usia')])>
            @error('usia')<span class="field-error">{{ $message }}</span>@enderror

            <label id="gender-label">Jenis Kelamin</label>
            <div class="gender-segmen" role="radiogroup" aria-labelledby="gender-label">
                @foreach (['pria' => 'Laki-laki', 'wanita' => 'Perempuan'] as $val => $label)
                    <div class="gender-opsi">
                        <input type="radio" name="gender" id="gender_{{ $val }}" value="{{ $val }}" @checked(old('gender', $profile->gender) === $val)>
                        <label for="gender_{{ $val }}">{{ $label }}</label>
                    </div>
                @endforeach
            </div>
            @error('gender')<span class="field-error">{{ $message }}</span>@enderror

            <div class="form-row">
                <div style="min-width: 130px;">
                    <label for="tinggi_badan">Tinggi Badan (cm)</label>
                    <input type="number" name="tinggi_badan" id="tinggi_badan" value="{{ old('tinggi_badan', $profile->tinggi_badan) }}"
                           placeholder="Contoh: 165" inputmode="numeric" min="1"
                           @class(['input-error' => $errors->has('tinggi_badan')])>
                    @error('tinggi_badan')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div style="min-width: 130px;">
                    <label for="berat_badan">Berat Badan (kg)</label>
                    <input type="number" name="berat_badan" id="berat_badan" value="{{ old('berat_badan', $profile->berat_badan) }}"
                           placeholder="Contoh: 55" inputmode="numeric" min="20" max="250"
                           @class(['input-error' => $errors->has('berat_badan')])>
                    @error('berat_badan')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <label for="ukuran_baju">Ukuran Baju</label>
            <input type="text" name="ukuran_baju" id="ukuran_baju" value="{{ old('ukuran_baju', $profile->ukuran_baju) }}"
                   placeholder="Contoh: M, L, XL" list="ukuran-baju-opsi"
                   @class(['input-error' => $errors->has('ukuran_baju')])>
            <datalist id="ukuran-baju-opsi">
                <option value="S"><option value="M"><option value="L"><option value="XL"><option value="XXL">
            </datalist>
            @error('ukuran_baju')<span class="field-error">{{ $message }}</span>@enderror

            <div class="bagian-foot">
                <button type="submit" class="btn btn-brand">Simpan</button>
                <button type="button" class="btn" onclick="this.closest('details').open=false">Batal</button>
            </div>
        </form>
    </details>

    {{-- ===== 3. Kontak ===== --}}
    <details id="bagian-kontak" class="bagian-card" @if ($bukaBagian === 'kontak') open @endif>
        <summary class="bagian-head"><span class="bagian-title">Kontak</span><span class="bagian-aksi"></span></summary>
        <div class="bagian-baca">
            <div class="baca-item">
                <div class="baca-label">Nomor WhatsApp</div>
                <div class="baca-value @if ($belum($profile->user->nomor_wa)) is-kosong @endif">{{ $profile->user->nomor_wa ?: '–' }}</div>
            </div>
            @if ($profile->rekeningTersamar())
                <div class="baca-item">
                    <div class="baca-label">Rekening</div>
                    <div class="baca-value">{{ $profile->rekeningTersamar() }}</div>
                </div>
            @endif
        </div>
        <form method="POST" action="{{ route('extras.profile.update') }}" class="bagian-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="bagian" value="kontak">

            <label for="nomor_wa">Nomor WhatsApp</label>
            <input type="tel" name="nomor_wa" id="nomor_wa" autocomplete="tel" value="{{ old('nomor_wa', $profile->user->nomor_wa) }}"
                   placeholder="Contoh: 08123456789"
                   @class(['input-error' => $errors->has('nomor_wa')])>
            @error('nomor_wa')<span class="field-error">{{ $message }}</span>@enderror
            <p class="field-hint">Untuk notifikasi WhatsApp</p>

            @if ($profile->rekeningTersamar())
                <p class="field-hint">Rekening {{ $profile->rekeningTersamar() }}. Untuk mengganti, kamu akan diminta lagi saat lolos di proyek berikutnya.</p>
            @endif

            <div class="bagian-foot">
                <button type="submit" class="btn btn-brand">Simpan</button>
                <button type="button" class="btn" onclick="this.closest('details').open=false">Batal</button>
            </div>
        </form>
    </details>

    {{-- ===== 4. Tentang Kamu ===== --}}
    <details id="bagian-tentang" class="bagian-card" @if ($bukaBagian === 'tentang') open @endif>
        <summary class="bagian-head"><span class="bagian-title">Tentang Kamu</span><span class="bagian-aksi"></span></summary>
        <div class="bagian-baca">
            <div class="baca-item full">
                <div class="baca-label">Tag</div>
                <div class="baca-value @if ($profile->categories->isEmpty()) is-kosong @endif">{{ $profile->categories->isNotEmpty() ? $profile->categories->pluck('nama')->map(fn ($n) => '#'.$n)->implode(' ') : '–' }}</div>
            </div>
            <div class="baca-item full">
                <div class="baca-label">Bahasa</div>
                <div class="baca-value @if ($belum($profile->bahasa)) is-kosong @endif">{{ $profile->bahasa ?: '–' }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('extras.profile.update') }}" class="bagian-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="bagian" value="tentang">

            <input type="hidden" name="categories_present" value="1">
            @include('partials.tag-input', ['name' => 'tag_nama', 'selected' => old('categories_present') ? old('tag_nama', []) : $profile->categories])
            @error('tag_nama')<span class="field-error">{{ $message }}</span>@enderror
            @error('categories.*')<span class="field-error">{{ $message }}</span>@enderror

            <label>Bahasa yang Kamu Kuasai</label>
            @php
                $existingBahasa = old('bahasa') !== null
                    ? array_values((array) old('bahasa'))
                    : preg_split('/\s*,\s*/', (string) $profile->bahasa, -1, PREG_SPLIT_NO_EMPTY);
            @endphp
            <div id="bahasa-wrap" data-max="10">
                @forelse ($existingBahasa as $i => $bahasa)
                    <div class="tautan-row">
                        <label for="bahasa_{{ $i }}" class="sr-only">Bahasa</label>
                        <input type="text" name="bahasa[]" id="bahasa_{{ $i }}" value="{{ $bahasa }}" placeholder="Contoh: Indonesia" maxlength="40" @class(['input-inline', 'input-error' => $errors->has("bahasa.$i")])>
                        <button type="button" class="btn-icon-danger btn-remove-row" aria-label="Hapus bahasa ini">&times;</button>
                    </div>
                @empty
                    <div class="tautan-row">
                        <label for="bahasa_0" class="sr-only">Bahasa</label>
                        <input type="text" name="bahasa[]" id="bahasa_0" placeholder="Contoh: Indonesia" maxlength="40" class="input-inline">
                        <button type="button" class="btn-icon-danger btn-remove-row" aria-label="Hapus bahasa ini">&times;</button>
                    </div>
                @endforelse
            </div>
            @error('bahasa')<span class="field-error">{{ $message }}</span>@enderror
            @error('bahasa.*')<span class="field-error">{{ $message }}</span>@enderror
            <button type="button" id="btn-add-bahasa" class="btn" style="margin-top: 4px;">+ Tambah bahasa</button>

            <div class="bagian-foot">
                <button type="submit" class="btn btn-brand">Simpan</button>
                <button type="button" class="btn" onclick="this.closest('details').open=false">Batal</button>
            </div>
        </form>
    </details>

    {{-- ===== 5. Pengalaman ===== --}}
    <details id="bagian-pengalaman" class="bagian-card" @if ($bukaBagian === 'pengalaman') open @endif>
        <summary class="bagian-head"><span class="bagian-title">Pengalaman</span><span class="bagian-aksi"></span></summary>
        @php $pengalamanUrut = $profile->pengalamanUrut(); @endphp
        <div class="bagian-baca">
            <div class="baca-item full">
                @if (empty($pengalamanUrut))
                    <div class="baca-value is-kosong">–</div>
                @else
                    @foreach ($pengalamanUrut as $pg)
                        <div class="baca-value" style="margin-bottom: 6px;">{{ $pg['judul'] }}{{ empty($pg['tahun']) ? '' : ' ('.$pg['tahun'].')' }}{{ empty($pg['keterangan']) ? '' : ' - '.$pg['keterangan'] }}</div>
                    @endforeach
                @endif
            </div>
        </div>
        <form method="POST" action="{{ route('extras.profile.update') }}" class="bagian-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="bagian" value="pengalaman">

            <p class="field-hint" style="margin-top: -4px;">Main film, iklan, video klip, teater, atau kerja lain yang relevan (maks 20). Kemampuan (naik motor, menari, dll) tambahkan sebagai tag di atas.</p>

            @php
                $maxTahun = now()->year + 1;
                if (old('pengalaman_judul')) {
                    $existingPengalaman = collect(old('pengalaman_judul'))->map(fn ($judul, $i) => [
                        'judul' => $judul,
                        'keterangan' => old('pengalaman_keterangan')[$i] ?? '',
                        'tahun' => old('pengalaman_tahun')[$i] ?? '',
                    ])->all();
                } else {
                    $existingPengalaman = $pengalamanUrut;
                }
            @endphp
            <div id="pengalaman-wrap" data-max="20">
                @forelse ($existingPengalaman as $i => $pg)
                    <div class="tautan-row pengalaman-row">
                        <div class="pengalaman-isi">
                            <label for="pengalaman_judul_{{ $i }}" class="sr-only">Judul pengalaman</label>
                            <input type="text" name="pengalaman_judul[]" id="pengalaman_judul_{{ $i }}" value="{{ $pg['judul'] }}" placeholder="Contoh: Figuran iklan Ramadan" maxlength="150" @class(['input-inline', 'input-error' => $errors->has("pengalaman_judul.$i")])>
                            <div class="pengalaman-sub">
                                <label for="pengalaman_keterangan_{{ $i }}" class="sr-only">Keterangan</label>
                                <input type="text" name="pengalaman_keterangan[]" id="pengalaman_keterangan_{{ $i }}" value="{{ $pg['keterangan'] }}" placeholder="Keterangan" maxlength="255" class="input-inline">
                                <label for="pengalaman_tahun_{{ $i }}" class="sr-only">Tahun</label>
                                <input type="number" name="pengalaman_tahun[]" id="pengalaman_tahun_{{ $i }}" value="{{ $pg['tahun'] }}" placeholder="Tahun" inputmode="numeric" min="1950" max="{{ $maxTahun }}" @class(['input-inline pengalaman-tahun', 'input-error' => $errors->has("pengalaman_tahun.$i")])>
                            </div>
                            @foreach (["pengalaman_judul.$i", "pengalaman_tahun.$i"] as $k)
                                @error($k)<span class="field-error">{{ $message }}</span>@enderror
                            @endforeach
                        </div>
                        <button type="button" class="btn-icon-danger btn-remove-row" aria-label="Hapus pengalaman ini">&times;</button>
                    </div>
                @empty
                    <div class="tautan-row pengalaman-row">
                        <div class="pengalaman-isi">
                            <label for="pengalaman_judul_0" class="sr-only">Judul pengalaman</label>
                            <input type="text" name="pengalaman_judul[]" id="pengalaman_judul_0" placeholder="Contoh: Figuran iklan Ramadan" maxlength="150" class="input-inline">
                            <div class="pengalaman-sub">
                                <label for="pengalaman_keterangan_0" class="sr-only">Keterangan</label>
                                <input type="text" name="pengalaman_keterangan[]" id="pengalaman_keterangan_0" placeholder="Keterangan" maxlength="255" class="input-inline">
                                <label for="pengalaman_tahun_0" class="sr-only">Tahun</label>
                                <input type="number" name="pengalaman_tahun[]" id="pengalaman_tahun_0" placeholder="Tahun" inputmode="numeric" min="1950" max="{{ $maxTahun }}" class="input-inline pengalaman-tahun">
                            </div>
                        </div>
                        <button type="button" class="btn-icon-danger btn-remove-row" aria-label="Hapus pengalaman ini">&times;</button>
                    </div>
                @endforelse
            </div>
            @error('pengalaman_judul')<span class="field-error">{{ $message }}</span>@enderror
            <button type="button" id="btn-add-pengalaman" class="btn" style="margin: 4px 0 16px;">+ Tambah pengalaman</button>

            <div class="bagian-foot">
                <button type="submit" class="btn btn-brand">Simpan</button>
                <button type="button" class="btn" onclick="this.closest('details').open=false">Batal</button>
            </div>
        </form>
    </details>

    {{-- ===== 6. Tautan & Tarif ===== --}}
    <details id="bagian-tautan" class="bagian-card" @if ($bukaBagian === 'tautan') open @endif>
        <summary class="bagian-head"><span class="bagian-title">Tautan &amp; Tarif</span><span class="bagian-aksi"></span></summary>
        <div class="bagian-baca">
            <div class="baca-item full">
                <div class="baca-label">Tautan Tambahan</div>
                @if (empty($profile->tautan_tambahan))
                    <div class="baca-value is-kosong">–</div>
                @else
                    @foreach ($profile->tautan_tambahan as $t)
                        <div class="baca-value" style="margin-bottom: 4px;"><a href="{{ $t['url'] }}" target="_blank" rel="noopener">{{ $t['label'] }}</a></div>
                    @endforeach
                @endif
            </div>
            <div class="baca-item">
                <div class="baca-label">Tarif</div>
                <div class="baca-value @if ($belum($profile->rate_card)) is-kosong @endif">{{ $profile->rate_card ? 'Rp '.number_format($profile->rate_card, 0, ',', '.') : '–' }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('extras.profile.update') }}" class="bagian-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="bagian" value="tautan">

            <div class="bagian-sub">Tautan Tambahan</div>
            <p class="field-hint" style="margin-top: -4px;">Punya portofolio/showreel? Taruh link-nya di sini. Instagram, TikTok, atau tautan lain juga boleh.</p>

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
                        <input type="text" name="tautan_label[]" id="tautan_label_{{ $i }}" value="{{ $tautan['label'] }}" placeholder="Nama (contoh: Instagram)" class="input-inline" style="flex: 0 0 130px;" @class(['input-error' => $errors->has("tautan_label.$i")])>
                        <label for="tautan_url_{{ $i }}" class="sr-only">URL tautan</label>
                        <input type="url" name="tautan_url[]" id="tautan_url_{{ $i }}" value="{{ $tautan['url'] }}" placeholder="https://..." @class(['input-inline', 'input-error' => $errors->has("tautan_url.$i")])>
                        <button type="button" class="btn-icon-danger btn-remove-row" aria-label="Hapus tautan ini">&times;</button>
                    </div>
                    @error("tautan_label.$i")<span class="field-error">{{ $message }}</span>@enderror
                    @error("tautan_url.$i")<span class="field-error">{{ $message }}</span>@enderror
                @empty
                    <div class="tautan-row">
                        <label for="tautan_label_0" class="sr-only">Nama tautan</label>
                        <input type="text" name="tautan_label[]" id="tautan_label_0" placeholder="Nama (contoh: Instagram)" class="input-inline" style="flex: 0 0 130px;">
                        <label for="tautan_url_0" class="sr-only">URL tautan</label>
                        <input type="url" name="tautan_url[]" id="tautan_url_0" placeholder="https://..." class="input-inline">
                        <button type="button" class="btn-icon-danger btn-remove-row" aria-label="Hapus tautan ini" style="display:none">&times;</button>
                    </div>
                @endforelse
            </div>
            <button type="button" id="btn-add-tautan" class="btn" style="margin-top: 4px;">+ Tambah Tautan</button>

            <div class="bagian-sub">Tarif</div>
            <label for="rate_card">Tarif yang Kamu Harapkan (Rp)</label>
            <input type="number" name="rate_card" id="rate_card" value="{{ old('rate_card', $profile->rate_card) }}"
                   placeholder="Contoh: 300000" inputmode="numeric" min="0"
                   oninput="this.form.querySelector('[data-pratinjau]').textContent = this.value ? 'Rp ' + Number(this.value).toLocaleString('id-ID') : ''"
                   @class(['input-error' => $errors->has('rate_card')])>
            <div data-pratinjau style="margin: -8px 0 14px; font-size: 12px; color: var(--text-muted);" aria-live="polite">{{ $profile->rate_card ? 'Rp '.number_format($profile->rate_card, 0, ',', '.') : '' }}</div>
            @error('rate_card')<span class="field-error">{{ $message }}</span>@enderror
            <p class="field-hint" style="margin-top: -8px;">Tarif harapan awal. Nominal akhir akan didiskusikan bersama Admin.</p>

            <div class="bagian-foot">
                <button type="submit" class="btn btn-brand">Simpan</button>
                <button type="button" class="btn" onclick="this.closest('details').open=false">Batal</button>
            </div>
        </form>
    </details>

    {{-- ===== 7. Privasi ===== --}}
    <details id="bagian-privasi" class="bagian-card" @if ($bukaBagian === 'privasi') open @endif>
        <summary class="bagian-head"><span class="bagian-title">Privasi</span><span class="bagian-aksi"></span></summary>
        <div class="bagian-baca">
            <div class="baca-item full">
                <div class="baca-label">Tampil di Website</div>
                <div class="baca-value">{{ $profile->izin_tampil_publik ? 'Diizinkan' : 'Tidak diizinkan' }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('extras.profile.update') }}" class="bagian-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="bagian" value="privasi">
            <input type="hidden" name="izin_present" value="1">
            <label style="display: flex; gap: 10px; align-items: flex-start; font-weight: 500;">
                <input type="checkbox" name="izin_tampil_publik" value="1" style="width: auto; min-height: auto; margin: 3px 0 0;"
                       @checked(old('izin_present') ? old('izin_tampil_publik') : $profile->izin_tampil_publik)>
                <span>Izinkan foto &amp; profil saya ditampilkan di website JBTB</span>
            </label>
            <p class="field-hint">Yang tampil hanya foto utama, username, dan tag usia. Nama asli, usia pasti, dan kontak tidak pernah ditampilkan. Admin JBTB yang memilih siapa yang tampil. Hapus centang kapan saja untuk langsung disembunyikan.</p>

            <div class="bagian-foot">
                <button type="submit" class="btn btn-brand">Simpan</button>
                <button type="button" class="btn" onclick="this.closest('details').open=false">Batal</button>
            </div>
        </form>
    </details>
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

    // BJ.3: daftar "+" (tautan & pengalaman): baris baru = salinan baris pertama dikosongkan
    function daftarPlus(wrapId, btnId) {
        var wrap = document.getElementById(wrapId);
        var btn = document.getElementById(btnId);
        var max = parseInt(wrap.dataset.max || '0', 10);
        function segarkan() {
            var rows = wrap.children;
            Array.prototype.forEach.call(wrap.querySelectorAll('.btn-remove-row'), function (b) { b.style.display = rows.length > 1 ? '' : 'none'; });
            btn.hidden = max > 0 && rows.length >= max;
        }
        btn.addEventListener('click', function () {
            var row = wrap.firstElementChild.cloneNode(true);
            var n = wrap.children.length + Date.now();
            row.querySelectorAll('.field-error').forEach(function (e) { e.remove(); });
            row.querySelectorAll('input').forEach(function (i) { i.value = ''; i.classList.remove('input-error'); i.id = i.id.replace(/_\d+$/, '_' + n); });
            row.querySelectorAll('label[for]').forEach(function (l) { l.htmlFor = l.htmlFor.replace(/_\d+$/, '_' + n); });
            wrap.appendChild(row);
            segarkan();
            row.querySelector('input').focus();
        });
        wrap.addEventListener('click', function (e) {
            if (!e.target.classList.contains('btn-remove-row')) return;
            e.target.closest('.tautan-row').remove();
            segarkan();
        });
        segarkan();
    }
    daftarPlus('tautan-wrap', 'btn-add-tautan');
    daftarPlus('pengalaman-wrap', 'btn-add-pengalaman');
    daftarPlus('bahasa-wrap', 'btn-add-bahasa');

    // CL.1.4: cuma satu bagian terbuka sekaligus.
    var bagianList = document.querySelectorAll('.bagian-card');
    bagianList.forEach(function (d) {
        d.addEventListener('toggle', function () {
            if (d.open) bagianList.forEach(function (lain) { if (lain !== d) lain.open = false; });
        });
    });

    // CL.1.1: redirect pasca-simpan bawa anchor #bagian-xxx, buka kartunya.
    var hashId = location.hash.slice(1);
    if (hashId) {
        var targetBagian = document.getElementById(hashId);
        if (targetBagian && targetBagian.tagName === 'DETAILS') targetBagian.open = true;
    }

    // Auto-scroll ke field error pertama
    document.addEventListener('DOMContentLoaded', function () {
        var first = document.querySelector('.input-error, .field-error');
        if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
})();
</script>
@endpush
