@extends('layouts.app')
@section('title', 'Detail Aset')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $loc     = $asset->location;
    $photo   = $loc->photo_url;
    $path    = ($loc->parent ? $loc->parent->name . ' › ' : '') . $loc->name;
    $others  = max(0, $roomTotal - 1);
@endphp

<style>
    .room-photo { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: 12px; border: 1px solid var(--border); display: block; }
    .room-none { width: 100%; aspect-ratio: 4 / 3; border-radius: 12px; border: 1px dashed #CFCFCF; color: #A3A3A3;
                 display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 12px; }
    .room-none i { font-size: 2rem; }
</style>

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
                    'Kode Aset'         => $asset->asset_code,
                    'Kategori'          => $asset->category->name,
                    'Lokasi'            => $path,
                    'Jumlah Unit'       => $asset->quantity . ' (tersedia ' . $asset->available . ')',
                    'Nomor Seri'        => $asset->serial_number ?? '-',
                    'Tanggal Pembelian' => $asset->purchase_date?->translatedFormat('d F Y') ?? '-',
                    'Dicatat'           => $asset->created_at?->translatedFormat('d F Y') ?? '-',
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

    <div class="col-12 col-xl-4">
        <div class="card-tams p-4 h-100 text-center d-flex flex-column">
            <h2 class="h6 fw-semibold text-start mb-3">Barcode Aset</h2>

            <div class="border rounded-3 bg-white p-4 mb-2 d-flex justify-content-center">
                <img src="{{ route('assets.barcode', $asset) }}" alt="Barcode {{ $asset->asset_code }}"
                     style="max-width:100%; height:auto">
            </div>

            <div class="fw-bold" style="letter-spacing:2px">{{ $asset->asset_code }}</div>
            <small class="text-muted mb-3">Code 128 &middot; hanya berisi kode aset</small>

            <div class="mt-auto d-grid gap-2">
                <a href="{{ route('assets.label', $asset) }}" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-printer"></i> Cetak Label
                </a>
                <a href="{{ route('assets.barcode', [$asset, 'unduh' => 1]) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-download"></i> Unduh SVG
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Lokasi aset --}}
<div class="card-tams p-4 mb-3">
    <div class="row g-4 align-items-center">
        <div class="col-12 col-md-5 col-xl-3">
            @if ($photo)
                <button type="button" class="btn p-0 border-0 w-100" data-bs-toggle="modal" data-bs-target="#modalRoom"
                        aria-label="Perbesar foto {{ $loc->name }}">
                    <img src="{{ $photo }}" alt="Foto {{ $loc->name }}" class="room-photo" loading="lazy">
                </button>
            @else
                <div class="room-none">
                    <i class="bi bi-image"></i>
                    <div class="small mt-1">Belum ada foto ruangan</div>
                    @if ($isAdmin)
                        <a href="{{ route('locations.index') }}" class="small text-decoration-none mt-1">Tambah di menu Lokasi</a>
                    @endif
                </div>
            @endif
        </div>

        <div class="col-12 col-md-7 col-xl-9">
            <div class="text-muted small mb-1">Lokasi Aset</div>
            <h2 class="h5 fw-bold mb-1">{{ $path }}</h2>
            <div class="text-muted small mb-3">{{ $loc->description ?: 'Tidak ada deskripsi lokasi.' }}</div>

            <div class="small fw-semibold mb-2">
                Aset lain di ruangan ini
                <span class="text-muted fw-normal">({{ $others }})</span>
            </div>
            @if ($roommates->isNotEmpty())
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach ($roommates as $r)
                        <a href="{{ route('assets.show', $r) }}" class="badge text-bg-light border text-decoration-none fw-medium">
                            {{ $r->asset_code }} &middot; {{ $r->name }}
                        </a>
                    @endforeach
                    @if ($others > $roommates->count())
                        <span class="badge text-bg-light border text-muted fw-normal">+{{ $others - $roommates->count() }} lainnya</span>
                    @endif
                </div>
            @else
                <div class="text-muted small mb-3">Tidak ada aset lain di ruangan ini.</div>
            @endif

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('assets.index', ['location_id' => $asset->location_id]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-list-ul"></i> Lihat semua aset di ruangan ini ({{ $roomTotal }})
                </a>
                @if ($isAdmin)
                    <a href="{{ route('locations.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-camera"></i> Kelola foto
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($isAdmin)
    <div class="card-tams p-4 mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h6 fw-semibold mb-0">Mutasi Stok (10 terbaru)</h2>
                <small class="text-muted">
                    Total masuk <strong class="text-success">{{ $movementTotals['masuk'] }}</strong> unit &middot;
                    total keluar <strong class="text-danger">{{ $movementTotals['keluar'] }}</strong> unit
                </small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('movements.create', ['jenis' => 'masuk', 'asset_id' => $asset->id]) }}" class="btn btn-sm btn-success">
                    <i class="bi bi-box-arrow-in-down"></i> Barang Masuk
                </a>
                @if ($asset->available > 0)
                    <a href="{{ route('movements.create', ['jenis' => 'keluar', 'asset_id' => $asset->id]) }}" class="btn btn-sm btn-danger">
                        <i class="bi bi-box-arrow-up"></i> Barang Keluar
                    </a>
                @else
                    <button type="button" class="btn btn-sm btn-danger" disabled title="Tidak ada unit yang tersedia untuk dikeluarkan">
                        <i class="bi bi-box-arrow-up"></i> Barang Keluar
                    </button>
                @endif
                <a href="{{ route('movements.index', ['q' => $asset->asset_code]) }}" class="btn btn-sm btn-outline-secondary">Lihat semua</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th><th>Jenis</th><th>Alasan</th>
                        <th class="text-center">Jumlah</th><th class="text-center">Stok</th>
                        <th>Nota</th><th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $m)
                        <tr>
                            <td>{{ $m->moved_at->format('d M Y') }}</td>
                            <td><span class="badge rounded-pill text-bg-{{ $m->type === 'masuk' ? 'success' : 'danger' }}">{{ $m->type_label }}</span></td>
                            <td>
                                {{ $m->reason_label }}
                                @if ($m->notes) <div class="small text-muted">{{ $m->notes }}</div> @endif
                            </td>
                            <td class="text-center fw-bold {{ $m->type === 'masuk' ? 'text-success' : 'text-danger' }}">
                                {{ $m->type === 'masuk' ? '+' : '-' }}{{ $m->quantity }}
                            </td>
                            <td class="text-center">{{ $m->qty_before }} <i class="bi bi-arrow-right small text-muted"></i> <strong>{{ $m->qty_after }}</strong></td>
                            <td>{{ $m->reference ?? '-' }}</td>
                            <td>{{ $m->creator->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">Belum ada barang masuk atau keluar untuk aset ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="card-tams p-4">
    <div class="d-flex justify-content-between mb-3">
        <h2 class="h6 fw-semibold mb-0">Riwayat Aset (10 terbaru)</h2>
        @if ($isAdmin)
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

@if ($photo)
    <div class="modal fade" id="modalRoom" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $path }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="{{ $photo }}" alt="Foto {{ $loc->name }}" class="img-fluid rounded" loading="lazy">
                </div>
            </div>
        </div>
    </div>
@endif
@endsection