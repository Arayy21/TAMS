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
            ->with(['category', 'location'])
            ->where('asset_code', $code)
            ->first();

        if (! $asset) {
            return response()->json(['found' => false, 'message' => 'Kode tidak ditemukan di sistem.']);
        }

        if ($asset->trashed()) {
            return response()->json(['found' => false, 'message' => 'Aset ini sudah dihapus (diarsipkan).']);
        }

        return response()->json([
            'found' => true,
            'asset' => [
                'id'        => $asset->id,
                'code'      => $asset->asset_code,
                'name'      => $asset->name,
                'category'  => $asset->category->name ?? '-',
                'location'  => $asset->location->name ?? '-',
                'condition' => $asset->asset_condition,
                'status'    => $asset->status,
                'quantity'  => $asset->quantity,
                'available' => $asset->available,
                'url'       => route('assets.show', $asset),
            ],
        ]);
    }
}