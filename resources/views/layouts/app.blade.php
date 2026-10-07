<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'TAMS') - TAMS</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-icon.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Token design system */
            --primary:#DC143C; --primary-dark:#B1002C; --primary-tint:#FFF1F0;
            --success:#28A745; --danger:#BA1A1A; --warning:#D97706;
            --bg:#FBF9F8; --surface:#F5F3F3; --border:#EAEAEA;
            --text:#4A4A4A; --heading:#121212; --muted:#5F5E5E;
            --shadow-1:0 4px 12px rgba(0,0,0,.05);
            --shadow-2:0 8px 24px rgba(0,0,0,.08);

            /* Override variabel Bootstrap */
            --bs-primary:#DC143C;   --bs-primary-rgb:220,20,60;
            --bs-success:#28A745;   --bs-success-rgb:40,167,69;
            --bs-danger:#BA1A1A;    --bs-danger-rgb:186,26,26;
            --bs-link-color:#B1002C; --bs-link-hover-color:#920022;
            --bs-link-color-rgb:177,0,44; --bs-link-hover-color-rgb:146,0,34;
            --bs-body-bg:#FBF9F8; --bs-body-color:#4A4A4A;
            --bs-heading-color:#121212; --bs-secondary-color:#5F5E5E;
            --bs-border-color:#EAEAEA; --bs-border-radius:.75rem;
            --bs-body-font-family:Inter, Arial, sans-serif;
            --bs-body-line-height:1.6;
        }
        body { background:var(--bg); color:var(--text); font-family:Inter, Arial, sans-serif; line-height:1.6; }
        h1,h2,h3,h4,h5,h6,.h1,.h2,.h3,.h4,.h5,.h6 { font-family:Poppins, Inter, sans-serif; font-weight:600; color:var(--heading); }

        /* ===== Override komponen Bootstrap ===== */
        .btn { border-radius:12px; font-weight:600; }
        .btn-primary { --bs-btn-bg:#DC143C; --bs-btn-border-color:#DC143C; --bs-btn-color:#fff;
                    --bs-btn-hover-bg:#B1002C; --bs-btn-hover-border-color:#B1002C; --bs-btn-hover-color:#fff;
                    --bs-btn-active-bg:#920022; --bs-btn-active-border-color:#920022; --bs-btn-active-color:#fff;
                    --bs-btn-focus-shadow-rgb:220,20,60; --bs-btn-disabled-bg:#DC143C; --bs-btn-disabled-border-color:#DC143C; }
        .btn-outline-primary { --bs-btn-color:#DC143C; --bs-btn-border-color:#DC143C;
                    --bs-btn-hover-bg:#DC143C; --bs-btn-hover-border-color:#DC143C; --bs-btn-hover-color:#fff;
                    --bs-btn-active-bg:#B1002C; --bs-btn-active-border-color:#B1002C; --bs-btn-active-color:#fff;
                    --bs-btn-focus-shadow-rgb:220,20,60; --bs-btn-disabled-color:#DC143C; --bs-btn-disabled-border-color:#DC143C; }
        .btn-success { --bs-btn-bg:#28A745; --bs-btn-border-color:#28A745; --bs-btn-hover-bg:#006622; --bs-btn-hover-border-color:#006622;
                    --bs-btn-active-bg:#00531A; --bs-btn-active-border-color:#00531A; --bs-btn-focus-shadow-rgb:40,167,69; }
        .btn-outline-success { --bs-btn-color:#28A745; --bs-btn-border-color:#28A745; --bs-btn-hover-bg:#28A745; --bs-btn-hover-border-color:#28A745;
                    --bs-btn-active-bg:#006622; --bs-btn-active-border-color:#006622; --bs-btn-focus-shadow-rgb:40,167,69; }
        .btn-outline-word { --bs-btn-color:#2B579A; --bs-btn-border-color:#2B579A;
                    --bs-btn-hover-bg:#2B579A; --bs-btn-hover-border-color:#2B579A; --bs-btn-hover-color:#fff;
                    --bs-btn-active-bg:#1E3F73; --bs-btn-active-border-color:#1E3F73; --bs-btn-active-color:#fff; }
        .btn-outline-danger { --bs-btn-color:#BA1A1A; --bs-btn-border-color:#BA1A1A; --bs-btn-hover-bg:#BA1A1A; --bs-btn-hover-border-color:#BA1A1A;
                    --bs-btn-active-bg:#93000A; --bs-btn-active-border-color:#93000A; --bs-btn-focus-shadow-rgb:186,26,26; }
        .btn-outline-word { --bs-btn-color:#2B579A; --bs-btn-border-color:#2B579A; --bs-btn-hover-bg:#2B579A; --bs-btn-hover-border-color:#2B579A; --bs-btn-hover-color:#fff; --bs-btn-active-bg:#1E3F73; --bs-btn-active-border-color:#1E3F73; --bs-btn-active-color:#fff; }
        .form-control, .form-select { border-color:#E5E5E5; border-radius:12px; }
        .form-control:focus, .form-select:focus { border-color:#DC143C; box-shadow:0 0 0 2px rgba(220,20,60,.18); }
        .form-check-input:checked { background-color:#DC143C; border-color:#DC143C; }
        .form-check-input:focus { border-color:#DC143C; box-shadow:0 0 0 2px rgba(220,20,60,.18); }
        .page-link { color:#B1002C; }
        /* Tab / pills (halaman Laporan) */
        .nav-pills { --bs-nav-pills-link-active-bg:#DC143C; --bs-nav-pills-link-active-color:#fff; }
        .nav-pills .nav-link { color:#4A4A4A; border-radius:12px; font-weight:500; }
        .nav-pills .nav-link:hover { color:#B1002C; background:#F5F3F3; }
        .nav-pills .nav-link.active, .nav-pills .show > .nav-link { background:#DC143C; color:#fff; font-weight:600; }
        .nav-pills .nav-link:focus-visible { box-shadow:0 0 0 2px rgba(220,20,60,.25); }
        .page-link:hover { color:#920022; background:#FFF1F0; }
        .page-item.active .page-link { background:#DC143C; border-color:#DC143C; color:#fff; }
        .badge { font-family:Inter, sans-serif; font-weight:600; }
        .badge.text-bg-warning { background-color:#D97706 !important; color:#fff !important; }

        /* ===== Layout ===== */
        .sidebar { width:220px; min-height:100vh; background:#fff; border-right:1px solid var(--border); position:fixed; top:0; left:0; z-index:1030; }
        .sidebar .brand { display:flex; align-items:center; gap:12px; padding:20px 20px 18px; margin-bottom:8px;
                        border-bottom:1px solid var(--border); text-decoration:none; }
        .sidebar .brand img { width:40px; height:40px; flex-shrink:0; }
        .brand-name { display:block; color:var(--heading); font-family:Poppins, sans-serif; font-size:20px; font-weight:600; letter-spacing:1.5px; line-height:1.1; }
        .brand-sub  { display:block; color:var(--muted); font-size:10.5px; font-weight:500; letter-spacing:.4px; margin-top:3px; }
        .sidebar .nav-link { color:var(--text); margin:2px 12px; padding:10px 16px; border-radius:12px; font-size:14px; font-weight:500; }
        .sidebar .nav-link:hover { color:var(--primary-dark); background:var(--surface); }
        .sidebar .nav-link.active { color:#fff; background:var(--primary); font-weight:600; }
        .sidebar .nav-link.disabled { opacity:.5; pointer-events:none; }
        .main { margin-left:220px; }
        .topbar { background:#fff; height:64px; border-bottom:1px solid var(--border); }
        .card-tams { background:#fff; border:1px solid var(--border); border-radius:12px; box-shadow:var(--shadow-1); transition:box-shadow .2s; }
        .card-tams:hover { box-shadow:var(--shadow-2); }
        .stat-card { border-left:6px solid var(--primary); }
        .stat-label { color:var(--muted); font-size:13px; font-weight:500; }
        .stat-value { font-family:Poppins, sans-serif; font-size:36px; font-weight:600; line-height:1.1; }
        .item-row { background:var(--surface); border-radius:8px; padding:10px 14px; }
        /* Sidebar: kelompok menu */
        .sidebar { overflow-y: auto; }
        .nav-group { padding: 4px 0 8px; }
        .nav-group + .nav-group { border-top: 1px solid var(--border); margin-top: 6px; padding-top: 14px; }
        .nav-label { padding: 0 28px; margin-bottom: 6px; font-size: 11px; font-weight: 700;
                    letter-spacing: .9px; text-transform: uppercase; color: var(--muted); opacity: .75; }
        .sidebar .nav-link i { width: 20px; text-align: center; }

        @media (max-width: 991px) {
            .sidebar { max-height: none; overflow-y: visible; }
        }
        /* Avatar */
        .avatar-frame { display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;
                        border-radius:50%; color:#fff; font-weight:600; letter-spacing:.5px; user-select:none;
                        box-shadow:0 0 0 2px #fff, 0 0 0 4px rgba(220,20,60,.25); transition:box-shadow .15s; }
        .avatar-btn { display:flex; align-items:center; gap:10px; background:none; border:0; padding:4px 8px 4px 4px;
                    border-radius:999px; }
        .avatar-btn:hover, .avatar-btn[aria-expanded="true"] { background:var(--surface); }
        .avatar-btn:hover .avatar-frame, .avatar-btn[aria-expanded="true"] .avatar-frame
                    { box-shadow:0 0 0 2px #fff, 0 0 0 4px var(--primary); }
        .avatar-btn:focus-visible { outline:2px solid var(--primary); outline-offset:2px; }

        /* Panel profil */
        .profile-menu { width:300px; max-width:calc(100vw - 24px); padding:0; overflow:hidden;
                        border:1px solid var(--border); border-radius:14px; box-shadow:var(--shadow-2); }
        .profile-head { text-align:center; padding:24px 20px 16px; background:var(--primary-tint); }
        .profile-head .avatar-frame { box-shadow:0 0 0 3px #fff, 0 0 0 5px rgba(220,20,60,.35); }
        .profile-info { padding:8px 20px; }
        .profile-info .row-item { display:flex; justify-content:space-between; padding:8px 0;
                                border-bottom:1px solid var(--surface); font-size:13px; }
        .profile-info .row-item:last-child { border-bottom:0; }
        .profile-info .label { color:var(--muted); }
        .profile-foot { padding:12px 20px 16px; }
        @media (max-width: 991px) {
            .sidebar { position:static; width:100%; min-height:auto; border-right:0; border-bottom:1px solid var(--border); }
            .main { margin-left:0; }
        }

        /* Sidebar setinggi layar, tombol Keluar selalu terlihat */
        .sidebar { height: 100vh; height: 100dvh; overflow-y: auto; }
        .sidebar-foot { position: sticky; bottom: 0; margin-top: auto; padding: 10px 0 12px;
                        background: var(--surface, #fff); border-top: 1px solid var(--border, #EAEAEA); }
        .sidebar-foot .nav-link { color: var(--danger, #BA1A1A); font-weight: 500; }
        .sidebar-foot .nav-link:hover { background: rgba(186, 26, 26, .08); color: var(--danger, #BA1A1A); }

        @media (max-width: 991px) {
            .sidebar { height: auto; overflow-y: visible; }
            .sidebar-foot { position: static; }
        }

        .profile-info .row-item { display: flex; justify-content: space-between; align-items: center; gap: 16px; }
        .profile-info .row-item .label { flex-shrink: 0; }
        .profile-info .row-item span:last-child { text-align: right; }
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
            <div class="row-item">
                <span class="label">Masuk terakhir</span>
                <span class="fw-medium">{{ auth()->user()->last_login_label }}</span>
            </div>
        </div>

        <div class="profile-foot">
            <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary w-100 mb-2">
                <i class="bi bi-person-gear me-1"></i> Profil Saya
            </a>
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