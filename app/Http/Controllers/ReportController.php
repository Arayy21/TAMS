<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Location;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Support\ImageUpload;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;

class ReportController extends Controller
{
    private const EVIDENCE_LIMIT = 30;

    public function index(Request $request)
    {
        $type = $this->type($request);
        $data = $this->rows($request, $type);

        return view('reports.index', [
            'type'           => $type,
            'data'           => $data,
            'categories'     => Category::orderBy('name')->get(),
            'locations'      => Location::orderBy('name')->get(),
            'opnames'        => StockOpname::orderByDesc('id')->get(),
            'selectedOpname' => $type === 'opname' ? $this->opnameFor($request) : null,
            'filters'        => $this->filterText($request, $type),
            // Halaman index tidak perlu membuat thumbnail base64 (berat)
            'evidence'       => $this->wantsEvidence($request, $type) ? $this->evidence($data, false) : null,
        ]);
    }

    public function print(Request $request)
    {
        $type     = $this->type($request);
        $data     = $this->rows($request, $type);
        $evidence = null;

        if ($this->wantsEvidence($request, $type)) {
            @ini_set('memory_limit', '512M');
            @set_time_limit(180);

            $evidence = $this->evidence($data);
        }

        return view('reports.print', [
            'type'     => $type,
            'title'    => $this->title($type),
            'data'     => $data,
            'filters'  => $this->filterText($request, $type),
            'evidence' => $evidence,
        ]);
    }

