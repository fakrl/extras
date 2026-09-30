<dialog id="client-baru-dialog" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 480px; width: calc(100% - 32px);">
    <div style="padding: 18px;">
        <div style="font-size: var(--fs-md); font-weight: 600; margin-bottom: 4px;">Tambah Akun Client</div>
        <p style="font-size: var(--fs-sm); color: var(--text-muted); margin: 0 0 14px;">Password sementara dibuat otomatis dan tampil sekali setelah disimpan.</p>
        <form method="POST" action="{{ route('super-admin.clients.store') }}">
            @csrf
            <input type="hidden" name="_form" value="client-baru">
            <label>Nama <span class="wajib" aria-hidden="true">*</span></label>
            <input type="text" name="name" value="{{ old('_form') === 'client-baru' ? old('name') : '' }}" required maxlength="255">

            <label>Nama perusahaan / PH</label>
            <input type="text" name="nama_perusahaan" value="{{ old('_form') === 'client-baru' ? old('nama_perusahaan') : '' }}" maxlength="255">

            <label>Username <span class="wajib" aria-hidden="true">*</span></label>
            <input type="text" name="username" value="{{ old('_form') === 'client-baru' ? old('username') : '' }}" required maxlength="50" pattern="[A-Za-z0-9_\-]+" title="Huruf, angka, - atau _ tanpa spasi">

            <label>Email</label>
            <input type="email" name="email" value="{{ old('_form') === 'client-baru' ? old('email') : '' }}">

            <label>Nomor WA</label>
            <input type="tel" name="nomor_wa" value="{{ old('_form') === 'client-baru' ? old('nomor_wa') : '' }}" maxlength="20" placeholder="08xxxxxxxxxx">

            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button>
                <button type="submit" class="btn btn-brand">Simpan</button>
            </div>
        </form>
    </div>
</dialog>
@if (old('_form') === 'client-baru' && $errors->any())
    <script>document.getElementById('client-baru-dialog').showModal();</script>
@endif
