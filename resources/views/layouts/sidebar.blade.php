@php
    $admin = auth()->user()->isAdmin();
    $active = fn (string ...$routes) => request()->routeIs(...$routes) ? 'active' : '';
@endphp

<aside class="sidebar d-flex flex-column">
    <a href="{{ $admin ? route('dashboard') : route('assets.index') }}" class="brand" aria-label="TAMS - Beranda">
        <img src="{{ asset('images/logo-icon.svg') }}" alt="Logo TAMS">
        <span>
            <span class="brand-name">TAMS</span>
            <span class="brand-sub">Assets Management</span>
        </span>
    </a>

    <nav class="nav flex-column">

        @if ($admin)
            <div class="nav-group">
                <div class="nav-label">Ringkasan</div>
                <a class="nav-link {{ $active('dashboard') }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </div>
        @endif

        <div class="nav-group">
            <div class="nav-label">Inventaris</div>
            <a class="nav-link {{ $active('assets.*') }}" href="{{ route('assets.index') }}">
                <i class="bi bi-box-seam me-2"></i> Data Aset
            </a>
            @if ($admin)
                <a class="nav-link {{ $active('categories.*') }}" href="{{ route('categories.index') }}">
                    <i class="bi bi-tags me-2"></i> Kategori
                </a>
                <a class="nav-link {{ $active('locations.*') }}" href="{{ route('locations.index') }}">
                    <i class="bi bi-geo-alt me-2"></i> Lokasi
                </a>
            @endif
            <a class="nav-link {{ $active('scan.*') }}" href="{{ route('scan.index') }}">
                <i class="bi bi-upc-scan me-2"></i> Scan Barcode
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Aktivitas</div>
            <a class="nav-link {{ $active('loans.*') }}" href="{{ route('loans.index') }}">
                <i class="bi bi-people me-2"></i> Peminjam
            </a>
            @if ($admin)
                <a class="nav-link {{ $active('opnames.*') }}" href="{{ route('opnames.index') }}">
                    <i class="bi bi-clipboard-check me-2"></i> Stok Opname
                </a>
                <a class="nav-link {{ $active('histories.*') }}" href="{{ route('histories.index') }}">
                    <i class="bi bi-clock-history me-2"></i> Riwayat Aset
                </a>
            @endif
        </div>

        @if ($admin)
            <div class="nav-group">
                <div class="nav-label">Pelaporan</div>
                <a class="nav-link {{ $active('reports.*') }}" href="{{ route('reports.index') }}">
                    <i class="bi bi-file-earmark-text me-2"></i> Laporan
                </a>
            </div>
        @endif

        @can('manage-users')
            <div class="nav-group">
                <div class="nav-label">Pengaturan</div>
                <a class="nav-link {{ $active('users.*') }}" href="{{ route('users.index') }}">
                    <i class="bi bi-person-gear me-2"></i> Kelola Pengguna
                </a>
            </div>
        @endcan

    </nav>

    <div class="sidebar-foot">
        <button type="button" class="nav-link border-0 bg-transparent text-start"
                style="width: calc(100% - 24px)"
                data-bs-toggle="modal" data-bs-target="#modalLogout">
            <i class="bi bi-box-arrow-left me-2"></i> Keluar
        </button>
    </div>
</aside>