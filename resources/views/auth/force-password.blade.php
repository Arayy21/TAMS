@extends('layouts.guest')
@section('title', 'Buat Kata Sandi Baru')

@section('content')
<h1 class="h4 fw-bold mb-1">Buat kata sandi baru</h1>
<p class="text-muted small mb-4">
    Halo, <strong>{{ auth()->user()->name }}</strong>. Kata sandi Anda baru saja disetel ulang oleh admin.
    Demi keamanan, buat kata sandi baru yang hanya Anda yang tahu.
</p>

<form method="POST" action="{{ route('password.force.update') }}" novalidate>
    @csrf @method('PUT')

    <div class="mb-3">
        <label for="password" class="form-label small fw-semibold">Kata sandi baru</label>
        <div class="input-group has-validation">
            <input type="password" id="password" name="password" autocomplete="new-password"
                   class="form-control @error('password') is-invalid @enderror" autofocus required>
            <button type="button" class="btn btn-outline-secondary" id="togglePw" aria-label="Tampilkan kata sandi">
                <i class="bi bi-eye" id="eye"></i>
            </button>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-text">Minimal 8 karakter dan berbeda dari kata sandi sementara.</div>
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label small fw-semibold">Ulangi kata sandi baru</label>
        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-brand w-100">Simpan dan Lanjutkan</button>
</form>

<form method="POST" action="{{ route('logout') }}" class="text-center mt-3">
    @csrf
    <button type="submit" class="btn btn-link btn-sm text-decoration-none text-muted">Keluar</button>
</form>
@endsection

@push('scripts')
<script>
    document.getElementById('togglePw').addEventListener('click', function () {
        var a = document.getElementById('password'), b = document.getElementById('password_confirmation'), show = a.type === 'password';
        a.type = b.type = show ? 'text' : 'password';
        document.getElementById('eye').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
</script>
@endpush