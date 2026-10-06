@extends('layouts.app')
@section('title', 'Selesaikan Opname')

@section('content')
@php
    $cards = [
        ['Sesuai',           $stats['match'], '#28A745', 'aset'],
        ['Kurang',           $stats['short'], '#BA1A1A', $stats['short_units'] . ' unit'],
        ['Lebih',            $stats['over'],  '#D97706', $stats['over_units'] . ' unit'],
        ['Kondisi Berbeda',  $stats['cond'],  '#DC143C', 'aset'],
        ['Belum Dicek',      $stats['unchecked'], '#5F5E5E', 'tidak diubah'],
    ];
    $condColor = ['baik' => 'success', 'rusak' => 'danger', 'perbaikan' => 'warning'];
@endphp

<nav class="small text-muted mb-3">
    <a href="{{ route('opnames.index') }}" class="text-decoration-none">Stok Opname</a> /
    <a href="{{ route('opnames.show', $opname) }}" class="text-decoration-none">{{ $opname->code }}</a> / Selesaikan
</nav>

<div class="card-tams p-4 mb-3">
    <h2 class="h5 fw-bold mb-1">{{ $opname->name }}</h2>
    <div class="text-muted small">
        {{ $opname->code }} &middot; {{ $opname->opname_date->format('d M Y') }} &middot;
        {{ $stats['checked'] }} dari {{ $stats['total'] }} aset sudah dicek
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach ($cards as [$label, $value, $color, $sub])
        <div class="col-6 col-xl">
            <div class="card-tams stat-card p-3" style="border-left-color: {{ $color }}">
                <div class="stat-label">{{ $label }}</div>
                <div class="stat-value" style="color: {{ $color }}">{{ $value }}</div>
                <div class="small text-muted">{{ $sub }}</div>
            </div>
        </div>
    @endforeach
</div>

@if ($stats['unchecked'] > 0)
    <div class="alert alert-warning d-flex gap-2 align-items-start" role="alert">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div><strong>{{ $stats['unchecked'] }} aset belum dicek.</strong> Aset tersebut dibiarkan apa adanya dan tidak ikut disesuaikan.
            <a href="{{ route('opnames.show', $opname) }}" class="alert-link">Kembali untuk melengkapi</a></div>
    </div>
@endif

<div class="card-tams p-4 mb-3">
    <h2 class="h6 fw-semibold mb-3">Aset dengan Selisih atau Perubahan Kondisi</h2>

    @if ($problems->isEmpty())
        <div class="text-center text-muted py-4">
            <i class="bi bi-check-circle fs-1 text-success"></i>
            <p class="mb-0">Semua aset yang dicek sesuai dengan data sistem.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Aset</th>
                        <th class="text-center">Seharusnya</th>
                        <th class="text-center">Fisik</th>
                        <th>Selisih</th>
                        <th>Kondisi</th>
                        <th>Jika Diterapkan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($problems as $p)
                        @php $d = $p->difference; @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $p->asset->asset_code }}</div>
                                <small class="text-muted">{{ $p->asset->name }}</small>
                            </td>
                            <td class="text-center">{{ $p->expected_qty }}</td>
                            <td class="text-center fw-semibold">{{ $p->physical_qty }}</td>
                            <td>
                                @if ($d === 0) <span class="text-muted">-</span>
                                @elseif ($d < 0) <span class="badge rounded-pill text-bg-danger">Kurang {{ abs($d) }}</span>
                                @else <span class="badge rounded-pill text-bg-warning">Lebih {{ $d }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($p->physical_condition === $p->system_condition)
                                    <span class="text-muted">-</span>
                                @else
                                    <span class="badge rounded-pill text-bg-{{ $condColor[$p->system_condition] }}">{{ ucfirst($p->system_condition) }}</span>
                                    <i class="bi bi-arrow-right small"></i>
                                    <span class="badge rounded-pill text-bg-{{ $condColor[$p->physical_condition] }}">{{ ucfirst($p->physical_condition) }}</span>
                                @endif
                            </td>
                            <td class="small">
                                @if (! $p->preview_ok)
                                    <span class="text-danger"><i class="bi bi-exclamation-circle"></i> Dilewati, tinjau manual</span>
                                @elseif ($d !== 0)
                                    Jumlah {{ $p->asset->quantity }} <i class="bi bi-arrow-right"></i> <strong>{{ $p->preview_qty }}</strong>
                                @else
                                    Jumlah tetap
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="card-tams p-4">
    <form method="POST" action="{{ route('opnames.finish', $opname) }}" id="formFinish">
        @csrf

        <div class="form-check mb-1">
            <input class="form-check-input" type="checkbox" name="apply" value="1" id="apply" @checked(old('apply'))>
            <label class="form-check-label fw-semibold" for="apply">Terapkan hasil ke data aset</label>
        </div>
        <div class="form-text mb-3 ms-4">
            Jumlah dan kondisi aset disesuaikan dengan hasil hitung, dan setiap perubahan dicatat di Riwayat Aset.
            Jika tidak dicentang, hasil opname hanya disimpan sebagai catatan dan data aset tidak berubah.
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input @error('confirm') is-invalid @enderror" type="checkbox" name="confirm" value="1" id="confirm" required>
            <label class="form-check-label" for="confirm">
                Saya memahami bahwa sesi yang sudah diselesaikan <strong>tidak dapat diubah lagi</strong>.
            </label>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" id="btnFinish">
                <i class="bi bi-check2-circle"></i> <span id="btnLabel">Selesaikan Opname</span>
            </button>
            <a href="{{ route('opnames.show', $opname) }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const apply = document.getElementById('apply');
    const label = document.getElementById('btnLabel');
    apply.addEventListener('change', () => {
        label.textContent = apply.checked ? 'Selesaikan dan Terapkan' : 'Selesaikan Opname';
    });
    label.textContent = apply.checked ? 'Selesaikan dan Terapkan' : 'Selesaikan Opname';

    document.getElementById('formFinish').addEventListener('submit', () => {
        const b = document.getElementById('btnFinish');
        b.disabled = true;
        label.textContent = 'Memproses...';
    });
</script>
@endpush