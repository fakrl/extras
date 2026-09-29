{{-- BI.1: popup profil Extras. Pemicu: a[data-profil-modal] (href = halaman penuh), opsional data-aksi-label + data-aksi-url | data-aksi-dialog (id) | data-aksi-fungsi (+ data-aksi-arg). --}}
<style>
.pm { width: min(760px, calc(100% - 32px)); max-width: none; max-height: calc(100dvh - 48px); padding: 0; border: 1px solid var(--border-color); border-radius: var(--radius-lg); background: var(--bg-card); color: var(--text-primary); overflow: hidden; }
.pm[open] { display: flex; flex-direction: column; }
.pm::backdrop { background: rgba(0, 0, 0, .55); }
.pm-head { display: flex; align-items: center; gap: 8px; padding: 6px 6px 6px 16px; border-bottom: 1px solid var(--border-color); background: var(--bg-card); flex-shrink: 0; }
.pm-nama { flex: 1; min-width: 0; font-size: var(--fs-md); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pm-penuh { min-height: 44px; padding: 0 12px; font-size: var(--fs-sm); white-space: nowrap; text-decoration: none; }
.pm-buka-hp { display: none; }
.pm-x { width: 44px; height: 44px; flex-shrink: 0; border: 0; border-radius: var(--radius-md); background: transparent; color: var(--text-primary); font-size: 20px; cursor: pointer; }
.pm-x:hover { background: var(--bg-card-hover); }
.pm-body { flex: 1; overflow-y: auto; overscroll-behavior: contain; }
.pm-body .pe-app { border: 0; border-radius: 0; }
.pm-foot { display: flex; justify-content: flex-end; gap: 8px; padding: 10px 16px; border-top: 1px solid var(--border-color); flex-shrink: 0; }
.pm-sk { padding: 20px; display: grid; gap: 14px; }
.pm-sk > div { background: var(--bg-card-hover); border-radius: var(--radius-md); height: 18px; }
.pm-sk > .is-foto { height: 240px; width: min(100%, 240px); }
.pm-sk > .is-judul { height: 44px; width: 60%; }
.pm-sk > .is-pendek { width: 40%; }
@media (prefers-reduced-motion: no-preference) {
    .pm-sk > div { animation: pm-kedip 1.2s ease-in-out infinite; }
    @keyframes pm-kedip { 50% { opacity: .45; } }
}
@media (max-width: 560px) {
    .pm { width: 100%; height: 100dvh; max-height: 100dvh; margin: 0; border: 0; border-radius: 0; }
    .pm-buka { display: none; }
    .pm-buka-hp { display: inline; }
    @media (prefers-reduced-motion: no-preference) {
        .pm[open] { animation: pm-naik .22s ease-out; }
        @keyframes pm-naik { from { transform: translateY(100%); } }
    }
}
</style>
<dialog id="profil-modal" class="pm" aria-labelledby="pm-nama">
    <div class="pm-head">
        <strong id="pm-nama" class="pm-nama">Memuat…</strong>
        <span id="pm-status" class="badge" hidden></span>
        <a id="pm-penuh" class="btn pm-penuh" href="#"><span class="pm-buka">Buka halaman penuh</span><span class="pm-buka-hp">Halaman penuh</span> ↗</a>
        <button type="button" class="pm-x" autofocus aria-label="Tutup profil" onclick="this.closest('dialog').close()"><i class="ti ti-x" aria-hidden="true"></i></button>
    </div>
    <div id="pm-isi" class="pm-body" aria-live="polite"></div>
    <div id="pm-foot" class="pm-foot" hidden></div>
    <template id="pm-kerangka"><div class="pm-sk" role="status" aria-label="Memuat profil"><div class="is-foto"></div><div class="is-judul"></div><div></div><div class="is-pendek"></div><div></div></div></template>
</dialog>
<script>
@include('partials.foto-lightbox-js')
(function () {
    var dlg = document.getElementById('profil-modal');
    var isi = document.getElementById('pm-isi'), foot = document.getElementById('pm-foot');
    var nama = document.getElementById('pm-nama'), status = document.getElementById('pm-status'), penuh = document.getElementById('pm-penuh');
    var kerangka = document.getElementById('pm-kerangka');
    var pemicu = null, token = 0, mundur = false;

    function aksi(a) {
        var d = a.dataset, el = null;
        foot.replaceChildren();
        if (d.aksiLabel && d.aksiUrl) {
            el = document.createElement('a');
            el.setAttribute('href', d.aksiUrl);
        } else if (d.aksiLabel && (d.aksiDialog ? document.getElementById(d.aksiDialog) : typeof window[d.aksiFungsi] === 'function')) {
            el = document.createElement('button');
            el.type = 'button';
            el.addEventListener('click', function () {
                dlg.close();
                var tujuan = d.aksiDialog && document.getElementById(d.aksiDialog);
                if (tujuan) { if (!tujuan.open) tujuan.showModal(); } else window[d.aksiFungsi](d.aksiArg);
            });
        }
        if (el) { el.className = 'btn btn-brand'; el.textContent = d.aksiLabel; foot.appendChild(el); }
        foot.hidden = !el;
    }

    function buka(a) {
        var n = ++token, url = new URL(a.href, location.href);
        pemicu = a;
        nama.textContent = 'Memuat…';
        status.hidden = true;
        penuh.setAttribute('href', a.href);
        isi.replaceChildren(kerangka.content.cloneNode(true));
        aksi(a);
        var state = { profil: url.pathname.split('/').slice(-2)[0] };
        if (dlg.open) history.replaceState(state, '', '#profil-' + state.profil);
        else { dlg.showModal(); history.pushState(state, '', '#profil-' + state.profil); }
        url.searchParams.set('partial', '1');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { if (!r.ok || r.redirected) throw r; return r.text(); })
            .then(function (html) {
                if (n !== token) return;
                // HTML partial dirender server sendiri (same-origin); script di dalamnya memang tidak dijalankan
                isi.innerHTML = html;
                var pe = isi.querySelector('.pe');
                if (!pe) throw 0;
                nama.textContent = pe.dataset.username ? '@' + pe.dataset.username : 'Profil Extras';
                status.textContent = pe.dataset.status;
                status.className = 'badge ' + pe.dataset.statusClass;
                status.hidden = false;
                isi.scrollTop = 0;
            })
            .catch(function () { if (n === token) location.href = a.href; });
    }

    document.addEventListener('click', function (e) {
        var a = e.target.closest('a[data-profil-modal]');
        if (!a || e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        buka(a);
    });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
    dlg.addEventListener('close', function () {
        if (dlg.open) return;
        token++;
        isi.replaceChildren();
        if (history.state && history.state.profil) { mundur = true; history.back(); }
        if (pemicu && pemicu.isConnected) pemicu.focus();
    });
    // didaftarkan sebelum live search: Back yang cuma nutup popup jangan sampai memuat ulang daftar
    window.addEventListener('popstate', function (e) {
        if (mundur) { mundur = false; e.stopImmediatePropagation(); return; }
        if (dlg.open) { e.stopImmediatePropagation(); dlg.close(); return; }
        if (e.state && e.state.profil) e.stopImmediatePropagation();
    });
}());
</script>
