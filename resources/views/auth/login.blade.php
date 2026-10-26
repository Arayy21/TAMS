<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#DC143C">
    <title>Masuk - TAMS</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-icon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary:#DC143C; --primary-dark:#B8102F; --primary-deep:#8E0C25; --primary-ring:rgba(220,20,60,.18);
                --bg:#F7F5F4; --border:#EAEAEA; --text:#121212; --muted:#5F5E5E; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, system-ui, -apple-system, "Segoe UI", Arial, sans-serif; color: var(--text);
               background: var(--bg); -webkit-font-smoothing: antialiased; }

        .login-shell { min-height: 100vh; min-height: 100dvh; display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 6fr); }

        /* ===== Panel merek ===== */
        .brand-panel { position: relative; overflow: hidden; color: #fff; padding: 48px 56px;
                       display: flex; flex-direction: column; justify-content: space-between; gap: 32px;
                       background: radial-gradient(120% 90% at 0% 0%, #E8284B 0%, var(--primary) 45%, var(--primary-deep) 100%); }
        .brand-panel::before, .brand-panel::after { content: ""; position: absolute; border-radius: 50%; pointer-events: none; }
        .brand-panel::before { width: 420px; height: 420px; right: -140px; top: -120px; background: rgba(255,255,255,.08); }
        .brand-panel::after  { width: 300px; height: 300px; left: -100px; bottom: -110px; background: rgba(255,255,255,.06); }
        .brand-panel > * { position: relative; z-index: 1; }

        .brand-row { display: flex; align-items: center; gap: 14px; }
        .brand-chip { width: 52px; height: 52px; flex-shrink: 0; border-radius: 14px; background: #fff;
                      display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 18px rgba(0,0,0,.18); }
        .brand-chip img { width: 36px; height: 36px; }
        .brand-name { font-size: 24px; font-weight: 700; letter-spacing: 2px; line-height: 1.1; }
        .brand-sub { font-size: 12px; opacity: .85; letter-spacing: .4px; }

        .hero h1 { font-size: clamp(26px, 2.8vw, 38px); font-weight: 700; line-height: 1.2; margin: 0 0 14px; }
        .hero p { margin: 0 0 28px; opacity: .9; max-width: 420px; line-height: 1.6; }
        .features { list-style: none; margin: 0; padding: 0; display: grid; gap: 14px; }
        .features li { display: flex; gap: 12px; align-items: flex-start; }
        .features i { width: 36px; height: 36px; flex-shrink: 0; border-radius: 10px; background: rgba(255,255,255,.16);
                      display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .features strong { display: block; font-size: 14px; }
        .features span { font-size: 13px; opacity: .85; }
        .brand-foot { font-size: 12px; opacity: .75; }

        /* ===== Sisi formulir ===== */
        .form-side { background: #fff; display: flex; align-items: center; justify-content: center; padding: 32px 24px; }
        .form-wrap { width: 100%; max-width: 420px; animation: rise .45s ease both; }
        .form-head h2 { font-size: 26px; font-weight: 700; margin: 0 0 6px; }
        .form-head p { color: var(--muted); margin: 0 0 24px; font-size: 14px; }

        .form-label { font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .field .input-group-text { background: #fff; color: var(--muted); border-right: 0; }
        .field .form-control { height: 48px; font-size: 16px; border-left: 0; }
        .field .form-control.is-invalid { background-image: none; padding-right: .75rem; }
        .field .toggle-btn { border: 1px solid #DEE2E6; border-left: 0; background: #fff; color: var(--muted); width: 48px; }
        .field .toggle-btn:hover { color: var(--primary); background: #fff; }
        .field:focus-within .input-group-text,
        .field:focus-within .form-control,
        .field:focus-within .toggle-btn { border-color: var(--primary); }
        .field .form-control:focus { box-shadow: none; }
        .field:focus-within { border-radius: 8px; box-shadow: 0 0 0 .2rem var(--primary-ring); }
        .field.has-validation .invalid-feedback { display: none; }

        .form-check-input:checked { background-color: var(--primary); border-color: var(--primary); }
        .form-check-input:focus { border-color: var(--primary); box-shadow: 0 0 0 .2rem var(--primary-ring); }

        .btn-login { --bs-btn-bg: var(--primary); --bs-btn-border-color: var(--primary); --bs-btn-color: #fff;
                     --bs-btn-hover-bg: var(--primary-dark); --bs-btn-hover-border-color: var(--primary-dark); --bs-btn-hover-color: #fff;
                     --bs-btn-active-bg: #A30E29; --bs-btn-active-border-color: #A30E29; --bs-btn-active-color: #fff;
                     --bs-btn-disabled-bg: var(--primary); --bs-btn-disabled-border-color: var(--primary); --bs-btn-disabled-color: #fff;
                     height: 48px; font-weight: 600; border-radius: 10px; }
        .btn-login:focus-visible { box-shadow: 0 0 0 .25rem var(--primary-ring); }
        .help-text { text-align: center; color: var(--muted); font-size: 13px; margin: 20px 0 0; }
        .form-foot { display: none; text-align: center; color: var(--muted); font-size: 12px; margin-top: 20px; }

        @keyframes rise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

        /* ===== Layar pendek (laptop kecil) ===== */
        @media (min-width: 992px) and (max-height: 700px) {
            .brand-panel { padding: 32px 44px; gap: 20px; }
            .features { display: none; }
            .hero p { margin-bottom: 0; }
        }

        /* ===== Tablet dan HP ===== */
        @media (max-width: 991.98px) {
            .login-shell { grid-template-columns: 1fr; grid-template-rows: auto 1fr; }
            .brand-panel { padding: 22px 20px 44px; border-radius: 0 0 28px 28px; gap: 0; }
            .hero, .features, .brand-foot { display: none; }
            .form-side { background: transparent; align-items: flex-start; padding: 0 16px max(24px, env(safe-area-inset-bottom)); }
            .form-wrap { background: #fff; border: 1px solid var(--border); border-radius: 18px; padding: 26px 22px;
                         margin-top: -22px; position: relative; z-index: 2; box-shadow: 0 10px 30px rgba(18,18,18,.08); }
            .form-head h2 { font-size: 22px; }
            .form-foot { display: block; }
        }
        @media (min-width: 576px) and (max-width: 991.98px) {
            .form-side { padding-top: 8px; }
            .form-wrap { padding: 32px 30px; }
        }
        @media (max-width: 359px) {
            .form-wrap { padding: 22px 16px; }
            .brand-name { font-size: 20px; }
        }
        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>
<div class="login-shell">

    {{-- Panel merek --}}
    <aside class="brand-panel">
        <div class="brand-row">
            <span class="brand-chip"><img src="{{ asset('images/logo-icon.svg') }}" alt="Logo TAMS"></span>
            <span>
                <span class="brand-name d-block">TAMS</span>
                <span class="brand-sub d-block">Technolife Assets Management System</span>
            </span>
        </div>

        <div class="hero">
            <h1>Kelola aset perusahaan dengan lebih tertib</h1>
            <p>Pendataan, peminjaman, stok opname, dan pelaporan aset dalam satu sistem.</p>
            <ul class="features">
                <li><i class="bi bi-box-seam"></i><div><strong>Data aset terpusat</strong><span>Kategori, lokasi, kondisi, dan jumlah unit</span></div></li>
                <li><i class="bi bi-upc-scan"></i><div><strong>Barcode dan scan</strong><span>Cetak label dan temukan aset dalam hitungan detik</span></div></li>
                <li><i class="bi bi-clipboard-check"></i><div><strong>Peminjaman dan stok opname</strong><span>Pantau tenggat dan cocokkan stok fisik</span></div></li>
            </ul>
        </div>

        <div class="brand-foot">&copy; {{ date('Y') }} TAMS &middot; Technolife</div>
    </aside>

    {{-- Formulir --}}
    <main class="form-side">
        <div class="form-wrap">
            <div class="form-head">
                <h2>Masuk ke akun Anda</h2>
                <p>Gunakan email dan kata sandi yang diberikan admin.</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success d-flex gap-2 align-items-start py-2 small" role="status">
                    <i class="bi bi-check-circle-fill mt-1"></i><div>{{ session('success') }}</div>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-warning d-flex gap-2 align-items-start py-2 small" role="alert">
                    <i class="bi bi-exclamation-triangle-fill mt-1"></i><div>{{ session('error') }}</div>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger d-flex gap-2 align-items-start py-2 small" role="alert">
                    <i class="bi bi-exclamation-circle-fill mt-1"></i>
                    <div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" id="formLogin" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group field has-validation">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               class="form-control @if ($errors->any()) is-invalid @endif"
                               placeholder="nama@perusahaan.com" autocomplete="username" inputmode="email"
                               autocapitalize="none" spellcheck="false" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Kata sandi</label>
                    <div class="input-group field has-validation">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="password"
                               class="form-control @if ($errors->any()) is-invalid @endif"
                               placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                        <button type="button" class="btn toggle-btn" id="togglePassword"
                                aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <i class="bi bi-eye" id="iconEye"></i>
                        </button>
                    </div>
                    <div id="capsHint" class="form-text text-warning-emphasis d-none" role="status">
                        <i class="bi bi-capslock"></i> Caps Lock sedang aktif
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label small" for="remember">Ingat saya di perangkat ini</label>
                </div>

                <button type="submit" class="btn btn-login w-100" id="btnLogin">
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="spin" aria-hidden="true"></span>
                    <span id="btnLabel">Masuk</span>
                </button>
            </form>

            <p class="help-text"><a href="{{ route('password.request') }}" style="color:var(--primary);font-weight:600;text-decoration:none">Lupa kata sandi?</a></p>
            <div class="form-foot">&copy; {{ date('Y') }} TAMS &middot; Technolife</div>
        </div>
    </main>
</div>

<script>
(function () {
    var pw = document.getElementById('password'), email = document.getElementById('email');
    var toggle = document.getElementById('togglePassword'), icon = document.getElementById('iconEye');
    var caps = document.getElementById('capsHint');
    var form = document.getElementById('formLogin'), btn = document.getElementById('btnLogin');
    var spin = document.getElementById('spin'), label = document.getElementById('btnLabel');

    // Tampilkan atau sembunyikan kata sandi
    toggle.addEventListener('click', function () {
        var show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
        toggle.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        pw.focus();
    });

    // Peringatan Caps Lock
    function checkCaps(e) {
        if (e.getModifierState) caps.classList.toggle('d-none', ! e.getModifierState('CapsLock'));
    }
    pw.addEventListener('keydown', checkCaps);
    pw.addEventListener('keyup', checkCaps);
    pw.addEventListener('blur', function () { caps.classList.add('d-none'); });

    // Cegah klik ganda
    function reset() { btn.disabled = false; spin.classList.add('d-none'); label.textContent = 'Masuk'; }
    form.addEventListener('submit', function () {
        btn.disabled = true; spin.classList.remove('d-none'); label.textContent = 'Memproses...';
    });
    window.addEventListener('pageshow', function (e) { if (e.persisted) reset(); });

    // Fokus otomatis hanya di perangkat non-sentuh (agar keyboard HP tidak menutupi halaman)
    if (! ('ontouchstart' in window)) { (email.value ? pw : email).focus(); }
})();
</script>
</body>
</html>