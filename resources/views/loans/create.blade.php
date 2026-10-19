@extends('layouts.app')
@section('title', 'Pinjam Aset')

@section('content')
<nav class="small text-muted mb-3">
    <a href="{{ route('loans.index') }}" class="text-decoration-none">Peminjam</a> / Pinjam Aset
</nav>

<div class="card-tams p-4" style="max-width:860px">
    @if ($assets->isEmpty())
        <div class="text-center text-muted py-4">
            <i class="bi bi-box-seam fs-1"></i>
            <p class="mb-0">Tidak ada aset yang tersedia untuk dipinjam.<br>
                <small>Aset harus berstatus aktif, berkondisi baik, dan masih memiliki sisa unit.</small></p>
        </div>
    @else
    <form method="POST" action="{{ route('loans.store') }}" enctype="multipart/form-data">
        @csrf
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
                        <option value="{{ $a->id }}" data-available="{{ $a->available_qty }}" data-total="{{ $a->quantity }}"
                                @selected(old('asset_id', $selected) == $a->id)>
                            {{ $a->asset_code }} - {{ $a->name }} (tersedia {{ $a->available_qty }})
                        </option>
                    @endforeach
                </select>
                @error('asset_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text" id="infoStok">Pilih aset untuk melihat sisa tersedia.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Nama Peminjam <span class="text-danger">*</span></label>
                <input type="text" name="borrower_name" id="borrower_name" value="{{ old('borrower_name') }}"
                       class="form-control @error('borrower_name') is-invalid @enderror">
                @error('borrower_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div id="warnPeminjam" class="alert alert-warning py-2 small mt-2 mb-0 d-none" role="alert"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Kontak / Divisi <span class="text-muted">(opsional)</span></label>
                <input type="text" name="borrower_contact" value="{{ old('borrower_contact') }}"
                       class="form-control @error('borrower_contact') is-invalid @enderror">
                @error('borrower_contact') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label for="quantity" class="form-label">Jumlah Dipinjam <span class="text-danger">*</span></label>
                <input type="number" name="quantity" id="quantity" min="1" value="{{ old('quantity', 1) }}"
                       class="form-control @error('quantity') is-invalid @enderror">
                @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Tanggal Pinjam <span class="text-danger">*</span></label>
                <input type="date" name="loaned_at" value="{{ old('loaned_at', now()->format('Y-m-d')) }}"
                       class="form-control @error('loaned_at') is-invalid @enderror">
                @error('loaned_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Batas Kembali <span class="text-muted">(opsional)</span></label>
                <input type="date" name="due_at" value="{{ old('due_at') }}"
                       class="form-control @error('due_at') is-invalid @enderror">
                @error('due_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="last_location_id" class="form-label">Lokasi Pemakaian <span class="text-danger">*</span></label>
                <select name="last_location_id" id="last_location_id" class="form-select @error('last_location_id') is-invalid @enderror">
                    <option value="">Pilih lokasi</option>
                    @foreach ($locations as $l)
                        <option value="{{ $l->id }}" @selected(old('last_location_id') == $l->id)>{{ $l->full_name }}</option>
                    @endforeach
                </select>
                @error('last_location_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Tempat barang dipakai atau disimpan selama dipinjam.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Detail Lokasi <span class="text-muted">(opsional)</span></label>
                <input type="text" name="last_location_note" value="{{ old('last_location_note') }}" maxlength="100"
                       placeholder="Contoh: Meja Pak Budi, lantai 2"
                       class="form-control @error('last_location_note') is-invalid @enderror">
                @error('last_location_note') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label">Foto Kondisi Barang Sebelum Dipinjam <span class="text-muted">(opsional, maks. 3)</span></label>
                @include('loans._photo_picker', ['id' => 'photosBefore', 'name' => 'photos_before', 'max' => 3])
                <div class="form-text">Disarankan melampirkan foto sebagai bukti kondisi barang saat keluar.</div>
            </div>

            <div class="col-12">
                <label class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
                <input type="text" name="notes" value="{{ old('notes') }}"
                       class="form-control @error('notes') is-invalid @enderror">
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">Simpan Peminjaman</button>
            <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">Batal</a>
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
    var sel = document.getElementById('asset_id');
    if (! sel) return;

    var qty = document.getElementById('quantity'), info = document.getElementById('infoStok');
    var URL_FIND = @json(route('scan.find'));

    // ----- Informasi stok -----
    function updateStok() {
        var opt = sel.selectedOptions[0];
        if (! opt || ! opt.dataset.available) {
            info.textContent = 'Pilih aset untuk melihat sisa tersedia.';
            qty.removeAttribute('max');
            return;
        }
        var av = parseInt(opt.dataset.available, 10);
        info.textContent = 'Tersedia ' + av + ' dari ' + opt.dataset.total + ' unit.';
        qty.max = av;
        if (parseInt(qty.value, 10) > av) qty.value = av;
    }
    sel.addEventListener('change', updateStok);
    updateStok();

    // ----- Peringatan peminjam yang masih terlambat -----
    var overdue = @json($overdueBorrowers);
    var nameInput = document.getElementById('borrower_name'), warn = document.getElementById('warnPeminjam');

    function cekPeminjam() {
        var d = overdue[nameInput.value.trim().toLowerCase()];
        if (d) {
            warn.textContent = nameInput.value.trim() + ' masih memiliki ' + d.total + ' peminjaman terlambat (' + d.units + ' unit) yang belum dikembalikan.';
            warn.classList.remove('d-none');
        } else {
            warn.classList.add('d-none');
        }
    }
    nameInput.addEventListener('input', cekPeminjam);
    cekPeminjam();

    // ----- Scan barcode -----
    var inp = document.getElementById('scanKode'), scanInfo = document.getElementById('scanInfo');

    function say(text, cls) { scanInfo.className = 'form-text ' + (cls || ''); scanInfo.textContent = text; }

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
                var why = a.status !== 'aktif' ? 'statusnya nonaktif'
                        : (a.condition !== 'baik' ? 'kondisinya ' + a.condition : 'tidak ada sisa unit yang tersedia');
                say(a.code + ' (' + a.name + ') tidak dapat dipinjam: ' + why + '.', 'text-danger');
                return;
            }
            sel.value = opt.value;
            sel.dispatchEvent(new Event('change'));
            say('Terpilih: ' + a.code + ' - ' + a.name + ' (tersedia ' + a.available + ').', 'text-success');
            inp.value = '';
            nameInput.focus();
        } catch (e) {
            say('Gagal mencari. Muat ulang halaman, mungkin sesi login sudah berakhir.', 'text-danger');
        }
    }

    // Enter dari scanner tidak boleh mengirim form
    inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); pick(inp.value); } });
    document.getElementById('btnScanCam').addEventListener('click', function () { openScanCamera(pick); });
    if (! ('ontouchstart' in window)) inp.focus();
})();
</script>
@endpush