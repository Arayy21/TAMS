@extends('layouts.app')
@section('title', 'Kelola Pengguna')

@section('content')
@php
    $roleBadge = ['admin' => 'primary', 'pengelola' => 'dark', 'staff' => 'secondary'];
    $roleName  = ['admin' => 'Admin', 'pengelola' => 'Pengelola', 'staff' => 'Pengguna'];
    $cards = [
        ['Total Akun', $summary['total'],    '#DC143C'],
        ['Aktif',      $summary['aktif'],    '#28A745'],
        ['Nonaktif',   $summary['nonaktif'], '#5F5E5E'],
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
    <form method="GET" action="{{ route('users.index') }}" class="row g-2 mb-3">
        <div class="col-12 col-md-4">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama atau email...">
        </div>
        <div class="col-6 col-md-2">
            <select name="role" class="form-select">
                <option value="">Semua peran</option>
                @foreach ($roleName as $k => $v)
                    <option value="{{ $k }}" @selected(request('role') == $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
            </select>
        </div>
        <div class="col-12 col-md-4 d-flex gap-2">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Cari</button>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Reset</a>
            <a href="{{ route('users.create') }}" class="btn btn-primary ms-auto"><i class="bi bi-plus-lg"></i> Tambah Akun</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>Pengguna</th>
                    <th>Peran</th>
                    <th>Status</th>
                    <th>Masuk Terakhir</th>
                    <th>Dibuat</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <x-avatar :user="$u" :size="36" />
                                <div>
                                    <div class="fw-semibold">
                                        {{ $u->name }}
                                        @if ($u->id === auth()->id())
                                            <span class="badge rounded-pill text-bg-light border ms-1">Anda</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $u->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge rounded-pill text-bg-{{ $roleBadge[$u->role] ?? 'secondary' }}">{{ $roleName[$u->role] ?? $u->role }}</span></td>
                        <td>
                            @if ($u->is_active)
                                <span class="badge rounded-pill text-bg-success">Aktif</span>
                            @else
                                <span class="badge rounded-pill text-bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <span @if ($u->last_login_at) title="{{ \Illuminate\Support\Carbon::parse($u->last_login_at)->format('d M Y H:i') }}" @endif>
                                {{ $u->last_login_label }}
                            </span>
                        </td>
                        <td>{{ $u->created_at?->format('d M Y') ?? '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-people fs-1"></i>
                            <p class="mb-0">Tidak ada akun ditemukan.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">Menampilkan {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} akun</small>
        {{ $users->links() }}
    </div>
</div>
@endsection