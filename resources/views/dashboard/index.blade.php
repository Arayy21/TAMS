@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $badge = ['baik' => 'success', 'rusak' => 'danger', 'perbaikan' => 'warning'];
    $cards = [
        ['Total Aset',        $stats['total'],     '#2563EB'],
        ['Kondisi Baik',      $stats['baik'],      '#16A34A'],
        ['Rusak',             $stats['rusak'],     '#DC2626'],
        ['Dalam Perbaikan',   $stats['perbaikan'], '#D97706'],
    ];
@endphp

{{-- Kartu ringkasan --}}
<div class="row g-3 mb-4">
    @foreach ($cards as [$label, $value, $color])
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-tams stat-card p-3" style="border-left-color: {{ $color }}">
                <div class="stat-label">{{ $label }}</div>
                <div class="stat-value" style="color: {{ $color }}">{{ number_format($value) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    {{-- Grafik per kategori --}}
    <div class="col-12 col-xl-6">
        <div class="card-tams p-4 h-100">
            <h2 class="h6 fw-semibold mb-3">Aset per Kategori</h2>
            @if ($perKategori->sum('total') > 0)
                <div style="height:260px"><canvas id="chartKategori"></canvas></div>
            @else
                <div class="text-center text-muted py-5">
                    <i class="bi bi-bar-chart fs-1"></i>
                    <p class="mb-0">Belum ada data aset.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Aset perlu perhatian --}}
    <div class="col-12 col-xl-6">
        <div class="card-tams p-4 h-100">
            <h2 class="h6 fw-semibold mb-3">Aset Perlu Perhatian</h2>
            @forelse ($perhatian as $a)
                <div class="item-row d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="fw-semibold small">{{ $a->asset_code }} &middot; {{ $a->name }}</div>
                        <div class="text-muted" style="font-size:12px">{{ $a->category }} &middot; {{ $a->location }}</div>
                    </div>
                    <span class="badge rounded-pill text-bg-{{ $badge[$a->asset_condition] ?? 'secondary' }}">
                        {{ ucfirst($a->asset_condition) }}
                    </span>
                </div>
            @empty
                <div class="text-center text-muted py-5">
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                    <p class="mb-0">Tidak ada aset yang perlu perhatian.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Aktivitas terbaru --}}
<div class="card-tams p-4">
    <h2 class="h6 fw-semibold mb-3">Aktivitas Terbaru</h2>
    @forelse ($aktivitas as $h)
        @php
            $label = match ($h->action) {
                'create'    => 'menambah aset',
                'update'    => 'memperbarui data',
                'location'  => 'mengubah lokasi',
                'condition' => 'mengubah kondisi',
                'status'    => 'mengubah status',
                'delete'    => 'menghapus aset',
                default     => 'memulihkan aset',
            };
        @endphp
        <div class="item-row d-flex gap-3 align-items-center mb-2 small">
            <span class="text-muted" style="min-width:110px">
                {{ \Carbon\Carbon::parse($h->created_at)->locale('id')->diffForHumans() }}
            </span>
            <span>
                <strong>{{ $h->user_name ?? 'Sistem' }}</strong> {{ $label }}
                <strong>{{ $h->asset_code }}</strong>
                @if ($h->old_value || $h->new_value)
                    <span class="text-muted">({{ $h->old_value ?? '-' }} &rarr; {{ $h->new_value ?? '-' }})</span>
                @endif
            </span>
        </div>
    @empty
        <div class="text-center text-muted py-4">
            <i class="bi bi-clock-history fs-1"></i>
            <p class="mb-0">Belum ada aktivitas.</p>
        </div>
    @endforelse
</div>
@endsection

@push('scripts')
@if ($perKategori->sum('total') > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('chartKategori'), {
        type: 'bar',
        data: {
            labels: @json($perKategori->pluck('name')),
            datasets: [{
                label: 'Jumlah aset',
                data: @json($perKategori->pluck('total')),
                backgroundColor: '#2563EB',
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
</script>
@endif
@endpush