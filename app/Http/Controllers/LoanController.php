<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Loan;
use App\Models\LoanPhoto;
use App\Models\Location;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoanController extends Controller
{
    private const MAX_PHOTOS = 3;

    public function index(Request $request)
    {
        $scope = $this->locationScope($request->location_id);

        $loans = Loan::with(['asset', 'originLocation.parent', 'lastLocation.parent', 'photos'])
            ->when($request->q, function ($q, $v) {
                $like = "%{$v}%";

                $q->where(function ($w) use ($like) {
                    $w->where('borrower_name', 'like', $like)
                      ->orWhere('last_location_note', 'like', $like)
                      ->orWhereHas('asset', fn ($a) => $a
                          ->where('name', 'like', $like)
                          ->orWhere('asset_code', 'like', $like))
                      ->orWhereHas('lastLocation', fn ($l) => $l->where('name', 'like', $like))
                      ->orWhereHas('lastLocation.parent', fn ($p) => $p->where('name', 'like', $like))
                      ->orWhereHas('originLocation', fn ($l) => $l->where('name', 'like', $like))
                      ->orWhereHas('originLocation.parent', fn ($p) => $p->where('name', 'like', $like));
                });
            })
            ->when($scope, fn ($q, $ids) => $q->whereIn('last_location_id', $ids))
            ->when($request->status, function ($q, $v) {
                match ($v) {
                    'dipinjam'     => $q->active(),
                    'segera'       => $q->dueSoon(),
                    'terlambat'    => $q->active()->whereNotNull('due_at')->whereDate('due_at', '<', today()),
                    'dikembalikan' => $q->whereNotNull('returned_at'),
                    default        => $q,
                };
            })
            ->orderByDesc('loaned_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'aktif'     => Loan::active()->count(),
            'unit'      => (int) Loan::active()->sum('quantity'),
            'terlambat' => Loan::active()->whereNotNull('due_at')->whereDate('due_at', '<', today())->count(),
        ];

        return view('loans.index', [
            'loans'     => $loans,
            'summary'   => $summary,
            'locations' => $this->locationOptions(),
        ]);
    }

    public function create(Request $request)
    {
        // Peminjam yang masih punya peminjaman terlambat (untuk peringatan di form)
        $overdueBorrowers = Loan::overdue()
            ->selectRaw('borrower_name, COUNT(*) as total, SUM(quantity) as units')
            ->groupBy('borrower_name')
            ->get()
            ->mapWithKeys(fn ($r) => [
                mb_strtolower(trim($r->borrower_name)) => ['total' => (int) $r->total, 'units' => (int) $r->units],
            ]);

        return view('loans.create', [
            'assets'           => $this->availableAssets(),
            'selected'         => $request->asset_id,
            'overdueBorrowers' => $overdueBorrowers,
            'locations'        => $this->locationOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(array_merge([
            'asset_id'           => ['required', Rule::exists('assets', 'id')->whereNull('deleted_at')],
            'borrower_name'      => 'required|string|max:100',
            'borrower_contact'   => 'nullable|string|max:50',
            'quantity'           => 'required|integer|min:1',
            'loaned_at'          => 'required|date',
            'due_at'             => 'nullable|date|after_or_equal:loaned_at',
            'last_location_id'   => 'required|exists:locations,id',
            'last_location_note' => 'nullable|string|max:100',
            'notes'              => 'nullable|string|max:255',
        ], $this->photoRules('photos_before')), array_merge([
            'asset_id.required'         => 'Pilih aset yang dipinjam.',
            'borrower_name.required'    => 'Nama peminjam wajib diisi.',
            'quantity.required'         => 'Jumlah wajib diisi.',
            'quantity.min'              => 'Jumlah minimal 1.',
            'loaned_at.required'        => 'Tanggal pinjam wajib diisi.',
            'due_at.after_or_equal'     => 'Batas kembali tidak boleh sebelum tanggal pinjam.',
            'last_location_id.required' => 'Pilih lokasi pemakaian barang.',
            'last_location_id.exists'   => 'Lokasi tidak ditemukan.',
        ], $this->photoMessages('photos_before')));

        $files = array_filter((array) $request->file('photos_before', []));
        $paths = [];

        try {
            foreach ($files as $file) {
                $paths[] = ImageUpload::store($file, $this->photoDir(), 'photos_before');
            }

            $loan = DB::transaction(function () use ($data, $paths) {
                // Kunci baris aset agar dua peminjaman bersamaan tidak melewati stok
                $asset = Asset::whereKey($data['asset_id'])->lockForUpdate()->firstOrFail();

                if ($asset->status !== 'aktif' || $asset->asset_condition !== 'baik') {
                    throw ValidationException::withMessages([
                        'asset_id' => 'Aset ini tidak dapat dipinjam (harus berstatus aktif dan kondisi baik).',
                    ]);
                }

                $available = $asset->available;
                if ($data['quantity'] > $available) {
                    throw ValidationException::withMessages([
                        'quantity' => "Jumlah melebihi sisa tersedia ({$available} unit).",
                    ]);
                }

                $loan = Loan::create(Arr::except($data, ['photos_before']) + [
                    'created_by'         => auth()->id(),
                    'last_location_at'   => now(),
                    'origin_location_id' => $asset->location_id,
                ]);

                foreach ($paths as $path) {
                    LoanPhoto::create([
                        'loan_id'    => $loan->id,
                        'stage'      => 'sebelum',
                        'path'       => $path,
                        'created_by' => auth()->id(),
                    ]);
                }

                return $loan;
            });
        } catch (\Throwable $e) {
            $this->discardFiles($paths);
            throw $e;
        }

        $redirect = redirect()->route('loans.index')
            ->with('success', "Peminjaman oleh {$loan->borrower_name} berhasil dicatat" . (count($paths) ? ' dengan ' . count($paths) . ' foto kondisi barang.' : '.'));

        $warnings = [];

        if (! count($paths)) {
            $warnings[] = 'Peminjaman dicatat tanpa foto kondisi barang sebelum dipinjam. Foto bisa ditambahkan lewat Edit Peminjaman.';
        }

        $lateCount = Loan::overdue()
            ->where('borrower_name', $loan->borrower_name)
            ->where('id', '!=', $loan->id)
            ->count();

        if ($lateCount > 0) {
            $warnings[] = "Perhatian: {$loan->borrower_name} masih memiliki {$lateCount} peminjaman yang terlambat dikembalikan.";
        }

        if ($warnings) {
            $redirect->with('warning', implode(' ', $warnings));
        }

        return $redirect;
    }

    public function returnLoan(Request $request, Loan $loan)
    {
        $data = $request->validate(array_merge([
            'returned_at' => 'required|date|before_or_equal:today|after_or_equal:' . $loan->loaned_at->format('Y-m-d'),
        ], $this->photoRules('photos_after')), array_merge([
            'returned_at.required'        => 'Tanggal pengembalian wajib diisi.',
            'returned_at.before_or_equal' => 'Tanggal pengembalian tidak boleh melebihi hari ini.',
            'returned_at.after_or_equal'  => 'Tanggal pengembalian tidak boleh sebelum tanggal pinjam.',
        ], $this->photoMessages('photos_after')));

        $files    = array_filter((array) $request->file('photos_after', []));
        $existing = $loan->photos()->where('stage', 'sesudah')->count();

        if ($existing + count($files) > self::MAX_PHOTOS) {
            throw ValidationException::withMessages([
                'photos_after' => 'Maksimal ' . self::MAX_PHOTOS . ' foto sesudah dikembalikan.',
            ]);
        }

        $paths   = [];
        $already = false;

        try {
            foreach ($files as $file) {
                $paths[] = ImageUpload::store($file, $this->photoDir(), 'photos_after');
            }

            DB::transaction(function () use ($loan, $data, $paths, &$already) {
                // Kunci baris agar tidak dikembalikan dua kali bersamaan
                $fresh = Loan::whereKey($loan->id)->lockForUpdate()->first();

                if ($fresh->returned_at !== null) {
                    $already = true;
                    return;
                }

                $fresh->update(['returned_at' => $data['returned_at']]);

                foreach ($paths as $path) {
                    LoanPhoto::create([
                        'loan_id'    => $fresh->id,
                        'stage'      => 'sesudah',
                        'path'       => $path,
                        'created_by' => auth()->id(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            $this->discardFiles($paths);
            throw $e;
        }

        if ($already) {
            $this->discardFiles($paths);

            return back()->with('info', 'Peminjaman ini sudah dikembalikan sebelumnya.');
        }

        $redirect = back()->with('success', "{$loan->borrower_name} telah mengembalikan {$loan->quantity} unit"
            . (count($paths) ? ' dengan ' . count($paths) . ' foto kondisi barang.' : '.'));

        if (! count($paths)) {
            $redirect->with('warning', 'Pengembalian dicatat tanpa foto kondisi barang. Foto bisa ditambahkan lewat Edit Peminjaman.');
        }

        return $redirect;
    }

    public function edit(Loan $loan)
    {
        $loan->load(['asset', 'originLocation.parent', 'photos']);

        // Batas jumlah: total unit dikurangi unit yang dipinjam peminjam lain (hanya untuk peminjaman aktif)
        $maxQty = $loan->is_active
            ? $loan->asset->quantity - ($loan->asset->borrowed - $loan->quantity)
            : null;

        return view('loans.edit', [
            'loan'      => $loan,
            'maxQty'    => $maxQty,
            'locations' => $this->locationOptions(),
        ]);
    }

    public function update(Request $request, Loan $loan)
    {
        $rules = [
            'borrower_name'      => 'required|string|max:100',
            'borrower_contact'   => 'nullable|string|max:50',
            'quantity'           => 'required|integer|min:1',
            'loaned_at'          => 'required|date',
            'due_at'             => 'nullable|date|after_or_equal:loaned_at',
            'notes'              => 'nullable|string|max:255',
            // Peminjaman yang masih aktif wajib punya lokasi, yang sudah kembali boleh kosong
            'last_location_id'   => ($loan->returned_at ? 'nullable' : 'required') . '|exists:locations,id',
            'last_location_note' => 'nullable|string|max:100',
        ];

        if ($loan->returned_at) {
            $rules['returned_at'] = 'required|date|after_or_equal:loaned_at|before_or_equal:today';
        }

        $data = $request->validate($rules, [
            'borrower_name.required'      => 'Nama peminjam wajib diisi.',
            'quantity.required'           => 'Jumlah wajib diisi.',
            'quantity.min'                => 'Jumlah minimal 1.',
            'loaned_at.required'          => 'Tanggal pinjam wajib diisi.',
            'due_at.after_or_equal'       => 'Batas kembali tidak boleh sebelum tanggal pinjam.',
            'returned_at.after_or_equal'  => 'Tanggal dikembalikan tidak boleh sebelum tanggal pinjam.',
            'returned_at.before_or_equal' => 'Tanggal dikembalikan tidak boleh melebihi hari ini.',
            'last_location_id.required'   => 'Pilih lokasi terakhir barang.',
            'last_location_id.exists'     => 'Lokasi tidak ditemukan.',
        ]);

        $noChange = false;

        DB::transaction(function () use ($loan, $data, &$noChange) {
            // Kunci aset dan peminjaman agar hitungan stok tidak bentrok
            $asset = Asset::withTrashed()->whereKey($loan->asset_id)->lockForUpdate()->firstOrFail();
            $fresh = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();

            // Stok hanya dicek untuk peminjaman yang masih aktif
            if ($fresh->returned_at === null) {
                $max = $asset->quantity - ($asset->borrowed - $fresh->quantity);

                if ($data['quantity'] > $max) {
                    throw ValidationException::withMessages([
                        'quantity' => "Jumlah melebihi stok yang tersedia (maksimal {$max} unit).",
                    ]);
                }
            }

            $fresh->fill($data);

            // Catat kapan lokasi terakhir diperbarui
            if ($fresh->isDirty('last_location_id') || $fresh->isDirty('last_location_note')) {
                $fresh->last_location_at = now();
            }

            if (! $fresh->isDirty()) {
                $noChange = true;
                return;
            }

            $fresh->save();
        });

        if ($noChange) {
            return redirect()->route('loans.index')->with('info', 'Tidak ada perubahan data.');
        }

        return redirect()->route('loans.index')
            ->with('success', "Data peminjaman {$data['borrower_name']} berhasil diperbarui.");
    }

    // ---------- helper ----------

    private function photoRules(string $key): array
    {
        return [
            $key        => 'nullable|array|max:' . self::MAX_PHOTOS,
            $key . '.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=8000,max_height=8000',
        ];
    }

    private function photoMessages(string $key): array
    {
        return [
            $key . '.max'            => 'Maksimal ' . self::MAX_PHOTOS . ' foto.',
            $key . '.*.uploaded'     => 'Foto gagal diunggah. Ukurannya mungkin melebihi batas server.',
            $key . '.*.image'        => 'File harus berupa gambar.',
            $key . '.*.mimes'        => 'Format foto harus JPG, PNG, atau WebP.',
            $key . '.*.max'          => 'Ukuran tiap foto maksimal 5 MB.',
            $key . '.*.dimensions'   => 'Resolusi foto terlalu besar (maksimal 8000 x 8000 piksel).',
        ];
    }

    private function photoDir(): string
    {
        return 'loans/' . now()->format('Y/m');
    }

    // Hapus berkas yang sudah tersimpan bila proses gagal
    private function discardFiles(array $paths): void
    {
        foreach ($paths as $path) {
            Storage::disk('local')->delete($path);
        }
    }

    // Aset yang bisa dipinjam beserta sisa stoknya
    private function availableAssets()
    {
        return Asset::where('status', 'aktif')
            ->where('asset_condition', 'baik')
            ->withSum(['loans as borrowed_sum' => fn ($q) => $q->whereNull('returned_at')], 'quantity')
            ->orderBy('name')
            ->get()
            ->each(fn ($a) => $a->available_qty = max(0, $a->quantity - (int) $a->borrowed_sum))
            ->filter(fn ($a) => $a->available_qty > 0)
            ->values();
    }

    // Daftar lokasi untuk pilihan, diurutkan menurut nama lengkap
    private function locationOptions()
    {
        return Location::with('parent')->get()
            ->sortBy('full_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    // Lokasi yang dipilih beserta ruangan di dalamnya (bila yang dipilih adalah gedung)
    private function locationScope($id): ?array
    {
        if (! $id || ! Location::whereKey($id)->exists()) {
            return null;
        }

        return Location::where('id', $id)->orWhere('parent_id', $id)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}