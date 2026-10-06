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
    <form method="POST" action="{{ route('loans.store') }}">
        @csrf
        <div class="row g-3">

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
                <input type="text" name="borrower_name" value="{{ old('borrower_name') }}"
                       class="form-control @error('borrower_name') is-invalid @enderror" id="borrower_name">
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

            <div class="col-12">
                <label class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
                <input type="text" name="notes" value="{{ old('notes') }}" class="form-control @error('notes') is-invalid @enderror">
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
@endsection

@push('scripts')
<script>
    const sel = document.getElementById('asset_id');
    const qty = document.getElementById('quantity');
    const info = document.getElementById('infoStok');

    function updateStok() {
        if (!sel) return;
        const opt = sel.selectedOptions[0];
        if (!opt || !opt.dataset.available) {
            info.textContent = 'Pilih aset untuk melihat sisa tersedia.';
            qty.removeAttribute('max');
            return;
        }
        const av = parseInt(opt.dataset.available);
        info.textContent = 'Tersedia ' + av + ' dari ' + opt.dataset.total + ' unit.';
        qty.max = av;
        if (parseInt(qty.value) > av) qty.value = av;
    }
    if (sel) { sel.addEventListener('change', updateStok); updateStok(); }

    const overdueBorrowers = @json($overdueBorrowers);
        const nameInput = document.getElementById('borrower_name');
        const warnBox   = document.getElementById('warnPeminjam');

        function cekPeminjam() {
            const data = overdueBorrowers[nameInput.value.trim().toLowerCase()];
            if (data) {
                warnBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>' +
                    nameInput.value.trim() + '</strong> masih memiliki ' + data.total +
                    ' peminjaman terlambat (' + data.units + ' unit) yang belum dikembalikan.';
                warnBox.classList.remove('d-none');
            } else {
                warnBox.classList.add('d-none');
            }
        }
        if (nameInput) { nameInput.addEventListener('input', cekPeminjam); cekPeminjam(); }
    </script>
@endpush