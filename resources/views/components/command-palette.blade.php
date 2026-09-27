<div id="cmd-palette-backdrop" style="display:none;position:fixed;inset:0;z-index:1000;backdrop-filter:blur(4px);background:rgba(0,0,0,0.4);align-items:flex-start;justify-content:center;padding-top:15vh;">
    <div style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:14px;width:100%;max-width:560px;margin:0 16px;box-shadow:0 8px 32px rgba(0,0,0,0.28);overflow:hidden;">
        <div style="display:flex;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border-color);">
            <i class="ti ti-search" style="font-size:18px;color:var(--text-muted);flex-shrink:0;"></i>
            <input type="text" id="cmd-search-input" placeholder="Cari proyek, akun..." autocomplete="off"
                style="flex:1;border:none;background:transparent;font-size:15px;color:var(--text-primary);outline:none;padding:0;margin:0;min-height:unset;width:auto;">
            <kbd style="font-size:11px;color:var(--text-muted);border:1px solid var(--border-color);border-radius:4px;padding:2px 5px;">Esc</kbd>
        </div>
        <div id="cmd-results" style="max-height:360px;overflow-y:auto;">
            <div id="cmd-empty" style="padding:20px 16px;text-align:center;color:var(--text-muted);font-size:13.5px;">Ketik minimal 2 karakter untuk mencari...</div>
        </div>
        <div style="padding:8px 14px;border-top:1px solid var(--border-color);display:flex;gap:12px;font-size:11px;color:var(--text-muted);">
            <span><kbd style="border:1px solid var(--border-color);border-radius:3px;padding:1px 4px;">↑↓</kbd> navigasi</span>
            <span><kbd style="border:1px solid var(--border-color);border-radius:3px;padding:1px 4px;">Enter</kbd> buka</span>
            <span><kbd style="border:1px solid var(--border-color);border-radius:3px;padding:1px 4px;">Esc</kbd> tutup</span>
            <span><kbd style="border:1px solid var(--border-color);border-radius:3px;padding:1px 4px;">Ctrl+K</kbd> toggle</span>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var backdrop = document.getElementById('cmd-palette-backdrop');
    var input = document.getElementById('cmd-search-input');
    var resultsEl = document.getElementById('cmd-results');
    var emptyEl = document.getElementById('cmd-empty');
    if (!backdrop) return;

    var debounceTimer = null;
    var activeIdx = -1;

    function open() {
        backdrop.style.display = 'flex';
        setTimeout(function() { input.focus(); input.select(); }, 50);
    }

    function close() {
        backdrop.style.display = 'none';
        input.value = '';
        resultsEl.innerHTML = '';
        resultsEl.appendChild(emptyEl);
        activeIdx = -1;
        emptyEl.textContent = 'Ketik minimal 2 karakter untuk mencari...';
        emptyEl.style.display = 'block';
    }

    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            backdrop.style.display === 'none' ? open() : close();
            return;
        }
        if (backdrop.style.display === 'none') return;
        if (e.key === 'Escape') { close(); return; }
        var items = resultsEl.querySelectorAll('[data-url]');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIdx = Math.min(activeIdx + 1, items.length - 1);
            updateActive(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIdx = Math.max(activeIdx - 1, 0);
            updateActive(items);
        } else if (e.key === 'Enter' && activeIdx >= 0 && items[activeIdx]) {
            window.location.href = items[activeIdx].dataset.url;
        }
    });

    function updateActive(items) {
        items.forEach(function(el, i) {
            el.style.background = i === activeIdx ? 'var(--bg-card-hover)' : '';
        });
        if (items[activeIdx]) items[activeIdx].scrollIntoView({ block: 'nearest' });
    }

    backdrop.addEventListener('click', function(e) {
        if (e.target === backdrop) close();
    });

    input.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        var q = input.value.trim();
        if (q.length < 2) {
            resultsEl.innerHTML = '';
            resultsEl.appendChild(emptyEl);
            emptyEl.style.display = 'block';
            return;
        }
        debounceTimer = setTimeout(function() { doSearch(q); }, 300);
    });

    function doSearch(q) {
        fetch('/super-admin/search?q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) { renderResults(data); })
        .catch(function() {
            emptyEl.textContent = 'Gagal memuat hasil.';
            emptyEl.style.display = 'block';
        });
    }

    function renderResults(data) {
        activeIdx = -1;
        resultsEl.innerHTML = '';
        var total = (data.proyek ? data.proyek.length : 0) + (data.akun ? data.akun.length : 0);
        if (total === 0) {
            emptyEl.textContent = 'Tidak ada hasil ditemukan.';
            emptyEl.style.display = 'block';
            resultsEl.appendChild(emptyEl);
            return;
        }
        emptyEl.style.display = 'none';

        if (data.proyek && data.proyek.length > 0) {
            var hdr = document.createElement('div');
            hdr.style.cssText = 'padding:8px 16px 4px;font-size:10px;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);font-weight:600;';
            hdr.textContent = 'Proyek Casting';
            resultsEl.appendChild(hdr);
            data.proyek.forEach(function(item) { resultsEl.appendChild(makeItem(item)); });
        }

        if (data.akun && data.akun.length > 0) {
            var hdr2 = document.createElement('div');
            hdr2.style.cssText = 'padding:8px 16px 4px;font-size:10px;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);font-weight:600;';
            hdr2.textContent = 'Akun';
            resultsEl.appendChild(hdr2);
            data.akun.forEach(function(item) { resultsEl.appendChild(makeItem(item)); });
        }
    }

    function makeItem(item) {
        var el = document.createElement('a');
        el.href = item.url;
        el.dataset.url = item.url;
        el.style.cssText = 'display:block;padding:10px 16px;cursor:pointer;border-radius:0;text-decoration:none;color:var(--text-primary);';
        el.innerHTML = '<div style="font-size:13.5px;font-weight:500;">' + escHtml(item.label) + '</div>' +
            '<div style="font-size:12px;color:var(--text-muted);margin-top:1px;">' + escHtml(item.sub) + '</div>';
        el.addEventListener('mouseover', function() {
            var items = resultsEl.querySelectorAll('[data-url]');
            items.forEach(function(x, i) {
                if (x === el) activeIdx = i;
                x.style.background = x === el ? 'var(--bg-card-hover)' : '';
            });
        });
        return el;
    }

    function escHtml(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
@endpush
