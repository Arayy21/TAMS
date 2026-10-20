@php
    $max        = $max ?? 3;
    $showErrors = $showErrors ?? true;
@endphp

<div class="photo-picker" data-max="{{ $max }}">
    {{-- Berkas final yang ikut terkirim bersama formulir (diisi oleh JavaScript) --}}
    <input type="file" id="{{ $id }}" name="{{ $name }}[]" class="form-control" data-holder
           accept="image/jpeg,image/png,image/webp" multiple>

    <div class="d-none" data-tools>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" data-act="camera">
                <i class="bi bi-camera-fill"></i> Ambil Foto (Kamera)
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-act="gallery">
                <i class="bi bi-images"></i> Pilih dari Galeri
            </button>
        </div>
        <input type="file" data-gallery accept="image/jpeg,image/png,image/webp" multiple hidden>
        <input type="file" data-native accept="image/*" capture="environment" hidden>
    </div>

    {{-- Kamera langsung di halaman --}}
    <div class="d-none mt-2 p-2 rounded-3 text-center" style="background:#1B1B1B" data-cam>
        <div class="position-relative">
            <video data-video autoplay playsinline muted
                   style="width:100%;max-height:340px;border-radius:10px;background:#000;object-fit:contain"></video>
            <div data-flash style="position:absolute;inset:0;background:#fff;opacity:0;pointer-events:none;transition:opacity .15s;border-radius:10px"></div>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-center mt-2">
            <button type="button" class="btn btn-primary btn-sm" data-act="shoot"><i class="bi bi-record-circle"></i> Ambil Foto</button>
            <button type="button" class="btn btn-outline-light btn-sm" data-act="flip"><i class="bi bi-arrow-repeat"></i> Balik Kamera</button>
            <button type="button" class="btn btn-outline-light btn-sm" data-act="native"><i class="bi bi-phone"></i> Aplikasi Kamera HP</button>
            <button type="button" class="btn btn-outline-light btn-sm" data-act="close">Tutup</button>
        </div>
        <div class="small mt-1" style="color:#BDBDBD" data-camnote></div>
    </div>

    <div class="form-text" data-note>JPG, PNG, atau WebP. Maksimal {{ $max }} foto, otomatis diperkecil.</div>
    <div class="d-flex flex-wrap gap-2 mt-2" data-previews></div>
</div>

@if ($showErrors && ($errors->has($name) || $errors->has($name . '.*')))
    <div class="text-danger small mt-1">
        @foreach (collect($errors->get($name))->merge(collect($errors->get($name . '.*'))->flatten())->unique() as $msg)
            <div>{{ $msg }}</div>
        @endforeach
    </div>
@endif

