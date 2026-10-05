@extends('layouts.app')
@section('title', 'Riwayat Aset')

@section('content')
<div class="card-tams p-4">
    <form method="GET" action="{{ route('histories.index') }}" class="row g-2 mb-3">
        <div class="col-12 col-md-5">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari kode atau nama aset...">
        </div>
        <div class="col-6 col-md-3">
            <select name="action" class="form-select">
                <option value="">Semua perubahan</option>
                @foreach (['create' => 'Ditambahkan', 'update' => 'Data diperbarui', 'location' => 'Lokasi', 'condition' => 'Kondisi', 'status' => 'Status', 'delete' => 'Dihapus'] as $k => $v)
                    <option value="{{ $k }}" @selected(request('action') == $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4 d-flex gap-2">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Cari</button>
            <a href="{{ route('histories.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr><th>Waktu</th><th>Aset</th><th>Perubahan</th><th>Detail</th><th>Oleh</th></tr>
            </thead>
            <tbody>
                @forelse ($histories as $h)
                    <tr>
                        <td>{{ $h->created_at->format('d M Y H:i') }}</td>
                        <td>
                            <a href="{{ route('assets.show', $h->asset_id) }}" class="fw-semibold text-decoration-none">{{ $h->asset->asset_code }}</a>
                            <div class="small text-muted">{{ $h->asset->name }}</div>
                        </td>
                        <td>{{ $h->action_label }}</td>
                        <td>{{ $h->change_text }}</td>
                        <td>{{ $h->user->name ?? 'Sistem' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-clock-history fs-1"></i>
                            <p class="mb-0">Belum ada riwayat.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">Total {{ $histories->total() }} catatan</small>
        {{ $histories->links() }}
    </div>
</div>
@endsection