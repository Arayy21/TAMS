@if (! empty($evidence) && isset($evidence['items']))
    @if ($evidence['items']->isEmpty())
        <div class="ev">
            <h3>Lampiran Foto Bukti</h3>
            <div class="ev-none">Tidak ada peminjaman yang memiliki foto bukti pada filter ini.</div>
        </div>
    @else
        @foreach ($evidence['items'] as $i => $it)
            @php $l = $it['loan']; @endphp
            <div class="ev">
                <h3>Bukti Foto Peminjaman ({{ $i + 1 }} dari {{ $evidence['items']->count() }})</h3>
                @if ($i === 0 && $evidence['limited'])
                    <div class="ev-warn">Menampilkan {{ $evidence['limit'] }} dari {{ $evidence['total'] }} peminjaman berfoto. Persempit filter untuk melihat sisanya.</div>
                @endif

                <table class="evi">
                    <tr><td class="k">Peminjam</td><td>{{ $l->borrower_name }}</td><td class="k">Aset</td><td>{{ $l->asset->asset_code }} - {{ $l->asset->name }}</td></tr>
                    <tr><td class="k">Kontak / Divisi</td><td>{{ $l->borrower_contact ?? '-' }}</td><td class="k">Jumlah</td><td>{{ $l->quantity }} unit</td></tr>
                    <tr><td class="k">Tgl Pinjam</td><td>{{ $l->loaned_at->format('d M Y') }}</td><td class="k">Batas Kembali</td><td>{{ $l->due_at?->format('d M Y') ?? '-' }}</td></tr>
                    <tr><td class="k">Tgl Kembali</td><td>{{ $l->returned_at?->format('d M Y') ?? '-' }}</td><td class="k">Status</td><td>{{ $l->status_label }}</td></tr>
                    <tr><td class="k">Lokasi Awal</td><td>{{ $l->originLocation?->full_name ?? '-' }}</td><td class="k">Lokasi Terakhir</td><td>{{ $l->last_location_label ?? 'Belum dicatat' }}</td></tr>
                </table>

                <div class="evh">Sebelum dipinjam ({{ count($it['before']) }} foto)</div>
                @if (count($it['before']))
                    <table class="evt"><tr>
                        @foreach ($it['before'] as $uri)
                            <td><img src="{{ $uri }}"></td>
                        @endforeach
                    </tr></table>
                @else
                    <div class="ev-none">Tidak ada foto.</div>
                @endif

                <div class="evh">Sesudah dikembalikan ({{ count($it['after']) }} foto)</div>
                @if (count($it['after']))
                    <table class="evt"><tr>
                        @foreach ($it['after'] as $uri)
                            <td><img src="{{ $uri }}"></td>
                        @endforeach
                    </tr></table>
                @else
                    <div class="ev-none">{{ $l->returned_at ? 'Tidak ada foto.' : 'Belum dikembalikan.' }}</div>
                @endif
            </div>
        @endforeach
    @endif
@endif