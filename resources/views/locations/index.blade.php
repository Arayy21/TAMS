@extends('layouts.app')
@section('title', 'Lokasi')

@section('content')
<style>
    .loc-thumb { width: 64px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border); display: block; }
    .loc-none { width: 64px; height: 48px; border-radius: 8px; border: 1px dashed #CFCFCF; color: #A3A3A3;
                display: flex; align-items: center; justify-content: center; font-size: 20px; }
    .photo-preview { max-height: 160px; max-width: 100%; border-radius: 10px; border: 1px solid var(--border); }
</style>

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card-tams p-4">
            <h2 class="h6 fw-semibold mb-3">Tambah Lokasi</h2>
            <form method="POST" action="{{ route('locations.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Nama <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Berada di <span class="text-muted">(opsional)</span></label>
                    <select name="parent_id" class="form-select">
                        <option value="">- Lokasi utama -</option>
                        @foreach ($parents as $p)
                            <option value="{{ $p->id }}" @selected(old('parent_id') == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Contoh: Ruang Rapat berada di Gedung A. Maksimal 2 tingkat. <em>(PDK: struktur lokasi)</em></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi <span class="text-muted">(opsional)</span></label>
                    <input type="text" name="description" value="{{ old('description') }}" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Foto <span class="text-muted">(opsional)</span></label>
                    <input type="file" name="photo" id="addPhoto" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <div class="form-text" id="addPhotoNote">JPG, PNG, atau WebP, maksimal 5 MB.</div>
                    <img id="addPreview" class="photo-preview mt-2 d-none" alt="Pratinjau foto">
                </div>
                <button class="btn btn-primary w-100">Simpan</button>
            </form>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        <div class="card-tams p-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Foto</th><th>Nama</th><th>Berada di</th><th>Jumlah Aset</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($locations as $l)
                            <tr>
                                <td>
                                    @if ($l->photo_url)
                                        <button type="button" class="btn p-0 border-0" data-bs-toggle="modal" data-bs-target="#modalPhoto"
                                                data-src="{{ $l->photo_url }}" data-title="{{ $l->name }}" aria-label="Lihat foto {{ $l->name }}">
                                            <img src="{{ $l->photo_url }}" alt="Foto {{ $l->name }}" class="loc-thumb" loading="lazy">
                                        </button>
                                    @else
                                        <div class="loc-none" title="Belum ada foto"><i class="bi bi-image"></i></div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $l->name }}</div>
                                    <small class="text-muted">{{ $l->description }}</small>
                                </td>
                                <td>{{ $l->parent->name ?? '-' }}</td>
                                <td>{{ $l->assets_count }}</td>
                                <td class="text-end text-nowrap">
                                    <button class="btn btn-sm btn-outline-primary btn-edit"
                                            data-bs-toggle="modal" data-bs-target="#modalEdit"
                                            data-id="{{ $l->id }}" data-name="{{ $l->name }}"
                                            data-parent="{{ $l->parent_id }}" data-desc="{{ $l->description }}"
                                            data-photo="{{ $l->photo_url }}">Edit</button>
                                    <form method="POST" action="{{ route('locations.destroy', $l) }}" class="d-inline"
                                          onsubmit="return confirm('Hapus lokasi {{ $l->name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada lokasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Edit --}}
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formEdit" class="modal-content" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Edit Lokasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Nama</label>
                    <input type="text" name="name" id="eName" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Berada di</label>
                    <select name="parent_id" id="eParent" class="form-select">
                        <option value="">- Lokasi utama -</option>
                        @foreach ($parents as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select></div>
                <div class="mb-3"><label class="form-label">Deskripsi</label>
                    <input type="text" name="description" id="eDesc" class="form-control"></div>

                <div class="mb-1">
                    <label class="form-label">Foto</label>
                    <div class="mb-2"><img id="eCurrent" class="photo-preview d-none" alt="Foto saat ini"></div>
                    <input type="file" name="photo" id="ePhoto" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <div class="form-text" id="ePhotoNote">Pilih foto baru untuk mengganti. JPG, PNG, atau WebP, maksimal 5 MB.</div>
                    <div class="form-check mt-2 d-none" id="eRemoveWrap">
                        <input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="eRemove">
                        <label class="form-check-label" for="eRemove">Hapus foto saat ini</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- Lihat foto --}}
<div class="modal fade" id="modalPhoto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="photoTitle">Foto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body text-center">
                <img id="photoFull" class="img-fluid rounded" alt="">
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var MAX = 5 * 1024 * 1024;

    // Pratinjau dan pemeriksaan ukuran di browser (pemeriksaan sebenarnya tetap di server)
    function bindPreview(input, img, note, defaultNote) {
        input.addEventListener('change', function () {
            var f = input.files[0];
            note.className = 'form-text';
            note.textContent = defaultNote;
            if (! f) { if (img.dataset.keep !== '1') img.classList.add('d-none'); return; }
            if (f.size > MAX) {
                input.value = '';
                note.className = 'form-text text-danger';
                note.textContent = 'Ukuran foto melebihi 5 MB. Pilih foto yang lebih kecil.';
                return;
            }
            img.src = URL.createObjectURL(f);
            img.classList.remove('d-none');
        });
    }

    bindPreview(document.getElementById('addPhoto'), document.getElementById('addPreview'),
                document.getElementById('addPhotoNote'), 'JPG, PNG, atau WebP, maksimal 5 MB.');

    var eCurrent = document.getElementById('eCurrent');
    bindPreview(document.getElementById('ePhoto'), eCurrent, document.getElementById('ePhotoNote'),
                'Pilih foto baru untuk mengganti. JPG, PNG, atau WebP, maksimal 5 MB.');

    // Isi form edit
    document.querySelectorAll('.btn-edit').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('formEdit').action = "{{ url('lokasi') }}/" + b.dataset.id;
            document.getElementById('eName').value   = b.dataset.name;
            document.getElementById('eParent').value = b.dataset.parent || '';
            document.getElementById('eDesc').value   = b.dataset.desc || '';
            document.getElementById('ePhoto').value  = '';
            document.getElementById('eRemove').checked = false;

            var has = !! b.dataset.photo;
            eCurrent.dataset.keep = has ? '1' : '0';
            if (has) { eCurrent.src = b.dataset.photo; eCurrent.classList.remove('d-none'); }
            else { eCurrent.removeAttribute('src'); eCurrent.classList.add('d-none'); }
            document.getElementById('eRemoveWrap').classList.toggle('d-none', ! has);
        });
    });

    // Lihat foto ukuran besar
    document.getElementById('modalPhoto').addEventListener('show.bs.modal', function (e) {
        var t = e.relatedTarget;
        document.getElementById('photoFull').src = t.dataset.src;
        document.getElementById('photoFull').alt = 'Foto ' + t.dataset.title;
        document.getElementById('photoTitle').textContent = t.dataset.title;
    });
})();
</script>
@endpush