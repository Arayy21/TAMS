@extends('layouts.app')
@section('title', 'Laporan')

@section('content')
@include('reports._style')

@php
    $tabs  = ['aset' => 'Daftar Aset', 'peminjaman' => 'Peminjaman', 'opname' => 'Stok Opname', 'mutasi' => 'Mutasi Stok'];
    $parts = ['aset' => 'assets', 'peminjaman' => 'loans', 'opname' => 'opname', 'mutasi' => 'movements'];
@endphp

<div class="card-tams p-4">
    <ul class="nav nav-pills mb-3 flex-wrap">
        @foreach ($tabs as $key => $label)
            <li class="nav-item">
                <a class="nav-link {{ $type === $key ? 'active' : '' }}" href="{{ route('reports.index', ['jenis' => $key]) }}">{{ $label }}</a>
            </li>
        @endforeach
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
        @elseif ($type === 'mutasi')
            <div class="col-6 col-lg-2">
                <select name="tipe" class="form-select">
                    <option value="">Semua jenis</option>
                    <option value="masuk" @selected(request('tipe') === 'masuk')>Masuk</option>
                    <option value="keluar" @selected(request('tipe') === 'keluar')>Keluar</option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <select name="alasan" class="form-select">
                    <option value="">Semua alasan</option>
                    @foreach (['pembelian' => 'Pembelian', 'rusak' => 'Rusak', 'dibuang' => 'Dibuang'] as $k => $v)
                        <option value="{{ $k }}" @selected(request('alasan') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-lg-2">
                <select name="category_id" class="form-select">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <div class="input-group">
                    <span class="input-group-text">Dari</span>
                    <input type="date" name="dari" value="{{ request('dari') }}" class="form-control">
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="input-group">
                    <span class="input-group-text">Sampai</span>
                    <input type="date" name="sampai" value="{{ request('sampai') }}" class="form-control">
                </div>
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
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="foto" value="1" id="foto" @checked(request('foto'))>
                    <label class="form-check-label" for="foto">
                        Sertakan <strong>foto bukti</strong> (sebelum dan sesudah dikembalikan) pada cetak, PDF, dan Word
                    </label>
                    <div class="form-text">Satu halaman per peminjaman, maksimal 30 peminjaman berfoto. Excel dan CSV hanya memuat jumlah foto.</div>
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
        <h2 class="h6 fw-semibold mb-0">Pratinjau {{ $tabs[$type] }}</h2>
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
            @include('reports._table_' . $parts[$type])
        </div>
    @endif

    {{-- Pratinjau lampiran foto bukti --}}
    @if ($type === 'peminjaman' && $evidence)
        <hr>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 fw-semibold mb-0"><i class="bi bi-images"></i> Lampiran Foto Bukti</h2>
            <small class="text-muted">{{ $evidence['loans']->count() }} dari {{ $evidence['total'] }} peminjaman berfoto</small>
        </div>

        @if ($evidence['limited'])
            <div class="alert alert-warning small py-2">
                Hanya {{ $evidence['limit'] }} peminjaman berfoto pertama yang disertakan dalam cetak, PDF, dan Word.
                Persempit filter (status atau tanggal) untuk melihat sisanya.
            </div>
        @endif

        @forelse ($evidence['loans'] as $l)
            @php
                $before = $l->photos->where('stage', 'sebelum')->values();
                $after  = $l->photos->where('stage', 'sesudah')->values();
            @endphp
            <div class="p-3 mb-3" style="background:var(--surface-2); border-radius:12px">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                    <div>
                        <strong>{{ $l->borrower_name }}</strong>
                        <span class="text-muted small">&middot; {{ $l->asset->asset_code }} {{ $l->asset->name }} &middot; {{ $l->quantity }} unit</span>
                    </div>
                    <span class="badge rounded-pill text-bg-{{ $l->status_color }}">{{ $l->status_label }}</span>
                </div>
                <div class="row g-3">
                    @foreach ([['Sebelum dipinjam', $before], ['Sesudah dikembalikan', $after]] as [$ttl, $list])
                        <div class="col-12 col-md-6">
                            <div class="small fw-semibold mb-1">{{ $ttl }} ({{ $list->count() }})</div>
                            <div class="d-flex flex-wrap gap-2">
                                @forelse ($list as $p)
                                    <a href="{{ $p->url }}" target="_blank" rel="noopener">
                                        <img src="{{ $p->url }}" alt="{{ $ttl }}" loading="lazy"
                                             style="width:110px;height:84px;object-fit:cover;border-radius:8px;border:1px solid #EAEAEA">
                                    </a>
                                @empty
                                    <span class="small text-muted">Tidak ada foto.</span>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-3 small">Tidak ada peminjaman berfoto pada filter ini.</div>
        @endforelse
    @endif
</div>
@endsection