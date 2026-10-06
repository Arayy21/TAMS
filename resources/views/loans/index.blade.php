@extends('layouts.app')
@section('title', 'Data Peminjam')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $cards = [
        ['Peminjaman Aktif',     $summary['aktif'],     '#2563EB'],
        ['Unit Sedang Dipinjam', $summary['unit'],      '#D97706'],
        ['Terlambat',            $summary['terlambat'], '#DC2626'],
    ];
@endphp

@unless ($isAdmin)
    <div class="alert alert-light border d-flex align-items-center gap-2 py-2 small" role="note">
        <i class="bi bi-eye"></i>
        <span>Halaman ini hanya untuk <strong>melihat</strong> data. Untuk mencatat peminjaman, gunakan tombol <strong>Pinjam</strong>.
        Pengembalian dan perubahan data dilakukan oleh admin.</span>
    </div>
@endunless

<div class="row g-3 mb-4">
    @foreach ($cards as [$label, $value, $color])
        <div class="col-12 col-md-4">
            <div class="card-tams stat-card p-3" style="border-left-color: {{ $color }}">
                <div class="stat-label">{{ $label }}</div>
                <div class="stat-value" style="color: {{ $color }}">{{ number_format($value) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="card-tams p-4">
    <form method="GET" action="{{ route('loans.index') }}" class="row g-2 mb-3">
        <div class="col-12 col-md-5">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                   placeholder="Cari nama peminjam, kode, atau nama aset...">
        </div>
        <div class="col-6 col-md-3">
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                @foreach (['dipinjam' => 'Dipinjam', 'segera' => 'Segera jatuh tempo', 'terlambat' => 'Terlambat', 'dikembalikan' => 'Dikembalikan'] as $k => $v)
                    <option value="{{ $k }}" @selected(request('status') == $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4 d-flex gap-2">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Cari</button>
            <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">Reset</a>
            <a href="{{ route('loans.create') }}" class="btn btn-primary ms-auto">
                <i class="bi bi-plus-lg"></i> Pinjam
            </a>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>Peminjam</th>
                    <th>Aset</th>
                    <th class="text-center">Dipinjam</th>
                    <th class="text-center">Sisa Tersedia</th>
                    <th>Tgl Pinjam</th>
                    <th>Batas Kembali</th>
                    <th>Status</th>
                    @if ($isAdmin) <th class="text-end">Aksi</th> @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($loans as $l)
                    <tr class="{{ $l->is_overdue ? 'table-danger' : '' }}">
                        <td>
                            <div class="fw-semibold">{{ $l->borrower_name }}</div>
                            <small class="text-muted">{{ $l->borrower_contact ?? '-' }}</small>
                        </td>
                        <td>
                            <a href="{{ route('assets.show', $l->asset_id) }}" class="fw-semibold text-decoration-none">{{ $l->asset->asset_code }}</a>
                            <div class="small text-muted">{{ $l->asset->name }}</div>
                        </td>
                        <td class="text-center fw-semibold">{{ $l->quantity }}</td>
                        <td class="text-center">
                            {{ $l->asset->available }}
                            <span class="text-muted small">dari {{ $l->asset->quantity }}</span>
                        </td>
                        <td>{{ $l->loaned_at->format('d M Y') }}</td>
                        <td>
                            {{ $l->due_at?->format('d M Y') ?? '-' }}
                            @if (! is_null($l->days_left))
                                <div class="small {{ $l->days_left < 0 ? 'text-danger fw-semibold' : ($l->days_left <= \App\Models\Loan::DUE_SOON_DAYS ? 'text-warning-emphasis fw-semibold' : 'text-muted') }}">
                                    @if ($l->days_left < 0) Terlambat {{ abs($l->days_left) }} hari
                                    @elseif ($l->days_left === 0) Jatuh tempo hari ini
                                    @else {{ $l->days_left }} hari lagi
                                    @endif
                                </div>
                            @endif
                            @if ($l->returned_at)
                                <div class="small text-muted">Kembali {{ $l->returned_at->format('d M Y') }}</div>
                            @endif
                        </td>
                        <td><span class="badge rounded-pill text-bg-{{ $l->status_color }}">{{ $l->status_label }}</span></td>
                        @if ($isAdmin)
                        <td class="text-end text-nowrap">
                            <a href="{{ route('loans.edit', $l) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Edit
                            </a>

                            @if ($l->is_active)
                                <button type="button" class="btn btn-sm btn-outline-success btn-return"
                                        data-bs-toggle="modal" data-bs-target="#modalReturn"
                                        data-id="{{ $l->id }}"
                                        data-min="{{ $l->loaned_at->format('Y-m-d') }}"
                                        data-text="{{ $l->borrower_name }} mengembalikan {{ $l->quantity }} unit {{ $l->asset->name }} ({{ $l->asset->asset_code }})">
                                    <i class="bi bi-arrow-return-left"></i> Kembalikan
                                </button>
                            @endif
                        </td>
                    @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 8 : 7 }}" class="text-center text-muted py-5">
                            <i class="bi bi-people fs-1"></i>
                            <p class="mb-0">Belum ada data peminjaman.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">Menampilkan {{ $loans->firstItem() ?? 0 }}-{{ $loans->lastItem() ?? 0 }} dari {{ $loans->total() }} peminjaman</small>
        {{ $loans->links() }}
    </div>
</div>
@endsection

@if (auth()->user()->isAdmin())
@push('scripts')
<div class="modal fade" id="modalReturn" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="formReturn" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Pengembalian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3" id="returnText"></p>
                <label for="returned_at" class="form-label">Tanggal dikembalikan</label>
                <input type="date" name="returned_at" id="returned_at" class="form-control"
                       value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success">Ya, Kembalikan</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.btn-return').forEach(b => b.addEventListener('click', () => {
        document.getElementById('formReturn').action = "{{ url('peminjam') }}/" + b.dataset.id + "/kembali";
        document.getElementById('returnText').textContent = b.dataset.text + '.';
        document.getElementById('returned_at').min = b.dataset.min;
    }));
</script>
@endpush
@endif