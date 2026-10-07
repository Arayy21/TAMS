<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Label Aset - TAMS</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-icon.svg') }}">
    <style id="pageStyle"></style>
    <style>
        :root { --w: 50mm; --h: 30mm; --s: 1; --primary: #DC143C; --border: #EAEAEA; --muted: #5F5E5E; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #F7F5F4; color: #121212; font-family: Inter, Arial, sans-serif;
               -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .toolbar { position: sticky; top: 0; z-index: 10; background: #fff; border-bottom: 1px solid var(--border);
                   padding: 12px 16px; display: flex; flex-wrap: wrap; gap: 12px 18px; align-items: flex-end; }
        .toolbar .f { display: flex; flex-direction: column; font-size: 12px; color: var(--muted); gap: 4px; }
        .toolbar input[type=number], .toolbar select { height: 34px; padding: 0 8px; border: 1px solid #CFCFCF;
                   border-radius: 8px; font-size: 14px; }
        .toolbar input[type=number] { width: 84px; }
        .chk { display: flex; align-items: center; gap: 6px; font-size: 13px; height: 34px; }
        .btn { height: 34px; padding: 0 16px; border-radius: 8px; border: 1px solid #CFCFCF; background: #fff; cursor: pointer; font-size: 14px; }
        .btn.primary { background: var(--primary); border-color: var(--primary); color: #fff; font-weight: 600; }
        .info { margin-left: auto; font-size: 13px; color: var(--muted); }
        .tips { padding: 8px 16px; font-size: 12px; color: var(--muted); }

        .wrap { padding: 16px; }
        #sheet { background: #fff; width: 210mm; min-height: 297mm; margin: 0 auto; padding: 10mm;
                 display: flex; flex-wrap: wrap; align-content: flex-start; gap: 3mm;
                 box-shadow: 0 2px 12px rgba(0, 0, 0, .08); }
        body[data-mode="satuan"] #sheet { width: var(--w); min-height: 0; padding: 0; gap: 0; display: block; }
        #source { display: none; }

        .label { width: var(--w); height: var(--h); padding: calc(var(--s) * 1.5mm); display: flex; flex-direction: column;
                 overflow: hidden; background: #fff; break-inside: avoid; page-break-inside: avoid; }
        body.garis .label { outline: .2mm dashed #999; outline-offset: -.2mm; }

        .l-head { display: flex; align-items: center; gap: calc(var(--s) * 1.2mm); }
        .l-head img { width: calc(var(--s) * 5.5mm); height: calc(var(--s) * 5.5mm); flex-shrink: 0; }
        .l-text { min-width: 0; line-height: 1.15; }
        .l-name { font-size: calc(var(--s) * 7.5pt); font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .l-brand { font-size: calc(var(--s) * 5pt); color: #444; letter-spacing: .4pt; }
        .l-bc { flex: 1; min-height: 0; margin: calc(var(--s) * 1mm) calc(var(--s) * 3.5mm) 0; }
        .l-bc svg { width: 100%; height: 100%; display: block; }
        .l-code { text-align: center; font-weight: 700; font-size: calc(var(--s) * 8pt); letter-spacing: calc(var(--s) * 1pt); line-height: 1.2; }
        .l-loc { text-align: center; font-size: calc(var(--s) * 5.5pt); color: #444; white-space: nowrap;
                 overflow: hidden; text-overflow: ellipsis; line-height: 1.2; }

        @media print {
            body { background: #fff; }
            .toolbar, .tips { display: none; }
            .wrap { padding: 0; }
            #sheet { box-shadow: none; margin: 0; width: auto; min-height: 0; padding: 0; }
            body[data-mode="satuan"] #sheet { width: auto; }
            body[data-mode="satuan"] .label { break-after: page; page-break-after: always; }
            body[data-mode="satuan"] .label:last-child { break-after: auto; page-break-after: auto; }
        }
    </style>
</head>
<body class="garis" data-mode="lembar">

    <div class="toolbar">
        <label class="f">Mode cetak
            <select id="mode">
                <option value="lembar">Lembar A4 (kertas stiker)</option>
                <option value="satuan">Satu label per halaman (printer label)</option>
            </select>
        </label>
        <label class="f">Lebar (mm)<input type="number" id="w" min="20" max="200" value="50"></label>
        <label class="f">Tinggi (mm)<input type="number" id="h" min="15" max="200" value="30"></label>
        <label class="f">Salinan per aset<input type="number" id="copies" min="1" max="50" value="1"></label>
        <label class="chk"><input type="checkbox" id="perUnit"> Sesuai jumlah unit</label>
        <label class="chk"><input type="checkbox" id="garis" checked> Garis tepi</label>
        <button type="button" class="btn primary" onclick="window.print()">Cetak</button>
        <button type="button" class="btn" onclick="window.close()">Tutup</button>
        <div class="info" id="info"></div>
    </div>
    <div class="tips">
        Pada dialog cetak: skala <strong>100%</strong> (bukan "Fit to page"), margin <strong>Default/None</strong>,
        dan hidupkan <strong>Background graphics</strong>. Matikan <strong>Garis tepi</strong> bila dicetak di kertas stiker.
    </div>

    <div class="wrap"><div id="sheet"></div></div>

    <div id="source">
        @foreach ($items as $it)
            @php $a = $it['asset']; @endphp
            <div class="label" data-qty="{{ $a->quantity }}">
                <div class="l-head">
                    <img src="{{ asset('images/logo-icon.svg') }}" alt="">
                    <div class="l-text">
                        <div class="l-name">{{ $a->name }}</div>
                        <div class="l-brand">TAMS &middot; Technolife</div>
                    </div>
                </div>
                <div class="l-bc">{!! $it['svg'] !!}</div>
                <div class="l-code">{{ $a->asset_code }}</div>
                <div class="l-loc">{{ $a->location->name ?? '' }}</div>
            </div>
        @endforeach
    </div>

    <script>
    (function () {
        var KEY = 'tams_label_settings', MAX = 300;
        var $ = function (id) { return document.getElementById(id); };
        var source = Array.prototype.slice.call(document.querySelectorAll('#source .label'));
        var els = { mode: $('mode'), w: $('w'), h: $('h'), copies: $('copies'), perUnit: $('perUnit'), garis: $('garis') };

        function clamp(v, min, max, def) { v = parseFloat(v); return isNaN(v) ? def : Math.min(max, Math.max(min, v)); }

        function load() {
            try {
                var s = JSON.parse(localStorage.getItem(KEY) || '{}');
                if (s.mode) els.mode.value = s.mode;
                if (s.w) els.w.value = s.w;
                if (s.h) els.h.value = s.h;
                if (typeof s.garis === 'boolean') els.garis.checked = s.garis;
            } catch (e) {}
        }
        function save() {
            try {
                localStorage.setItem(KEY, JSON.stringify({ mode: els.mode.value, w: els.w.value, h: els.h.value, garis: els.garis.checked }));
            } catch (e) {}
        }

        function render() {
            var w = clamp(els.w.value, 20, 200, 50), h = clamp(els.h.value, 15, 200, 30);
            var copies = Math.round(clamp(els.copies.value, 1, 50, 1));
            var mode = els.mode.value, root = document.documentElement;

            root.style.setProperty('--w', w + 'mm');
            root.style.setProperty('--h', h + 'mm');
            root.style.setProperty('--s', Math.max(0.5, Math.min(w / 50, h / 30)).toFixed(2));
            document.body.setAttribute('data-mode', mode);
            document.body.classList.toggle('garis', els.garis.checked);
            els.copies.disabled = els.perUnit.checked;
            $('pageStyle').textContent = mode === 'satuan'
                ? '@page{size:' + w + 'mm ' + h + 'mm;margin:0}'
                : '@page{size:A4;margin:10mm}';

            var sheet = $('sheet'), total = 0, cut = false;
            sheet.innerHTML = '';
            source.forEach(function (el) {
                var n = els.perUnit.checked ? Math.min(50, Math.max(1, parseInt(el.dataset.qty, 10) || 1)) : copies;
                for (var i = 0; i < n; i++) {
                    if (total >= MAX) { cut = true; return; }
                    sheet.appendChild(el.cloneNode(true));
                    total++;
                }
            });

            $('info').textContent = total + ' label dari ' + source.length + ' aset' + (cut ? ' (dibatasi ' + MAX + ' label)' : '');
            save();
        }

        Object.keys(els).forEach(function (k) {
            els[k].addEventListener('input', render);
            els[k].addEventListener('change', render);
        });

        load();
        render();
    })();
    </script>
</body>
</html>