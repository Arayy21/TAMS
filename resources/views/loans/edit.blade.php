@extends('layouts.app')
@section('title', 'Edit Peminjaman')

@section('content')
<nav class="small text-muted mb-3">
    <a href="{{ route('loans.index') }}" class="text-decoration-none">Peminjam</a> / Edit
</nav>

<div class="card-tams p-4" style="max-width:860px">
    <form method="POST" action="{{ route('loans.update', $loan) }}">
        @csrf @method('PUT')
        <div class="row g-3">

            <div class="col-12">
                <label class="form-label">Aset</label>
                <input type="text" class="form-control" disabled
                       value="{{ $loan->asset->asset_code }} - {{ $loan->asset->name }}">
                <div class="form-text">Aset tidak dapat diubah. Jika salah pilih aset, kembalikan peminjaman ini lalu buat peminjaman baru.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Nama Peminjam <span class="text-danger">*</span></label>
                <input type="text" name="borrower_name" value="{{ old('borrower_name', $loan->borrower_name) }}"
                       class="form-control @error('borrower_name') is-invalid @enderror">
                @error('borrower_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Kontak / Divisi <span class="text-muted">(opsional)</span></label>
                <input type="text" name="borrower_contact" value="{{ old('borrower_contact', $loan->borrower_contact) }}"
                       class="form-control @error('borrower_contact') is-invalid @enderror">
                @error('borrower_contact') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Jumlah Dipinjam <span class="text-danger">*</span></label>
                <input type="number" name="quantity" min="1" @if($maxQty) max="{{ $maxQty }}" @endif
                       value="{{ old('quantity', $loan->quantity) }}"
                       class="form-control @error('quantity') is-invalid @enderror">
                @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @if ($maxQty)
                    <div class="form-text">Maksimal {{ $maxQty }} unit.</div>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label">Tanggal Pinjam <span class="text-danger">*</span></label>
                <input type="date" name="loaned_at" value="{{ old('loaned_at', $loan->loaned_at->format('Y-m-d')) }}"
                       class="form-control @error('loaned_at') is-invalid @enderror">
                @error('loaned_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Batas Kembali <span class="text-muted">(opsional)</span></label>
                <input type="date" name="due_at" value="{{ old('due_at', $loan->due_at?->format('Y-m-d')) }}"
                       class="form-control @error('due_at') is-invalid @enderror">
                @error('due_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            @if ($loan->returned_at)
                <div class="col-md-4">
                    <label class="form-label">Tanggal Dikembalikan <span class="text-danger">*</span></label>
                    <input type="date" name="returned_at" max="{{ now()->format('Y-m-d') }}"
                           value="{{ old('returned_at', $loan->returned_at->format('Y-m-d')) }}"
                           class="form-control @error('returned_at') is-invalid @enderror">
                    @error('returned_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @endif

            <div class="col-12">
                <label class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
                <input type="text" name="notes" value="{{ old('notes', $loan->notes) }}"
                       class="form-control @error('notes') is-invalid @enderror">
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection