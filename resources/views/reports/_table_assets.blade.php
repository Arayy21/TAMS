<table class="rpt">
    <thead>
        <tr>
            <th class="c">No</th><th>Kode</th><th>Nama Aset</th><th>Kategori</th><th>Lokasi</th>
            <th class="c">Jumlah</th><th class="c">Dipinjam</th><th class="c">Tersedia</th>
            <th>Kondisi</th><th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($data as $i => $a)
            @php $borrowed = (int) $a->borrowed_sum; @endphp
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td>{{ $a->asset_code }}</td>
                <td>{{ $a->name }}</td>
                <td>{{ $a->category->name }}</td>
                <td>{{ $a->location->name }}</td>
                <td class="c">{{ $a->quantity }}</td>
                <td class="c">{{ $borrowed }}</td>
                <td class="c">{{ max(0, $a->quantity - $borrowed) }}</td>
                <td>{{ ucfirst($a->asset_condition) }}</td>
                <td>{{ ucfirst($a->status) }}</td>
            </tr>
        @empty
            <tr><td colspan="10" class="c">Tidak ada data sesuai filter.</td></tr>
        @endforelse
    </tbody>
    @if ($data->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="5">Total ({{ $data->count() }} aset)</td>
                <td class="c">{{ $data->sum('quantity') }}</td>
                <td class="c">{{ $data->sum(fn ($a) => (int) $a->borrowed_sum) }}</td>
                <td class="c">{{ $data->sum(fn ($a) => max(0, $a->quantity - (int) $a->borrowed_sum)) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    @endif
</table>