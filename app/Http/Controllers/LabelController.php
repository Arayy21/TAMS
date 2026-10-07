<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function single(Asset $asset)
    {
        $asset->loadMissing('location');

        return $this->render(collect([$asset]));
    }

    public function bulk(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1|max:200',
            'ids.*' => 'integer|exists:assets,id',
        ], [
            'ids.required' => 'Pilih minimal satu aset untuk dicetak labelnya.',
            'ids.min'      => 'Pilih minimal satu aset untuk dicetak labelnya.',
            'ids.max'      => 'Maksimal 200 aset sekali cetak.',
        ]);

        $assets = Asset::with('location')
            ->whereIn('id', $request->ids)
            ->orderBy('asset_code')
            ->get();

        return $this->render($assets);
    }

    private function render($assets)
    {
        // Hanya kode yang valid untuk barcode
        $assets = $assets->filter(fn ($a) => Asset::isValidCode($a->asset_code))->values();

        if ($assets->isEmpty()) {
            return redirect()->route('assets.index')
                ->with('error', 'Tidak ada aset dengan kode yang valid untuk dicetak labelnya.');
        }

        return view('labels.print', [
            'items' => $assets->map(fn ($a) => ['asset' => $a, 'svg' => $a->barcodeInline()]),
        ]);
    }
}