@php
    $max        = $max ?? 3;
    $showErrors = $showErrors ?? true;
@endphp

<div class="photo-picker" data-max="{{ $max }}">
    <input type="file" id="{{ $id }}" name="{{ $name }}[]" class="form-control"
           accept="image/jpeg,image/png,image/webp" multiple>
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

    function init(box) {
        var input = box.querySelector('input[type=file]'), note = box.querySelector('[data-note]'), prev = box.querySelector('[data-previews]');
        var max = parseInt(box.dataset.max, 10) || 3, files = [], base = note.textContent;
        var canSync = typeof DataTransfer !== 'undefined';

        function say(text, bad) { note.textContent = text || base; note.className = 'form-text' + (bad ? ' text-danger' : ''); }

        function sync() {
            if (canSync) {
                try { var dt = new DataTransfer(); files.forEach(function (f) { dt.items.add(f); }); input.files = dt.files; }
                catch (e) { canSync = false; }
            }
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

        input.addEventListener('change', async function () {
            if (! canSync) return;
            var picked = Array.prototype.slice.call(input.files);
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
        });
    }

    document.querySelectorAll('.photo-picker').forEach(init);
})();
</script>
@endPushOnce