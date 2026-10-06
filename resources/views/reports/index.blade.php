@extends('layouts.app')
@section('title', 'Laporan')

@section('content')
@include('reports._style')

<div class="card-tams p-4">
    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $type === 'aset' ? 'active' : '' }}" href="{{ route('reports.index', ['jenis' => 'aset']) }}">Daftar Aset</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $type === 'peminjaman' ? 'active' : '' }}" href="{{ route('reports.index', ['jenis' => 'peminjaman']) }}">Peminjaman</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $type === 'opname' ? 'active' : '' }}" href="{{ route('reports.index', ['jenis' => 'opname']) }}">Stok Opname</a>
        </li>
    </ul>

    <form method="GET" action="{{ route('reports.index') }}" class="row g-2 mb-2">
        <input type="hidden" name="jenis" value="{{ $type }}">

        @if ($type === 'aset')
            <div class="col-6 col-lg-3">
                <select name="category_id" class="form-select">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <select name="location_id" class="form-select">
                    <option value="">Semua lokasi</option>
                    @foreach ($locations as $l)
                        <option value="{{ $l->id }}" @selected(request('location_id') == $l->id)>{{ $l->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <select name="condition" class="form-select">
                    <option value="">Semua kondisi</option>
                    @foreach (['baik', 'rusak', 'perbaikan'] as $v)
                        <option value="{{ $v }}" @selected(request('condition') == $v)>{{ ucfirst($v) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <select name="status" class="form-select">
                    <option value="">Semua status</option>
                    @foreach (['aktif', 'nonaktif'] as $v)
                        <option value="{{ $v }}" @selected(request('status') == $v)>{{ ucfirst($v) }}</option>
                    @endforeach
                </select>
            </div>
                @elseif ($type === 'opname')
            <div class="col-12 col-lg-5">
                <select name="opname_id" class="form-select">
                    <option value="">Semua sesi</option>
                    @foreach ($opnames as $o)
                        <option value="{{ $o->id }}" @selected(request('opname_id') == $o->id)>
                            {{ $o->code }} - {{ $o->name }} ({{ $o->opname_date->format('d M Y') }}, {{ $o->status_label }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <select name="location_id" class="form-select">
                    <option value="">Semua ruangan</option>
                    @foreach ($locations as $l)
                        <option value="{{ $l->id }}" @selected(request('location_id') == $l->id)>{{ $l->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-4">
                <select name="hasil" class="form-select">
                    <option value="">Semua aset</option>
                    <option value="selisih" @selected(request('hasil') === 'selisih')>Hanya selisih / kondisi berbeda</option>
                    <option value="belum" @selected(request('hasil') === 'belum')>Hanya yang belum dicek</option>
                </select>
            </div>
        @else
            <div class="col-12 col-lg-4">
                <select name="status" class="form-select">
                    <option value="">Semua status</option>
                    @foreach (['dipinjam' => 'Dipinjam', 'segera' => 'Segera jatuh tempo', 'terlambat' => 'Terlambat', 'dikembalikan' => 'Dikembalikan'] as $k => $v)
                        <option value="{{ $k }}" @selected(request('status') == $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-4">
                <div class="input-group">
                    <span class="input-group-text">Dari</span>
                    <input type="date" name="dari" value="{{ request('dari') }}" class="form-control">
                </div>
            </div>
            <div class="col-6 col-lg-4">
                <div class="input-group">
                    <span class="input-group-text">Sampai</span>
                    <input type="date" name="sampai" value="{{ request('sampai') }}" class="form-control">
                </div>
            </div>
        @endif

        <div class="col-12 d-flex flex-wrap gap-2 align-items-center mt-2">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Tampilkan</button>
            <a href="{{ route('reports.index', ['jenis' => $type]) }}" class="btn btn-outline-secondary">Reset</a>

            <div class="ms-auto d-flex flex-wrap gap-2">
                <a href="{{ route('reports.print', request()->query()) }}" target="_blank" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Cetak
                </a>
                <a href="{{ route('reports.pdf', request()->query()) }}" class="btn btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf"></i> PDF
                </a>
                <a href="{{ route('reports.word', request()->query()) }}" class="btn btn-outline-word">
                    <i class="bi bi-file-earmark-word"></i> Word
                </a>
                <a href="{{ route('reports.excel', request()->query()) }}" class="btn btn-outline-success">
                    <i class="bi bi-file-earmark-excel"></i> Excel
                </a>
                <a href="{{ route('reports.csv', request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-filetype-csv"></i> CSV
                </a>
            </div>
        </div>
        <div class="form-text">Klik <strong>Tampilkan</strong> dulu setelah mengubah filter, agar hasil cetak dan unduhan sesuai.</div>
    </form>

    <hr>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h6 fw-semibold mb-0">Pratinjau {{ ['aset' => 'Daftar Aset', 'peminjaman' => 'Peminjaman', 'opname' => 'Hasil Stok Opname'][$type] }}</h2>
        <small class="text-muted">{{ $data->count() }} data</small>
    </div>

    @if ($type === 'opname' && $filters)
        <div class="small text-muted mb-2">
            @foreach ($filters as $k => $v)
                {{ $k }}: <strong>{{ $v }}</strong>@if (! $loop->last) &middot; @endif
            @endforeach
        </div>
    @endif

    @if ($type === 'opname' && $opnames->isEmpty())
        <div class="text-center text-muted py-4">
            <i class="bi bi-clipboard-check fs-1"></i>
            <p class="mb-0">Belum ada sesi stok opname. Buat sesi lebih dulu di menu Stok Opname.</p>
        </div>
    @else
        <div class="table-responsive">
            @include('reports._table_' . ['aset' => 'assets', 'peminjaman' => 'loans', 'opname' => 'opname'][$type])
        </div>
    @endif
</div>
@endsection