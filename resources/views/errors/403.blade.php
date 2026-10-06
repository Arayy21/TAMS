<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses Ditolak - TAMS</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-icon.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-3">
    <div class="text-center bg-white border rounded-4 p-5" style="max-width:440px">
        <i class="bi bi-shield-lock text-danger" style="font-size:3.5rem"></i>
        <h1 class="h4 fw-bold mt-3">Akses Ditolak</h1>
        <p class="text-muted">Akun Anda tidak memiliki izin untuk membuka halaman ini. Hubungi admin jika Anda membutuhkan akses.</p>
        <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke Beranda</a>
    </div>
</body>
</html>