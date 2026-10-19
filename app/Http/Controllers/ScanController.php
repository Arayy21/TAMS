<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function index()
    {
        return view('scan.index');
    }

    // Mencari aset dari kode hasil scan (dipanggil lewat JavaScript)
    public function find(Request $request)
    {
        $code = strtoupper(trim((string) $request->query('kode', '')));

        if (! Asset::isValidCode($code)) {
            return response()->json(['found' => false, 'message' => 'Format kode tidak dikenali.']);
        }

        $asset = Asset::withTrashed()
            ->with(['category', 'location.parent'])
            ->where('asset_code', $code)
            ->first();

        if (! $asset) {
            return response()->json(['found' => false, 'message' => 'Kode tidak ditemukan di sistem.']);
        }

        if ($asset->trashed()) {
            return response()->json(['found' => false, 'message' => 'Aset ini sudah dihapus (diarsipkan).']);
        }

        // Peminjaman yang masih berjalan: siapa, berapa unit, dari mana ke mana
        $loans = $asset->loans()->active()
            ->with(['originLocation.parent', 'lastLocation.parent'])
            ->orderBy('due_at')
            ->get()
            ->map(function ($l) {
                $left = $l->days_left;

                return [
                    'borrower'  => $l->borrower_name,
                    'contact'   => $l->borrower_contact,
                    'quantity'  => (int) $l->quantity,
                    'origin'    => $l->originLocation?->full_name,
                    'last'      => $l->lastLocation?->full_name,
                    'note'      => $l->last_location_note,
                    'overdue'   => $l->is_overdue,
                    'due_text'  => is_null($left) ? 'Tanpa batas kembali'
                                    : ($left < 0 ? 'Terlambat ' . abs($left) . ' hari'
                                    : ($left === 0 ? 'Jatuh tempo hari ini'
                                    : $left . ' hari lagi (' . $l->due_at->format('d M Y') . ')')),
                ];
            })
            ->values();

        return response()->json([
            'found' => true,
            'asset' => [
                'id'        => $asset->id,
                'code'      => $asset->asset_code,
                'name'      => $asset->name,
                'category'  => $asset->category->name ?? '-',
                'location'  => $asset->location?->full_name ?? '-',
                'condition' => $asset->asset_condition,
                'status'    => $asset->status,
                'quantity'  => $asset->quantity,
                'available' => $asset->available,
                'url'       => route('assets.show', $asset),
                'loans'     => $loans,
            ],
        ]);
    }
}