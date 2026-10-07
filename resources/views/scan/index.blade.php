@extends('layouts.app')
@section('title', 'Scan Barcode')

@section('content')
<style>
    #reader { width: 100%; max-width: 420px; margin: 0 auto; border-radius: 12px; overflow: hidden; }
    .scan-input { font-size: 1.25rem; letter-spacing: 1px; height: 54px; }
    .result-card { border-left: 6px solid var(--success); }
    .result-card.fail { border-left-color: var(--danger); }
    .hist-row { background: var(--surface-2); border-radius: 8px; padding: 8px 12px; font-size: 13px; }
</style>

<div class="row g-3">
    <div class="col-12 col-xl-6">
        <div class="card-tams p-4 mb-3">
            <h2 class="h6 fw-semibold mb-1">Scanner atau Ketik Kode</h2>
            <p class="small text-muted">Arahkan scanner ke barcode (kolom terisi otomatis), atau ketik kode lalu tekan Enter.</p>

            <div class="input-group mb-3">
                <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                <input type="text" id="kode" class="form-control scan-input" placeholder="TCH-0001"
                       autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="30">
                <button class="btn btn-primary" id="btnCari" type="button">Cari</button>
            </div>

            <div class="d-flex flex-wrap gap-4 small">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="autoOpen">
                    <label class="form-check-label" for="autoOpen">Langsung buka Detail Aset</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="sound" checked>
                    <label class="form-check-label" for="sound">Bunyi saat scan</label>
                </div>
            </div>
        </div>

        <div id="result"></div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card-tams p-4">
            <h2 class="h6 fw-semibold mb-1">Kamera HP atau Laptop</h2>
            <p class="small text-muted">Arahkan kamera ke barcode pada jarak 10-20 cm dengan cahaya cukup.</p>

            <div id="secureWarn" class="alert alert-warning small d-none">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Kamera langsung tidak bisa dipakai di alamat ini karena bukan <strong>HTTPS</strong> atau localhost.
                Gunakan <strong>Ambil Foto Barcode</strong>, atau buka TAMS lewat HTTPS.
            </div>

            <div id="readerWrap" class="d-none mb-3"><div id="reader"></div></div>

            <div class="d-flex flex-wrap gap-2">
                <button type="button" id="btnCam" class="btn btn-primary">
                    <i class="bi bi-camera-video"></i> <span>Buka Kamera</span>
                </button>
                <label class="btn btn-outline-secondary mb-0">
                    <i class="bi bi-camera"></i> Ambil Foto Barcode
                    <input type="file" id="fileScan" accept="image/*" capture="environment" hidden>
                </label>
            </div>
            <div id="camMsg" class="small mt-2"></div>
            <div id="reader-file" class="d-none"></div>
        </div>
    </div>

    <div class="col-12">
        <div class="card-tams p-4">
            <h2 class="h6 fw-semibold mb-3">Riwayat Scan (sesi ini)</h2>
            <div id="history"><div class="text-muted small">Belum ada scan.</div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    var URL_FIND = @json(route('scan.find'));
    var $ = function (id) { return document.getElementById(id); };
    var isTouch = 'ontouchstart' in window;
    var input = $('kode'), busy = false, camOn = false, cam = null, lastText = '', lastAt = 0, history = [];
    var CONDITION = { baik: 'success', rusak: 'danger', perbaikan: 'warning' };

    function h(tag, cls, text) {
        var e = document.createElement(tag);
        if (cls) e.className = cls;
        if (text !== undefined) e.textContent = text;
        return e;
    }
    function focusInput() { if (! isTouch) input.focus(); }

    // Bunyi singkat: tinggi = berhasil, rendah = gagal
    var audio;
    function beep(ok) {
        if (! $('sound').checked) return;
        try {
            audio = audio || new (window.AudioContext || window.webkitAudioContext)();
            var o = audio.createOscillator(), g = audio.createGain();
            o.frequency.value = ok ? 1000 : 300; g.gain.value = 0.08;
            o.connect(g); g.connect(audio.destination); o.start();
            setTimeout(function () { o.stop(); }, ok ? 120 : 350);
        } catch (e) {}
    }

    function renderFound(a) {
        var box = $('result'); box.innerHTML = '';
        var card = h('div', 'card-tams result-card p-4');

        var top = h('div', 'd-flex justify-content-between align-items-start gap-2 mb-2');
        var left = h('div');
        var code = h('div', 'fw-bold fs-5', a.code); code.style.color = 'var(--primary)';
        left.append(code, h('div', 'fw-semibold', a.name), h('div', 'small text-muted', a.category + ' \u00B7 ' + a.location));
        var badges = h('div', 'd-flex gap-1 flex-wrap justify-content-end');
        badges.append(
            h('span', 'badge rounded-pill text-bg-' + (CONDITION[a.condition] || 'secondary'), a.condition.charAt(0).toUpperCase() + a.condition.slice(1)),
            h('span', 'badge rounded-pill text-bg-' + (a.status === 'aktif' ? 'primary' : 'secondary'), a.status.charAt(0).toUpperCase() + a.status.slice(1))
        );
        top.append(left, badges);

        var stats = h('div', 'row g-2 my-2 text-center');
        [['Jumlah', a.quantity], ['Tersedia', a.available]].forEach(function (s) {
            var col = h('div', 'col-6'), b = h('div', 'p-2'); b.style.background = 'var(--surface-2)'; b.style.borderRadius = '8px';
            b.append(h('div', 'small text-muted', s[0]), h('div', 'fw-bold fs-5', String(s[1])));
            col.append(b); stats.append(col);
        });

        var actions = h('div', 'd-flex gap-2');
        var open = h('a', 'btn btn-primary', 'Buka Detail Aset'); open.href = a.url;
        var again = h('button', 'btn btn-outline-secondary', 'Scan Lagi'); again.type = 'button';
        again.addEventListener('click', function () { box.innerHTML = ''; input.value = ''; focusInput(); });
        actions.append(open, again);

        card.append(top, stats, actions); box.append(card);
    }

    function renderFail(code, msg) {
        var box = $('result'); box.innerHTML = '';
        var card = h('div', 'card-tams result-card fail p-4');
        card.append(h('div', 'fw-bold text-danger', code || '(kosong)'), h('div', 'small', msg));
        box.append(card);
    }

    function addHistory(entry) {
        history.unshift(entry); history = history.slice(0, 10);
        var box = $('history'); box.innerHTML = '';
        history.forEach(function (r) {
            var row = h('div', 'hist-row d-flex gap-3 align-items-center mb-2');
            row.append(h('span', 'text-muted', r.time));
            var c = h('strong', r.ok ? '' : 'text-danger', r.code);
            row.append(c);
            row.append(h('span', 'flex-grow-1 text-truncate', r.text));
            row.append(h('span', 'badge text-bg-light border', r.src));
            if (r.url) { var a = h('a', '', 'Buka'); a.href = r.url; row.append(a); }
            box.append(row);
        });
    }

    async function lookup(raw, src) {
        var code = (raw || '').trim().toUpperCase();
        if (! code || busy) return;
        busy = true;
        var time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        try {
            var res = await fetch(URL_FIND + '?kode=' + encodeURIComponent(code), { headers: { 'Accept': 'application/json' } });
            var data;
            try { data = await res.json(); }
            catch (e) { throw new Error('Respons tidak valid. Muat ulang halaman, mungkin sesi login sudah berakhir.'); }

            if (data.found) {
                renderFound(data.asset); beep(true);
                addHistory({ time: time, code: data.asset.code, text: data.asset.name, ok: true, src: src, url: data.asset.url });
                if ($('autoOpen').checked) setTimeout(function () { window.location.href = data.asset.url; }, 400);
            } else {
                renderFail(code, data.message || 'Tidak ditemukan.'); beep(false);
                addHistory({ time: time, code: code, text: data.message || 'Tidak ditemukan', ok: false, src: src });
            }
        } catch (e) {
            renderFail(code, e.message || 'Gagal menghubungi server.'); beep(false);
        } finally {
            busy = false; input.value = ''; focusInput();
        }
    }

    // Scanner USB mengetik kode lalu menekan Enter
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); lookup(input.value, 'scanner'); } });
    $('btnCari').addEventListener('click', function () { lookup(input.value, 'ketik'); });
    document.addEventListener('click', function (e) { if (! e.target.closest('button, a, select, input, label, textarea')) focusInput(); });
    focusInput();

    // ----- Kamera -----
    function camMsg(text, cls) { var m = $('camMsg'); m.className = 'small mt-2 ' + (cls || 'text-muted'); m.textContent = text; }
    function libReady() { return typeof Html5Qrcode !== 'undefined'; }
    function makeScanner(id) {
        return new Html5Qrcode(id, { formatsToSupport: [Html5QrcodeSupportedFormats.CODE_128], verbose: false });
    }

    if (! window.isSecureContext) $('secureWarn').classList.remove('d-none');

    async function startCam() {
        if (! window.isSecureContext) { camMsg('Kamera hanya bisa dibuka lewat HTTPS atau localhost.', 'text-danger'); return; }
        if (! libReady()) { camMsg('Library kamera gagal dimuat. Periksa koneksi internet.', 'text-danger'); return; }
        try {
            $('readerWrap').classList.remove('d-none');
            cam = cam || makeScanner('reader');
            await cam.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 280, height: 120 } },
                function (text) {
                    var now = Date.now();
                    if (text === lastText && now - lastAt < 3000) return;
                    lastText = text; lastAt = now; lookup(text, 'kamera');
                }, function () {});
            camOn = true;
            $('btnCam').querySelector('span').textContent = 'Tutup Kamera';
            camMsg('Kamera aktif. Arahkan ke barcode.');
        } catch (e) {
            $('readerWrap').classList.add('d-none');
            camMsg('Kamera tidak dapat dibuka. Pastikan izin kamera diberikan di browser. (' + e + ')', 'text-danger');
        }
    }
    async function stopCam() {
        try { if (cam && camOn) await cam.stop(); } catch (e) {}
        camOn = false;
        $('readerWrap').classList.add('d-none');
        $('btnCam').querySelector('span').textContent = 'Buka Kamera';
        camMsg('');
    }
    $('btnCam').addEventListener('click', function () { camOn ? stopCam() : startCam(); });

    // ----- Foto barcode (tanpa HTTPS) -----
    $('fileScan').addEventListener('change', async function (e) {
        var file = e.target.files[0];
        if (! file) return;
        if (! libReady()) { camMsg('Library pembaca gagal dimuat. Periksa koneksi internet.', 'text-danger'); return; }
        camMsg('Membaca foto...');
        var reader = makeScanner('reader-file');
        try {
            var text = await reader.scanFile(file, false);
            camMsg('');
            lookup(text, 'foto');
        } catch (err) {
            camMsg('Barcode tidak terbaca pada foto. Dekatkan, pastikan terang, tidak buram, dan seluruh barcode terlihat.', 'text-danger');
        } finally {
            try { reader.clear(); } catch (x) {}
            e.target.value = '';
        }
    });

    window.addEventListener('beforeunload', function () { if (cam && camOn) { try { cam.stop(); } catch (e) {} } });
})();
</script>
@endpush