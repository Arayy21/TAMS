@extends('layouts.app')
@section('title', 'Stok Opname')

@section('content')
<div class="card-tams p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h6 fw-semibold mb-0">Sesi Stok Opname</h2>
            <small class="text-muted">Pengecekan fisik aset dibandingkan dengan data sistem.</small>
        </div>
        <a href="{{ route('opnames.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Buat Sesi
        </a>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>Kode</th><th>Nama Sesi</th><th>Tanggal</th><th>Cakupan</th>
                    <th style="min-width:160px">Progres</th><th>Status</th><th>Dibuat oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($opnames as $o)
                    @php $pct = $o->items_count ? round($o->checked_count / $o->items_count * 100) : 0; @endphp
                    <tr>
                        <td class="fw-semibold">{{ $o->code }}</td>
                        <td>{{ $o->name }}</td>
                        <td>{{ $o->opname_date->format('d M Y') }}</td>
                        <td>{{ $o->location->name ?? 'Seluruh aset' }}</td>
                        <td>
                            <div class="progress" style="height:8px">
                                <div class="progress-bar" style="width:{{ $pct }}%"></div>
                            </div>
                            <small class="text-muted">{{ $o->checked_count }} / {{ $o->items_count }} aset dicek</small>
                        </td>
                        <td><span class="badge rounded-pill text-bg-{{ $o->status_color }}">{{ $o->status_label }}</span></td>
                        <td>{{ $o->creator->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-clipboard-check fs-1"></i>
                            <p class="mb-0">Belum ada sesi stok opname.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">Total {{ $opnames->total() }} sesi</small>
        {{ $opnames->links() }}
    </div>
</div>
@endsection