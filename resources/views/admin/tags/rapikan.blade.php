{{-- BR.5: dialog "Rapikan tag" (pengganti halaman Kelola Tag). Param: tags (perluDirapikan + extras_profiles_count). Pemicu: [data-rapikan-tag]. --}}
<dialog id="rapikan-tag" class="rt" aria-labelledby="rt-judul" onclick="if (event.target === this) this.close()">
    <div class="rt-head">
        <strong id="rt-judul">Rapikan tag</strong>
        <button type="button" class="rt-x" aria-label="Tutup" onclick="this.closest('dialog').close()"><i class="ti ti-x"></i></button>
    </div>
    <div class="rt-body">
        <p class="rt-note">Tag tanpa grup (<strong>Lainnya</strong>) dan tag yang dipakai ≤1 Extras. Tag Lainnya belum tampil di profil publik.</p>
        <div class="rt-pesan" role="status" aria-live="polite"></div>
        <ul class="rt-list">
            @foreach ($tags as $t)
                <li>
                    <div class="rt-nama">#{{ $t->nama }} <span class="badge {{ $t->grup ? 'badge-netral' : 'badge-pending' }}">{{ $t->grup ?? 'Lainnya' }}</span> <span class="rt-meta">{{ $t->extras_profiles_count }} Extras</span></div>
                    <div class="rt-aksi">
                        <form method="POST" action="{{ route('admin.tags.update', $t) }}" data-rt>
                            @csrf @method('PATCH')
                            <select name="grup" aria-label="Pindah grup #{{ $t->nama }}" onchange="this.form.requestSubmit()">
                                <option value="">Lainnya</option>
                                @foreach (array_keys(\App\Models\ExtrasCategory::GRUP) as $g)
                                    <option value="{{ $g }}" @selected($t->grup === $g)>{{ $g }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('admin.tags.gabung', $t) }}" data-rt data-rt-hilang data-rt-konfirmasi="Gabung #{{ $t->nama }} ke tag tujuan? Semua Extras & peran pindah, lalu #{{ $t->nama }} dihapus.">
                            @csrf
                            <input type="text" name="tujuan" list="rt-saran" data-rt-cari placeholder="Gabung ke…" aria-label="Gabung #{{ $t->nama }} ke" required autocomplete="off">
                            <button type="submit" class="btn btn-sm">Gabung</button>
                        </form>
                        <form method="POST" action="{{ route('admin.tags.destroy', $t) }}" data-rt data-rt-hilang data-rt-konfirmasi="Hapus #{{ $t->nama }}? Dilepas dari {{ $t->extras_profiles_count }} Extras.">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger-outline" aria-label="Hapus #{{ $t->nama }}"><i class="ti ti-trash"></i> Hapus</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
        <datalist id="rt-saran"></datalist>
    </div>
</dialog>
@once
@push('styles')
<style>
    .fpanel-rapikan { background: none; border: 0; padding: 0; margin: 0; min-height: 0; font-size: var(--fs-xs); color: var(--accent-strong); text-decoration: underline; cursor: pointer; align-self: flex-start; }
    .rt { max-width: none; width: min(640px, calc(100% - 32px)); max-height: calc(100dvh - 48px); padding: 0; border: 1px solid var(--border-color); border-radius: var(--radius-lg); background: var(--bg-card); color: var(--text-primary); }
    .rt[open] { display: flex; flex-direction: column; }
    .rt::backdrop { background: rgba(0, 0, 0, .55); }
    .rt-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 6px 6px 6px 16px; border-bottom: 1px solid var(--border-color); }
    .rt-x { width: 44px; height: 44px; border: 0; background: none; color: var(--text-primary); font-size: 20px; cursor: pointer; border-radius: var(--radius-md); }
    .rt-body { overflow-y: auto; padding: 12px 16px 16px; }
    .rt-note { font-size: var(--fs-xs); color: var(--text-muted); margin: 0 0 8px; }
    .rt-pesan:empty { display: none; }
    .rt-pesan { font-size: var(--fs-sm); padding: 8px 10px; border-radius: var(--radius-md); background: var(--bg-nav-active); margin-bottom: 8px; }
    .rt-list { list-style: none; margin: 0; padding: 0; }
    .rt-list li { padding: 10px 0; border-top: 1px solid var(--border-color); display: grid; gap: 8px; }
    .rt-list li:first-child { border-top: 0; }
    .rt-nama { font-weight: 600; display: flex; gap: 6px; align-items: center; flex-wrap: wrap; overflow-wrap: anywhere; }
    .rt-meta { font-size: var(--fs-xs); color: var(--text-muted); font-weight: 400; }
    .rt-aksi { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr) auto; gap: 8px; align-items: center; }
    .rt-aksi form { display: flex; gap: 6px; margin: 0; min-width: 0; }
    .rt-aksi select, .rt-aksi input { flex: 1; min-width: 0; min-height: 40px; margin: 0; padding: 6px 10px; font-size: var(--fs-sm); }
    @media (max-width: 560px) {
        .rt { width: 100%; max-height: 100dvh; height: 100dvh; margin: 0; border: 0; border-radius: 0; }
        .rt-aksi { grid-template-columns: minmax(0, 1fr) auto; }
        .rt-aksi form:nth-child(2) { grid-column: 1 / -1; grid-row: 2; }
    }
</style>
@endpush
@push('scripts')
<script>
(function () {
    var timer;
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-rapikan-tag]');
        var dlg = document.getElementById('rapikan-tag');
        if (b && dlg) dlg.showModal();
    });
    document.addEventListener('submit', function (e) {
        var form = e.target.closest('form[data-rt]');
        if (!form) return;
        e.preventDefault();
        if (form.dataset.rtKonfirmasi && !confirm(form.dataset.rtKonfirmasi)) return;
        var dlg = form.closest('dialog'), pesan = dlg.querySelector('.rt-pesan');
        fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (h) {
                pesan.textContent = h.j.pesan || h.j.message || 'Gagal.';
                if (!h.ok) return;
                dlg.dataset.ubah = '1';
                if (form.hasAttribute('data-rt-hilang')) form.closest('li').remove();
            })
            .catch(function () { form.submit(); });
    });
    document.addEventListener('input', function (e) {
        if (!e.target.matches('[data-rt-cari]')) return;
        clearTimeout(timer);
        var q = e.target.value;
        timer = setTimeout(function () {
            fetch('{{ route('tag.cari', [], false) }}?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (daftar) {
                    var dl = document.getElementById('rt-saran');
                    dl.replaceChildren.apply(dl, daftar.map(function (n) { var o = document.createElement('option'); o.value = n; return o; }));
                })
                .catch(function () {});
        }, 250);
    });
    document.addEventListener('close', function (e) {
        if (e.target.id === 'rapikan-tag' && e.target.dataset.ubah) location.reload();
    }, true);
}());
</script>
@endpush
@endonce
