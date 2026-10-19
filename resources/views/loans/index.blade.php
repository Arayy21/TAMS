@extends('layouts.app')
@section('title', 'Data Peminjam')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $cards = [
        ['Peminjaman Aktif',     $summary['aktif'],     '#DC143C'],
        ['Unit Sedang Dipinjam', $summary['unit'],      '#D97706'],
        ['Terlambat',            $summary['terlambat'], '#BA1A1A'],
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
        <div class="col-12 col-lg-5">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                   placeholder="Cari peminjam, aset, atau lokasi...">
        </div>
        <div class="col-6 col-lg-3">
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                @foreach (['dipinjam' => 'Dipinjam', 'segera' => 'Segera jatuh tempo', 'terlambat' => 'Terlambat', 'dikembalikan' => 'Dikembalikan'] as $k => $v)
                    <option value="{{ $k }}" @selected(request('status') == $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-lg-4">
            <select name="location_id" class="form-select" aria-label="Filter lokasi terakhir">
                <option value="">Semua lokasi terakhir</option>
                @foreach ($locations as $l)
                    <option value="{{ $l->id }}" @selected(request('location_id') == $l->id)>{{ $l->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Cari</button>
            <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">Reset</a>
            <span class="form-text d-none d-md-inline ms-2 mt-0">
                Pencarian juga mencocokkan lokasi awal, lokasi terakhir, dan detail lokasi.
            </span>
            <a href="{{ route('loans.create') }}" class="btn btn-primary ms-auto"><i class="bi bi-plus-lg"></i> Pinjam</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle tbl-cards">
            <thead class="table-light">
                <tr>
                    <th>Peminjam</th>
                    <th>Aset</th>
                    <th>Lokasi Awal</th>
                    <th>Lokasi Terakhir</th>
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
                    @php
                        $pb = $l->photos->where('stage', 'sebelum')->values();
                        $pa = $l->photos->where('stage', 'sesudah')->values();
                    @endphp
                    <tr class="{{ $l->is_overdue ? 'table-danger' : '' }}">

                        {{-- 1. Peminjam --}}
                        <td class="cell-title" data-label="Peminjam">
                            <div class="fw-semibold">{{ $l->borrower_name }}</div>
                            <small class="text-muted">{{ $l->borrower_contact ?? '-' }}</small>
                        </td>

                        {{-- 2. Aset --}}
                        <td data-label="Aset">
                            <div class="cv">
                                <a href="{{ route('assets.show', $l->asset_id) }}" class="fw-semibold text-decoration-none">{{ $l->asset->asset_code }}</a>
                                <div class="small text-muted">{{ $l->asset->name }}</div>
                            </div>
                        </td>

                        {{-- 3. Lokasi awal --}}
                        <td data-label="Lokasi awal">
                            <div class="cv">
                                @if ($l->origin_location_id)
                                    <div class="fw-medium">{{ $l->originLocation->full_name }}</div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </td>

                        {{-- 4. Lokasi terakhir --}}
                        <td data-label="Lokasi terakhir">
                            <div class="cv">
                                @if ($l->last_location_id)
                                    <div class="fw-medium">{{ $l->lastLocation->full_name }}</div>
                                    @if ($l->last_location_note)
                                        <div class="small text-muted">{{ $l->last_location_note }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">Belum dicatat</span>
                                @endif
                            </div>
                        </td>

                        {{-- 5. Dipinjam --}}
                        <td class="cell-num text-center fw-semibold" data-label="Dipinjam">{{ $l->quantity }}</td>

                        {{-- 6. Sisa tersedia --}}
                        <td class="cell-num text-center" data-label="Tersedia">
                            {{ $l->asset->available }}
                            <span class="text-muted small">dari {{ $l->asset->quantity }}</span>
                        </td>

                        {{-- 7. Tanggal pinjam --}}
                        <td data-label="Tgl pinjam">{{ $l->loaned_at->format('d M Y') }}</td>

                        {{-- 8. Batas kembali --}}
                        <td data-label="Batas kembali">
                            <div class="cv">
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
                            </div>
                        </td>

                        {{-- 9. Status dan foto bukti --}}
                        <td class="cell-status" data-label="Status">
                            <div class="cv">
                                <span class="badge rounded-pill text-bg-{{ $l->status_color }}">{{ $l->status_label }}</span>
                                @if ($l->photos->isNotEmpty())
                                    <button type="button" class="btn btn-link btn-sm p-0 d-block mt-1 text-decoration-none btn-photos"
                                            data-bs-toggle="modal" data-bs-target="#modalLoanPhotos"
                                            data-title="{{ $l->borrower_name }} &middot; {{ $l->asset->asset_code }}"
                                            data-before="{{ $pb->map(fn ($p) => $p->url)->toJson() }}"
                                            data-after="{{ $pa->map(fn ($p) => $p->url)->toJson() }}">
                                        <i class="bi bi-images"></i> {{ $pb->count() }} sebelum &middot; {{ $pa->count() }} sesudah
                                    </button>
                                @else
                                    <span class="d-block small text-muted mt-1">Tanpa foto</span>
                                @endif
                            </div>
                        </td>

                        {{-- 10. Aksi (admin) --}}
                        @if ($isAdmin)
                            <td class="cell-actions text-end text-nowrap">
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
                        <td colspan="{{ $isAdmin ? 10 : 9 }}" class="cell-empty text-center text-muted py-5">
                            <i class="bi bi-people fs-1"></i>
                            <p class="mb-0">{{ request()->hasAny(['q', 'status', 'location_id']) ? 'Tidak ada peminjaman yang cocok dengan pencarian.' : 'Belum ada data peminjaman.' }}</p>
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

{{-- Galeri foto bukti --}}
<div class="modal fade" id="modalLoanPhotos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lpTitle">Foto Bukti</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="fw-semibold small mb-2">Sebelum dipinjam</div>
                <div class="d-flex flex-wrap gap-2 mb-4" id="lpBefore"></div>
                <div class="fw-semibold small mb-2">Sesudah dikembalikan</div>
                <div class="d-flex flex-wrap gap-2" id="lpAfter"></div>
                <div class="small text-muted mt-3">Ketuk foto untuk membukanya dalam ukuran penuh.</div>
            </div>
        </div>
    </div>
</div>

{{-- Konfirmasi pengembalian (admin) --}}
@if ($isAdmin)
<div class="modal fade" id="modalReturn" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="formReturn" class="modal-content" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Pengembalian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3" id="returnText"></p>

                <label for="returned_at" class="form-label">Tanggal dikembalikan</label>
                <input type="date" name="returned_at" id="returned_at" class="form-control mb-3"
                       value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required>

                <label class="form-label">Foto Kondisi Barang Sesudah Dikembalikan <span class="text-muted">(opsional, maks. 3)</span></label>
                @include('loans._photo_picker', ['id' => 'photosAfter', 'name' => 'photos_after', 'max' => 3, 'showErrors' => false])
                <div class="form-text">Disarankan melampirkan foto sebagai bukti kondisi barang saat kembali.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success">Ya, Kembalikan</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    // Galeri foto bukti
    var modal = document.getElementById('modalLoanPhotos');

    function fill(id, raw) {
        var box = document.getElementById(id), list = [];
        box.innerHTML = '';
        try { list = JSON.parse(raw || '[]'); } catch (e) {}

        if (! list.length) {
            var s = document.createElement('span'); s.className = 'text-muted small'; s.textContent = 'Tidak ada foto.';
            box.append(s);
            return;
        }
        list.forEach(function (u) {
            var a = document.createElement('a'); a.href = u; a.target = '_blank'; a.rel = 'noopener';
            var im = document.createElement('img'); im.src = u; im.loading = 'lazy'; im.alt = 'Foto bukti';
            im.style.cssText = 'width:160px;height:120px;object-fit:cover;border-radius:10px;border:1px solid #EAEAEA';
            a.append(im); box.append(a);
        });
    }

    modal.addEventListener('show.bs.modal', function (e) {
        var t = e.relatedTarget;
        document.getElementById('lpTitle').textContent = 'Foto Bukti: ' + t.dataset.title;
        fill('lpBefore', t.dataset.before);
        fill('lpAfter', t.dataset.after);
    });
})();
</script>

@if (auth()->user()->isAdmin())
<script>
    document.querySelectorAll('.btn-return').forEach(b => b.addEventListener('click', () => {
        document.getElementById('formReturn').action = "{{ url('peminjam') }}/" + b.dataset.id + "/kembali";
        document.getElementById('returnText').textContent = b.dataset.text + '.';
        document.getElementById('returned_at').min = b.dataset.min;
    }));
</script>
@endif
@endpush