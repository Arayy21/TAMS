@extends('layouts.app')
@section('title', 'Detail Aset')

@section('content')
<nav class="small text-muted mb-3">
    <a href="{{ route('assets.index') }}" class="text-decoration-none">Data Aset</a> / {{ $asset->asset_code }}
</nav>

<div class="row g-3 mb-3">
    <div class="col-12 col-xl-8">
        <div class="card-tams p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h2 class="h4 fw-bold mb-2">{{ $asset->name }}</h2>
                    <span class="badge rounded-pill text-bg-{{ $asset->condition_color }}">{{ ucfirst($asset->asset_condition) }}</span>
                    <span class="badge rounded-pill text-bg-{{ $asset->status_color }}">{{ ucfirst($asset->status) }}</span>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('assets.edit', $asset) }}" class="btn btn-primary btn-sm">Edit</a>
                    <form method="POST" action="{{ route('assets.destroy', $asset) }}"
                          onsubmit="return confirm('Hapus aset {{ $asset->asset_code }}? Riwayat tetap tersimpan.')">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm">Hapus</button>
                    </form>
                </div>
            </div>

            <div class="row g-3">
                @foreach ([
                    'Kode Aset' => $asset->asset_code,
                    'Kategori' => $asset->category->name,
                    'Lokasi' => $asset->location->name,
                    'Nomor Seri' => $asset->serial_number ?? '-',
                    'Tanggal Pembelian' => $asset->purchase_date?->translatedFormat('d F Y') ?? '-',
                    'Dicatat' => $asset->created_at?->translatedFormat('d F Y') ?? '-',
                    'Jumlah Unit' => $asset->quantity . ' (tersedia ' . $asset->available . ')',
                ] as $label => $value)
                    <div class="col-sm-6">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="fw-semibold">{{ $value }}</div>
                    </div>
                @endforeach
                <div class="col-12">
                    <div class="text-muted small">Keterangan</div>
                    <div>{{ $asset->description ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Slot barcode: diisi di Fase 21 --}}
    <div class="col-12 col-xl-4">
        <div class="card-tams p-4 h-100 text-center text-muted d-flex flex-column justify-content-center">
            <i class="bi bi-upc-scan fs-1"></i>
            <div class="fw-semibold mt-2">Barcode Aset</div>
            <small>Akan ditambahkan pada tahap Barcode.</small>
        </div>
    </div>
</div>

<div class="card-tams p-4">
    <div class="d-flex justify-content-between mb-3">
        <h2 class="h6 fw-semibold mb-0">Riwayat Aset (10 terbaru)</h2>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('histories.index', ['q' => $asset->asset_code]) }}" class="small text-decoration-none">Lihat semua</a>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>Waktu</th><th>Perubahan</th><th>Detail</th><th>Oleh</th></tr></thead>
            <tbody>
                @forelse ($histories as $h)
                    <tr>
                        <td>{{ $h->created_at->format('d M Y H:i') }}</td>
                        <td class="fw-semibold">{{ $h->action_label }}</td>
                        <td>{{ $h->change_text }}</td>
                        <td>{{ $h->user->name ?? 'Sistem' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">Belum ada riwayat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection