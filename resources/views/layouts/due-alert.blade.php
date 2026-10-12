@if (! empty($dueAlert) && ($dueAlert['overdue'] > 0 || $dueAlert['soon'] > 0))
    @php
        $near = $dueAlert['nearest'] ?? null;
        $left = $near?->days_left;
        $when = is_null($left) ? '' : ($left <= 0 ? 'hari ini' : ($left === 1 ? 'besok' : "dalam {$left} hari"));
    @endphp

    <div id="dueAlert" data-key="{{ $dueAlert['overdue'] }}-{{ $dueAlert['soon'] }}-{{ $left }}"
         class="alert alert-{{ $dueAlert['overdue'] > 0 ? 'danger' : 'warning' }} alert-dismissible fade show d-flex align-items-start gap-2"
         role="alert">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>
            @if ($dueAlert['overdue'] > 0)
                <strong>{{ $dueAlert['overdue'] }} peminjaman terlambat</strong> dikembalikan.
                <a href="{{ route('loans.index', ['status' => 'terlambat']) }}" class="alert-link">Lihat daftar</a>
            @endif

            @if ($dueAlert['soon'] > 0 && $near)
                @if ($dueAlert['overdue'] > 0) <br> @endif

                @if ($dueAlert['soon'] === 1)
                    <strong>1 peminjaman</strong> jatuh tempo <strong>{{ $when }}</strong>
                    ({{ $near->borrower_name }}, {{ $near->asset->asset_code }}).
                @else
                    <strong>{{ $dueAlert['soon'] }} peminjaman</strong> akan jatuh tempo, yang terdekat <strong>{{ $when }}</strong>
                    ({{ $near->borrower_name }}, {{ $near->asset->asset_code }}).
                @endif
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