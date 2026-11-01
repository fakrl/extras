{{-- AD-05: satu baris peran, dipakai create & edit (isi dari old() atau data proyek). Param: i, k (array), kunci (peran lama tidak boleh dihapus) --}}
<div class="kelas-row" style="background: var(--bg-card-hover, rgba(255,255,255,0.02)); border: 1px solid var(--border-color); border-radius: 10px; padding: 14px; margin-bottom: 14px;">
    @if (! empty($k['id']))
        <input type="hidden" name="kelas[{{ $i }}][id]" value="{{ $k['id'] }}">
    @endif

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px dashed var(--border-color); padding-bottom: 8px;">
        <span class="kelas-label-badge" style="font-weight: 600; font-size: 13px; color: var(--accent); display: flex; align-items: center; gap: 6px;">
            <i class="ti ti-user-star"></i> Peran #{{ $i + 1 }}
        </span>
        <button type="button" class="btn-icon-danger btn-remove-kelas" aria-label="Hapus peran" style="display:none; padding: 2px 8px; font-size: 12px;" @if (! empty($kunci) && ! empty($k['id'])) title="Kelas ini tidak bisa dihapus, proyek sudah punya pendaftar" @endif>
            <i class="ti ti-trash"></i> Hapus
        </button>
    </div>

    <div class="form-row">
        <div style="flex: 2; min-width: 200px;">
            <label style="font-weight: 600;">Nama Peran <span class="wajib" aria-hidden="true">*</span></label>
            <input type="text" name="kelas[{{ $i }}][nama_kelas]" value="{{ $k['nama_kelas'] ?? '' }}" placeholder="misal: Ibu-ibu 29-50th" required>
        </div>
        <div style="flex: 1.5; min-width: 170px;">
            <label style="font-weight: 600;">Budget / Orang (Rp)</label>
            <input type="number" name="kelas[{{ $i }}][budget_client]" value="{{ $k['budget_client'] ?? '' }}" min="0" placeholder="Rp 0">
        </div>
        <div style="flex: 1; min-width: 120px;">
            <label style="font-weight: 600;">Kuota Peran <span class="wajib" aria-hidden="true">*</span></label>
            <input type="number" name="kelas[{{ $i }}][kuota_kelas]" class="input-kuota-kelas" value="{{ $k['kuota_kelas'] ?? '' }}" min="1" required placeholder="Jml orang">
        </div>
    </div>

    <div class="form-row" style="margin-top: 10px;">
        <div>
            <label style="font-size: 12px;">Jam Callsheet Client <span style="color: var(--text-muted); font-weight: 400;">(rundown PH)</span></label>
            <input type="time" name="kelas[{{ $i }}][jam_callsheet]" value="{{ $k['jam_callsheet'] ?? '' }}">
        </div>
        <div>
            <label style="font-size: 12px;">Jam Panggil Extras <span style="color: var(--text-muted); font-weight: 400;">(standby talent)</span></label>
            <input type="time" name="kelas[{{ $i }}][jam_callingan]" value="{{ $k['jam_callingan'] ?? '' }}">
        </div>
        <div>
            <label style="font-size: 12px;">Tipe Kontinuitas</label>
            <select name="kelas[{{ $i }}][tipe_continuity]">
                <option value="free" @selected(($k['tipe_continuity'] ?? 'free') === 'free')>Sehari (bebas)</option>
                <option value="continuity" @selected(($k['tipe_continuity'] ?? '') === 'continuity')>Berlanjut (beberapa hari)</option>
            </select>
        </div>
    </div>

    <div style="margin-top: 10px;">
        <label style="font-size: 12px;">Keterangan Scene <span style="color: var(--text-muted); font-weight: 400;">(lokasi scene, wardrobe, dll)</span></label>
        <input type="text" name="kelas[{{ $i }}][keterangan_scene]" value="{{ $k['keterangan_scene'] ?? '' }}" placeholder="misal: Scene 12-14 warung kopi, baju casual...">
    </div>

    <div style="margin-top: 10px;">
        <label style="font-size: 12px;">Kriteria / Karakter Khusus</label>
        <textarea name="kelas[{{ $i }}][kriteria]" rows="2" placeholder="Contoh: wanita 25-35 th, ekspresi natural, look sederhana..." maxlength="500">{{ $k['kriteria'] ?? '' }}</textarea>
    </div>

    <div style="margin-top: 10px;">
        <label style="font-size: 12px;">Tag yang dicari <span style="color: var(--text-muted); font-weight: 400;">(dipakai untuk filter &amp; kecocokan profil extras)</span></label>
        @include('partials.tag-input', ['name' => 'kelas['.$i.'][tag_nama]', 'selected' => $k['tag_nama'] ?? []])
    </div>
</div>
