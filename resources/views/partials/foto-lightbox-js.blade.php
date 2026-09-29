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
