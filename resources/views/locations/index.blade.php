@extends('layouts.app')
@section('title', 'Lokasi')

@section('content')
<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card-tams p-4">
            <h2 class="h6 fw-semibold mb-3">Tambah Lokasi</h2>
            <form method="POST" action="{{ route('locations.store') }}">
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
                <button class="btn btn-primary w-100">Simpan</button>
            </form>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        <div class="card-tams p-4">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Nama</th><th>Berada di</th><th>Jumlah Aset</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($locations as $l)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $l->name }}</div>
                                <small class="text-muted">{{ $l->description }}</small>
                            </td>
                            <td>{{ $l->parent->name ?? '-' }}</td>
                            <td>{{ $l->assets_count }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary btn-edit"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit"
                                        data-id="{{ $l->id }}" data-name="{{ $l->name }}"
                                        data-parent="{{ $l->parent_id }}" data-desc="{{ $l->description }}">Edit</button>
                                <form method="POST" action="{{ route('locations.destroy', $l) }}" class="d-inline"
                                      onsubmit="return confirm('Hapus lokasi {{ $l->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada lokasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formEdit" class="modal-content">
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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.btn-edit').forEach(b => b.addEventListener('click', () => {
        document.getElementById('formEdit').action = "{{ url('lokasi') }}/" + b.dataset.id;
        document.getElementById('eName').value   = b.dataset.name;
        document.getElementById('eParent').value = b.dataset.parent || '';
        document.getElementById('eDesc').value   = b.dataset.desc || '';
    }));
</script>
@endpush