<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Location;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\AssetHistory;

class StockOpnameController extends Controller
{
    public function index()
    {
        $opnames = StockOpname::with(['location', 'creator'])
            ->withCount('items')
            ->withCount(['items as checked_count' => fn ($q) => $q->whereNotNull('physical_qty')])
            ->orderByDesc('id')
            ->paginate(10);

        return view('opnames.index', compact('opnames'));
    }

    public function create()
    {
        return view('opnames.create', [
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'opname_date' => 'required|date',
            'location_id' => 'nullable|exists:locations,id',
            'notes'       => 'nullable|string|max:255',
        ], [
            'name.required'        => 'Nama sesi opname wajib diisi.',
            'opname_date.required' => 'Tanggal opname wajib diisi.',
        ]);

        $locationId = $data['location_id'] ?? null;

        // Satu sesi berjalan per ruangan (atau satu untuk seluruh aset)
        $running = StockOpname::where('status', 'berjalan')
            ->when($locationId,
                fn ($q) => $q->where('location_id', $locationId),
                fn ($q) => $q->whereNull('location_id'))
            ->exists();

        if ($running) {
            throw ValidationException::withMessages([
                'location_id' => 'Masih ada sesi opname yang berjalan untuk cakupan ini. Selesaikan dulu sebelum membuat yang baru.',
            ]);
        }

        // Foto data aset aktif saat ini
        $assets = Asset::where('status', 'aktif')
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->withSum(['loans as borrowed_sum' => fn ($q) => $q->whereNull('returned_at')], 'quantity')
            ->orderBy('asset_code')
            ->get();

        if ($assets->isEmpty()) {
            throw ValidationException::withMessages([
                'location_id' => 'Tidak ada aset aktif pada cakupan ini, sehingga sesi tidak dapat dibuat.',
            ]);
        }

        $opname = DB::transaction(function () use ($data, $assets) {
            $opname = StockOpname::create([
                'code'        => StockOpname::generateCode(),
                'name'        => $data['name'],
                'opname_date' => $data['opname_date'],
                'location_id' => $data['location_id'] ?? null,
                'notes'       => $data['notes'] ?? null,
                'status'      => 'berjalan',
                'created_by'  => auth()->id(),
            ]);

            $now  = now();
            $rows = $assets->map(fn ($a) => [
                'stock_opname_id'  => $opname->id,
                'asset_id'         => $a->id,
                'system_qty'       => $a->quantity,
                'borrowed_qty'     => (int) $a->borrowed_sum,
                'system_condition' => $a->asset_condition,
                'created_at'       => $now,
                'updated_at'       => $now,
            ])->all();

            StockOpnameItem::insert($rows);

            return $opname;
        });

        return redirect()->route('opnames.index')
            ->with('success', "Sesi {$opname->code} dibuat dengan {$assets->count()} aset untuk dihitung.");
    }

    public function show(StockOpname $opname)
    {
        $opname->load(['location', 'creator' , 'finisher']);

        $items = $opname->items()
            ->with(['asset.category', 'asset.location'])
            ->get()
            ->sortBy(fn ($i) => $i->asset->asset_code)
            ->values();

        return view('opnames.show', [
            'opname'  => $opname,
            'items'   => $items,
            'checked' => $items->whereNotNull('physical_qty')->count(),
        ]);
    }

    public function updateItem(Request $request, StockOpname $opname, StockOpnameItem $item)
    {
        abort_unless((int) $item->stock_opname_id === (int) $opname->id, 404);

        if (! $opname->is_running) {
            return response()->json(['message' => 'Sesi opname sudah selesai dan tidak dapat diubah.'], 422);
        }

        $data = $request->validate([
            'physical_qty'       => 'required|integer|min:0|max:1000000',
            'physical_condition' => 'required|in:baik,rusak,perbaikan',
            'notes'              => 'nullable|string|max:255',
        ], [
            'physical_qty.required'       => 'Jumlah fisik wajib diisi.',
            'physical_qty.integer'        => 'Jumlah fisik harus berupa angka bulat.',
            'physical_qty.min'            => 'Jumlah fisik tidak boleh negatif.',
            'physical_condition.required' => 'Kondisi fisik wajib dipilih.',
        ]);

        $item->update($data + [
            'checked_by' => auth()->id(),
            'checked_at' => now(),
        ]);
        $item->refresh();

        return response()->json([
            'ok'         => true,
            'difference' => $item->difference,
            'checked'    => $opname->items()->whereNotNull('physical_qty')->count(),
            'total'      => $opname->items()->count(),
        ]);
    }

    public function finishForm(StockOpname $opname)
    {
        if (! $opname->is_running) {
            return redirect()->route('opnames.show', $opname)->with('info', 'Sesi ini sudah selesai.');
        }

        $items = $opname->items()->with('asset.category')->get();
        $stats = $this->summarize($items);

        if ($stats['checked'] === 0) {
            return redirect()->route('opnames.show', $opname)
                ->with('error', 'Belum ada aset yang dicek. Isi minimal satu hasil hitung sebelum menyelesaikan opname.');
        }

        // Aset yang bermasalah: selisih jumlah atau kondisi berbeda
        $problems = $items
            ->filter(fn ($i) => $i->is_checked && ($i->difference !== 0 || $i->physical_condition !== $i->system_condition))
            ->sortBy(fn ($i) => $i->asset->asset_code)
            ->values();

        // Pratinjau dampak jika diterapkan
        $problems->each(function ($i) {
            $i->preview_qty = $i->asset->quantity + $i->difference;
            $i->preview_ok  = ! $i->asset->trashed()
                && $i->preview_qty >= 1
                && $i->preview_qty >= $i->asset->borrowed;
        });

        return view('opnames.finish', compact('opname', 'stats', 'problems'));
    }