@pushOnce('scripts')
<script>
(function () {
    var MAX_SIDE = 1600, MAX_BYTES = 5 * 1024 * 1024;
    var active = null; // pemilih foto yang kameranya sedang menyala
    var isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
    var supportsSync = (function () { try { new DataTransfer(); return true; } catch (e) { return false; } })();

    // Perkecil dan ubah ke JPG di browser agar unggahan ringan
    function toJpeg(file) {
        return new Promise(function (resolve) {
            var url = URL.createObjectURL(file), img = new Image();
            img.onload = function () {
                URL.revokeObjectURL(url);
                var k = Math.min(1, MAX_SIDE / Math.max(img.naturalWidth, img.naturalHeight));
                var c = document.createElement('canvas');
                c.width = Math.round(img.naturalWidth * k); c.height = Math.round(img.naturalHeight * k);
                var x = c.getContext('2d'); x.fillStyle = '#fff'; x.fillRect(0, 0, c.width, c.height);
                x.drawImage(img, 0, 0, c.width, c.height);
                c.toBlob(function (b) {
                    if (! b) { resolve(file); return; }
                    resolve(new File([b], (file.name.replace(/\.[^.]+$/, '') || 'foto') + '.jpg', { type: 'image/jpeg' }));
                }, 'image/jpeg', 0.85);
            };
            img.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
            img.src = url;
        });
    }

    function camError(e) {
        var n = e && e.name;
        if (n === 'NotAllowedError' || n === 'SecurityError') return 'Izin kamera ditolak. Izinkan kamera pada pengaturan situs di browser, atau gunakan Pilih dari Galeri.';
        if (n === 'NotFoundError' || n === 'OverconstrainedError') return 'Kamera tidak ditemukan pada perangkat ini.';
        if (n === 'NotReadableError') return 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi itu lalu coba lagi.';
        return 'Kamera tidak dapat dibuka. Gunakan Pilih dari Galeri.';
    }

    function init(box) {
        var holder = box.querySelector('[data-holder]'), tools = box.querySelector('[data-tools]');
        if (! supportsSync) return; // peramban lama: tetap memakai kolom pilih file bawaan

        var gallery = box.querySelector('[data-gallery]'), nativeCam = box.querySelector('[data-native]');
        var cam = box.querySelector('[data-cam]'), video = box.querySelector('[data-video]'), flash = box.querySelector('[data-flash]');
        var camNote = box.querySelector('[data-camnote]'), note = box.querySelector('[data-note]'), prev = box.querySelector('[data-previews]');
        var btnCamera = box.querySelector('[data-act="camera"]'), btnGallery = box.querySelector('[data-act="gallery"]');
        var max = parseInt(box.dataset.max, 10) || 3, base = note.textContent, files = [], stream = null, facing = 'environment';

        holder.classList.add('d-none');
        tools.classList.remove('d-none');

        function remaining() { return max - files.length; }
        function say(text, bad) { note.textContent = text || base; note.className = 'form-text' + (bad ? ' text-danger' : ''); }

        function sync() {
            try {
                var dt = new DataTransfer();
                files.forEach(function (f) { dt.items.add(f); });
                holder.files = dt.files;
            } catch (e) {}

            btnCamera.disabled = btnGallery.disabled = remaining() <= 0;

            prev.innerHTML = '';
            files.forEach(function (f, i) {
                var w = document.createElement('div'); w.style.cssText = 'position:relative;width:76px;height:76px';
                var im = document.createElement('img'); im.src = URL.createObjectURL(f); im.alt = 'Pratinjau foto';
                im.style.cssText = 'width:76px;height:76px;object-fit:cover;border-radius:10px;border:1px solid #EAEAEA';
                var b = document.createElement('button'); b.type = 'button'; b.setAttribute('aria-label', 'Hapus foto'); b.textContent = '\u00D7';
                b.style.cssText = 'position:absolute;top:-7px;right:-7px;width:22px;height:22px;border-radius:50%;border:0;background:#BA1A1A;color:#fff;line-height:20px;font-size:15px;padding:0;cursor:pointer';
                b.addEventListener('click', function () { files.splice(i, 1); sync(); say(); });
                w.append(im, b); prev.append(w);
            });
        }

        // Foto dari galeri atau aplikasi kamera bawaan: diperkecil lalu dimasukkan ke daftar
        async function addFiles(picked) {
            if (! picked.length) return;
            say('Memproses foto...');
            var added = [], problem = '';

            for (var i = 0; i < picked.length; i++) {
                if (files.length + added.length >= max) { problem = 'Hanya ' + max + ' foto yang bisa dilampirkan.'; break; }
                if (! /^image\//.test(picked[i].type)) { problem = 'Hanya file gambar yang diterima.'; continue; }
                var out = await toJpeg(picked[i]);
                if (out.size > MAX_BYTES) { problem = 'Ada foto yang masih lebih dari 5 MB, pilih foto lain.'; continue; }
                added.push(out);
            }

            files = files.concat(added);
            sync();
            say(problem, !! problem);
        }

        // ----- Kamera langsung -----
        function stopStream() {
            if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
            video.srcObject = null;
        }

        function closeCam() {
            stopStream();
            cam.classList.add('d-none');
            if (active && active.box === box) active = null;
        }

        async function startStream() {
            stopStream();
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: facing }, width: { ideal: 1920 }, height: { ideal: 1080 } },
                audio: false
            });
            video.srcObject = stream;
            try { await video.play(); } catch (e) {}
        }

        function fallbackCamera() {
            if (isTouch) { nativeCam.click(); return; }
            say('Kamera langsung membutuhkan HTTPS atau alamat localhost (127.0.0.1). Gunakan Pilih dari Galeri, atau buka TAMS lewat alamat tersebut.', true);
        }

        async function openCam() {
            if (remaining() <= 0) { say('Batas ' + max + ' foto sudah tercapai.', true); return; }

            var ok = window.isSecureContext && navigator.mediaDevices && navigator.mediaDevices.getUserMedia;
            if (! ok) { fallbackCamera(); return; }

            if (active && active.box !== box) active.close();

            try {
                camNote.textContent = 'Membuka kamera...';
                cam.classList.remove('d-none');
                await startStream();
                active = { box: box, close: closeCam };
                camNote.textContent = 'Arahkan ke barang, lalu tekan Ambil Foto. Sisa ' + remaining() + ' foto.';
                say();
            } catch (e) {
                closeCam();
                say(camError(e), true);
            }
        }

        function shoot() {
            if (! stream || ! video.videoWidth || remaining() <= 0) return;

            var w = video.videoWidth, h = video.videoHeight, k = Math.min(1, MAX_SIDE / Math.max(w, h));
            var c = document.createElement('canvas');
            c.width = Math.round(w * k); c.height = Math.round(h * k);
            c.getContext('2d').drawImage(video, 0, 0, c.width, c.height);

            flash.style.opacity = 0.8;
            setTimeout(function () { flash.style.opacity = 0; }, 130);

            c.toBlob(function (b) {
                if (! b) { camNote.textContent = 'Gagal mengambil foto, coba lagi.'; return; }
                files.push(new File([b], 'kamera-' + Date.now() + '.jpg', { type: 'image/jpeg' }));
                sync();
                say();
                if (remaining() <= 0) { closeCam(); say('Batas ' + max + ' foto tercapai.'); }
                else camNote.textContent = files.length + ' dari ' + max + ' foto tersimpan. Ambil lagi atau tekan Tutup.';
            }, 'image/jpeg', 0.85);
        }

        btnCamera.addEventListener('click', openCam);
        btnGallery.addEventListener('click', function () { gallery.click(); });

        box.querySelector('[data-act="shoot"]').addEventListener('click', shoot);
        box.querySelector('[data-act="close"]').addEventListener('click', closeCam);
        box.querySelector('[data-act="native"]').addEventListener('click', function () { closeCam(); nativeCam.click(); });
        box.querySelector('[data-act="flip"]').addEventListener('click', async function () {
            facing = facing === 'environment' ? 'user' : 'environment';
            try { await startStream(); } catch (e) { camNote.textContent = camError(e); }
        });

        gallery.addEventListener('change', function (e) { addFiles(Array.prototype.slice.call(e.target.files)); e.target.value = ''; });
        nativeCam.addEventListener('change', function (e) { addFiles(Array.prototype.slice.call(e.target.files)); e.target.value = ''; });

        // Lepas kamera saat formulir dikirim atau jendela ditutup, dan kosongkan pilihan bila di dalam jendela (modal)
        var form = box.closest('form');
        if (form) form.addEventListener('submit', closeCam);

        var modal = box.closest('.modal');
        if (modal) modal.addEventListener('hidden.bs.modal', function () { closeCam(); files = []; sync(); say(); });

        sync();
    }

    document.querySelectorAll('.photo-picker').forEach(init);

    // Kamera dimatikan saat tab disembunyikan atau halaman ditinggalkan
    document.addEventListener('visibilitychange', function () { if (document.hidden && active) active.close(); });
    window.addEventListener('pagehide', function () { if (active) active.close(); });
})();
</script>
@endPushOnce