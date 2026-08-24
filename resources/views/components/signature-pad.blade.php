{{--
    Komponen canvas signature — tanda tangan digambar langsung di browser
    (RF-26), BUKAN upload scan, BUKAN e-signature tersertifikasi (PSrE).
    Dipakai di halaman kontrak (Admin & Extras) dan invoice (Admin & CD).

    Usage: <x-signature-pad name="ttd_extras" />
    Hasil signature disimpan sebagai base64 PNG di hidden input bernama
    sesuai $name, dikirim bersama form saat submit.
--}}
@props(['name'])

<div class="signature-pad-wrap">
    <canvas id="canvas-{{ $name }}" width="500" height="200"
            style="border:1px solid #ccc; border-radius:8px; background:#fff; touch-action:none; max-width:100%;"></canvas>
    <input type="hidden" name="{{ $name }}" id="input-{{ $name }}">
    <div style="margin-top: 8px;">
        <button type="button" class="btn btn-sm" onclick="clearSignature('{{ $name }}')">Hapus & Ulangi</button>
    </div>
</div>

@once
    @push('scripts')
    <script>
        var signaturePads = {};

        function initSignaturePad(name) {
            var canvas = document.getElementById('canvas-' + name);
            var ctx = canvas.getContext('2d');
            var drawing = false;
            var lastX = 0, lastY = 0;

            ctx.strokeStyle = '#0B1A12';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';

            function pos(e) {
                var rect = canvas.getBoundingClientRect();
                var clientX = e.touches ? e.touches[0].clientX : e.clientX;
                var clientY = e.touches ? e.touches[0].clientY : e.clientY;
                return {
                    x: (clientX - rect.left) * (canvas.width / rect.width),
                    y: (clientY - rect.top) * (canvas.height / rect.height),
                };
            }

            function start(e) {
                drawing = true;
                var p = pos(e);
                lastX = p.x; lastY = p.y;
            }
            function move(e) {
                if (!drawing) return;
                e.preventDefault();
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

            signaturePads[name] = { canvas: canvas, ctx: ctx };
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
