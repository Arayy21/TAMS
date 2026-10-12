<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\Loan;
use App\Models\StockMovement;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ----- Aset per kondisi (jenis dan unit) -----
        $cond = Asset::select(
                'asset_condition',
                DB::raw('COUNT(*) as jenis'),
                DB::raw('COALESCE(SUM(quantity), 0) as unit')
            )
            ->groupBy('asset_condition')
            ->get()
            ->keyBy('asset_condition');

        $pick = fn (string $k, string $f) => (int) ($cond->get($k)?->$f ?? 0);

        $stats = [
            'jenis'     => (int) $cond->sum('jenis'),
            'unit'      => (int) $cond->sum('unit'),
            'baik'      => ['jenis' => $pick('baik', 'jenis'),      'unit' => $pick('baik', 'unit')],
            'rusak'     => ['jenis' => $pick('rusak', 'jenis'),     'unit' => $pick('rusak', 'unit')],
            'perbaikan' => ['jenis' => $pick('perbaikan', 'jenis'), 'unit' => $pick('perbaikan', 'unit')],
        ];

        // ----- Peminjaman -----
        $assetIds = fn () => Asset::select('id');

        $borrowedUnits = (int) Loan::active()->whereIn('asset_id', $assetIds())->sum('quantity');
        $loansActive   = Loan::active()->whereIn('asset_id', $assetIds())->count();

        $loan = [
            'units'    => $borrowedUnits,
            'active'   => $loansActive,
            'overdue'  => Loan::overdue()->count(),
            'soon'     => Loan::dueSoon()->count(),
            'tersedia' => max(0, $stats['unit'] - $borrowedUnits),
        ];

        // ----- Mutasi stok bulan ini -----
        $month = fn () => StockMovement::whereBetween('moved_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]);

        $mutasi = [
            'masuk'  => (int) $month()->where('type', 'masuk')->sum('quantity'),
            'keluar' => (int) $month()->where('type', 'keluar')->sum('quantity'),
            'total'  => $month()->count(),
        ];

        // ----- Unit per kategori (untuk grafik) -----
        $perKategori = DB::table('categories as c')
            ->leftJoin('assets as a', function ($join) {
                $join->on('a.category_id', '=', 'c.id')->whereNull('a.deleted_at');
            })
            ->select('c.name', DB::raw('COUNT(a.id) as jenis'), DB::raw('COALESCE(SUM(a.quantity), 0) as unit'))
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('unit')
            ->get();

        // ----- Aset perlu perhatian -----
        $attention = Asset::with(['category', 'location'])
            ->whereIn('asset_condition', ['rusak', 'perbaikan'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        // ----- Peminjaman perlu tindakan (terlambat atau segera jatuh tempo) -----
        $loansDue = Loan::with('asset')
            ->active()
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<=', today()->addDays(Loan::DUE_SOON_DAYS))
            ->orderBy('due_at')
            ->limit(5)
            ->get();

        // ----- Stok opname terakhir -----
        $opname = StockOpname::with('location')
            ->withCount([
                'items',
                'items as checked_count' => fn ($q) => $q->whereNotNull('physical_qty'),
            ])
            ->orderByDesc('id')
            ->first();

        // ----- Aktivitas terbaru -----
        $activities = AssetHistory::with(['asset', 'user'])
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        return view('dashboard.index', compact(
            'stats', 'loan', 'mutasi', 'perKategori', 'attention', 'loansDue', 'opname', 'activities'
        ));
    }
}