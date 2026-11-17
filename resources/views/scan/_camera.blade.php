<div class="modal fade" id="modalScanCam" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Scan Barcode dengan Kamera</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div id="camSecureWarn" class="alert alert-warning small d-none">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Kamera langsung hanya bisa dipakai lewat <strong>HTTPS</strong> atau localhost.
                    Gunakan <strong>Ambil Foto Barcode</strong> di bawah.
                </div>
                <div id="camReader" style="width:100%; border-radius:12px; overflow:hidden"></div>
                <div id="camStatus" class="small text-muted mt-2 text-center"></div>
                <div class="form-check form-switch d-flex justify-content-center gap-2 mt-2 mb-0">
                    <input class="form-check-input cam-flip-toggle" type="checkbox" role="switch" id="flipModal">
                    <label class="form-check-label small" for="flipModal">Balik tampilan kiri-kanan (hanya pratinjau)</label>
                </div>
                <div class="text-center mt-2">
                    <label class="btn btn-outline-secondary btn-sm mb-0">
                        <i class="bi bi-camera"></i> Ambil Foto Barcode
                        <input type="file" id="camFile" accept="image/*" capture="environment" hidden>
                    </label>
                </div>
                <div id="camFileReader" style="position:absolute; left:-9999px; top:0; width:400px; height:300px;" aria-hidden="true"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    var modalEl = document.getElementById('modalScanCam');
    var modal = null, scanner = null, running = false, callback = null, handled = false;

    function status(text, cls) {
        var s = document.getElementById('camStatus');
        s.className = 'small mt-2 text-center ' + (cls || 'text-muted');
        s.textContent = text;
    }

    function make(id) {
        return new Html5Qrcode(id, {
            formatsToSupport: [Html5QrcodeSupportedFormats.CODE_128],
            experimentalFeatures: { useBarCodeDetectorIfSupported: true },
            verbose: false
        });
    }

    function finish(text) {
        if (handled) return;
        handled = true;
        var cb = callback;
        modal.hide();
        if (cb) cb(String(text).trim().toUpperCase());
    }

    /* ---------- Scan kamera langsung ---------- */
    async function start() {
        handled = false;
        if (!window.isSecureContext) {
            document.getElementById('camSecureWarn').classList.remove('d-none');
            status('');
            return;
        }
        document.getElementById('camSecureWarn').classList.add('d-none');
        if (typeof Html5Qrcode === 'undefined') {
            status('Library kamera gagal dimuat. Periksa koneksi internet.', 'text-danger');
            return;
        }
        try {
            scanner = scanner || make('camReader');
            status('Membuka kamera...');
            await scanner.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: { width: 280, height: 120 },
                    videoConstraints: {
                        facingMode: 'environment',
                        width: { ideal: 1920 },
                        height: { ideal: 1080 }
                    }
                },
                finish,
                function () {}
            );
            running = true;
            status('Arahkan kamera ke barcode.');
        } catch (e) {
            status('Kamera tidak dapat dibuka. Pastikan izin kamera diberikan. (' + e + ')', 'text-danger');
        }
    }

    async function stop() {
        try { if (scanner && running) await scanner.stop(); } catch (e) {}
        running = false;
    }

    modalEl.addEventListener('shown.bs.modal', start);
    modalEl.addEventListener('hidden.bs.modal', stop);

    /* ---------- Baca barcode dari foto ---------- */
    function loadBitmap(file) {
        if (window.createImageBitmap) return createImageBitmap(file);
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () { URL.revokeObjectURL(url); resolve(img); };
            img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('Gambar tidak valid')); };
            img.src = url;
        });
    }

    // Perkecil gambar (dan opsional putar 90°) lalu jadikan File JPEG
    function resizeToFile(src, maxSide, rotate) {
        var w = src.width, h = src.height;
        var k = Math.min(1, maxSide / Math.max(w, h));
        var cw = Math.round(w * k), ch = Math.round(h * k);
        var c = document.createElement('canvas');
        c.width = rotate ? ch : cw;
        c.height = rotate ? cw : ch;
        var ctx = c.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, c.width, c.height);
        if (rotate) {
            ctx.translate(c.width / 2, c.height / 2);
            ctx.rotate(Math.PI / 2);
            ctx.drawImage(src, -cw / 2, -ch / 2, cw, ch);
        } else {
            ctx.drawImage(src, 0, 0, cw, ch);
        }
        return new Promise(function (resolve) {
            c.toBlob(function (b) {
                resolve(new File([b], 'scan.jpg', { type: 'image/jpeg' }));
            }, 'image/jpeg', 0.92);
        });
    }

    async function nativeDetect(src) {
        if (!('BarcodeDetector' in window)) return null;
        try {
            var det = new BarcodeDetector({ formats: ['code_128'] });
            var res = await det.detect(src);
            return res.length ? res[0].rawValue : null;
        } catch (e) { return null; }
    }

    async function readPhoto(file) {
        var bmp = await loadBitmap(file);
        try {
            // 1) BarcodeDetector bawaan browser (paling akurat untuk foto HP)
            var t = await nativeDetect(bmp);
            if (t) return t;

            // 2) Fallback html5-qrcode: coba beberapa ukuran & rotasi
            var reader = make('camFileReader');
            try {
                var sizes = [1600, 1000, 2400];
                for (var i = 0; i < sizes.length; i++) {
                    for (var r = 0; r < 2; r++) {
                        try {
                            var f = await resizeToFile(bmp, sizes[i], r === 1);
                            return await reader.scanFile(f, false);
                        } catch (e) { /* coba kombinasi berikutnya */ }
                    }
                }
            } finally {
                try { reader.clear(); } catch (x) {}
            }
            throw new Error('not found');
        } finally {
            if (bmp && bmp.close) bmp.close();
        }
    }

    document.getElementById('camFile').addEventListener('change', async function (e) {
        var input = e.target;
        var file = input.files[0];
        if (!file) return;
        if (typeof Html5Qrcode === 'undefined') {
            status('Library pembaca gagal dimuat. Periksa koneksi internet.', 'text-danger');
            return;
        }
        status('Membaca foto...');
        try {
            finish(await readPhoto(file));
        } catch (err) {
            status('Barcode tidak terbaca pada foto. Dekatkan, pastikan terang, tidak buram, dan barcode memenuhi sebagian besar frame.', 'text-danger');
        } finally {
            input.value = '';
        }
    });

    window.openScanCamera = function (cb) {
        callback = cb;
        modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    };
})();
</script>
@endpush