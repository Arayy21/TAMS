<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - TAMS</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-icon.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600&display=swap" rel="stylesheet">
    <style>
        body { background:#FBF9F8; color:#4A4A4A; font-family:Inter, Arial, sans-serif; line-height:1.6; min-height:100vh; }
        .login-card { width:100%; max-width:400px; background:#fff; border:1px solid #EAEAEA; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.05); }
        .brand { color:#121212; font-family:Poppins, sans-serif; font-size:26px; font-weight:600; letter-spacing:2px; }
        .btn { border-radius:12px; }
        .btn-primary { background:#DC143C; border-color:#DC143C; }
        .btn-primary:hover, .btn-primary:active, .btn-primary:focus { background:#B1002C !important; border-color:#B1002C !important; }
        .btn-outline-secondary { border-color:#E5E5E5; color:#4A4A4A; }
        .form-control { height:44px; border-color:#E5E5E5; border-radius:12px; }
        .form-control:focus { border-color:#DC143C; box-shadow:0 0 0 2px rgba(220,20,60,.18); }
        .form-check-input:checked { background-color:#DC143C; border-color:#DC143C; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">

<div class="login-card p-4 p-sm-5">
    <div class="text-center mb-4">
        <img src="{{ asset('images/logo-icon.svg') }}" alt="Logo TAMS" width="64" height="64" class="mb-3">
        <div class="brand">TAMS</div>
        <div class="text-muted small">Technolife Assets Management System</div>
    </div>

    @if (session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif

     @if (session('error'))
        <div class="alert alert-warning py-2 small">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('login.attempt') }}" id="formLogin" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label small fw-medium">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="nama@perusahaan.com" autocomplete="username" autofocus required>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label small fw-medium">Kata sandi</label>
            <div class="input-group has-validation">
                <input type="password" id="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Tampilkan kata sandi">
                    <i class="bi bi-eye" id="iconEye"></i>
                </button>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label small" for="remember">Ingat saya</label>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="btnLogin">Masuk</button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">Lupa kata sandi? Hubungi admin.</p>
</div>

<script>
    // Tampilkan / sembunyikan kata sandi
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon  = document.getElementById('iconEye');
        const show  = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    // Cegah klik ganda
    document.getElementById('formLogin').addEventListener('submit', function () {
        const btn = document.getElementById('btnLogin');
        btn.disabled = true;
        btn.textContent = 'Memproses...';
    });
</script>
</body>
</html>