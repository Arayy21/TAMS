@php
    $in  = (int) $data->where('type', 'masuk')->sum('quantity');
    $out = (int) $data->where('type', 'keluar')->sum('quantity');
    $net = $in - $out;
@endphp

<table class="rpt">
    <thead>
        <tr>
            <th class="c">No</th><th>Tanggal</th><th>Kode</th><th>Nama Aset</th>
            <th>Jenis</th><th>Alasan</th><th class="c">Jumlah</th>
            <th class="c">Stok Sebelum</th><th class="c">Stok Sesudah</th>
            <th>Nota</th><th>Petugas</th><th>Catatan</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($data as $i => $m)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td>{{ $m->moved_at->format('d M Y') }}</td>
                <td>{{ $m->asset->asset_code }}</td>
                <td>{{ $m->asset->name }}</td>
                <td class="{{ $m->type === 'masuk' ? 'ok' : 'bad' }}">{{ $m->type_label }}</td>
                <td>{{ $m->reason_label }}</td>
                <td class="c {{ $m->type === 'masuk' ? 'ok' : 'bad' }}">{{ $m->type === 'masuk' ? '+' : '-' }}{{ $m->quantity }}</td>
                <td class="c">{{ $m->qty_before }}</td>
                <td class="c">{{ $m->qty_after }}</td>
                <td>{{ $m->reference ?? '-' }}</td>
                <td>{{ $m->creator->name ?? '-' }}</td>
                <td>{{ $m->notes ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="12" class="c">Tidak ada data sesuai filter.</td></tr>
        @endforelse
    </tbody>
    @if ($data->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="4">Total ({{ $data->count() }} mutasi)</td>
                <td colspan="2">Masuk {{ $in }} &middot; Keluar {{ $out }}</td>
                <td class="c">{{ $net > 0 ? '+' : '' }}{{ $net }}</td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
    @endif
</table>