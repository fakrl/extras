@extends('layouts.app')

@section('title', 'Lengkapi Profil')

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

    {{-- ===== Foto & Video: paling penting buat Casting Director, taruh paling atas ===== --}}
    <div class="profile-section">
        <div class="profile-section-title">1. Reel — Foto Profil</div>
        <p class="field-hint" style="margin-top: -4px;">Ini yang pertama dilihat Casting Director. Pakai foto wajah yang jelas & terang.</p>

        <form method="POST" action="{{ route('extras.profile.foto') }}" enctype="multipart/form-data">
            @csrf
            <label for="upload-foto" class="media-upload-box">
                @if ($profile->foto_profil_path)
                    <img src="{{ route('extras.media.foto', $profile) }}" alt="Foto profil" class="media-upload-preview">
                    <span class="media-upload-overlay">Ketuk untuk ganti foto</span>
                @else
                    <span class="media-upload-empty">
                        <i class="ti ti-camera"></i>
                        Ketuk untuk pilih foto
                    </span>
                @endif
            </label>
            <input type="file" name="foto" id="upload-foto" accept="image/jpeg,image/png"
                   style="display: none;" onchange="this.form.submit()">
        </form>
        <p class="field-hint">Format JPG/PNG, maksimal 5MB.</p>
    </div>

    <div class="profile-section">
        <div class="profile-section-title">2. Reel — Video Perkenalan</div>
        <p class="field-hint" style="margin-top: -4px;">Video singkat (30-60 detik) memperkenalkan diri. Boleh direkam pakai HP.</p>

        <form method="POST" action="{{ route('extras.profile.video') }}" enctype="multipart/form-data">
            @csrf
            <label for="upload-video" class="media-upload-box media-upload-box-video">
                @if ($profile->video_profil_path)
                    <video src="{{ route('extras.media.video', $profile) }}" class="media-upload-preview" controls></video>
                @else
                    <span class="media-upload-empty">
                        <i class="ti ti-video"></i>
                        Ketuk untuk pilih video
                    </span>
                @endif
            </label>
            <input type="file" name="video" id="upload-video" accept="video/mp4,video/quicktime,video/webm"
                   style="display: none;" onchange="this.form.submit()">
        </form>
        @if ($profile->video_profil_path)
            <label for="upload-video" class="btn btn-sm" style="margin-top: 8px; cursor: pointer;">Ganti Video</label>
        @endif
        <p class="field-hint">Format MP4/MOV, maksimal 50MB.</p>
    </div>

    <div class="profile-section">
        <div class="profile-section-title">3. Reel — Foto Tambahan</div>
        <p class="field-hint" style="margin-top: -4px;">Foto lain buat Admin menilai — misal dari sisi samping, badan penuh, atau gaya lain. Boleh diisi sebagian, boleh diganti kapan saja.</p>

        <div class="photo-slot-grid">
            @foreach ($fotoTambahan as $slot => $foto)
                <div>
                    <form method="POST" action="{{ route('extras.profile.foto-tambahan', $slot) }}" enctype="multipart/form-data">
                        @csrf
                        <label for="upload-slot-{{ $slot }}" class="media-upload-box photo-slot-box">
                            @if ($foto)
                                <img src="{{ route('extras.media.foto-tambahan', [$profile, $slot]) }}" alt="Foto tambahan {{ $slot }}" class="media-upload-preview">
                                <span class="media-upload-overlay">Ketuk untuk ganti</span>
                            @else
                                <span class="media-upload-empty">
                                    <i class="ti ti-plus"></i>
                                    Slot {{ $slot }}
                                </span>
                            @endif
                        </label>
                        <input type="file" name="foto" id="upload-slot-{{ $slot }}" accept="image/jpeg,image/png"
                               style="display: none;" onchange="this.form.submit()">
                    </form>
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
            <div class="profile-section-title">4. Nama Panggilan</div>

            <label>Nama Panggung / Alias <span class="required-mark">*</span></label>
            <input type="text" name="alias" value="{{ old('alias', $profile->alias) }}" required
                   placeholder="Contoh: Rina, Bang Jack" inputmode="text">
            <p class="field-hint">Nama ini yang dilihat Casting Director — bukan nama asli kamu di KTP.</p>

            <label>Nama Asli (sesuai KTP) <span class="required-mark">*</span></label>
            <input type="text" name="nama_asli" value="{{ old('nama_asli', $profile->nama_asli) }}" required
                   placeholder="Contoh: Rina Wulandari" inputmode="text">
            <p class="field-hint">Dipakai di dokumen kontrak resmi, bukan yang tampil ke publik.</p>

            <label>Username <span class="required-mark">*</span></label>
            <input type="text" name="username" value="{{ old('username', $profile->user->username) }}" required
                   placeholder="Contoh: rina_wulan" maxlength="50">
            <p class="field-hint">Huruf, angka, garis bawah, dan strip saja (tanpa spasi). Bisa dipakai untuk masuk selain email, dan tampil di samping alias kamu.</p>

            <label>Nomor WhatsApp</label>
            <input type="text" name="nomor_wa" value="{{ old('nomor_wa', $profile->user->nomor_wa) }}"
                   placeholder="Contoh: 08123456789" inputmode="tel">
            <p class="field-hint">Buat notifikasi WhatsApp (apply, hasil seleksi, kontrak, pengingat jadwal).</p>
        </div>

        <div class="profile-section">
            <div class="profile-section-title">5. Data Diri</div>

            <label>Usia</label>
            <input type="number" name="usia" value="{{ old('usia', $profile->usia) }}"
                   placeholder="Contoh: 28" inputmode="numeric" min="1" max="120">

            <label>Jenis Kelamin</label>
            <select name="gender">
                <option value="">Pilih salah satu</option>
                <option value="pria" @selected(old('gender', $profile->gender) === 'pria')>Laki-laki</option>
                <option value="wanita" @selected(old('gender', $profile->gender) === 'wanita')>Perempuan</option>
            </select>

            <label>Tinggi Badan (cm)</label>
            <input type="number" name="tinggi_badan" value="{{ old('tinggi_badan', $profile->tinggi_badan) }}"
                   placeholder="Contoh: 165" inputmode="numeric">
        </div>

        <div class="profile-section">
            <div class="profile-section-title">6. Ciri-ciri Fisik</div>
            <p class="field-hint" style="margin-top: -4px;">Membantu Casting Director mencocokkan kamu dengan kebutuhan peran.</p>

            <label>Ukuran Baju</label>
            <input type="text" name="ukuran_baju" value="{{ old('ukuran_baju', $profile->ukuran_baju) }}"
                   placeholder="Contoh: M, L, XL">

            <label>Warna Kulit</label>
            <input type="text" name="warna_kulit" value="{{ old('warna_kulit', $profile->warna_kulit) }}"
                   placeholder="Contoh: Sawo matang, Kuning langsat">
        </div>

        <div class="profile-section">
            <div class="profile-section-title">7. Pengalaman & Kemampuan</div>

            <label>Pengalaman Main / Kerja Sebelumnya</label>
            <textarea name="pengalaman" rows="3" placeholder="Contoh: Pernah jadi figuran di iklan A, sinetron B...">{{ old('pengalaman', $profile->pengalaman) }}</textarea>
            <p class="field-hint">Kosongkan saja kalau belum pernah punya pengalaman — nggak masalah.</p>

            <label>Bahasa yang Kamu Kuasai</label>
            <input type="text" name="bahasa" value="{{ old('bahasa', $profile->bahasa) }}"
                   placeholder="Contoh: Indonesia, Jawa, Inggris">
        </div>

        <div class="profile-section">
            <div class="profile-section-title">8. Tautan Tambahan</div>
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
            <div class="profile-section-title">9. Tarif</div>

            <label>Tarif yang Kamu Harapkan (Rp)</label>
            <input type="number" name="rate_card" value="{{ old('rate_card', $profile->rate_card) }}"
                   placeholder="Contoh: 300000" inputmode="numeric" min="0">
            <p class="field-hint">Ini cuma harapan awal kamu — nanti masih akan dibicarakan lagi sama Admin sebelum deal.</p>
        </div>

        <button type="submit" class="btn btn-brand" style="width: 100%; margin-top: 8px;">Simpan Profil</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
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
    })();
</script>
@endpush
