@php $a = $asset ?? null; @endphp
@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Kode Aset</label>
        <input type="text" class="form-control" value="{{ $a->asset_code ?? $nextCode }}" disabled>
        <div class="form-text">Dibuat otomatis oleh sistem.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Nama Aset <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $a->name ?? '') }}" class="form-control @error('name') is-invalid @enderror">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Kategori <span class="text-danger">*</span></label>
        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
            <option value="">Pilih kategori</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}" @selected(old('category_id', $a->category_id ?? '') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Lokasi <span class="text-danger">*</span></label>
        <select name="location_id" class="form-select @error('location_id') is-invalid @enderror">
            <option value="">Pilih lokasi</option>
            @foreach ($locations as $l)
                <option value="{{ $l->id }}" @selected(old('location_id', $a->location_id ?? '') == $l->id)>{{ $l->name }}</option>
            @endforeach
        </select>
        @error('location_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Kondisi <span class="text-danger">*</span></label>
        <select name="asset_condition" class="form-select @error('asset_condition') is-invalid @enderror">
            @foreach (['baik', 'rusak', 'perbaikan'] as $v)
                <option value="{{ $v }}" @selected(old('asset_condition', $a->asset_condition ?? 'baik') == $v)>{{ ucfirst($v) }}</option>
            @endforeach
        </select>
        @error('asset_condition') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-select @error('status') is-invalid @enderror">
            @foreach (['aktif', 'nonaktif'] as $v)
                <option value="{{ $v }}" @selected(old('status', $a->status ?? 'aktif') == $v)>{{ ucfirst($v) }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Dua kolom di bawah PERLU DIKONFIRMASI KE PERUSAHAAN (wajib atau tidak) --}}
    <div class="col-md-6">
        <label class="form-label">Nomor Seri <span class="text-muted">(opsional)</span></label>
        <input type="text" name="serial_number" value="{{ old('serial_number', $a->serial_number ?? '') }}" class="form-control @error('serial_number') is-invalid @enderror">
        @error('serial_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Tanggal Pembelian <span class="text-muted">(opsional)</span></label>
        <input type="date" name="purchase_date" value="{{ old('purchase_date', optional($a->purchase_date ?? null)->format('Y-m-d')) }}" class="form-control @error('purchase_date') is-invalid @enderror">
        @error('purchase_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Keterangan <span class="text-muted">(opsional)</span></label>
        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $a->description ?? '') }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>