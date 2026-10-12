@extends('layouts.app')
@section('title', $type === 'masuk' ? 'Barang Masuk' : 'Barang Keluar')

@section('content')
<nav class="small text-muted mb-3">
    <a href="{{ route('movements.index') }}" class="text-decoration-none">Barang Masuk/Keluar</a> / {{ $type === 'masuk' ? 'Barang Masuk' : 'Barang Keluar' }}
</nav>

<div class="card-tams p-4" style="max-width:860px">
    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $type === 'masuk' ? 'active' : '' }}" href="{{ route('movements.create', ['jenis' => 'masuk']) }}">
                <i class="bi bi-box-arrow-in-down me-1"></i> Barang Masuk
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $type === 'keluar' ? 'active' : '' }}" href="{{ route('movements.create', ['jenis' => 'keluar']) }}">
                <i class="bi bi-box-arrow-up me-1"></i> Barang Keluar
            </a>
        </li>
    </ul>

    @if ($assets->isEmpty())
        <div class="text-center text-muted py-4">
            <i class="bi bi-box-seam fs-1"></i>
            <p class="mb-0">
                {{ $type === 'keluar' ? 'Tidak ada aset dengan unit tersedia untuk dikeluarkan.' : 'Belum ada aset. Tambahkan aset terlebih dahulu di menu Data Aset.' }}
            </p>
        </div>
    @else
    <form method="POST" action="{{ route('movements.store') }}">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Scan Barcode Aset <span class="text-muted">(opsional)</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                    <input type="text" id="scanKode" class="form-control" placeholder="Scan atau ketik kode, lalu Enter"
                           autocomplete="off" autocapitalize="characters" maxlength="30">
                    <button type="button" class="btn btn-outline-secondary" id="btnScanCam">
                        <i class="bi bi-camera-video"></i> Kamera
                    </button>
                </div>
                <div id="scanInfo" class="form-text"></div>
            </div>

            <div class="col-12">
                <label for="asset_id" class="form-label">Aset <span class="text-danger">*</span></label>
                <select name="asset_id" id="asset_id" class="form-select @error('asset_id') is-invalid @enderror">
                    <option value="">Pilih aset</option>
                    @foreach ($assets as $a)
                        <option value="{{ $a->id }}" data-qty="{{ $a->quantity }}" data-available="{{ $a->available_qty }}"
                                @selected(old('asset_id', $selected) == $a->id)>
                            {{ $a->asset_code }} - {{ $a->name }}
                            (jumlah {{ $a->quantity }}@if ($type === 'keluar'), tersedia {{ $a->available_qty }}@endif)
                            @if ($a->status === 'nonaktif') [nonaktif] @endif
                        </option>
                    @endforeach
                </select>
                @error('asset_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div id="preview" class="form-text">Pilih aset untuk melihat jumlahnya.</div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Alasan <span class="text-danger">*</span></label>
                <select name="reason" class="form-select @error('reason') is-invalid @enderror">
                    @foreach (\App\Models\StockMovement::REASONS[$type] as $k => $v)
                        <option value="{{ $k }}" @selected(old('reason') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label for="quantity" class="form-label">Jumlah Unit <span class="text-danger">*</span></label>
                <input type="number" name="quantity" id="quantity" min="1" value="{{ old('quantity', 1) }}"
                       class="form-control @error('quantity') is-invalid @enderror">
                @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                <input type="date" name="moved_at" max="{{ now()->format('Y-m-d') }}"
                       value="{{ old('moved_at', now()->format('Y-m-d')) }}"
                       class="form-control @error('moved_at') is-invalid @enderror">
                @error('moved_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Nomor Nota / Referensi <span class="text-muted">(opsional)</span></label>
                <input type="text" name="reference" value="{{ old('reference') }}" maxlength="50"
                       class="form-control @error('reference') is-invalid @enderror">
                @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
                <input type="text" name="notes" value="{{ old('notes') }}" maxlength="255"
                       class="form-control @error('notes') is-invalid @enderror">
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="alert alert-light border small mt-4 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            @if ($type === 'masuk')
                Jumlah unit aset akan <strong>bertambah</strong>. Jika aset sedang nonaktif karena kosong, ia diaktifkan kembali.
            @else
                Jumlah unit aset akan <strong>berkurang</strong>. Unit yang sedang dipinjam tidak dapat dikeluarkan.
                Jika semua unit keluar, aset otomatis <strong>dinonaktifkan</strong>.
            @endif
            Pencatatan tidak dapat diedit atau dihapus. Jika salah, catat mutasi kebalikannya.
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-{{ $type === 'masuk' ? 'success' : 'danger' }}">
                {{ $type === 'masuk' ? 'Catat Barang Masuk' : 'Catat Barang Keluar' }}
            </button>
            <a href="{{ route('movements.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
    @endif
</div>

@if ($assets->isNotEmpty())
    @include('scan._camera')
@endif
@endsection

@push('scripts')
<script>
(function () {
    var TYPE = @json($type), URL_FIND = @json(route('scan.find'));
    var sel = document.getElementById('asset_id'), qty = document.getElementById('quantity');
    var prev = document.getElementById('preview'), inp = document.getElementById('scanKode'), info = document.getElementById('scanInfo');
    if (! sel) return;

    function update() {
        var o = sel.selectedOptions[0];
        prev.className = 'form-text';
        if (! o || ! o.dataset.qty) { prev.textContent = 'Pilih aset untuk melihat jumlahnya.'; qty.removeAttribute('max'); return; }

        var cur = parseInt(o.dataset.qty, 10), av = parseInt(o.dataset.available, 10), n = parseInt(qty.value, 10) || 0;
        if (TYPE === 'keluar') {
            qty.max = av;
            prev.textContent = 'Jumlah sekarang ' + cur + ' unit, tersedia ' + av + ' (sisanya sedang dipinjam). Setelah dicatat: ' + (cur - n) + ' unit.';
            if (n > av) prev.className = 'form-text text-danger';
        } else {
            prev.textContent = 'Jumlah sekarang ' + cur + ' unit. Setelah dicatat: ' + (cur + n) + ' unit.';
        }
    }
    sel.addEventListener('change', update);
    qty.addEventListener('input', update);
    update();

    function say(text, cls) { info.className = 'form-text ' + (cls || ''); info.textContent = text; }

    async function pick(raw) {
        var code = (raw || '').trim().toUpperCase();
        if (! code) return;
        say('Mencari ' + code + '...');
        try {
            var res = await fetch(URL_FIND + '?kode=' + encodeURIComponent(code), { headers: { 'Accept': 'application/json' } });
            var d = await res.json();
            if (! d.found) { say(d.message || 'Tidak ditemukan.', 'text-danger'); return; }

            var a = d.asset;
            var opt = Array.prototype.find.call(sel.options, function (o) { return o.value === String(a.id); });
            if (! opt) {
                say(a.code + ' (' + a.name + ') tidak dapat dipilih: ' + (TYPE === 'keluar' ? 'tidak ada unit yang tersedia untuk dikeluarkan.' : 'tidak ada di daftar.'), 'text-danger');
                return;
            }
            sel.value = opt.value;
            update();
            say('Terpilih: ' + a.code + ' - ' + a.name + '.', 'text-success');
            inp.value = '';
            qty.focus(); qty.select();
        } catch (e) {
            say('Gagal mencari. Muat ulang halaman, mungkin sesi login sudah berakhir.', 'text-danger');
        }
    }

    inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); pick(inp.value); } });
    document.getElementById('btnScanCam').addEventListener('click', function () { openScanCamera(pick); });
    if (! ('ontouchstart' in window)) inp.focus();
})();
</script>
@endpush