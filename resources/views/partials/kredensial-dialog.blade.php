@php
    $k = session('kredensial');
    $teksKredensial = "Halo {$k['nama']}, berikut akun SIM Casting JBTB kamu:\nUsername: {$k['username']}\nPassword: {$k['password']}\nLogin: {$k['url']}\nSetelah masuk, kamu akan diminta mengganti password.";
@endphp
<dialog id="kredensial-dialog" style="border: 1px solid var(--border-color); border-radius: 10px; padding: 0; max-width: 440px; width: calc(100% - 32px);">
    <div style="padding: 18px;">
        <div style="font-size: var(--fs-md); font-weight: 600; margin-bottom: 6px;">Kredensial akun {{ $k['nama'] }}</div>
        <p style="font-size: var(--fs-sm); color: var(--text-muted); margin: 0 0 12px;">Hanya tampil sekali. Kalau terlanjur tertutup, pakai "Reset Password" untuk membuat yang baru.</p>
        <textarea id="kredensial-teks" readonly rows="6" style="width: 100%; font-family: monospace; font-size: var(--fs-sm); margin-bottom: 12px;">{{ $teksKredensial }}</textarea>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end;">
            <button type="button" class="btn" onclick="this.closest('dialog').close()">Tutup</button>
            <a class="btn" target="_blank" rel="noopener" href="https://wa.me/{{ $k['nomor_wa'] }}?text={{ rawurlencode($teksKredensial) }}"><i class="ti ti-brand-whatsapp"></i> Kirim via WA</a>
            <button type="button" class="btn btn-brand" id="kredensial-salin">Salin semua</button>
        </div>
    </div>
</dialog>
<script>
(function () {
    var dlg = document.getElementById('kredensial-dialog');
    var teks = document.getElementById('kredensial-teks');
    var btn = document.getElementById('kredensial-salin');
    dlg.showModal();
    btn.addEventListener('click', function () {
        var ok = function () { btn.textContent = 'Tersalin'; };
        var fallback = function () { teks.select(); try { document.execCommand('copy'); ok(); } catch (e) {} };
        if (navigator.clipboard) navigator.clipboard.writeText(teks.value).then(ok, fallback); else fallback();
    });
})();
</script>
