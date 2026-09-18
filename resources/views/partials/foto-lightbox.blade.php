@if (count($fotos) === 0)
    <p style="color:var(--text-muted); font-size:12px;">Belum ada foto tambahan.</p>
@else
<style>
.lightbox-thumbs { display:grid; grid-template-columns: repeat(auto-fill, minmax(72px, 1fr)); gap: 6px; }
.lightbox-thumbs img { width:100%; aspect-ratio:1/1; object-fit:cover; border-radius:8px; cursor:pointer; }
</style>

@php $dlgId = $lightboxId . '-dialog'; $imgId = $lightboxId . '-img'; @endphp

<div class="lightbox-thumbs">
    @foreach ($fotos as $i => $foto)
        <img src="{{ $foto['url'] }}" alt="{{ $foto['alt'] }}" onclick="_lbOpen('{{ $lightboxId }}', {{ $i }})">
    @endforeach
</div>

<dialog id="{{ $dlgId }}"
    style="border:1px solid var(--border-color); border-radius:12px; padding:0; background:#000; max-width:95vw; position:relative;"
    data-fotos="{{ json_encode(array_column($fotos, 'url')) }}"
    data-current="0">
    <img id="{{ $imgId }}" src="" alt="" style="max-width:90vw; max-height:90vh; object-fit:contain; display:block;">
    <button type="button" onclick="document.getElementById('{{ $dlgId }}').close()"
        style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,.6); color:#fff; border:none; border-radius:50%; width:28px; height:28px; cursor:pointer; font-size:16px; line-height:1;">×</button>
    @if (count($fotos) > 1)
        <button type="button" onclick="_lbPrev('{{ $lightboxId }}')"
            style="position:absolute; top:50%; left:8px; transform:translateY(-50%); background:rgba(0,0,0,.6); color:#fff; border:none; border-radius:50%; width:32px; height:32px; cursor:pointer; font-size:20px; line-height:1;">‹</button>
        <button type="button" onclick="_lbNext('{{ $lightboxId }}')"
            style="position:absolute; top:50%; right:8px; transform:translateY(-50%); background:rgba(0,0,0,.6); color:#fff; border:none; border-radius:50%; width:32px; height:32px; cursor:pointer; font-size:20px; line-height:1;">›</button>
    @endif
</dialog>

<script>
if (!window._lbOpen) {
    window._lbOpen = function(lightboxId, idx) {
        var dlg = document.getElementById(lightboxId + '-dialog');
        var fotos = JSON.parse(dlg.dataset.fotos);
        dlg.dataset.current = idx;
        document.getElementById(lightboxId + '-img').src = fotos[idx];
        dlg.showModal();
    };
    window._lbPrev = function(lightboxId) {
        var dlg = document.getElementById(lightboxId + '-dialog');
        var fotos = JSON.parse(dlg.dataset.fotos);
        var idx = (parseInt(dlg.dataset.current) - 1 + fotos.length) % fotos.length;
        dlg.dataset.current = idx;
        document.getElementById(lightboxId + '-img').src = fotos[idx];
    };
    window._lbNext = function(lightboxId) {
        var dlg = document.getElementById(lightboxId + '-dialog');
        var fotos = JSON.parse(dlg.dataset.fotos);
        var idx = (parseInt(dlg.dataset.current) + 1) % fotos.length;
        dlg.dataset.current = idx;
        document.getElementById(lightboxId + '-img').src = fotos[idx];
    };
}
(function() {
    var dlg = document.getElementById('{{ $dlgId }}');
    if (dlg) {
        dlg.addEventListener('click', function(e) { if (e.target === dlg) dlg.close(); });
    }
})();
</script>
@endif
