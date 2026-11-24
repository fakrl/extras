<dialog id="extras-baru-dialog" class="dash-dialog" style="border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); padding: 0; max-width: 480px; width: calc(100% - 32px);">
    <div style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
            <div style="font-size: var(--fs-md); font-weight: 600;">Tambah Akun Extras</div>
            <button type="button" class="btn-ikon" onclick="this.closest('dialog').close()" aria-label="Tutup" style="border: none; background: transparent; cursor: pointer; font-size: 18px; color: var(--text-muted);"><i class="ti ti-x"></i></button>
        </div>
        <p style="font-size: var(--fs-sm); color: var(--text-muted); margin: 0 0 16px;">Password sementara dibuat otomatis dan tampil sekali setelah disimpan. NIK/KTP & rekening diminta nanti saat Extras lolos dan mau TTD kontrak.</p>

        <form method="POST" action="{{ route('admin.akun.extras.store') }}">
            @csrf
            <input type="hidden" name="_form" value="extras-baru">

            <label>Nama <span class="wajib" aria-hidden="true">*</span></label>
            <input type="text" name="name" value="{{ old('_form') === 'extras-baru' ? old('name') : '' }}" required maxlength="255">

            <label>Username <span class="wajib" aria-hidden="true">*</span></label>
            <input type="text" name="username" value="{{ old('_form') === 'extras-baru' ? old('username') : '' }}" required maxlength="50" pattern="[A-Za-z0-9_\-]+" title="Huruf, angka, - atau _ tanpa spasi">

            <label>Nomor WA <span class="wajib" aria-hidden="true">*</span></label>
            <input type="tel" name="nomor_wa" value="{{ old('_form') === 'extras-baru' ? old('nomor_wa') : '' }}" required maxlength="20" placeholder="08xxxxxxxxxx">

            <label>Email</label>
            <input type="email" name="email" value="{{ old('_form') === 'extras-baru' ? old('email') : '' }}">

            <div class="extras-baru-error" style="color: var(--danger); font-size: var(--fs-xs); margin-top: 6px; display: none;"></div>

            <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 18px;">
                <button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button>
                <button type="submit" class="btn btn-brand">Simpan</button>
            </div>
        </form>
    </div>
</dialog>

@if (old('_form') === 'extras-baru' && $errors->any())
    <script>document.getElementById('extras-baru-dialog').showModal();</script>
@endif
