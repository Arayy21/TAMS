<aside class="sidebar d-flex flex-column">
    <a href="{{ route('dashboard') }}" class="brand" aria-label="TAMS - ke Dashboard">
    <img src="{{ asset('images/logo-icon.svg') }}" alt="Logo TAMS">
    <span>
        <span class="brand-name">TAMS</span>
        <span class="brand-sub">Assets Management</span>
    </span>
    </a>
    <nav class="nav flex-column">
        @if (auth()->user()->isAdmin())
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ auth()->user()->isAdmin() ? route('dashboard') : route('assets.index') }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        @endif

        <a class="nav-link {{ request()->routeIs('assets.*') ? 'active' : '' }}" href="{{ route('assets.index') }}">
            <i class="bi bi-box-seam me-2"></i> Data Aset
        </a>

        <a class="nav-link {{ request()->routeIs('loans.*') ? 'active' : '' }}" href="{{ route('loans.index') }}">
            <i class="bi bi-people me-2"></i> Peminjam
        </a>

        @if (auth()->user()->isAdmin())
            <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                <i class="bi bi-tags me-2"></i> Kategori
            </a>
            <a class="nav-link {{ request()->routeIs('locations.*') ? 'active' : '' }}" href="{{ route('locations.index') }}">
                <i class="bi bi-geo-alt me-2"></i> Lokasi
            </a>
            <a class="nav-link {{ request()->routeIs('histories.*') ? 'active' : '' }}" href="{{ route('histories.index') }}">
                <i class="bi bi-clock-history me-2"></i> Riwayat Aset
            </a>
            <a class="nav-link {{ request()->routeIs('opnames.*') ? 'active' : '' }}" href="{{ route('opnames.index') }}">
                <i class="bi bi-clipboard-check me-2"></i> Stok Opname
            </a>
            <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                <i class="bi bi-file-earmark-text me-2"></i> Laporan
            </a>
        @endif
    </nav>
        
    </nav>
        <div class="mt-auto pb-3">
        <button type="button" class="nav-link border-0 bg-transparent text-start"
                style="width: calc(100% - 24px)"
                data-bs-toggle="modal" data-bs-target="#modalLogout">
            <i class="bi bi-box-arrow-left me-2"></i> Keluar
        </button>
    </div>
</aside>