    public function finish(Request $request, StockOpname $opname)
    {
        $request->validate([
            'confirm' => 'accepted',
        ], [
            'confirm.accepted' => 'Centang pernyataan konfirmasi terlebih dahulu.',
        ]);

        if (! $opname->items()->whereNotNull('physical_qty')->exists()) {
            return redirect()->route('opnames.show', $opname)
                ->with('error', 'Belum ada aset yang dicek.');
        }

        $apply   = $request->boolean('apply');
        $report  = ['quantity' => 0, 'condition' => 0, 'skipped' => []];
        $already = false;

        DB::transaction(function () use ($opname, $apply, &$report, &$already) {
            // Kunci sesi agar tidak diselesaikan dua kali bersamaan
            $fresh = StockOpname::whereKey($opname->id)->lockForUpdate()->first();

            if (! $fresh->is_running) {
                $already = true;
                return;
            }

            if ($apply) {
                $items = $fresh->items()->whereNotNull('physical_qty')->get();

                foreach ($items as $item) {
                    $asset = Asset::withTrashed()->whereKey($item->asset_id)->lockForUpdate()->first();

                    if (! $asset || $asset->trashed()) {
                        $report['skipped'][] = ($asset->asset_code ?? '#' . $item->asset_id) . ' (aset sudah dihapus)';
                        continue;
                    }

                    $this->applyItem($fresh, $item, $asset, $report);
                }
            }

            $fresh->update([
                'status'              => 'selesai',
                'adjustments_applied' => $apply,
                'finished_by'         => auth()->id(),
                'finished_at'         => now(),
            ]);
        });

        if ($already) {
            return redirect()->route('opnames.show', $opname)->with('info', 'Sesi ini sudah diselesaikan sebelumnya.');
        }

        $message = $apply
            ? "Opname {$opname->code} selesai. {$report['quantity']} jumlah aset dan {$report['condition']} kondisi aset disesuaikan."
            : "Opname {$opname->code} selesai. Hasil dicatat tanpa mengubah data aset.";

        $redirect = redirect()->route('opnames.show', $opname)->with('success', $message);

        if ($report['skipped']) {
            $redirect->with('warning', 'Dilewati, perlu ditinjau manual: ' . implode('; ', $report['skipped']) . '.');
        }

        return $redirect;
    }

    // ---------- helper ----------

    private function applyItem(StockOpname $opname, StockOpnameItem $item, Asset $asset, array &$report): void
    {
        $diff  = $item->difference;
        $notes = "Penyesuaian stok opname {$opname->code}";

        if ($diff !== 0) {
            $newQty = $asset->quantity + $diff;

            if ($newQty < 1 || $newQty < $asset->borrowed) {
                $report['skipped'][] = "{$asset->asset_code} (jumlah baru {$newQty} tidak valid)";
            } else {
                $this->history($asset, 'update', 'quantity', (string) $asset->quantity, (string) $newQty, $notes);
                $asset->quantity = $newQty;
                $report['quantity']++;
            }
        }

        if ($item->physical_condition !== $asset->asset_condition) {
            $this->history($asset, 'condition', 'asset_condition', $asset->asset_condition, $item->physical_condition, $notes);
            $asset->asset_condition = $item->physical_condition;
            $report['condition']++;
        }

        if ($asset->isDirty()) {
            $asset->updated_by = auth()->id();
            $asset->save();
        }
    }

    private function history(Asset $asset, string $action, string $field, ?string $old, ?string $new, string $notes): void
    {
        AssetHistory::create([
            'asset_id'   => $asset->id,
            'user_id'    => auth()->id(),
            'action'     => $action,
            'field_name' => $field,
            'old_value'  => $old,
            'new_value'  => $new,
            'notes'      => $notes,
            'created_at' => now(),
        ]);
    }

    private function summarize($items): array
    {
        $checked = $items->filter(fn ($i) => $i->is_checked);
        $short   = $checked->filter(fn ($i) => $i->difference < 0);
        $over    = $checked->filter(fn ($i) => $i->difference > 0);

        return [
            'total'       => $items->count(),
            'checked'     => $checked->count(),
            'unchecked'   => $items->count() - $checked->count(),
            'match'       => $checked->filter(fn ($i) => $i->difference === 0)->count(),
            'short'       => $short->count(),
            'short_units' => abs($short->sum(fn ($i) => $i->difference)),
            'over'        => $over->count(),
            'over_units'  => $over->sum(fn ($i) => $i->difference),
            'cond'        => $checked->filter(fn ($i) => $i->physical_condition !== $i->system_condition)->count(),
        ];
    }

}