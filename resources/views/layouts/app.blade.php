<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'TAMS') - TAMS</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-icon.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary:#2563EB; --sidebar:#1E293B; --bg:#F1F5F9; --muted:#64748B; }
        body { background:var(--bg); font-family:Inter, Arial, sans-serif; }
        .sidebar { width:220px; min-height:100vh; background:var(--sidebar); position:fixed; top:0; left:0; z-index:1030; }
        .sidebar .brand { display:flex; align-items:center; gap:12px; padding:20px 20px 18px; margin-bottom:8px;
                  border-bottom:1px solid rgba(255,255,255,.08); text-decoration:none; }
        .sidebar .brand img { width:40px; height:40px; flex-shrink:0; }
        .brand-name { display:block; color:#fff; font-size:20px; font-weight:700; letter-spacing:1.5px; line-height:1.1; }
        .brand-sub  { display:block; color:#94A3B8; font-size:10.5px; font-weight:500; letter-spacing:.4px; margin-top:3px; }
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
        /* Avatar */
        .avatar-frame { display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;
                        border-radius:50%; color:#fff; font-weight:600; letter-spacing:.5px; user-select:none;
                        box-shadow:0 0 0 2px #fff, 0 0 0 4px rgba(37,99,235,.25); transition:box-shadow .15s; }
        .avatar-btn { display:flex; align-items:center; gap:10px; background:none; border:0; padding:4px 8px 4px 4px;
                    border-radius:999px; }
        .avatar-btn:hover, .avatar-btn[aria-expanded="true"] { background:#F1F5F9; }
        .avatar-btn:hover .avatar-frame, .avatar-btn[aria-expanded="true"] .avatar-frame
                    { box-shadow:0 0 0 2px #fff, 0 0 0 4px var(--primary); }
        .avatar-btn:focus-visible { outline:2px solid var(--primary); outline-offset:2px; }

        /* Panel profil */
        .profile-menu { width:300px; max-width:calc(100vw - 24px); padding:0; overflow:hidden;
                        border:1px solid #E2E8F0; border-radius:14px; box-shadow:0 12px 32px rgba(15,23,42,.14); }
        .profile-head { text-align:center; padding:24px 20px 16px; background:linear-gradient(180deg,#EFF6FF,#fff); }
        .profile-head .avatar-frame { box-shadow:0 0 0 3px #fff, 0 0 0 5px rgba(37,99,235,.35); }
        .profile-info { padding:8px 20px; }
        .profile-info .row-item { display:flex; justify-content:space-between; padding:8px 0;
                                border-bottom:1px solid #F1F5F9; font-size:13px; }
        .profile-info .row-item:last-child { border-bottom:0; }
        .profile-info .label { color:var(--muted); }
        .profile-foot { padding:12px 20px 16px; }
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
    <button type="button" class="avatar-btn" data-bs-toggle="dropdown" aria-expanded="false"
            aria-label="Buka profil {{ auth()->user()->name }}">
        <x-avatar :user="auth()->user()" :size="38" />
        <span class="d-none d-md-block text-start lh-sm">
            <span class="d-block fw-semibold small">{{ auth()->user()->name }}</span>
            <span class="d-block text-muted" style="font-size:11px">{{ auth()->user()->role_label }}</span>
        </span>
        <i class="bi bi-chevron-down small text-muted d-none d-md-block"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-end profile-menu">
        <div class="profile-head">
            <x-avatar :user="auth()->user()" :size="72" />
            <div class="fw-bold mt-3">{{ auth()->user()->name }}</div>
            <div class="text-muted small mb-2">{{ auth()->user()->email }}</div>
            <span class="badge rounded-pill text-bg-primary">{{ auth()->user()->role_label }}</span>
        </div>

        <div class="profile-info">
            <div class="row-item">
                <span class="label">Peran</span>
                <span class="fw-medium">{{ auth()->user()->role_label }}</span>
            </div>
            <div class="row-item">
                <span class="label">Status akun</span>
                <span class="fw-medium text-success">
                    <i class="bi bi-circle-fill" style="font-size:8px"></i> Aktif
                </span>
            </div>
            <div class="row-item">
                <span class="label">Bergabung sejak</span>
                <span class="fw-medium">{{ auth()->user()->created_at?->translatedFormat('d F Y') ?? '-' }}</span>
            </div>
        </div>

        <div class="profile-foot">
            <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#modalLogout">
                <i class="bi bi-box-arrow-right me-1"></i> Keluar
            </button>
        </div>
    </div>
</div>
        </div>

        <main class="p-4">
            @include('layouts.due-alert')
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