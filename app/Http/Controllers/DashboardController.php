<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Jumlah aset per kondisi
        $kondisi = DB::table('assets')
            ->whereNull('deleted_at')
            ->select('asset_condition', DB::raw('COUNT(*) as total'))
            ->groupBy('asset_condition')
            ->pluck('total', 'asset_condition');

        $stats = [
            'total'     => (int) $kondisi->sum(),
            'baik'      => (int) $kondisi->get('baik', 0),
            'rusak'     => (int) $kondisi->get('rusak', 0),
            'perbaikan' => (int) $kondisi->get('perbaikan', 0),
        ];

        // Aset per kategori (untuk grafik)
        $perKategori = DB::table('categories as c')
            ->leftJoin('assets as a', function ($join) {
                $join->on('a.category_id', '=', 'c.id')->whereNull('a.deleted_at');
            })
            ->select('c.name', DB::raw('COUNT(a.id) as total'))
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('total')
            ->get();

        // Aset yang perlu perhatian (rusak / perbaikan)
        $perhatian = DB::table('assets as a')
            ->join('categories as c', 'c.id', '=', 'a.category_id')
            ->join('locations as l', 'l.id', '=', 'a.location_id')
            ->whereNull('a.deleted_at')
            ->whereIn('a.asset_condition', ['rusak', 'perbaikan'])
            ->select('a.asset_code', 'a.name', 'a.asset_condition', 'c.name as category', 'l.name as location')
            ->orderByDesc('a.updated_at')
            ->limit(5)
            ->get();

        // Aktivitas terbaru
        $aktivitas = DB::table('asset_histories as h')
            ->join('assets as a', 'a.id', '=', 'h.asset_id')
            ->leftJoin('users as u', 'u.id', '=', 'h.user_id')
            ->select('h.action', 'h.old_value', 'h.new_value', 'h.created_at', 'a.asset_code', 'u.name as user_name')
            ->orderByDesc('h.created_at')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact('stats', 'perKategori', 'perhatian', 'aktivitas'));
    }
}