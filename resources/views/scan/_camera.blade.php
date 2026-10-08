<div class="modal fade" id="modalScanCam" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
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
                <div class="text-center mt-2">
                    <label class="btn btn-outline-secondary btn-sm mb-0">
                        <i class="bi bi-camera"></i> Ambil Foto Barcode
                        <input type="file" id="camFile" accept="image/*" capture="environment" hidden>
                    </label>
                </div>
                <div id="camFileReader" class="d-none"></div>
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
        return new Html5Qrcode(id, { formatsToSupport: [Html5QrcodeSupportedFormats.CODE_128], verbose: false });
    }
    function finish(text) {
        if (handled) return;
        handled = true;
        var cb = callback;
        modal.hide();
        if (cb) cb(String(text).trim().toUpperCase());
    }

    async function start() {
        handled = false;
        if (! window.isSecureContext) {
            document.getElementById('camSecureWarn').classList.remove('d-none');
            status('');
            return;
        }
        document.getElementById('camSecureWarn').classList.add('d-none');
        if (typeof Html5Qrcode === 'undefined') { status('Library kamera gagal dimuat. Periksa koneksi internet.', 'text-danger'); return; }
        try {
            scanner = scanner || make('camReader');
            status('Membuka kamera...');
            await scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 280, height: 120 } }, finish, function () {});
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

    document.getElementById('camFile').addEventListener('change', async function (e) {
        var file = e.target.files[0];
        if (! file) return;
        if (typeof Html5Qrcode === 'undefined') { status('Library pembaca gagal dimuat. Periksa koneksi internet.', 'text-danger'); return; }
        status('Membaca foto...');
        var reader = make('camFileReader');
        try { finish(await reader.scanFile(file, false)); }
        catch (err) { status('Barcode tidak terbaca pada foto. Dekatkan, pastikan terang dan tidak buram.', 'text-danger'); }
        finally { try { reader.clear(); } catch (x) {} e.target.value = ''; }
    });

    window.openScanCamera = function (cb) {
        callback = cb;
        modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    };
})();
</script>
@endpush