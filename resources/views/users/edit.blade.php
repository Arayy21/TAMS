@extends('layouts.app')
@section('title', 'Edit Akun')

@section('content')
<nav class="small text-muted mb-3">
    <a href="{{ route('users.index') }}" class="text-decoration-none">Kelola Pengguna</a> / Edit {{ $user->name }}
</nav>

<div class="card-tams p-4 mb-3" style="max-width:860px">
    <div class="small text-muted mb-3">
        Masuk terakhir: <strong>{{ $user->last_login_label }}</strong>
        &middot; Dibuat {{ $user->created_at?->format('d M Y') ?? '-' }}
    </div>
    <form method="POST" action="{{ route('users.update', $user) }}">
        @method('PUT')
        @include('users._form')
    </form>
</div>

<div class="card-tams p-4" style="max-width:860px; border-color:#F1B8B8">
    <h2 class="h6 fw-semibold text-danger mb-2">Hapus Akun</h2>

    @if ($user->id === auth()->id())
        <p class="small text-muted mb-0">Akun yang sedang Anda pakai tidak dapat dihapus.</p>
    @elseif ($user->hasActivity())
        <p class="small text-muted mb-0">
            Akun ini sudah memiliki riwayat aktivitas (perubahan aset, peminjaman, atau opname), sehingga tidak dapat dihapus
            agar kolom <em>Oleh</em> di riwayat tetap menunjukkan nama yang benar.
            Gunakan sakelar <strong>Status Akun</strong> di atas untuk menonaktifkannya.
        </p>
    @else
        <p class="small text-muted">Akun ini belum memiliki aktivitas apa pun. Penghapusan bersifat permanen.</p>
        <form method="POST" action="{{ route('users.destroy', $user) }}"
              onsubmit="return confirm('Hapus akun {{ $user->name }} secara permanen?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Hapus Akun</button>
        </form>
    @endif
</div>
@endsection