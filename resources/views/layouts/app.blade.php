<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'TAMS') - TAMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary:#2563EB; --sidebar:#1E293B; --bg:#F1F5F9; --muted:#64748B; }
        body { background:var(--bg); font-family:Inter, Arial, sans-serif; }
        .sidebar { width:220px; min-height:100vh; background:var(--sidebar); position:fixed; top:0; left:0; z-index:1030; }
        .sidebar .brand { color:#fff; font-size:22px; font-weight:700; padding:20px 24px; }
        .sidebar .nav-link { color:#94A3B8; margin:2px 12px; padding:10px 16px; border-radius:8px; font-size:14px; font-weight:500; }
        .sidebar .nav-link:hover { color:#fff; background:rgba(255,255,255,.06); }
        .sidebar .nav-link.active { color:#fff; background:var(--primary); }
        .sidebar .nav-link.disabled { opacity:.5; pointer-events:none; }
        .main { margin-left:220px; }
        .topbar { background:#fff; height:64px; border-bottom:1px solid #E2E8F0; }
        .card-tams { background:#fff; border:1px solid #E2E8F0; border-radius:12px; }
        .stat-card { border-left:6px solid var(--primary); }
        .stat-label { color:var(--muted); font-size:13px; font-weight:500; }
        .stat-value { font-size:36px; font-weight:700; line-height:1.1; }
        .item-row { background:#F8FAFC; border-radius:8px; padding:10px 14px; }
        @media (max-width: 991px) {
            .sidebar { position:static; width:100%; min-height:auto; }
            .main { margin-left:0; }
        }
    </style>
</head>
<body>
    @include('layouts.sidebar')

    <div class="main">
        <div class="topbar d-flex align-items-center justify-content-between px-4">
            <h1 class="h5 fw-bold mb-0">@yield('title')</h1>
            <div class="dropdown">
    <button class="btn btn-light border rounded-pill px-3 dropdown-toggle" data-bs-toggle="dropdown">
        <i class="bi bi-person-circle me-1"></i> {{ auth()->user()?->name ?? 'Tamu' }}
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><span class="dropdown-item-text small text-muted">{{ auth()->user()?->email ?? 'Belum masuk' }}</span></li>
        <li><hr class="dropdown-divider"></li>
        @auth
            <li>
                <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#modalLogout">
                    <i class="bi bi-box-arrow-right me-2"></i> Keluar
                </button>
            </li>
        @else
            <li><a class="dropdown-item" href="{{ route('login') }}">Masuk</a></li>
        @endauth
    </ul>
</div>
        </div>

        <main class="p-4">
            @include('layouts.alerts')
            @yield('content')
        </main>
    </div>
    
    @auth
        @include('layouts.logout-modal')
    @endauth
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>