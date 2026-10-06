@if (! empty($dueAlert) && ($dueAlert['overdue'] > 0 || $dueAlert['soon'] > 0))
    <div id="dueAlert" data-key="{{ $dueAlert['overdue'] }}-{{ $dueAlert['soon'] }}"
         class="alert alert-{{ $dueAlert['overdue'] > 0 ? 'danger' : 'warning' }} alert-dismissible fade show d-flex align-items-start gap-2"
         role="alert">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>
            @if ($dueAlert['overdue'] > 0)
                <strong>{{ $dueAlert['overdue'] }} peminjaman terlambat</strong> dikembalikan.
                <a href="{{ route('loans.index', ['status' => 'terlambat']) }}" class="alert-link">Lihat daftar</a>
            @endif
            @if ($dueAlert['soon'] > 0)
                @if ($dueAlert['overdue'] > 0) <br> @endif
                <strong>{{ $dueAlert['soon'] }} peminjaman</strong> jatuh tempo dalam {{ \App\Models\Loan::DUE_SOON_DAYS }} hari.
                <a href="{{ route('loans.index', ['status' => 'segera']) }}" class="alert-link">Lihat daftar</a>
            @endif
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    <script>
        (function () {
            var el = document.getElementById('dueAlert');
            if (sessionStorage.getItem('dueAlertClosed') === el.dataset.key) { el.remove(); return; }
            el.addEventListener('closed.bs.alert', function () {
                sessionStorage.setItem('dueAlertClosed', el.dataset.key);
            });
        })();
    </script>
@endif