<table class="rpt">
    <thead>
        <tr>
            <th class="c">No</th><th>Peminjam</th><th>Kontak / Divisi</th><th>Aset</th>
            <th class="c">Jumlah</th><th>Lokasi Awal</th><th>Lokasi Terakhir</th>
            <th>Tgl Pinjam</th><th>Batas Kembali</th><th>Tgl Kembali</th><th>Status</th>
            <th class="c">Foto (Sebelum/Sesudah)</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($data as $i => $l)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td>{{ $l->borrower_name }}</td>
                <td>{{ $l->borrower_contact ?? '-' }}</td>
                <td>{{ $l->asset->asset_code }} - {{ $l->asset->name }}</td>
                <td class="c">{{ $l->quantity }}</td>
                <td>{{ $l->originLocation?->full_name ?? '-' }}</td>
                <td>{{ $l->last_location_label ?? 'Belum dicatat' }}</td>
                <td>{{ $l->loaned_at->format('d M Y') }}</td>
                <td>{{ $l->due_at?->format('d M Y') ?? '-' }}</td>
                <td>{{ $l->returned_at?->format('d M Y') ?? '-' }}</td>
                <td>{{ $l->status_label }}</td>
                <td class="c">{{ $l->photos->where('stage', 'sebelum')->count() }} / {{ $l->photos->where('stage', 'sesudah')->count() }}</td>
            </tr>
        @empty
            <tr><td colspan="12" class="c">Tidak ada data sesuai filter.</td></tr>
        @endforelse
    </tbody>
    @if ($data->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="4">Total ({{ $data->count() }} peminjaman)</td>
                <td class="c">{{ $data->sum('quantity') }}</td>
                <td colspan="7"></td>
            </tr>
        </tfoot>
    @endif
</table>