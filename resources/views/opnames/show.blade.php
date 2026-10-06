@extends('layouts.app')
@section('title', 'Opname ' . $opname->code)

@section('content')
@php
    $editable = $opname->is_running;
    $total    = $items->count();
    $pct      = $total ? round($checked / $total * 100) : 0;
    $condColor = ['baik' => 'success', 'rusak' => 'danger', 'perbaikan' => 'warning'];
@endphp

<style>
    tr.item-row.row-checked td:first-child { box-shadow: inset 4px 0 0 var(--success); }
    .qty-input { width: 90px; }
    .note-input { min-width: 150px; }
</style>

<nav class="small text-muted mb-3">
    <a href="{{ route('opnames.index') }}" class="text-decoration-none">Stok Opname</a> / {{ $opname->code }}
</nav>

<div class="card-tams p-4 mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h2 class="h5 fw-bold mb-0">{{ $opname->name }}</h2>
                <span class="badge rounded-pill text-bg-{{ $opname->status_color }}">{{ $opname->status_label }}</span>
            </div>
            <div class="text-muted small">
                {{ $opname->code }} &middot; {{ $opname->opname_date->format('d M Y') }} &middot;
                {{ $opname->location->name ?? 'Seluruh aset' }} &middot; dibuat oleh {{ $opname->creator->name ?? '-' }}
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.index', ['jenis' => 'opname', 'opname_id' => $opname->id]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-text"></i> Laporan
            </a>
            @if ($editable)
                <a href="{{ route('opnames.finish.form', $opname) }}" class="btn btn-primary">
                    <i class="bi bi-check2-circle"></i> Selesaikan Opname
                </a>
            @endif
        </div>
    </div>

    <div class="mt-3">
        <div class="d-flex justify-content-between small mb-1">
            <span><strong id="checkedCount">{{ $checked }}</strong> dari {{ $total }} aset sudah dicek</span>
            <span id="progressPct">{{ $pct }}%</span>
        </div>
        <div class="progress" style="height:10px">
            <div class="progress-bar" id="progressBar" style="width:{{ $pct }}%"></div>
        </div>
    </div>

    <div class="alert alert-light border small mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        Hitung unit yang <strong>ada di tempat</strong>. Unit yang sedang dipinjam tidak ikut dihitung, sehingga
        <strong>Seharusnya = jumlah di sistem &minus; dipinjam</strong>. Jika semua sesuai, klik <strong>Sesuai</strong>
        agar terisi otomatis.
    </div>
    @unless ($editable)
    <div class="alert alert-success small mt-3 mb-0">
            <i class="bi bi-check-circle me-1"></i>
            Diselesaikan oleh <strong>{{ $opname->finisher->name ?? '-' }}</strong>
            pada {{ $opname->finished_at?->format('d M Y H:i') ?? '-' }}.
            {{ $opname->adjustments_applied ? 'Hasil sudah diterapkan ke data aset.' : 'Hasil hanya dicatat, data aset tidak diubah.' }}
    </div>
    @endunless
</div>

