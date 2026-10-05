@extends('layouts.app')
@section('title', 'Data Aset')

@section('content')
<div class="card-tams p-4">
    <form method="GET" action="{{ route('assets.index') }}" class="row g-2 mb-3">
        <div class="col-12 col-lg-3">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari kode atau nama aset...">
        </div>
        <div class="col-6 col-lg-2">
            <select name="category_id" class="form-select">
                <option value="">Semua kategori</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <select name="location_id" class="form-select">
                <option value="">Semua lokasi</option>
                @foreach ($locations as $l)
                    <option value="{{ $l->id }}" @selected(request('location_id') == $l->id)>{{ $l->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-lg-1">
            <select name="condition" class="form-select">
                <option value="">Kondisi</option>
                @foreach (['baik', 'rusak', 'perbaikan'] as $v)
                    <option value="{{ $v }}" @selected(request('condition') == $v)>{{ ucfirst($v) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-lg-1">
            <select name="status" class="form-select">
                <option value="">Status</option>
                @foreach (['aktif', 'nonaktif'] as $v)
                    <option value="{{ $v }}" @selected(request('status') == $v)>{{ ucfirst($v) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-lg-3 d-flex gap-2">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Cari</button>
            <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary">Reset</a>
            <a href="{{ route('assets.create') }}" class="btn btn-primary ms-auto"><i class="bi bi-plus-lg"></i> Tambah Aset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>Kode</th><th>Nama Aset</th><th>Kategori</th><th>Lokasi</th>
                    <th>Kondisi</th><th>Status</th><th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assets as $a)
                    <tr>
                        <td><a href="{{ route('assets.show', $a) }}" class="fw-semibold text-decoration-none">{{ $a->asset_code }}</a></td>
                        <td>{{ $a->name }}</td>
                        <td>{{ $a->category->name }}</td>
                        <td>{{ $a->location->name }}</td>
                        <td><span class="badge rounded-pill text-bg-{{ $a->condition_color }}">{{ ucfirst($a->asset_condition) }}</span></td>
                        <td><span class="badge rounded-pill text-bg-{{ $a->status_color }}">{{ ucfirst($a->status) }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('assets.show', $a) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                            <a href="{{ route('assets.edit', $a) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1"></i>
                            <p class="mb-0">Tidak ada aset ditemukan.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">Menampilkan {{ $assets->firstItem() ?? 0 }}-{{ $assets->lastItem() ?? 0 }} dari {{ $assets->total() }} aset</small>
        {{ $assets->links() }}
    </div>
</div>
@endsection