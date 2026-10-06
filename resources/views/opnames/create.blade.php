@extends('layouts.app')
@section('title', 'Buat Sesi Stok Opname')

@section('content')
<nav class="small text-muted mb-3">
    <a href="{{ route('opnames.index') }}" class="text-decoration-none">Stok Opname</a> / Buat Sesi
</nav>

<div class="card-tams p-4" style="max-width:760px">
    <form method="POST" action="{{ route('opnames.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Nama Sesi <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Opname Ruang Rapat Oktober 2026"
                       class="form-control @error('name') is-invalid @enderror">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Tanggal Opname <span class="text-danger">*</span></label>
                <input type="date" name="opname_date" value="{{ old('opname_date', now()->format('Y-m-d')) }}"
                       class="form-control @error('opname_date') is-invalid @enderror">
                @error('opname_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Cakupan</label>
                <select name="location_id" class="form-select @error('location_id') is-invalid @enderror">
                    <option value="">Seluruh aset aktif</option>
                    @foreach ($locations as $l)
                        <option value="{{ $l->id }}" @selected(old('location_id') == $l->id)>Ruangan: {{ $l->name }}</option>
                    @endforeach
                </select>
                @error('location_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
                <input type="text" name="notes" value="{{ old('notes') }}" class="form-control @error('notes') is-invalid @enderror">
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="alert alert-light border small mt-4 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Setelah sesi dibuat, sistem menyimpan <strong>foto data</strong> jumlah dan kondisi aset pada saat ini
            sebagai pembanding hasil hitung fisik. Hanya aset berstatus <strong>aktif</strong> yang diikutkan.
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">Buat Sesi</button>
            <a href="{{ route('opnames.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection