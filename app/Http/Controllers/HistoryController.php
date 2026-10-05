<?php

namespace App\Http\Controllers;

use App\Models\AssetHistory;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $histories = AssetHistory::with(['asset', 'user'])
            ->when($request->q, fn ($q, $v) => $q->whereHas('asset',
                fn ($a) => $a->withTrashed()->where('asset_code', 'like', "%{$v}%")->orWhere('name', 'like', "%{$v}%")))
            ->when($request->action, fn ($q, $v) => $q->where('action', $v))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('histories.index', compact('histories'));
    }
}