<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Location;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
}