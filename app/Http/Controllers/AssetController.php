<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $assets = Asset::with(['category', 'location'])
            ->withSum(['loans as borrowed_sum' => fn ($q) => $q->whereNull('returned_at')], 'quantity')
            ->when($request->q, fn ($q, $v) => $q->where(
                fn ($w) => $w->where('asset_code', 'like', "%{$v}%")->orWhere('name', 'like', "%{$v}%")
            ))
            ->when($request->category_id, fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->location_id, fn ($q, $v) => $q->where('location_id', $v))
            ->when($request->condition, fn ($q, $v) => $q->where('asset_condition', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('assets.index', [
            'assets'     => $assets,
            'categories' => Category::orderBy('name')->get(),
            'locations'  => Location::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('assets.create', [
            'categories' => Category::orderBy('name')->get(),
            'locations'  => Location::orderBy('name')->get(),
            'nextCode'   => Asset::generateCode(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $asset = DB::transaction(function () use ($data) {
            $asset = Asset::create($data + [
                'asset_code' => Asset::generateCode(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            $this->log($asset, 'create', null, null, null, 'Aset ditambahkan');

            return $asset;
        });

        return redirect()->route('assets.show', $asset)
            ->with('success', "Aset {$asset->asset_code} berhasil ditambahkan.");
    }

    public function show(Asset $asset)
    {
        $asset->load(['category', 'location']);
        $histories = $asset->histories()->with('user')->limit(10)->get();

        return view('assets.show', compact('asset', 'histories'));
    }

    public function edit(Asset $asset)
    {
        return view('assets.edit', [
            'asset'      => $asset,
            'categories' => Category::orderBy('name')->get(),
            'locations'  => Location::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Asset $asset)
    {
        if ((int) $request->quantity < $asset->borrowed) {
            return back()->withInput()->withErrors([
                'quantity' => "Jumlah tidak boleh kurang dari unit yang sedang dipinjam ({$asset->borrowed}).",
            ]);
        }
        $asset->fill($this->validated($request));
        $dirty = $asset->getDirty();
        $old   = $asset->getOriginal();

        if (! $dirty) {
            return redirect()->route('assets.show', $asset)->with('info', 'Tidak ada perubahan data.');
        }

        DB::transaction(function () use ($asset, $dirty, $old) {
            $asset->updated_by = auth()->id();
            $asset->save();
            $this->logChanges($asset, $dirty, $old);
        });

        return redirect()->route('assets.show', $asset)
            ->with('success', "Aset {$asset->asset_code} berhasil diperbarui.");
    }

    public function destroy(Asset $asset)
    {
        DB::transaction(function () use ($asset) {
            $this->log($asset, 'delete', null, null, null, 'Aset dihapus (arsip)');
            $asset->delete(); // soft delete
        });

        return redirect()->route('assets.index')
            ->with('success', "Aset {$asset->asset_code} berhasil dihapus.");
    }

    public function barcode(Request $request, Asset $asset)
    {
        abort_unless(Asset::isValidCode($asset->asset_code), 404);

        $headers = ['Content-Type' => 'image/svg+xml'];

        if ($request->boolean('unduh')) {
            $headers['Content-Disposition'] = 'attachment; filename="barcode-' . $asset->asset_code . '.svg"';
        }

        return response($asset->barcodeSvg(), 200, $headers);
    }

    // ---------- helper ----------

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'            => 'required|string|max:150',
            'category_id'     => 'required|exists:categories,id',
            'location_id'     => 'required|exists:locations,id',
            'asset_condition' => 'required|in:baik,rusak,perbaikan',
            'status'          => 'required|in:aktif,nonaktif',
            'description'     => 'nullable|string|max:1000',
            'serial_number'   => 'nullable|string|max:100',
            'purchase_date'   => 'nullable|date',
            'quantity'        => 'required|integer|min:1|max:100000',
        ]);
    }

    private function logChanges(Asset $asset, array $dirty, array $old): void
    {
        $special = ['location_id' => 'location', 'asset_condition' => 'condition', 'status' => 'status'];
        $general = [];

        foreach ($dirty as $field => $new) {
    // Perubahan jumlah dicatat dengan nilai lama dan baru
            if ($field === 'quantity') {
                $this->log($asset, 'update', 'quantity', (string) ($old['quantity'] ?? ''), (string) $new, 'Jumlah unit diubah');
                continue;
            }

            if (! isset($special[$field])) {
                $general[] = $field;
                continue;
            }

            $oldVal = $old[$field] ?? null;
            $newVal = $new;

            if ($field === 'location_id') {
                $oldVal = Location::find($oldVal)?->name;
                $newVal = Location::find($new)?->name;
            }

            $this->log($asset, $special[$field], $field, $oldVal, $newVal);
        }

        if ($general) {
            $this->log($asset, 'update', implode(', ', $general));
        }
    }

    private function log(Asset $asset, string $action, ?string $field = null, ?string $old = null, ?string $new = null, ?string $notes = null): void
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