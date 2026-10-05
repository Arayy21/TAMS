<aside class="sidebar d-flex flex-column">
    <div class="brand">TAMS</div>
    <nav class="nav flex-column">
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        <a class="nav-link {{ request()->routeIs('assets.*') ? 'active' : '' }}" href="{{ route('assets.index') }}">
            <i class="bi bi-box-seam me-2"></i> Data Aset
        </a>
        <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
            <i class="bi bi-tags me-2"></i> Kategori
        </a>
        <a class="nav-link {{ request()->routeIs('locations.*') ? 'active' : '' }}" href="{{ route('locations.index') }}">
            <i class="bi bi-geo-alt me-2"></i> Lokasi
        </a>
        <a class="nav-link {{ request()->routeIs('histories.*') ? 'active' : '' }}" href="{{ route('histories.index') }}">
            <i class="bi bi-clock-history me-2"></i> Riwayat Aset
        </a>
        {{-- Laporan dan Pengguna: PERLU DIKONFIRMASI KE PERUSAHAAN --}}
    </nav>
        <div class="mt-auto pb-3">
        <button type="button" class="nav-link border-0 bg-transparent text-start"
                style="width: calc(100% - 24px)"
                data-bs-toggle="modal" data-bs-target="#modalLogout">
            <i class="bi bi-box-arrow-left me-2"></i> Keluar
        </button>
    </div>
</aside>