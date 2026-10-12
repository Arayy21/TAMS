@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $first = explode(' ', trim(auth()->user()->name))[0];

    // [label, nilai, keterangan, ikon, warna, warna lembut, tautan]
    $inventory = [
        ['Total Aset',      $stats['jenis'],               $stats['unit'] . ' unit',                    'bi-box-seam',      '#DC143C', '#FBE7EB', route('assets.index')],
        ['Kondisi Baik',    $stats['baik']['jenis'],       $stats['baik']['unit'] . ' unit',            'bi-check-circle',  '#28A745', '#E3F4E8', route('assets.index', ['condition' => 'baik'])],
        ['Rusak',           $stats['rusak']['jenis'],      $stats['rusak']['unit'] . ' unit',           'bi-x-octagon',     '#BA1A1A', '#F8E3E3', route('assets.index', ['condition' => 'rusak'])],
        ['Dalam Perbaikan', $stats['perbaikan']['jenis'],  $stats['perbaikan']['unit'] . ' unit',       'bi-tools',         '#D97706', '#FBEFD9', route('assets.index', ['condition' => 'perbaikan'])],
    ];

    $activity = [
        ['Unit Tersedia',       $loan['tersedia'], 'dari ' . $stats['unit'] . ' unit',                  'bi-boxes',          '#28A745', '#E3F4E8', route('assets.index')],
        ['Sedang Dipinjam',     $loan['units'],    $loan['active'] . ' peminjaman aktif',               'bi-people',         '#5F5E5E', '#ECEAEA', route('loans.index', ['status' => 'dipinjam'])],
        ['Terlambat',           $loan['overdue'],  $loan['soon'] . ' segera jatuh tempo',               'bi-hourglass-split', $loan['overdue'] > 0 ? '#BA1A1A' : '#5F5E5E', $loan['overdue'] > 0 ? '#F8E3E3' : '#ECEAEA', route('loans.index', ['status' => 'terlambat'])],
        ['Mutasi Bulan Ini',    '+' . $mutasi['masuk'] . ' / -' . $mutasi['keluar'], $mutasi['total'] . ' transaksi', 'bi-arrow-left-right', '#DC143C', '#FBE7EB', route('movements.index')],
    ];

    $chartHeight = max(220, $perKategori->count() * 46);
@endphp

<style>
    .sec-label { font-size: 11px; font-weight: 700; letter-spacing: .9px; text-transform: uppercase; color: var(--muted); margin: 0 0 10px; }
    .kpi { position: relative; display: flex; align-items: center; gap: 14px; padding: 18px 20px; height: 100%;
           background: #fff; border: 1px solid var(--border); border-radius: 16px; overflow: hidden;
           text-decoration: none; color: inherit; transition: box-shadow .15s, transform .15s; }
    .kpi:hover { box-shadow: 0 8px 20px rgba(18, 18, 18, .08); transform: translateY(-1px); color: inherit; }
    .kpi::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: var(--c); }
    .kpi-icon { width: 46px; height: 46px; flex-shrink: 0; border-radius: 12px; display: flex; align-items: center;
                justify-content: center; font-size: 20px; color: var(--c); background: var(--cs); }
    .kpi-label { font-size: 13px; font-weight: 500; color: var(--muted); }
    .kpi-value { font-size: 28px; font-weight: 700; line-height: 1.1; color: var(--text); }
    .kpi-sub { font-size: 12px; color: var(--muted); }
    .dash-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 10px 14px;
                margin-bottom: 8px; background: var(--surface-2); border-radius: 10px; text-decoration: none; color: inherit; }
    a.dash-row:hover { background: #EFEDEC; color: inherit; }
    .dash-empty { text-align: center; color: var(--muted); padding: 28px 0; }
    .dash-empty i { font-size: 2rem; }
</style>

{{-- Sapaan dan tombol cepat --}}
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Selamat datang, {{ $first }}</h2>
        <div class="text-muted small">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('assets.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Aset</a>
        <a href="{{ route('scan.index') }}" class="btn btn-outline-secondary"><i class="bi bi-upc-scan"></i> Scan</a>
        <a href="{{ route('loans.create') }}" class="btn btn-outline-secondary"><i class="bi bi-people"></i> Pinjam</a>
    </div>
</div>

{{-- Kartu inventaris --}}
<div class="sec-label">Inventaris</div>
<div class="row g-3 mb-4">
    @foreach ($inventory as [$label, $value, $sub, $icon, $color, $soft, $url])
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ $url }}" class="kpi" style="--c: {{ $color }}; --cs: {{ $soft }}">
                <span class="kpi-icon"><i class="bi {{ $icon }}"></i></span>
                <span>
                    <span class="kpi-label d-block">{{ $label }}</span>
                    <span class="kpi-value d-block">{{ number_format($value) }}</span>
                    <span class="kpi-sub d-block">{{ $sub }}</span>
                </span>
            </a>
        </div>
    @endforeach
