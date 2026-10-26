@extends('layouts.guest')
@section('title', 'Lupa Kata Sandi')

@section('content')
@php $contact = config('tams.admin_contact'); @endphp

<h1 class="h4 fw-bold mb-1">Lupa kata sandi?</h1>

@if (session('status'))
    <div class="alert alert-success d-flex gap-2 align-items-start mt-3" role="status">
        <i class="bi bi-check-circle-fill mt-1"></i>
        <div>
            <strong>Permintaan dikirim ke admin.</strong>
            <div class="small mt-1">Jika email Anda terdaftar, admin akan menerimanya. Admin memeriksa identitas Anda terlebih dahulu,
            lalu memberi <strong>kata sandi sementara</strong> secara langsung. Saat masuk, Anda diminta membuat kata sandi baru.</div>
        </div>
    </div>

    @if ($contact)
        <div class="small p-3 rounded-3 mb-3" style="background:#F5F3F3">
            <i class="bi bi-telephone me-1"></i> Butuh cepat? Hubungi admin: <strong>{{ $contact }}</strong>
        </div>
    @endif

    <a href="{{ route('login') }}" class="btn btn-brand w-100 d-flex align-items-center justify-content-center">Kembali ke Halaman Masuk</a>
@else
    <p class="text-muted small mb-4">
        Kata sandi tidak dapat dikirim lewat email. Kirim permintaan ke admin. Admin akan memeriksa identitas Anda,
        lalu memberi kata sandi sementara.
    </p>

    <form method="POST" action="{{ route('password.request.store') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label small fw-semibold">Email akun</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="nama@perusahaan.com" autocomplete="username" inputmode="email"
                   autocapitalize="none" spellcheck="false" autofocus required>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
            <label for="message" class="form-label small fw-semibold">Pesan untuk admin <span class="text-muted fw-normal">(opsional)</span></label>
            <textarea id="message" name="message" rows="2" maxlength="255" class="form-control @error('message') is-invalid @enderror"
                      placeholder="Contoh: Saya Budi dari bagian gudang, nomor HP 0812...">{{ old('message') }}</textarea>
            @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Bantu admin mengenali Anda, misalnya nama lengkap, bagian, dan cara menghubungi Anda.</div>
        </div>

        <button type="submit" class="btn btn-brand w-100">Kirim Permintaan</button>
    </form>

    @if ($contact)
        <div class="small p-3 rounded-3 mt-3" style="background:#F5F3F3">
            <i class="bi bi-telephone me-1"></i> Butuh cepat? Hubungi admin: <strong>{{ $contact }}</strong>
        </div>
    @endif

    <div class="text-center mt-4"><a href="{{ route('login') }}" class="link small">&larr; Kembali ke halaman masuk</a></div>
@endif
@endsection