<div class="card-tams p-4">
    <div class="row g-2 mb-3">
        <div class="col-12 col-md-5">
            <input type="text" id="q" class="form-control" placeholder="Cari kode atau nama aset...">
        </div>
        <div class="col-6 col-md-3">
            <select id="f" class="form-select">
                <option value="">Semua aset</option>
                <option value="belum">Belum dicek</option>
                <option value="sudah">Sudah dicek</option>
                <option value="selisih">Ada selisih</option>
            </select>
        </div>
        <div class="col-6 col-md-4 text-md-end align-self-center small text-muted" id="filterInfo"></div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>Aset</th>
                    <th class="text-center">Di Sistem</th>
                    <th class="text-center">Dipinjam</th>
                    <th class="text-center">Seharusnya</th>
                    <th>Kondisi Sistem</th>
                    <th>Hitung Fisik</th>
                    <th>Kondisi Fisik</th>
                    <th>Selisih</th>
                    <th>Catatan</th>
                    @if ($editable) <th class="text-end">Aksi</th> @endif
                </tr>
            </thead>
            <tbody id="tbody">
                @foreach ($items as $it)
                    @php
                        $diff = $it->difference;
                        $cond = $it->physical_condition ?? $it->system_condition;
                    @endphp
                    <tr class="item-row {{ $it->is_checked ? 'row-checked' : '' }}"
                        data-url="{{ route('opnames.items.update', [$opname, $it]) }}"
                        data-search="{{ strtolower($it->asset->asset_code . ' ' . $it->asset->name) }}"
                        data-checked="{{ $it->is_checked ? 1 : 0 }}"
                        data-diff="{{ $it->is_checked && $diff !== 0 ? 1 : 0 }}"
                        data-expected="{{ $it->expected_qty }}"
                        data-syscond="{{ $it->system_condition }}">
                        <td>
                            <a href="{{ route('assets.show', $it->asset_id) }}" target="_blank" class="fw-semibold text-decoration-none">{{ $it->asset->asset_code }}</a>
                            <div>{{ $it->asset->name }}</div>
                            <small class="text-muted">{{ $it->asset->category->name }} &middot; {{ $it->asset->location->name }}</small>
                        </td>
                        <td class="text-center">{{ $it->system_qty }}</td>
                        <td class="text-center">{{ $it->borrowed_qty }}</td>
                        <td class="text-center fw-bold">{{ $it->expected_qty }}</td>
                        <td><span class="badge rounded-pill text-bg-{{ $condColor[$it->system_condition] }}">{{ ucfirst($it->system_condition) }}</span></td>
                        <td>
                            <input type="number" min="0" class="form-control form-control-sm qty-input"
                                   value="{{ $it->physical_qty }}" @disabled(! $editable)>
                        </td>
                        <td>
                            <select class="form-select form-select-sm cond-input" @disabled(! $editable)>
                                @foreach (['baik', 'rusak', 'perbaikan'] as $c)
                                    <option value="{{ $c }}" @selected($cond === $c)>{{ ucfirst($c) }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="diff-cell">
                            @if (! $it->is_checked)
                                <span class="text-muted">-</span>
                            @elseif ($diff === 0)
                                <span class="badge rounded-pill text-bg-success">Sesuai</span>
                            @elseif ($diff < 0)
                                <span class="badge rounded-pill text-bg-danger">Kurang {{ abs($diff) }}</span>
                            @else
                                <span class="badge rounded-pill text-bg-warning">Lebih {{ $diff }}</span>
                            @endif
                        </td>
                        <td>
                            <input type="text" maxlength="255" class="form-control form-control-sm note-input"
                                   value="{{ $it->notes }}" @disabled(! $editable)>
                        </td>
                        @if ($editable)
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-success btn-ok">Sesuai</button>
                                <button type="button" class="btn btn-sm btn-primary btn-save">Simpan</button>
                                <div class="row-msg small mt-1"></div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const CSRF    = @json(csrf_token());
    const TOTAL   = {{ $total }};
    const rows    = Array.from(document.querySelectorAll('tr.item-row'));
    const qInput  = document.getElementById('q');
    const fSelect = document.getElementById('f');

    function diffBadge(d) {
        if (d === 0) return '<span class="badge rounded-pill text-bg-success">Sesuai</span>';
        if (d < 0)   return '<span class="badge rounded-pill text-bg-danger">Kurang ' + Math.abs(d) + '</span>';
        return '<span class="badge rounded-pill text-bg-warning">Lebih ' + d + '</span>';
    }

    function applyFilter() {
        const q = qInput.value.trim().toLowerCase(), f = fSelect.value;
        let shown = 0;
        rows.forEach(tr => {
            let ok = tr.dataset.search.includes(q);
            if (f === 'belum')   ok = ok && tr.dataset.checked === '0';
            if (f === 'sudah')   ok = ok && tr.dataset.checked === '1';
            if (f === 'selisih') ok = ok && tr.dataset.diff === '1';
            tr.classList.toggle('d-none', ! ok);
            if (ok) shown++;
        });
        document.getElementById('filterInfo').textContent = 'Menampilkan ' + shown + ' dari ' + TOTAL + ' aset';
    }
    qInput.addEventListener('input', applyFilter);
    fSelect.addEventListener('change', applyFilter);
    applyFilter();

    async function saveRow(tr, qty, cond) {
        const msg  = tr.querySelector('.row-msg');
        const btns = tr.querySelectorAll('button');
        btns.forEach(b => b.disabled = true);
        msg.className = 'row-msg small mt-1 text-muted';
        msg.textContent = 'Menyimpan...';

        try {
            const res = await fetch(tr.dataset.url, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({
                    physical_qty: qty,
                    physical_condition: cond,
                    notes: tr.querySelector('.note-input').value || null
                })
            });
            const data = await res.json().catch(() => ({}));

            if (! res.ok) {
                const first = data.errors ? Object.values(data.errors)[0][0]
                            : (data.message || 'Gagal menyimpan. Muat ulang halaman lalu coba lagi.');
                throw new Error(first);
            }

            tr.classList.add('row-checked');
            tr.dataset.checked = '1';
            tr.dataset.diff = data.difference !== 0 ? '1' : '0';
            tr.querySelector('.diff-cell').innerHTML = diffBadge(data.difference);

            document.getElementById('checkedCount').textContent = data.checked;
            const pct = data.total ? Math.round(data.checked / data.total * 100) : 0;
            document.getElementById('progressBar').style.width = pct + '%';
            document.getElementById('progressPct').textContent = pct + '%';

            msg.className = 'row-msg small mt-1 text-success';
            msg.textContent = 'Tersimpan';
            applyFilter();
        } catch (e) {
            msg.className = 'row-msg small mt-1 text-danger';
            msg.textContent = e.message;
        } finally {
            btns.forEach(b => b.disabled = false);
        }
    }

    rows.forEach(tr => {
        const qty = tr.querySelector('.qty-input'), cond = tr.querySelector('.cond-input');
        const ok = tr.querySelector('.btn-ok'), save = tr.querySelector('.btn-save');
        if (! save) return;

        save.addEventListener('click', () => {
            if (qty.value === '') { qty.focus(); return; }
            saveRow(tr, parseInt(qty.value, 10), cond.value);
        });
        ok.addEventListener('click', () => {
            qty.value = tr.dataset.expected;
            cond.value = tr.dataset.syscond;
            saveRow(tr, parseInt(tr.dataset.expected, 10), tr.dataset.syscond);
        });
        qty.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); save.click(); } });
    });
</script>
@endpush