</div>

{{-- Kartu aktivitas --}}
<div class="sec-label">Aktivitas</div>
<div class="row g-3 mb-4">
    @foreach ($activity as [$label, $value, $sub, $icon, $color, $soft, $url])
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ $url }}" class="kpi" style="--c: {{ $color }}; --cs: {{ $soft }}">
                <span class="kpi-icon"><i class="bi {{ $icon }}"></i></span>
                <span>
                    <span class="kpi-label d-block">{{ $label }}</span>
                    <span class="kpi-value d-block">{{ is_numeric($value) ? number_format($value) : $value }}</span>
                    <span class="kpi-sub d-block">{{ $sub }}</span>
                </span>
            </a>
        </div>
    @endforeach
</div>

{{-- Grafik dan aset perlu perhatian --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-xl-7">
        <div class="card-tams p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 fw-semibold mb-0">Unit per Kategori</h2>
                <small class="text-muted">{{ $stats['unit'] }} unit &middot; {{ $stats['jenis'] }} jenis aset</small>
            </div>
            @if ($perKategori->sum('unit') > 0)
                <div style="height: {{ $chartHeight }}px"><canvas id="chartKategori"></canvas></div>
            @else
                <div class="dash-empty"><i class="bi bi-bar-chart d-block"></i>Belum ada data aset.</div>
            @endif
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card-tams p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 fw-semibold mb-0">Aset Perlu Perhatian</h2>
                <span class="small">
                    <a href="{{ route('assets.index', ['condition' => 'rusak']) }}" class="text-decoration-none">Rusak ({{ $stats['rusak']['jenis'] }})</a>
                    &middot;
                    <a href="{{ route('assets.index', ['condition' => 'perbaikan']) }}" class="text-decoration-none">Perbaikan ({{ $stats['perbaikan']['jenis'] }})</a>
                </span>
            </div>
            @forelse ($attention as $a)
                <a href="{{ route('assets.show', $a) }}" class="dash-row">
                    <span>
                        <span class="d-block fw-semibold small">{{ $a->asset_code }} &middot; {{ $a->name }}</span>
                        <span class="d-block text-muted" style="font-size:12px">{{ $a->category->name }} &middot; {{ $a->location->name }} &middot; {{ $a->quantity }} unit</span>
                    </span>
                    <span class="badge rounded-pill text-bg-{{ $a->condition_color }}">{{ ucfirst($a->asset_condition) }}</span>
                </a>
            @empty
                <div class="dash-empty"><i class="bi bi-check-circle text-success d-block"></i>Semua aset dalam kondisi baik.</div>
            @endforelse
        </div>
    </div>
</div>

{{-- Peminjaman dan stok opname --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-xl-7">
        <div class="card-tams p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 fw-semibold mb-0">Peminjaman Perlu Tindakan</h2>
                <a href="{{ route('loans.index') }}" class="small text-decoration-none">Lihat semua</a>
            </div>
            @forelse ($loansDue as $l)
                @php
                    $d   = $l->days_left;
                    $txt = $d < 0 ? 'Terlambat ' . abs($d) . ' hari' : ($d === 0 ? 'Jatuh tempo hari ini' : ($d === 1 ? 'Besok' : $d . ' hari lagi'));
                @endphp
                <a href="{{ route('loans.index', ['q' => $l->borrower_name]) }}" class="dash-row">
                    <span>
                        <span class="d-block fw-semibold small">{{ $l->borrower_name }}</span>
                        <span class="d-block text-muted" style="font-size:12px">
                            {{ $l->asset->asset_code }} &middot; {{ $l->asset->name }} &middot; {{ $l->quantity }} unit
                        </span>
                    </span>
                    <span class="text-end">
                        <span class="badge rounded-pill text-bg-{{ $d < 0 ? 'danger' : 'warning' }}">{{ $txt }}</span>
                        <span class="d-block text-muted" style="font-size:11px">batas {{ $l->due_at->format('d M Y') }}</span>
                    </span>
                </a>
            @empty
                <div class="dash-empty"><i class="bi bi-calendar-check text-success d-block"></i>Tidak ada peminjaman yang terlambat atau segera jatuh tempo.</div>
            @endforelse
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card-tams p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 fw-semibold mb-0">Stok Opname</h2>
                <a href="{{ route('opnames.index') }}" class="small text-decoration-none">Lihat semua</a>
            </div>
            @if ($opname)
                @php $pct = $opname->items_count ? round($opname->checked_count / $opname->items_count * 100) : 0; @endphp
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-semibold">{{ $opname->name }}</div>
                        <div class="text-muted small">
                            {{ $opname->code }} &middot; {{ $opname->opname_date->format('d M Y') }} &middot; {{ $opname->location->name ?? 'Seluruh aset' }}
                        </div>
                    </div>
                    <span class="badge rounded-pill text-bg-{{ $opname->status_color }}">{{ $opname->status_label }}</span>
                </div>
                <div class="d-flex justify-content-between small mb-1">
                    <span>{{ $opname->checked_count }} dari {{ $opname->items_count }} aset dicek</span>
                    <span>{{ $pct }}%</span>
                </div>
                <div class="progress mb-3" style="height:10px"><div class="progress-bar" style="width: {{ $pct }}%"></div></div>
                <a href="{{ route('opnames.show', $opname) }}" class="btn btn-sm {{ $opname->is_running ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $opname->is_running ? 'Lanjutkan Opname' : 'Lihat Hasil' }}
                </a>
            @else
                <div class="dash-empty">
                    <i class="bi bi-clipboard-check d-block"></i>
                    <p class="mb-2">Belum ada sesi stok opname.</p>
                    <a href="{{ route('opnames.create') }}" class="btn btn-sm btn-outline-primary">Buat Sesi</a>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Aktivitas terbaru --}}
<div class="card-tams p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 fw-semibold mb-0">Aktivitas Terbaru</h2>
        <a href="{{ route('histories.index') }}" class="small text-decoration-none">Lihat semua</a>
    </div>
    @forelse ($activities as $h)
        <div class="dash-row">
            <span class="d-flex flex-wrap align-items-center gap-2 small">
                <strong>{{ $h->user->name ?? 'Sistem' }}</strong>
                <span>{{ strtolower($h->action_label) }}</span>
                @if ($h->asset)
                    <a href="{{ route('assets.show', $h->asset_id) }}" class="fw-semibold text-decoration-none">{{ $h->asset->asset_code }}</a>
                @endif
                @if ($h->old_value !== null || $h->new_value !== null)
                    <span class="text-muted">({{ $h->change_text }})</span>
                @endif
            </span>
            <span class="text-muted small text-nowrap">{{ $h->created_at->locale('id')->diffForHumans() }}</span>
        </div>
    @empty
        <div class="dash-empty"><i class="bi bi-clock-history d-block"></i>Belum ada aktivitas.</div>
    @endforelse
</div>
@endsection

@push('scripts')
@if ($perKategori->sum('unit') > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    (function () {
        var jenis = @json($perKategori->pluck('jenis'));
        new Chart(document.getElementById('chartKategori'), {
            type: 'bar',
            data: {
                labels: @json($perKategori->pluck('name')),
                datasets: [{
                    label: 'Jumlah unit',
                    data: @json($perKategori->pluck('unit')),
                    backgroundColor: '#DC143C',
                    borderRadius: 6,
                    maxBarThickness: 28
                }]
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (c) { return c.parsed.x + ' unit'; },
                            afterLabel: function (c) { return jenis[c.dataIndex] + ' jenis aset'; }
                        }
                    }
                },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#EFEDEC' } },
                    y: { grid: { display: false } }
                }
            }
        });
    })();
</script>
@endif
@endpush