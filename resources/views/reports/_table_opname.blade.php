@php
    $cls = fn ($it) => ! $it->is_checked ? '' : ($it->difference === 0 ? 'ok' : ($it->difference < 0 ? 'bad' : 'warn'));
    $checked    = $data->filter(fn ($i) => $i->is_checked);
    $cMatch     = $checked->filter(fn ($i) => $i->difference === 0)->count();
    $cShort     = $checked->filter(fn ($i) => $i->difference < 0)->count();
    $cOver      = $checked->filter(fn ($i) => $i->difference > 0)->count();
    $cUnchecked = $data->count() - $checked->count();
@endphp

<table class="rpt">
    <thead>
        <tr>
            <th class="c">No</th><th>Sesi</th><th>Kode</th><th>Nama Aset</th><th>Lokasi</th>
            <th class="c">Di Sistem</th><th class="c">Dipinjam</th><th class="c">Seharusnya</th>
            <th class="c">Fisik</th><th>Selisih</th>
            <th>Kondisi Sistem</th><th>Kondisi Fisik</th><th>Catatan</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($data as $i => $it)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td>{{ $it->opname->code }}</td>
                <td>{{ $it->asset->asset_code }}</td>
                <td>{{ $it->asset->name }}</td>
                <td>{{ $it->asset->location->name }}</td>
                <td class="c">{{ $it->system_qty }}</td>
                <td class="c">{{ $it->borrowed_qty }}</td>
                <td class="c">{{ $it->expected_qty }}</td>
                <td class="c">{{ $it->is_checked ? $it->physical_qty : '-' }}</td>
                <td class="{{ $cls($it) }}">{{ $it->diff_label }}</td>
                <td>{{ ucfirst($it->system_condition) }}</td>
                <td>{{ $it->physical_condition ? ucfirst($it->physical_condition) : '-' }}</td>
                <td>{{ $it->notes ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="13" class="c">Tidak ada data sesuai filter.</td></tr>
        @endforelse
    </tbody>
    @if ($data->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="5">Total ({{ $data->count() }} baris)</td>
                <td class="c">{{ $data->sum('system_qty') }}</td>
                <td class="c">{{ $data->sum('borrowed_qty') }}</td>
                <td class="c">{{ $data->sum(fn ($i) => $i->expected_qty) }}</td>
                <td class="c">{{ $checked->sum('physical_qty') }}</td>
                <td colspan="4">Sesuai {{ $cMatch }} &middot; Kurang {{ $cShort }} &middot; Lebih {{ $cOver }} &middot; Belum dicek {{ $cUnchecked }}</td>
            </tr>
        </tfoot>
    @endif
</table>