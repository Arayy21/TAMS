<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title') - TAMS</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-icon.svg') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary:#DC143C; --primary-dark:#B8102F; --ring:rgba(220,20,60,.18); --border:#EAEAEA; --muted:#5F5E5E; }
        body { margin:0; background:#F7F5F4; color:#121212; font-family:Inter, system-ui, -apple-system, "Segoe UI", Arial, sans-serif; }
        .guest-wrap { min-height:100vh; min-height:100dvh; display:flex; align-items:center; justify-content:center; padding:24px 16px; }
        .guest-card { width:100%; max-width:440px; background:#fff; border:1px solid var(--border); border-radius:18px; padding:32px 28px; box-shadow:0 10px 30px rgba(18,18,18,.06); }
        .brand-name { font-size:22px; font-weight:700; letter-spacing:2px; color:#1E3A8A; }
        .form-control { height:46px; font-size:16px; }
        textarea.form-control { height:auto; }
        .form-control:focus { border-color:var(--primary); box-shadow:0 0 0 .2rem var(--ring); }
        .btn-brand { --bs-btn-bg:var(--primary); --bs-btn-border-color:var(--primary); --bs-btn-color:#fff;
                     --bs-btn-hover-bg:var(--primary-dark); --bs-btn-hover-border-color:var(--primary-dark); --bs-btn-hover-color:#fff;
                     --bs-btn-active-bg:#A30E29; --bs-btn-active-border-color:#A30E29; --bs-btn-active-color:#fff;
                     --bs-btn-disabled-bg:var(--primary); --bs-btn-disabled-border-color:var(--primary); --bs-btn-disabled-color:#fff;
                     height:46px; font-weight:600; border-radius:10px; }
        a.link { color:var(--primary); text-decoration:none; font-weight:600; }
        @media (max-width:420px) { .guest-card { padding:24px 18px; } }
    </style>
</head>
<body>
<div class="guest-wrap">
    <div class="guest-card">
        <div class="text-center mb-4">
            <img src="{{ asset('images/logo-icon.svg') }}" alt="Logo TAMS" width="56" height="56" class="mb-2">
            <div class="brand-name">TAMS</div>
            <div class="small text-muted">Technolife Assets Management System</div>
        </div>

        @yield('content')

        <div class="text-center small text-muted mt-4">&copy; {{ date('Y') }} TAMS &middot; Technolife</div>
    </div>
</div>
@stack('scripts')
</body>
</html>