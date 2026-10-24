<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\Category;
use App\Models\Location;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

        // Aset baru minimal 1 unit (nol hanya terjadi lewat Barang Keluar)
        if ($data['quantity'] < 1) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah unit minimal 1 untuk aset baru.']);
        }

        $file = $this->uploadedPhoto($request);
        $path = null;

        try {
            if ($file) {
                $path = ImageUpload::store($file, $this->photoDir(), 'photo');
            }

            $asset = DB::transaction(function () use ($data, $path) {
                $asset = Asset::create($data + [
                    'asset_code' => Asset::generateCode(),
                    'photo'      => $path,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
                $this->log($asset, 'create', null, null, null, $path ? 'Aset ditambahkan (dengan foto)' : 'Aset ditambahkan');

                return $asset;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }

        return redirect()->route('assets.show', $asset)
            ->with('success', "Aset {$asset->asset_code} berhasil ditambahkan.");
    }

    public function show(Asset $asset)
    {
        $asset->load(['category', 'location.parent']);
        $histories = $asset->histories()->with('user')->limit(10)->get();

        // Peminjaman yang masih berjalan (beserta lokasi awal dan lokasi terakhir)
        $activeLoans = $asset->loans()->active()
            ->with(['originLocation.parent', 'lastLocation.parent'])
            ->orderBy('due_at')
            ->get();

        // Aset lain di ruangan yang sama
        $roomTotal = Asset::where('location_id', $asset->location_id)->count();
        $roommates = Asset::where('location_id', $asset->location_id)
            ->where('id', '!=', $asset->id)
            ->orderBy('asset_code')
            ->limit(5)
            ->get(['id', 'asset_code', 'name']);

        $movements = collect();
        $movementTotals = ['masuk' => 0, 'keluar' => 0];

        if (auth()->user()->isAdmin()) {
            $movements = $asset->movements()->with('creator')
                ->orderByDesc('moved_at')->orderByDesc('id')->limit(10)->get();

            $movementTotals = [
                'masuk'  => (int) $asset->movements()->where('type', 'masuk')->sum('quantity'),
                'keluar' => (int) $asset->movements()->where('type', 'keluar')->sum('quantity'),
            ];
        }

        return view('assets.show', compact(
            'asset', 'histories', 'activeLoans', 'movements', 'movementTotals', 'roomTotal', 'roommates'
        ));
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
        // Jumlah tidak boleh kurang dari unit yang sedang dipinjam
        if ((int) $request->quantity < $asset->borrowed) {
            return back()->withInput()->withErrors([
                'quantity' => "Jumlah tidak boleh kurang dari unit yang sedang dipinjam ({$asset->borrowed}).",
            ]);
        }

        $asset->fill($this->validated($request));

        $oldPhoto = $asset->photo;
        $newPath  = null;
        $file     = $this->uploadedPhoto($request);

        try {
            if ($file) {
                $newPath      = ImageUpload::store($file, $this->photoDir(), 'photo');
                $asset->photo = $newPath;
            } elseif ($request->boolean('remove_photo') && $oldPhoto) {
                $asset->photo = null;
            }

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
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $e;
        }

        // Foto lama dihapus setelah data berhasil disimpan
        if ($oldPhoto && $oldPhoto !== $asset->photo) {
            Storage::disk('local')->delete($oldPhoto);
        }

        return redirect()->route('assets.show', $asset)
            ->with('success', "Aset {$asset->asset_code} berhasil diperbarui.");
    }

    public function destroy(Asset $asset)
    {
        DB::transaction(function () use ($asset) {
            $this->log($asset, 'delete', null, null, null, 'Aset dihapus (arsip)');
            $asset->delete(); // soft delete: foto tetap tersimpan
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

    // Menyajikan foto aset (hanya untuk pengguna yang sudah login)
    public function photo(Asset $asset)
    {
        abort_unless($asset->photo && Storage::disk('local')->exists($asset->photo), 404);

        return response()->file(Storage::disk('local')->path($asset->photo), [
            'Cache-Control'          => 'private, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // ---------- helper ----------

    private function validated(Request $request): array
    {
        $v = $request->validate([
            'name'            => 'required|string|max:150',
            'category_id'     => 'required|exists:categories,id',
            'location_id'     => 'required|exists:locations,id',
            'quantity'        => 'required|integer|min:0|max:100000',
            'asset_condition' => 'required|in:baik,rusak,perbaikan',
            'status'          => 'required|in:aktif,nonaktif',
            'description'     => 'nullable|string|max:1000',
            'serial_number'   => 'nullable|string|max:100',
            'purchase_date'   => 'nullable|date',
            'photo'           => 'nullable|array|max:1',
            'photo.*'         => 'image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=8000,max_height=8000',
            'remove_photo'    => 'nullable|boolean',
        ], [
            'photo.max'          => 'Hanya 1 foto yang dapat dilampirkan.',
            'photo.*.uploaded'   => 'Foto gagal diunggah. Ukurannya mungkin melebihi batas server.',
            'photo.*.image'      => 'File harus berupa gambar.',
            'photo.*.mimes'      => 'Format foto harus JPG, PNG, atau WebP.',
            'photo.*.max'        => 'Ukuran foto maksimal 5 MB.',
            'photo.*.dimensions' => 'Resolusi foto terlalu besar (maksimal 8000 x 8000 piksel).',
        ]);

        return Arr::except($v, ['photo', 'remove_photo']);
    }

    // Foto yang diunggah (satu berkas), atau null
    private function uploadedPhoto(Request $request): ?UploadedFile
    {
        $f = $request->file('photo');

        if (is_array($f)) {
            $f = Arr::first(array_filter($f));
        }

        return $f instanceof UploadedFile ? $f : null;
    }

    private function photoDir(): string
    {
        return 'assets/' . now()->format('Y/m');
    }

    private function logChanges(Asset $asset, array $dirty, array $old): void
    {
        $special = ['location_id' => 'location', 'asset_condition' => 'condition', 'status' => 'status'];
        $general = [];

        foreach ($dirty as $field => $new) {
            // Perubahan foto dicatat dengan keterangan
            if ($field === 'photo') {
                $msg = $new === null ? 'Foto aset dihapus' : (! empty($old['photo']) ? 'Foto aset diganti' : 'Foto aset ditambahkan');
                $this->log($asset, 'update', null, null, null, $msg);
                continue;
            }

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