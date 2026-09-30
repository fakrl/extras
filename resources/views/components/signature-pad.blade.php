{{--
    Komponen canvas signature: tanda tangan digambar langsung di browser
    (RF-26), BUKAN upload scan, BUKAN e-signature tersertifikasi (PSrE).
    Dipakai di halaman kontrak (Admin & Extras) dan invoice (Admin & Client).

    Usage: <x-signature-pad name="ttd_extras" />
    Hasil signature disimpan sebagai base64 PNG di hidden input bernama
    sesuai $name, dikirim bersama form saat submit.
--}}
@props(['name'])

<div class="signature-pad-wrap" style="text-align: center;">
    <p style="font-size: var(--fs-sm); color: var(--text-secondary); margin: 0 0 6px;">Tanda tangan di dalam kotak</p>
    <canvas id="canvas-{{ $name }}"
            style="border:1px solid var(--border-color); border-radius:8px; background:#fff; touch-action:none; width:100%; height:180px; display:block;"></canvas>
    <input type="hidden" name="{{ $name }}" id="input-{{ $name }}">
    <div style="margin-top: 8px;">
        <button type="button" class="btn btn-sm" onclick="clearSignature('{{ $name }}')">Ulangi (Hapus Tanda Tangan)</button>
    </div>
</div>

@once
    @push('scripts')
    <script>
        var signaturePads = {};

        function sizeSignatureCanvas(name) {
            var canvas = document.getElementById('canvas-' + name);
            var dpr = window.devicePixelRatio || 1;
            var rect = canvas.getBoundingClientRect();
            // ponytail: resize cuma kalau lebar beneran berubah (address bar HP juga memicu resize); set width selalu mengosongkan kanvas
            if (signaturePads[name] && canvas.width === Math.round(rect.width * dpr)) return;
            if (signaturePads[name]) document.getElementById('input-' + name).value = '';
            canvas.width = Math.round(rect.width * dpr);
            canvas.height = Math.round(rect.height * dpr);
            var ctx = canvas.getContext('2d');
            ctx.scale(dpr, dpr);
            ctx.strokeStyle = '#0B1A12';
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            signaturePads[name] = { canvas: canvas, ctx: ctx };
        }

        function initSignaturePad(name) {
            sizeSignatureCanvas(name);
            var canvas = signaturePads[name].canvas;
            var drawing = false;
            var lastX = 0, lastY = 0;

            function pos(e) {
                var rect = canvas.getBoundingClientRect();
                var clientX = e.touches ? e.touches[0].clientX : e.clientX;
                var clientY = e.touches ? e.touches[0].clientY : e.clientY;
                return { x: clientX - rect.left, y: clientY - rect.top };
            }

            function dot(p) {
                var ctx = signaturePads[name].ctx;
                ctx.beginPath();
                ctx.arc(p.x, p.y, signaturePads[name].ctx.lineWidth / 2, 0, Math.PI * 2);
                ctx.fillStyle = '#0B1A12';
                ctx.fill();
            }

            function start(e) {
                drawing = true;
                var p = pos(e);
                lastX = p.x; lastY = p.y;
                dot(p);
                syncSignature(name);
            }
            function move(e) {
                if (!drawing) return;
                e.preventDefault();
                var ctx = signaturePads[name].ctx;
                var p = pos(e);
                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(p.x, p.y);
                ctx.stroke();
                lastX = p.x; lastY = p.y;
                syncSignature(name);
            }
            function end() { drawing = false; }

            canvas.addEventListener('mousedown', start);
            canvas.addEventListener('mousemove', move);
            canvas.addEventListener('mouseup', end);
            canvas.addEventListener('mouseleave', end);
            canvas.addEventListener('touchstart', start);
            canvas.addEventListener('touchmove', move);
            canvas.addEventListener('touchend', end);

            var resizeTimer;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function () { sizeSignatureCanvas(name); }, 200);
            });
        }

        function syncSignature(name) {
            var canvas = signaturePads[name].canvas;
            document.getElementById('input-' + name).value = canvas.toDataURL('image/png');
        }

        function clearSignature(name) {
            var pad = signaturePads[name];
            pad.ctx.clearRect(0, 0, pad.canvas.width, pad.canvas.height);
            document.getElementById('input-' + name).value = '';
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[id^="canvas-"]').forEach(function (canvas) {
                initSignaturePad(canvas.id.replace('canvas-', ''));
            });
        });
    </script>
    @endpush
@endonce
