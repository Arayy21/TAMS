<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $movements = StockMovement::with(['asset', 'creator'])
            ->when($request->q, function ($q, $v) {
                $q->where(function ($w) use ($v) {
                    $w->where('reference', 'like', "%{$v}%")
                      ->orWhereHas('asset', fn ($a) => $a
                          ->where('asset_code', 'like', "%{$v}%")
                          ->orWhere('name', 'like', "%{$v}%"));
                });
            })
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->when($request->reason, fn ($q, $v) => $q->where('reason', $v))
            ->when($request->dari, fn ($q, $v) => $q->whereDate('moved_at', '>=', $v))
            ->when($request->sampai, fn ($q, $v) => $q->whereDate('moved_at', '<=', $v))
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $start = now()->startOfMonth()->toDateString();
        $end   = now()->endOfMonth()->toDateString();
        $month = fn () => StockMovement::whereBetween('moved_at', [$start, $end]);

        $summary = [
            'masuk'     => (int) $month()->where('type', 'masuk')->sum('quantity'),
            'keluar'    => (int) $month()->where('type', 'keluar')->sum('quantity'),
            'transaksi' => $month()->count(),
        ];

        return view('movements.index', compact('movements', 'summary'));
    }

    public function create(Request $request)
    {
        $type = $request->query('jenis') === 'keluar' ? 'keluar' : 'masuk';

        $assets = Asset::withSum(['loans as borrowed_sum' => fn ($q) => $q->whereNull('returned_at')], 'quantity')
            ->orderBy('name')
            ->get()
            ->each(fn ($a) => $a->available_qty = max(0, $a->quantity - (int) $a->borrowed_sum));

        // Barang keluar hanya untuk aset yang masih punya unit tersedia
        if ($type === 'keluar') {
            $assets = $assets->filter(fn ($a) => $a->available_qty > 0)->values();
        }

        return view('movements.create', [
            'type'     => $type,
            'assets'   => $assets,
            'selected' => $request->asset_id,
        ]);
    }

    public function store(Request $request)
    {
        $reasons = $request->type === 'masuk' ? ['pembelian'] : ['rusak', 'dibuang'];

        $data = $request->validate([
            'type'      => ['required', Rule::in(['masuk', 'keluar'])],
            'asset_id'  => ['required', Rule::exists('assets', 'id')->whereNull('deleted_at')],
            'reason'    => ['required', Rule::in($reasons)],
            'quantity'  => 'required|integer|min:1|max:100000',
            'moved_at'  => 'required|date|before_or_equal:today',
            'reference' => 'nullable|string|max:50',
            'notes'     => 'nullable|string|max:255',
        ], [
            'asset_id.required'      => 'Pilih aset terlebih dahulu.',
            'reason.required'        => 'Alasan wajib dipilih.',
            'reason.in'              => 'Alasan tidak sesuai dengan jenis mutasi.',
            'quantity.required'      => 'Jumlah wajib diisi.',
            'quantity.min'           => 'Jumlah minimal 1.',
            'moved_at.required'      => 'Tanggal wajib diisi.',
            'moved_at.before_or_equal' => 'Tanggal tidak boleh melebihi hari ini.',
        ]);

        $result = ['reactivated' => false, 'deactivated' => false];
        $movement = null;

        DB::transaction(function () use ($data, &$result, &$movement) {
            // Kunci baris aset agar dua pencatatan bersamaan tidak melewati stok
            $asset  = Asset::whereKey($data['asset_id'])->lockForUpdate()->firstOrFail();
            $before = (int) $asset->quantity;

            if ($data['type'] === 'keluar') {
                $available = $asset->available;

                if ($data['quantity'] > $available) {
                    throw ValidationException::withMessages([
                        'quantity' => "Jumlah melebihi unit yang tersedia ({$available} unit). Unit yang sedang dipinjam tidak dapat dikeluarkan.",
                    ]);
                }
                $after = $before - $data['quantity'];
            } else {
                $after = $before + $data['quantity'];

                if ($after > 100000) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Jumlah total unit tidak boleh lebih dari 100.000.',
                    ]);
                }
            }

            $movement = StockMovement::create([
                'asset_id'   => $asset->id,
                'type'       => $data['type'],
                'reason'     => $data['reason'],
                'quantity'   => $data['quantity'],
                'qty_before' => $before,
                'qty_after'  => $after,
                'moved_at'   => $data['moved_at'],
                'reference'  => $data['reference'] ?? null,
                'notes'      => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $label = ($data['type'] === 'masuk' ? 'Barang masuk' : 'Barang keluar') . ': ' . ucfirst($data['reason'])
                   . ($movement->reference ? " (nota {$movement->reference})" : '');

            $this->history($asset, 'update', 'quantity', (string) $before, (string) $after, $label);
            $asset->quantity = $after;

            // Semua unit keluar: aset dinonaktifkan. Masuk pada aset kosong: diaktifkan kembali.
            if ($after === 0 && $asset->status === 'aktif') {
                $this->history($asset, 'status', 'status', 'aktif', 'nonaktif', 'Semua unit keluar');
                $asset->status = 'nonaktif';
                $result['deactivated'] = true;
            } elseif ($data['type'] === 'masuk' && $before === 0 && $asset->status === 'nonaktif') {
                $this->history($asset, 'status', 'status', 'nonaktif', 'aktif', 'Barang masuk pada aset kosong');
                $asset->status = 'aktif';
                $result['reactivated'] = true;
            }

            $asset->updated_by = auth()->id();
            $asset->save();
        });

        $movement->load('asset');
        $sign = $movement->type === 'masuk' ? 'masuk' : 'keluar';

        $redirect = redirect()->route('movements.index')->with(
            'success',
            "Barang {$sign} {$movement->asset->asset_code} sebanyak {$movement->quantity} unit dicatat. Jumlah sekarang {$movement->qty_after} unit."
        );

        if ($result['deactivated']) {
            $redirect->with('warning', "Semua unit {$movement->asset->asset_code} telah keluar, sehingga aset otomatis dinonaktifkan.");
        } elseif ($result['reactivated']) {
            $redirect->with('info', "{$movement->asset->asset_code} diaktifkan kembali karena ada barang masuk.");
        }

        return $redirect;
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
}