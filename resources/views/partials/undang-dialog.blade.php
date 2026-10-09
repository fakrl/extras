{{-- CE: cangkang dialog "Undang ke proyek"; isinya dimuat saat tombol [data-undang] diklik. --}}
<dialog id="undang-dialog" class="xmodal" aria-label="Undang ke proyek" onclick="if (event.target === this) this.close()">
    <div id="undang-isi" class="xmodal-body" aria-live="polite"></div>
</dialog>
<script>
document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-undang]');
    if (!b) return;
    var dlg = document.getElementById('undang-dialog'), isi = document.getElementById('undang-isi');
    isi.textContent = 'Memuat…';
    if (!dlg.open) dlg.showModal();
    fetch(b.dataset.undang, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { if (!r.ok) throw r; return r.text(); })
        .then(function (html) { isi.innerHTML = html; })
        .catch(function () { isi.textContent = 'Gagal memuat daftar proyek. Tutup lalu coba lagi.'; });
});
</script>