    public function pdf(Request $request)
    {
        $type     = $this->type($request);
        $data     = $this->rows($request, $type);
        $evidence = null;

        if ($this->wantsEvidence($request, $type)) {
            @ini_set('memory_limit', '512M');
            @set_time_limit(180);

            $evidence = $this->evidence($data);
        }

        $pdf = Pdf::loadView('reports.pdf', [
            'type'     => $type,
            'title'    => $this->title($type),
            'data'     => $data,
            'filters'  => $this->filterText($request, $type),
            'evidence' => $evidence,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->fileName($type, 'pdf'));
    }

    public function word(Request $request)
    {
        $type = $this->type($request);

        if ($this->wantsEvidence($request, $type)) {
            @ini_set('memory_limit', '512M');
            @set_time_limit(180);
        }

        [$type, $title, $head, $rows, $total, $filters, $data] = $this->exportData($request);

        Settings::setOutputEscapingEnabled(true);

        $word = new PhpWord();
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(9);

        $section = $word->addSection([
            'orientation' => 'landscape',
            'marginTop' => 800, 'marginBottom' => 800, 'marginLeft' => 800, 'marginRight' => 800,
        ]);

        $section->addText('TAMS', ['bold' => true, 'size' => 16, 'color' => 'DC143C']);
        $section->addText('Technolife Assets Management System', ['color' => '64748B']);
        $section->addText($title, ['bold' => true, 'size' => 14], ['alignment' => 'center', 'spaceBefore' => 200]);

        $meta = $filters ? $this->filterLine($filters) . ' | ' : '';
        $section->addText(
            $meta . 'Dicetak pada ' . now()->format('d M Y H:i') . ' oleh ' . auth()->user()->name,
            ['color' => '475569'],
            ['alignment' => 'center', 'spaceAfter' => 200]
        );

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CBD5E1', 'cellMargin' => 60]);

        $table->addRow();
        foreach ($head as $h) {
            $table->addCell(null, ['bgColor' => 'F1F5F9'])->addText($h, ['bold' => true]);
        }

        foreach ($rows as $r) {
            $table->addRow();
            foreach ($r as $c) {
                $table->addCell()->addText((string) $c);
            }
        }

        if ($rows) {
            $table->addRow();
            foreach ($total as $c) {
                $table->addCell(null, ['bgColor' => 'F8FAFC'])->addText((string) $c, ['bold' => true]);
            }
        } else {
            $section->addText('Tidak ada data sesuai filter.');
        }

        // Lampiran foto bukti (satu halaman per peminjaman)
        $temp = [];

        if ($this->wantsEvidence($request, $type)) {
            // Word memakai file sementara, jadi data URI tidak dibutuhkan
            $this->addWordEvidence($word, $this->evidence($data, false), $temp);
        }

        $name = $this->fileName($type, 'docx');

        return response()->streamDownload(function () use ($word, &$temp) {
            try {
                IOFactory::createWriter($word, 'Word2007')->save('php://output');
            } finally {
                foreach ($temp as $f) {
                    @unlink($f);
                }
            }
        }, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    public function excel(Request $request)
    {
        [$type, $title, $head, $rows, $total, $filters] = $this->exportData($request);

        $book  = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle(['aset' => 'Daftar Aset', 'peminjaman' => 'Peminjaman', 'opname' => 'Hasil Opname', 'mutasi' => 'Mutasi Stok'][$type]);

        $sheet->setCellValue('A1', 'TAMS - ' . $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $meta = $filters ? $this->filterLine($filters) . ' | ' : '';
        $sheet->setCellValue('A2', $meta . 'Dicetak pada ' . now()->format('d M Y H:i') . ' oleh ' . auth()->user()->name);

        $sheet->fromArray($head, null, 'A4');
        $sheet->fromArray($rows, null, 'A5');

        $lastRow = 4 + count($rows);

        if ($rows) {
            $lastRow++;
            $sheet->fromArray($total, null, 'A' . $lastRow);
            $sheet->getStyle("A{$lastRow}:" . Coordinate::stringFromColumnIndex(count($head)) . $lastRow)
                  ->getFont()->setBold(true);
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($head));

        $sheet->getStyle("A4:{$lastCol}4")->getFont()->setBold(true);
        $sheet->getStyle("A4:{$lastCol}4")->getFill()
              ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        $sheet->getStyle("A4:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()
              ->setBorderStyle(Border::BORDER_THIN);

        for ($i = 1; $i <= count($head); $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }

        $name = $this->fileName($type, 'xlsx');

        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
        }, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function csv(Request $request)
    {
        [$type, , $head, $rows] = $this->exportData($request);

        $name = $this->fileName($type, 'csv');

        return response()->streamDownload(function () use ($head, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, $head, ';');
            foreach ($rows as $r) {
                fputcsv($out, $r, ';');
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ---------- validasi dan jenis laporan ----------

    private function type(Request $request): string
    {
        $request->validate([
            'jenis'     => 'nullable|in:aset,peminjaman,opname,mutasi',
            'dari'      => 'nullable|date',
            'sampai'    => 'nullable|date|after_or_equal:dari',
            'opname_id' => 'nullable|exists:stock_opnames,id',
            'hasil'     => 'nullable|in:selisih,belum',
            'tipe'      => 'nullable|in:masuk,keluar',
            'alasan'    => 'nullable|in:pembelian,rusak,dibuang',
            'foto'      => 'nullable|boolean',
        ], [
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            'opname_id.exists'      => 'Sesi opname tidak ditemukan.',
        ]);

        return $request->input('jenis', 'aset');
    }

    private function title(string $type): string
    {
        return match ($type) {
            'opname'     => 'Laporan Hasil Stok Opname',
            'mutasi'     => 'Laporan Mutasi Stok (Barang Masuk/Keluar)',
            'peminjaman' => 'Laporan Peminjaman Aset',
            default      => 'Laporan Daftar Aset',
        };
    }

    private function rows(Request $request, string $type)
    {
        return match ($type) {
            'aset'   => $this->assetRows($request),
            'opname' => $this->opnameRows($request),
            'mutasi' => $this->movementRows($request),
            default  => $this->loanRows($request),
        };
    }

    // ---------- pengambilan data ----------

    private function assetRows(Request $request)
    {
        return Asset::with(['category', 'location'])
            ->withSum(['loans as borrowed_sum' => fn ($q) => $q->whereNull('returned_at')], 'quantity')
            ->when($request->category_id, fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->location_id, fn ($q, $v) => $q->where('location_id', $v))
            ->when($request->condition, fn ($q, $v) => $q->where('asset_condition', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('asset_code')
            ->get();
    }

    private function loanRows(Request $request)
    {
        return Loan::with(['asset', 'photos', 'originLocation.parent', 'lastLocation.parent'])
            ->when($request->status, function ($q, $v) {
                match ($v) {
                    'dipinjam'     => $q->active(),
                    'segera'       => $q->dueSoon(),
                    'terlambat'    => $q->active()->whereNotNull('due_at')->whereDate('due_at', '<', today()),
                    'dikembalikan' => $q->whereNotNull('returned_at'),
                    default        => $q,
                };
            })
            ->when($request->dari, fn ($q, $v) => $q->whereDate('loaned_at', '>=', $v))
            ->when($request->sampai, fn ($q, $v) => $q->whereDate('loaned_at', '<=', $v))
            ->orderByDesc('loaned_at')
            ->orderByDesc('id')
            ->get();
    }

    // Sesi yang dipilih; null berarti "Semua sesi"
    private function opnameFor(Request $request): ?StockOpname
    {
        return $request->opname_id
            ? StockOpname::with('location')->find($request->opname_id)
            : null;
    }

    private function opnameRows(Request $request)
    {
        return StockOpnameItem::with(['opname', 'asset.category', 'asset.location'])
            ->when($request->opname_id, fn ($q, $v) => $q->where('stock_opname_id', $v))
            ->when($request->location_id, fn ($q, $v) => $q->whereHas('asset', fn ($a) => $a->where('location_id', $v)))
            ->get()
            ->sortBy([
                fn ($a, $b) => $b->stock_opname_id <=> $a->stock_opname_id,
                fn ($a, $b) => strcmp($a->asset->asset_code, $b->asset->asset_code),
            ])
            ->values()
            ->when($request->hasil === 'selisih', fn ($c) => $c->filter(
                fn ($i) => $i->is_checked && ($i->difference !== 0 || $i->physical_condition !== $i->system_condition)
            )->values())
            ->when($request->hasil === 'belum', fn ($c) => $c->reject(fn ($i) => $i->is_checked)->values());
    }

    private function movementRows(Request $request)
    {
        return StockMovement::with(['asset.category', 'creator'])
            ->when($request->tipe, fn ($q, $v) => $q->where('type', $v))
            ->when($request->alasan, fn ($q, $v) => $q->where('reason', $v))
            ->when($request->category_id, fn ($q, $v) => $q->whereHas('asset', fn ($a) => $a->where('category_id', $v)))
            ->when($request->dari, fn ($q, $v) => $q->whereDate('moved_at', '>=', $v))
            ->when($request->sampai, fn ($q, $v) => $q->whereDate('moved_at', '<=', $v))
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->get();
    }

    // ---------- foto bukti ----------

    private function wantsEvidence(Request $request, string $type): bool
    {
        return $type === 'peminjaman' && $request->boolean('foto');
    }

    /**
     * Peminjaman yang punya foto (dibatasi agar berkas tidak terlalu besar).
     *
     * Key yang selalu ada: loans, items, total, limited, limit.
     * $withUris = false melewati pembuatan thumbnail base64 (items berisi collection kosong).
     */
    private function evidence($data, bool $withUris = true): array
    {
        $with  = $data->filter(fn ($l) => $l->photos->isNotEmpty())->values();
        $loans = $with->take(self::EVIDENCE_LIMIT)->values();

        return [
            'loans'   => $loans,
            'items'   => $withUris
                ? $loans->map(fn ($l) => [
                    'loan'   => $l,
                    'before' => $this->photoUris($l->photos->where('stage', 'sebelum')),
                    'after'  => $this->photoUris($l->photos->where('stage', 'sesudah')),
                ])
                : collect(),
            'total'   => $with->count(),
            'limited' => $with->count() > self::EVIDENCE_LIMIT,
            'limit'   => self::EVIDENCE_LIMIT,
        ];
    }

    // Foto dalam bentuk data URI (untuk cetak dan PDF)
    private function photoUris($photos): array
    {
        return $photos->map(function ($p) {
            $bin = ImageUpload::reportThumbnail($p->path);

            return $bin ? 'data:image/jpeg;base64,' . base64_encode($bin) : null;
        })->filter()->values()->all();
    }

    private function addWordEvidence(PhpWord $word, array $evidence, array &$temp): void
    {
        $page = ['orientation' => 'landscape', 'marginTop' => 700, 'marginBottom' => 700, 'marginLeft' => 800, 'marginRight' => 800];

        if ($evidence['loans']->isEmpty()) {
            $s = $word->addSection($page);
            $s->addText('Lampiran Foto Bukti', ['bold' => true, 'size' => 14, 'color' => 'DC143C']);
            $s->addText('Tidak ada peminjaman yang memiliki foto bukti pada filter ini.', ['color' => '777777']);

            return;
        }

        $count = $evidence['loans']->count();

        foreach ($evidence['loans'] as $i => $l) {
            $s = $word->addSection($page);
            $s->addText('Bukti Foto Peminjaman (' . ($i + 1) . ' dari ' . $count . ')', ['bold' => true, 'size' => 14, 'color' => 'DC143C']);

            if ($i === 0 && $evidence['limited']) {
                $s->addText(
                    "Menampilkan {$evidence['limit']} dari {$evidence['total']} peminjaman berfoto. Persempit filter untuk melihat sisanya.",
                    ['italic' => true, 'color' => '92400E', 'size' => 9]
                );
            }

            $pairs = [
                ['Peminjam', $l->borrower_name],
                ['Aset', $l->asset->asset_code . ' - ' . $l->asset->name],
                ['Kontak / Divisi', $l->borrower_contact ?? '-'],
                ['Jumlah', $l->quantity . ' unit'],
                ['Tgl Pinjam', $l->loaned_at->format('d M Y')],
                ['Batas Kembali', $l->due_at?->format('d M Y') ?? '-'],
                ['Tgl Kembali', $l->returned_at?->format('d M Y') ?? '-'],
                ['Status', $l->status_label],
                ['Lokasi Awal', $l->originLocation?->full_name ?? '-'],
                ['Lokasi Terakhir', $l->last_location_label ?? 'Belum dicatat'],
            ];

            $t = $s->addTable(['borderSize' => 6, 'borderColor' => 'CBD5E1', 'cellMargin' => 50]);
            foreach (array_chunk($pairs, 2) as $row) {
                $t->addRow();
                foreach ($row as [$k, $v]) {
                    $t->addCell(1900, ['bgColor' => 'F1F5F9'])->addText($k, ['bold' => true, 'size' => 9]);
                    $t->addCell(5700)->addText((string) $v, ['size' => 9]);
                }
            }

            $this->addWordPhotos($s, 'Sebelum dipinjam', $l->photos->where('stage', 'sebelum'), $temp);
            $this->addWordPhotos($s, 'Sesudah dikembalikan', $l->photos->where('stage', 'sesudah'), $temp);
        }
    }

    private function addWordPhotos($section, string $label, $photos, array &$temp): void
    {
        $section->addText($label . ' (' . $photos->count() . ' foto)', ['bold' => true, 'size' => 10], ['spaceBefore' => 120]);

        if ($photos->isEmpty()) {
            $section->addText('Tidak ada foto.', ['color' => '777777', 'size' => 9]);

            return;
        }

        $t = $section->addTable(['cellMargin' => 60]);
        $t->addRow();

        foreach ($photos as $p) {
            $cell = $t->addCell(2800);
            $bin  = ImageUpload::reportThumbnail($p->path, 700, 525, 75);

            if (! $bin) {
                $cell->addText('(foto tidak dapat dibaca)', ['color' => '777777', 'size' => 9]);
                continue;
            }

            $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tams_' . Str::random(16) . '.jpg';
            file_put_contents($file, $bin);
            $temp[] = $file;

            $cell->addImage($file, ['width' => 150, 'height' => 112]);
        }
    }

    // ---------- filter, ekspor, dan tabel ----------

    private function filterText(Request $request, string $type): array
    {
        $f = [];

        if ($type === 'aset') {
            if ($request->category_id) $f['Kategori'] = Category::find($request->category_id)?->name;
            if ($request->location_id) $f['Lokasi']   = Location::find($request->location_id)?->name;
            if ($request->condition)   $f['Kondisi']  = ucfirst($request->condition);
            if ($request->status)      $f['Status']   = ucfirst($request->status);
        } elseif ($type === 'opname') {
            if ($o = $this->opnameFor($request)) {
                $f['Sesi']    = $o->code . ' - ' . $o->name;
                $f['Tanggal'] = $o->opname_date->format('d M Y');
                $f['Cakupan'] = $o->location->name ?? 'Seluruh aset';
                $f['Status']  = $o->status_label . ($o->is_running ? '' : ($o->adjustments_applied ? ' (diterapkan ke data aset)' : ' (hanya dicatat)'));
            } else {
                $f['Sesi'] = 'Semua sesi';
            }
            if ($request->location_id) $f['Ruangan'] = Location::find($request->location_id)?->name;
            if ($request->hasil) {
                $f['Tampilan'] = $request->hasil === 'selisih' ? 'Hanya selisih / kondisi berbeda' : 'Belum dicek';
            }
        } elseif ($type === 'mutasi') {
            if ($request->tipe)        $f['Jenis']        = ucfirst($request->tipe);
            if ($request->alasan)      $f['Alasan']       = ucfirst($request->alasan);
            if ($request->category_id) $f['Kategori']     = Category::find($request->category_id)?->name;
            if ($request->dari)        $f['Dari tanggal'] = Carbon::parse($request->dari)->format('d M Y');
            if ($request->sampai)      $f['Sampai']       = Carbon::parse($request->sampai)->format('d M Y');
        } else {
            if ($request->status) $f['Status']       = ucfirst($request->status);
            if ($request->dari)   $f['Dari tanggal'] = Carbon::parse($request->dari)->format('d M Y');
            if ($request->sampai) $f['Sampai']       = Carbon::parse($request->sampai)->format('d M Y');
            if ($request->boolean('foto')) $f['Foto bukti'] = 'Disertakan (maks. ' . self::EVIDENCE_LIMIT . ' peminjaman)';
        }

        return array_filter($f);
    }

    private function exportData(Request $request): array
    {
        $type = $this->type($request);
        $data = $this->rows($request, $type);

        [$head, $rows, $total] = $this->tabular($type, $data);

        return [$type, $this->title($type), $head, $rows, $total, $this->filterText($request, $type), $data];
    }

    private function tabular(string $type, $data): array
    {
        if ($type === 'aset') {
            $head = ['No', 'Kode', 'Nama Aset', 'Kategori', 'Lokasi', 'Jumlah', 'Dipinjam', 'Tersedia', 'Kondisi', 'Status'];

            $rows = $data->values()->map(function ($a, $i) {
                $b = (int) $a->borrowed_sum;

                return [$i + 1, $a->asset_code, $a->name, $a->category->name, $a->location->name,
                        $a->quantity, $b, max(0, $a->quantity - $b),
                        ucfirst($a->asset_condition), ucfirst($a->status)];
            })->all();

            $total = ['', '', 'Total (' . $data->count() . ' aset)', '', '',
                    $data->sum('quantity'),
                    $data->sum(fn ($a) => (int) $a->borrowed_sum),
                    $data->sum(fn ($a) => max(0, $a->quantity - (int) $a->borrowed_sum)),
                    '', ''];
        } elseif ($type === 'opname') {
            $head = ['No', 'Sesi', 'Kode', 'Nama Aset', 'Lokasi', 'Di Sistem', 'Dipinjam', 'Seharusnya',
                    'Fisik', 'Selisih', 'Kondisi Sistem', 'Kondisi Fisik', 'Catatan'];

            $rows = $data->values()->map(fn ($it, $i) => [
                $i + 1, $it->opname->code, $it->asset->asset_code, $it->asset->name, $it->asset->location->name,
                $it->system_qty, $it->borrowed_qty, $it->expected_qty,
                $it->is_checked ? $it->physical_qty : '-',
                $it->diff_label,
                ucfirst($it->system_condition),
                $it->physical_condition ? ucfirst($it->physical_condition) : '-',
                $it->notes ?? '-',
            ])->all();

            $checked = $data->filter(fn ($i) => $i->is_checked);
            $summary = 'Sesuai ' . $checked->filter(fn ($i) => $i->difference === 0)->count()
                    . ' | Kurang ' . $checked->filter(fn ($i) => $i->difference < 0)->count()
                    . ' | Lebih ' . $checked->filter(fn ($i) => $i->difference > 0)->count()
                    . ' | Belum dicek ' . ($data->count() - $checked->count());

            $total = ['', 'Total (' . $data->count() . ' baris)', '', '', '',
                    $data->sum('system_qty'),
                    $data->sum('borrowed_qty'),
                    $data->sum(fn ($i) => $i->expected_qty),
                    $checked->sum('physical_qty'),
                    $summary, '', '', ''];
        } elseif ($type === 'mutasi') {
            $head = ['No', 'Tanggal', 'Kode', 'Nama Aset', 'Jenis', 'Alasan', 'Jumlah',
                    'Stok Sebelum', 'Stok Sesudah', 'Nota', 'Petugas', 'Catatan'];

            $rows = $data->values()->map(fn ($m, $i) => [
                $i + 1,
                $m->moved_at->format('d M Y'),
                $m->asset->asset_code,
                $m->asset->name,
                $m->type_label,
                $m->reason_label,
                ($m->type === 'masuk' ? '+' : '-') . $m->quantity,
                $m->qty_before,
                $m->qty_after,
                $m->reference ?? '-',
                $m->creator->name ?? '-',
                $m->notes ?? '-',
            ])->all();

            $in  = (int) $data->where('type', 'masuk')->sum('quantity');
            $out = (int) $data->where('type', 'keluar')->sum('quantity');
            $net = $in - $out;

            $total = ['', 'Total (' . $data->count() . ' mutasi)', '', '',
                    'Masuk ' . $in, 'Keluar ' . $out, ($net > 0 ? '+' : '') . $net,
                    '', '', '', '', ''];
        } else {
            $head = ['No', 'Peminjam', 'Kontak / Divisi', 'Aset', 'Jumlah', 'Lokasi Awal', 'Lokasi Terakhir',
                    'Tgl Pinjam', 'Batas Kembali', 'Tgl Kembali', 'Status', 'Foto (Sebelum/Sesudah)'];

            $rows = $data->values()->map(fn ($l, $i) => [
                $i + 1,
                $l->borrower_name,
                $l->borrower_contact ?? '-',
                $l->asset->asset_code . ' - ' . $l->asset->name,
                $l->quantity,
                $l->originLocation?->full_name ?? '-',
                $l->last_location_label ?? 'Belum dicatat',
                $l->loaned_at->format('d M Y'),
                $l->due_at?->format('d M Y') ?? '-',
                $l->returned_at?->format('d M Y') ?? '-',
                $l->status_label,
                $l->photos->where('stage', 'sebelum')->count() . ' / ' . $l->photos->where('stage', 'sesudah')->count(),
            ])->all();

            $total = ['', 'Total (' . $data->count() . ' peminjaman)', '', '', $data->sum('quantity'), '', '', '', '', '', '', ''];
        }

        return [$head, $rows, $total];
    }

    private function filterLine(array $filters): string
    {
        return collect($filters)->map(fn ($v, $k) => "{$k}: {$v}")->implode(', ');
    }

    private function fileName(string $type, string $ext): string
    {
        return 'laporan-' . $type . '-' . now()->format('Ymd-His') . '.' . $ext;
    }
}