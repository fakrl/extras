<dialog id="client-baru-dialog" class="dash-dialog" style="border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); padding: 0; max-width: 480px; width: calc(100% - 32px);">
    <div style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
            <div style="font-size: var(--fs-md); font-weight: 600;">Tambah Akun Client</div>
            <button type="button" class="btn-ikon" onclick="this.closest('dialog').close()" aria-label="Tutup" style="border: none; background: transparent; cursor: pointer; font-size: 18px; color: var(--text-muted);"><i class="ti ti-x"></i></button>
        </div>
        <p style="font-size: var(--fs-sm); color: var(--text-muted); margin: 0 0 16px;">Password sementara dibuat otomatis dan tampil sekali setelah disimpan.</p>

        <form id="client-baru-form" method="POST" action="{{ route('super-admin.clients.store') }}">
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

            <div class="client-baru-error" style="color: var(--danger); font-size: var(--fs-xs); margin-top: 6px; display: none;"></div>

            <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 18px;">
                <button type="button" class="btn" onclick="this.closest('dialog').close()">Batal</button>
                <button type="submit" class="btn btn-brand">Simpan</button>
            </div>
        </form>
    </div>
</dialog>

@if (old('_form') === 'client-baru' && $errors->any())
    <script>document.getElementById('client-baru-dialog').showModal();</script>
@endif

@once
@push('scripts')
<script>
    (function () {
        var form = document.getElementById('client-baru-form');
        if (!form) return;
        var dialog = document.getElementById('client-baru-dialog');
        var errEl = form.querySelector('.client-baru-error');
        var submitBtn = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', function (e) {
            var selectClient = document.getElementById('client_id');
            if (!selectClient) return; // fallback to standard submit if not on project form

            e.preventDefault();
            errEl.style.display = 'none';
            errEl.textContent = '';
            submitBtn.disabled = true;

            var body = {
                name: form.querySelector('input[name="name"]').value,
                nama_perusahaan: form.querySelector('input[name="nama_perusahaan"]').value || null,
                username: form.querySelector('input[name="username"]').value,
                email: form.querySelector('input[name="email"]').value || null,
                nomor_wa: form.querySelector('input[name="nomor_wa"]').value || null,
            };

            fetch('{{ route('super-admin.clients.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(body)
            })
            .then(function (r) {
                return r.json().then(function (data) { return { ok: r.ok, data: data }; });
            })
            .then(function (res) {
                submitBtn.disabled = false;
                if (!res.ok) {
                    errEl.textContent = res.data.errors ? Object.values(res.data.errors).flat().join(' ') : (res.data.message || 'Gagal membuat akun Client.');
                    errEl.style.display = 'block';
                    return;
                }
                var opt = document.createElement('option');
                opt.value = res.data.id;
                opt.textContent = res.data.name;
                selectClient.appendChild(opt);
                selectClient.value = res.data.id;

                dialog.close();
                form.reset();

                if (window.tampilkanKredensial && res.data.kredensial) {
                    window.tampilkanKredensial(res.data.kredensial);
                }
            })
            .catch(function () {
                submitBtn.disabled = false;
                errEl.textContent = 'Gagal menghubungi server.';
                errEl.style.display = 'block';
            });
        });
    })();
</script>
@endpush
@endonce
