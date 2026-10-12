@extends('layouts.app')
@section('title', 'Barang Masuk/Keluar')

@section('content')
@php
    $cards = [
        ['Masuk Bulan Ini',     $summary['masuk'] . ' unit',  '#28A745'],
        ['Keluar Bulan Ini',    $summary['keluar'] . ' unit', '#BA1A1A'],
        ['Transaksi Bulan Ini', $summary['transaksi'],        '#DC143C'],
    ];
@endphp

<div class="row g-3 mb-4">
    @foreach ($cards as [$label, $value, $color])
        <div class="col-12 col-md-4">
            <div class="card-tams stat-card p-3" style="border-left-color: {{ $color }}">
                <div class="stat-label">{{ $label }}</div>
                <div class="stat-value" style="color: {{ $color }}">{{ $value }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="card-tams p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h6 fw-semibold mb-0">Mutasi Stok</h2>
            <small class="text-muted">Pencatatan pembelian (masuk) dan barang rusak atau dibuang (keluar).</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('movements.create', ['jenis' => 'masuk']) }}" class="btn btn-success">
                <i class="bi bi-box-arrow-in-down"></i> Barang Masuk
            </a>
            <a href="{{ route('movements.create', ['jenis' => 'keluar']) }}" class="btn btn-danger">
                <i class="bi bi-box-arrow-up"></i> Barang Keluar
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('movements.index') }}" class="row g-2 mb-3">
        <div class="col-12 col-lg-3">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari kode, nama aset, atau nota...">
        </div>
        <div class="col-6 col-lg-2">
            <select name="type" class="form-select">
                <option value="">Semua jenis</option>
                <option value="masuk" @selected(request('type') === 'masuk')>Masuk</option>
                <option value="keluar" @selected(request('type') === 'keluar')>Keluar</option>
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <select name="reason" class="form-select">
                <option value="">Semua alasan</option>
                @foreach (['pembelian' => 'Pembelian', 'rusak' => 'Rusak', 'dibuang' => 'Dibuang'] as $k => $v)
                    <option value="{{ $k }}" @selected(request('reason') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <input type="date" name="dari" value="{{ request('dari') }}" class="form-control" title="Dari tanggal">
        </div>
        <div class="col-6 col-lg-2">
            <input type="date" name="sampai" value="{{ request('sampai') }}" class="form-control" title="Sampai tanggal">
        </div>
        <div class="col-12 col-lg-1 d-flex gap-2">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
            <a href="{{ route('movements.index') }}" class="btn btn-outline-secondary" title="Reset">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th><th>Aset</th><th>Jenis</th><th>Alasan</th>
                    <th class="text-center">Jumlah</th><th class="text-center">Stok</th>
                    <th>Nota</th><th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movements as $m)
                    <tr>
                        <td>{{ $m->moved_at->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('assets.show', $m->asset_id) }}" class="fw-semibold text-decoration-none">{{ $m->asset->asset_code }}</a>
                            <div class="small text-muted">{{ $m->asset->name }}</div>
                        </td>
                        <td>
                            <span class="badge rounded-pill text-bg-{{ $m->type === 'masuk' ? 'success' : 'danger' }}">{{ $m->type_label }}</span>
                        </td>
                        <td>{{ $m->reason_label }}</td>
                        <td class="text-center fw-bold {{ $m->type === 'masuk' ? 'text-success' : 'text-danger' }}">
                            {{ $m->type === 'masuk' ? '+' : '-' }}{{ $m->quantity }}
                        </td>
                        <td class="text-center">{{ $m->qty_before }} <i class="bi bi-arrow-right small text-muted"></i> <strong>{{ $m->qty_after }}</strong></td>
                        <td>
                            {{ $m->reference ?? '-' }}
                            @if ($m->notes) <div class="small text-muted">{{ $m->notes }}</div> @endif
                        </td>
                        <td>{{ $m->creator->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-arrow-left-right fs-1"></i>
                            <p class="mb-0">Belum ada pencatatan barang masuk atau keluar.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">Menampilkan {{ $movements->firstItem() ?? 0 }}-{{ $movements->lastItem() ?? 0 }} dari {{ $movements->total() }} mutasi</small>
        {{ $movements->links() }}
    </div>
</div>
@endsection