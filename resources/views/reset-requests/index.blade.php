@extends('layouts.app')
@section('title', 'Permintaan Reset Kata Sandi')

@section('content')
@php
    $roleName = ['admin' => 'Admin', 'pengelola' => 'Pengelola', 'staff' => 'Pengguna'];
    $temp     = session('temp_password');
@endphp

@if ($temp)
    <div class="card-tams p-4 mb-3" style="border-color:#F5D58A;background:#FFFBEB">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="fw-bold mb-1"><i class="bi bi-key-fill text-warning"></i> Kata sandi sementara untuk {{ $temp['name'] }}</div>
                <div class="small text-muted mb-2">{{ $temp['email'] }}</div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <code id="tempPw" class="fs-4 px-3 py-1 bg-white border rounded-3" style="letter-spacing:2px;user-select:all">{{ $temp['password'] }}</code>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCopy"><i class="bi bi-clipboard"></i> Salin</button>
                </div>
            </div>
            <div class="small" style="max-width:380px">
                <div class="fw-semibold text-danger mb-1"><i class="bi bi-exclamation-triangle"></i> Ditampilkan sekali saja</div>
                Catat atau salin sekarang. Sampaikan langsung kepada pengguna (tatap muka atau telepon, bukan grup obrolan).
                Pengguna <strong>wajib mengganti</strong> kata sandi ini saat masuk. Setelah halaman dimuat ulang, kata sandi ini tidak dapat dilihat lagi.
            </div>
        </div>
    </div>
@endif

<div class="card-tams p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h6 fw-semibold mb-0">Permintaan Reset Kata Sandi</h2>
            <small class="text-muted">Permintaan yang tidak diproses dalam {{ \App\Models\PasswordResetRequest::EXPIRE_DAYS }} hari kedaluwarsa otomatis.</small>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-people"></i> Kelola Pengguna</a>
    </div>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><a class="nav-link {{ $tab === 'menunggu' ? 'active' : '' }}" href="{{ route('reset-requests.index', ['tab' => 'menunggu']) }}">Menunggu <span class="badge text-bg-light ms-1">{{ $counts['menunggu'] }}</span></a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'riwayat' ? 'active' : '' }}" href="{{ route('reset-requests.index', ['tab' => 'riwayat']) }}">Riwayat <span class="badge text-bg-light ms-1">{{ $counts['riwayat'] }}</span></a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'semua' ? 'active' : '' }}" href="{{ route('reset-requests.index', ['tab' => 'semua']) }}">Semua</a></li>
    </ul>

    <div class="table-responsive">
        <table class="table align-middle tbl-cards">
            <thead class="table-light">
                <tr>
                    <th>Pemohon</th><th>Peran</th><th>Pesan</th><th>Diminta</th><th>Masuk Terakhir</th><th>Status</th><th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $r)
                    <tr>
                        <td class="cell-title" data-label="Pemohon">
                            <div class="d-flex align-items-center gap-3">
                                <x-avatar :user="$r->user" :size="36" />
                                <div>
                                    <div class="fw-semibold">{{ $r->user->name }}</div>
                                    <small class="text-muted">{{ $r->user->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td data-label="Peran"><div class="cv">{{ $roleName[$r->user->role] ?? $r->user->role }}</div></td>
                        <td data-label="Pesan"><div class="cv" style="max-width:260px">{{ $r->message ?: '-' }}</div></td>
                        <td data-label="Diminta">
                            <div class="cv" title="{{ $r->created_at->format('d M Y H:i') }}">
                                {{ $r->created_at->locale('id')->diffForHumans() }}
                                <div class="small text-muted">IP {{ $r->requested_ip ?? '-' }}</div>
                            </div>
                        </td>
                        <td data-label="Masuk terakhir"><div class="cv">{{ $r->user->last_login_label }}</div></td>
                        <td class="cell-status" data-label="Status">
                            <div class="cv">
                                <span class="badge rounded-pill text-bg-{{ $r->status_color }}">{{ $r->status_label }}</span>
                                @if ($r->handled_at)
                                    <div class="small text-muted mt-1">oleh {{ $r->handler->name ?? '-' }}<br>{{ $r->handled_at->format('d M Y H:i') }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="cell-actions text-end text-nowrap">
                            @if ($r->status === 'menunggu')
                                <button type="button" class="btn btn-sm btn-primary btn-reset" data-bs-toggle="modal" data-bs-target="#modalReset"
                                        data-id="{{ $r->id }}" data-name="{{ $r->user->name }}" data-email="{{ $r->user->email }}">
                                    <i class="bi bi-key"></i> Setel Ulang
                                </button>
                                <form method="POST" action="{{ route('reset-requests.reject', $r) }}" class="d-inline"
                                      onsubmit="return confirm('Tolak permintaan dari {{ $r->user->name }}?')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">Tolak</button>
                                </form>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="cell-empty text-center text-muted py-5">
                            <i class="bi bi-key fs-1"></i>
                            <p class="mb-0">{{ $tab === 'menunggu' ? 'Tidak ada permintaan yang menunggu.' : 'Belum ada permintaan.' }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">Menampilkan {{ $requests->firstItem() ?? 0 }}-{{ $requests->lastItem() ?? 0 }} dari {{ $requests->total() }} permintaan</small>
        {{ $requests->links() }}
    </div>
</div>

<div class="modal fade" id="modalReset" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="formReset" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Setel Ulang Kata Sandi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Akun: <strong id="rName"></strong> <span class="text-muted" id="rEmail"></span></p>
                <p class="small text-muted">Sistem membuat kata sandi sementara yang ditampilkan <strong>satu kali</strong>.
                Semua sesi akun ini dikeluarkan, dan pengguna wajib membuat kata sandi baru saat masuk.</p>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="verified" value="1" id="verified" required>
                    <label class="form-check-label small" for="verified">
                        Saya sudah memastikan bahwa yang meminta adalah pemilik akun ini (kenal langsung, telepon, atau bertemu).
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Setel Ulang</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.btn-reset').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('formReset').action = "{{ url('pengguna/permintaan-reset') }}/" + b.dataset.id + "/setel";
            document.getElementById('rName').textContent = b.dataset.name;
            document.getElementById('rEmail').textContent = '(' + b.dataset.email + ')';
            document.getElementById('verified').checked = false;
        });
    });

    var copy = document.getElementById('btnCopy');
    if (copy) copy.addEventListener('click', async function () {
        var el = document.getElementById('tempPw'), text = el.textContent.trim(), ok = false;
        try { await navigator.clipboard.writeText(text); ok = true; } catch (e) {}
        if (! ok) {
            var r = document.createRange(); r.selectNodeContents(el);
            var s = window.getSelection(); s.removeAllRanges(); s.addRange(r);
            try { ok = document.execCommand('copy'); } catch (e) {}
        }
        copy.innerHTML = ok ? '<i class="bi bi-check2"></i> Tersalin' : 'Salin manual (blok teks)';
    });
})();
</script>
@endpush