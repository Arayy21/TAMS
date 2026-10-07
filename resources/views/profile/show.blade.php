@extends('layouts.app')
@section('title', 'Profil Saya')

@section('content')
<div class="row g-3">

    <div class="col-12 col-lg-4">
        <div class="card-tams p-4 text-center">
            <x-avatar :user="$user" :size="96" />
            <h2 class="h5 fw-bold mt-3 mb-0">{{ $user->name }}</h2>
            <div class="text-muted small mb-2">{{ $user->email }}</div>
            <span class="badge rounded-pill text-bg-primary">{{ $user->role_label }}</span>

            <<div class="profile-info text-start mt-3" style="padding:0">
                <div class="row-item">
                    <span class="label">Status akun</span>
                    <span class="fw-medium text-success">Aktif</span>
                </div>
                <div class="row-item">
                    <span class="label">Bergabung sejak</span>
                    <span class="fw-medium">{{ $user->created_at?->format('d M Y') ?? '-' }}</span>
                </div>
                <div class="row-item">
                    <span class="label">Masuk terakhir</span>
                    <span class="fw-medium">{{ $user->last_login_label }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8">

        <div class="card-tams p-4 mb-3">
            <h2 class="h6 fw-semibold mb-3">Data Akun</h2>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                               class="form-control @error('name') is-invalid @enderror">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" value="{{ $user->email }}" class="form-control" disabled>
                        <div class="form-text">Perubahan email dilakukan oleh admin.</div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Simpan Profil</button>
            </form>
        </div>

        <div class="card-tams p-4">
            <h2 class="h6 fw-semibold mb-3">Ganti Kata Sandi</h2>
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Kata sandi saat ini <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" autocomplete="current-password"
                               class="form-control pw @error('current_password', 'password') is-invalid @enderror">
                        @error('current_password', 'password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kata sandi baru <span class="text-danger">*</span></label>
                        <input type="password" name="password" autocomplete="new-password"
                               class="form-control pw @error('password', 'password') is-invalid @enderror">
                        @error('password', 'password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Minimal 8 karakter.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ulangi kata sandi baru <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" autocomplete="new-password" class="form-control pw">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showPw">
                            <label class="form-check-label small" for="showPw">Tampilkan kata sandi</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Ubah Kata Sandi</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('showPw').addEventListener('change', function (e) {
        document.querySelectorAll('.pw').forEach(function (i) { i.type = e.target.checked ? 'text' : 'password'; });
    });
</script>
@endpush