<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $loans = Loan::with('asset')
            ->when($request->q, function ($q, $v) {
                $q->where(function ($w) use ($v) {
                    $w->where('borrower_name', 'like', "%{$v}%")
                      ->orWhereHas('asset', fn ($a) => $a
                          ->where('name', 'like', "%{$v}%")
                          ->orWhere('asset_code', 'like', "%{$v}%"));
                });
            })
            ->when($request->status, function ($q, $v) {
                match ($v) {
                    'dipinjam'     => $q->active(),
                    'terlambat'    => $q->active()->whereNotNull('due_at')->whereDate('due_at', '<', today()),
                    'segera'       => $q->dueSoon(),
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

        return view('loans.index', compact('loans', 'summary'));
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
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_id'         => ['required', Rule::exists('assets', 'id')->whereNull('deleted_at')],
            'borrower_name'    => 'required|string|max:100',
            'borrower_contact' => 'nullable|string|max:50',
            'quantity'         => 'required|integer|min:1',
            'loaned_at'        => 'required|date',
            'due_at'           => 'nullable|date|after_or_equal:loaned_at',
            'notes'            => 'nullable|string|max:255',
        ], [
            'asset_id.required'      => 'Pilih aset yang dipinjam.',
            'borrower_name.required' => 'Nama peminjam wajib diisi.',
            'quantity.required'      => 'Jumlah wajib diisi.',
            'quantity.min'           => 'Jumlah minimal 1.',
            'loaned_at.required'     => 'Tanggal pinjam wajib diisi.',
            'due_at.after_or_equal'  => 'Batas kembali tidak boleh sebelum tanggal pinjam.',
        ]);

        $loan = DB::transaction(function () use ($data) {
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

            return Loan::create($data + ['created_by' => auth()->id()]);
        });

        $redirect = redirect()->route('loans.index')
            ->with('success', "Peminjaman oleh {$loan->borrower_name} berhasil dicatat.");

        $lateCount = Loan::overdue()
            ->where('borrower_name', $loan->borrower_name)
            ->where('id', '!=', $loan->id)
            ->count();

        if ($lateCount > 0) {
            $redirect->with('warning', "Perhatian: {$loan->borrower_name} masih memiliki {$lateCount} peminjaman yang terlambat dikembalikan.");
        }

        return $redirect;
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

    public function returnLoan(Request $request, Loan $loan)
    {
        $data = $request->validate([
            'returned_at' => 'required|date|before_or_equal:today|after_or_equal:' . $loan->loaned_at->format('Y-m-d'),
        ], [
            'returned_at.required'        => 'Tanggal pengembalian wajib diisi.',
            'returned_at.before_or_equal' => 'Tanggal pengembalian tidak boleh melebihi hari ini.',
            'returned_at.after_or_equal'  => 'Tanggal pengembalian tidak boleh sebelum tanggal pinjam.',
        ]);

        DB::transaction(function () use ($loan, $data, &$already) {
            // Kunci baris agar tidak dikembalikan dua kali bersamaan
            $fresh = Loan::whereKey($loan->id)->lockForUpdate()->first();

            if ($fresh->returned_at !== null) {
                $already = true;
                return;
            }

            $fresh->update(['returned_at' => $data['returned_at']]);
        });

        if (! empty($already)) {
            return back()->with('info', 'Peminjaman ini sudah dikembalikan sebelumnya.');
        }

        return back()->with('success', "{$loan->borrower_name} telah mengembalikan {$loan->quantity} unit.");
    }

    public function edit(Loan $loan)
    {
        $loan->load('asset');

        // Batas jumlah: total unit dikurangi unit yang dipinjam peminjam lain (hanya untuk peminjaman aktif)
        $maxQty = $loan->is_active
            ? $loan->asset->quantity - ($loan->asset->borrowed - $loan->quantity)
            : null;

        return view('loans.edit', compact('loan', 'maxQty'));
    }

    public function update(Request $request, Loan $loan)
    {
        $rules = [
            'borrower_name'    => 'required|string|max:100',
            'borrower_contact' => 'nullable|string|max:50',
            'quantity'         => 'required|integer|min:1',
            'loaned_at'        => 'required|date',
            'due_at'           => 'nullable|date|after_or_equal:loaned_at',
            'notes'            => 'nullable|string|max:255',
        ];

        if ($loan->returned_at) {
            $rules['returned_at'] = 'required|date|after_or_equal:loaned_at|before_or_equal:today';
        }

        $data = $request->validate($rules, [
            'borrower_name.required'     => 'Nama peminjam wajib diisi.',
            'quantity.required'          => 'Jumlah wajib diisi.',
            'quantity.min'               => 'Jumlah minimal 1.',
            'loaned_at.required'         => 'Tanggal pinjam wajib diisi.',
            'due_at.after_or_equal'      => 'Batas kembali tidak boleh sebelum tanggal pinjam.',
            'returned_at.after_or_equal' => 'Tanggal dikembalikan tidak boleh sebelum tanggal pinjam.',
            'returned_at.before_or_equal'=> 'Tanggal dikembalikan tidak boleh melebihi hari ini.',
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
}