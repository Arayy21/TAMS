@php
    $u      = $user ?? null;
    $isEdit = (bool) $u;
    $isSelf = $u && $u->id === auth()->id();
@endphp
@csrf

@if ($isSelf)
    <div class="alert alert-light border small">
        <i class="bi bi-info-circle me-1"></i> Peran dan status akun Anda sendiri tidak dapat diubah.
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nama <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $u->name ?? '') }}" class="form-control @error('name') is-invalid @enderror">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" value="{{ old('email', $u->email ?? '') }}" class="form-control @error('email') is-invalid @enderror">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Peran <span class="text-danger">*</span></label>
        @if ($isSelf)
            <input type="hidden" name="role" value="{{ $u->role }}">
        @endif
        <select name="role" class="form-select @error('role') is-invalid @enderror" @disabled($isSelf)>
            @foreach (['admin' => 'Admin', 'pengelola' => 'Pengelola', 'staff' => 'Pengguna'] as $k => $v)
                <option value="{{ $k }}" @selected(old('role', $u->role ?? 'staff') === $k)>{{ $v }}</option>
            @endforeach
        </select>
        @error('role') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label d-block">Status Akun</label>
        @if ($isSelf)
            <input type="hidden" name="is_active" value="1">
        @else
            <input type="hidden" name="is_active" value="0">
        @endif
        <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
                   @checked((bool) old('is_active', $u->is_active ?? true)) @disabled($isSelf)>
            <label class="form-check-label" for="is_active">Akun aktif (boleh masuk)</label>
        </div>
    </div>

    <div class="col-12">
        <div class="small text-muted border rounded-3 p-3" style="background:#F5F3F3">
            <div><strong>Admin</strong>: akses penuh, termasuk Kelola Pengguna.</div>
            <div><strong>Pengelola</strong>: akses penuh ke data aset, peminjaman, opname, dan laporan, tanpa Kelola Pengguna.</div>
            <div><strong>Pengguna</strong>: mengelola data aset dan mencatat peminjaman. Data peminjaman hanya bisa dilihat.</div>
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label">Kata Sandi @unless ($isEdit)<span class="text-danger">*</span>@endunless</label>
        <input type="password" id="password" name="password" autocomplete="new-password"
               class="form-control @error('password') is-invalid @enderror">
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">
            {{ $isEdit ? 'Kosongkan jika tidak ingin mengubah kata sandi.' : 'Minimal 8 karakter.' }}
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Ulangi Kata Sandi</label>
        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="form-control">
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="showPw">
            <label class="form-check-label small" for="showPw">Tampilkan kata sandi</label>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Buat Akun' }}</button>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>

@push('scripts')
<script>
    document.getElementById('showPw').addEventListener('change', function (e) {
        ['password', 'password_confirmation'].forEach(function (id) {
            document.getElementById(id).type = e.target.checked ? 'text' : 'password';
        });
    });
</script>
@endpush