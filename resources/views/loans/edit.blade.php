@extends('layouts.app')
@section('title', 'Edit Peminjaman')

@section('content')
@php
    $before = $loan->photos->where('stage', 'sebelum')->values();
    $after  = $loan->photos->where('stage', 'sesudah')->values();
@endphp

<nav class="small text-muted mb-3">
    <a href="{{ route('loans.index') }}" class="text-decoration-none">Peminjam</a> / Edit
</nav>

<div class="card-tams p-4 mb-3" style="max-width:860px">
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
                <input type="number" name="quantity" min="1" @if ($maxQty) max="{{ $maxQty }}" @endif
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
                <label class="form-label">Lokasi Awal</label>
                <input type="text" class="form-control" disabled
                       value="{{ $loan->originLocation?->full_name ?? 'Belum dicatat' }}">
                <div class="form-text">Lokasi aset saat dipinjam. Tidak dapat diubah.</div>
            </div>

            <div class="col-md-6">
                <label for="last_location_id" class="form-label">Lokasi Terakhir @unless ($loan->returned_at)<span class="text-danger">*</span>@endunless</label>
                <select name="last_location_id" id="last_location_id" class="form-select @error('last_location_id') is-invalid @enderror">
                    <option value="">Pilih lokasi</option>
                    @foreach ($locations as $l)
                        <option value="{{ $l->id }}" @selected(old('last_location_id', $loan->last_location_id) == $l->id)>{{ $l->full_name }}</option>
                    @endforeach
                </select>
                @error('last_location_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @if ($loan->last_location_at)
                    <div class="form-text">Terakhir diperbarui {{ $loan->last_location_at->format('d M Y H:i') }}.</div>
                @else
                    <div class="form-text text-warning-emphasis">Lokasi belum pernah dicatat.</div>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label">Detail Lokasi <span class="text-muted">(opsional)</span></label>
                <input type="text" name="last_location_note" value="{{ old('last_location_note', $loan->last_location_note) }}" maxlength="100"
                       placeholder="Contoh: Meja Pak Budi, lantai 2"
                       class="form-control @error('last_location_note') is-invalid @enderror">
                @error('last_location_note') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

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

{{-- Foto bukti (form terpisah dari form di atas) --}}
<div class="card-tams p-4" style="max-width:860px">
    <h2 class="h6 fw-semibold mb-1">Foto Bukti</h2>
    <p class="small text-muted mb-4">Maksimal 3 foto per tahap. Foto yang dihapus tidak dapat dikembalikan.</p>

    <div class="row g-4">
        @foreach ([
            ['sebelum', 'Sebelum Dipinjam', $before, true],
            ['sesudah', 'Sesudah Dikembalikan', $after, (bool) $loan->returned_at],
        ] as [$stage, $title, $list, $allowed])
            <div class="col-12 col-md-6">
                <div class="fw-semibold small mb-2">{{ $title }} ({{ $list->count() }}/3)</div>

                @if ($list->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach ($list as $p)
                            <div style="width:110px">
                                <a href="{{ $p->url }}" target="_blank" rel="noopener">
                                    <img src="{{ $p->url }}" alt="Foto {{ strtolower($title) }}" loading="lazy"
                                         style="width:110px;height:84px;object-fit:cover;border-radius:10px;border:1px solid #EAEAEA">
                                </a>
                                <form method="POST" action="{{ route('loans.photos.destroy', $p) }}"
                                      onsubmit="return confirm('Hapus foto ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger w-100 mt-1">Hapus</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="small text-muted mb-3">Belum ada foto.</div>
                @endif

                @if (! $allowed)
                    <div class="small text-muted">Foto sesudah dapat ditambahkan setelah barang dikembalikan.</div>
                @elseif ($list->count() < 3)
                    <form method="POST" action="{{ route('loans.photos.store', $loan) }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="stage" value="{{ $stage }}">
                        @include('loans._photo_picker', [
                            'id' => 'photosAdd' . ucfirst($stage), 'name' => 'photos',
                            'max' => 3 - $list->count(), 'showErrors' => false,
                        ])
                        <button type="submit" class="btn btn-sm btn-primary mt-2">Unggah Foto</button>
                    </form>
                @else
                    <div class="small text-muted">Batas 3 foto sudah tercapai.</div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection