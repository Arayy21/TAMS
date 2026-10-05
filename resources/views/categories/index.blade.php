@extends('layouts.app')
@section('title', 'Kategori')

@section('content')
<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card-tams p-4">
            <h2 class="h6 fw-semibold mb-3">Tambah Kategori</h2>
            <form method="POST" action="{{ route('categories.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Nama <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Prefix Kode <span class="text-muted">(opsional)</span></label>
                    <input type="text" name="code_prefix" value="{{ old('code_prefix') }}" class="form-control" maxlength="10">
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
                <thead class="table-light"><tr><th>Nama</th><th>Prefix</th><th>Jumlah Aset</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($categories as $c)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $c->name }}</div>
                                <small class="text-muted">{{ $c->description }}</small>
                            </td>
                            <td>{{ $c->code_prefix ?? '-' }}</td>
                            <td>{{ $c->assets_count }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary btn-edit"
                                        data-bs-toggle="modal" data-bs-target="#modalEdit"
                                        data-id="{{ $c->id }}" data-name="{{ $c->name }}"
                                        data-prefix="{{ $c->code_prefix }}" data-desc="{{ $c->description }}">Edit</button>
                                <form method="POST" action="{{ route('categories.destroy', $c) }}" class="d-inline"
                                      onsubmit="return confirm('Hapus kategori {{ $c->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada kategori.</td></tr>
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
            <div class="modal-header"><h5 class="modal-title">Edit Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Nama</label>
                    <input type="text" name="name" id="eName" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Prefix Kode</label>
                    <input type="text" name="code_prefix" id="ePrefix" class="form-control" maxlength="10"></div>
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
        document.getElementById('formEdit').action = "{{ url('kategori') }}/" + b.dataset.id;
        document.getElementById('eName').value   = b.dataset.name;
        document.getElementById('ePrefix').value = b.dataset.prefix || '';
        document.getElementById('eDesc').value   = b.dataset.desc || '';
    }));
</script>
@endpush