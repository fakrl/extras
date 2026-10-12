{{-- AD-05: satu baris peran, dipakai create & edit (isi dari old() atau data proyek). Param: i, k (array), kunci (peran lama tidak boleh dihapus) --}}
<div class="kelas-row" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 12px; margin-bottom: 10px;">
    @if (! empty($k['id']))
        <input type="hidden" name="kelas[{{ $i }}][id]" value="{{ $k['id'] }}">
    @endif
    <div class="form-row" style="align-items: flex-end;">
        <div>
            <label>Nama Karakter <span class="wajib" aria-hidden="true">*</span></label>
            <input type="text" name="kelas[{{ $i }}][nama_kelas]" value="{{ $k['nama_kelas'] ?? '' }}" placeholder="misal: Ibu-ibu 29-50th" required>
        </div>
        <div>
            <label>Budget Client (Rp) <span class="wajib" aria-hidden="true">*</span></label>
            <input type="number" name="kelas[{{ $i }}][budget_client]" value="{{ $k['budget_client'] ?? '' }}" min="0" required>
        </div>
        <div>
            <label>Kuota Kelas <span class="wajib" aria-hidden="true">*</span></label>
            <input type="number" name="kelas[{{ $i }}][kuota_kelas]" value="{{ $k['kuota_kelas'] ?? '' }}" min="1" required>
        </div>
        <div style="flex: 0;">
            <button type="button" class="btn-icon-danger btn-remove-kelas" style="display:none" @if (! empty($kunci) && ! empty($k['id'])) title="Kelas ini tidak bisa dihapus, proyek sudah punya pendaftar" @endif>&times;</button>
        </div>
    </div>
    <div class="form-row" style="margin-top: 8px;">
        <div>
            <label>Jam Callsheet Client <span style="color: var(--text-muted); font-weight: 400;">(waktu rundown PH)</span></label>
            <input type="time" name="kelas[{{ $i }}][jam_callsheet]" value="{{ $k['jam_callsheet'] ?? '' }}">
        </div>
        <div>
            <label>Jam Callingan Extras <span style="color: var(--text-muted); font-weight: 400;">(otomatis -1 jam jika kosong)</span></label>
            <input type="time" name="kelas[{{ $i }}][jam_callingan]" value="{{ $k['jam_callingan'] ?? '' }}">
        </div>
        <div>
            <label>Tipe Kontinuitas</label>
            <select name="kelas[{{ $i }}][tipe_continuity]">
                <option value="free" @selected(($k['tipe_continuity'] ?? 'free') === 'free')>Bebas (Single Day)</option>
                <option value="continuity" @selected(($k['tipe_continuity'] ?? '') === 'continuity')>Continuity (Multi-day)</option>
            </select>
        </div>
    </div>
    <div style="margin-top: 8px;">
        <label>Keterangan Scene <span style="color: var(--text-muted); font-weight: 400;">(misal: Scene 12-14 warung kopi, baju casual)</span></label>
        <input type="text" name="kelas[{{ $i }}][keterangan_scene]" value="{{ $k['keterangan_scene'] ?? '' }}" placeholder="Scene 12-14 di warung kopi...">
    </div>
    <div style="margin-top: 8px;">
        <label>Kriteria yang dibutuhkan</label>
        <textarea name="kelas[{{ $i }}][kriteria]" rows="2" placeholder="Contoh: wanita 25-35 th, ekspresi natural, look sederhana" maxlength="500">{{ $k['kriteria'] ?? '' }}</textarea>
    </div>
    <div style="margin-top: 8px;">
        <label>Tag yang dicari <span style="color: var(--text-muted); font-weight: 400;">(dipakai untuk % cocok)</span></label>
        @include('partials.tag-input', ['name' => 'kelas['.$i.'][tag_nama]', 'selected' => $k['tag_nama'] ?? []])
    </div>
</div>
