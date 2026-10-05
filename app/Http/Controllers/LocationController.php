<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function index()
    {
        return view('locations.index', [
            'locations' => Location::with('parent')->withCount('assets')->orderBy('name')->get(),
            'parents'   => Location::whereNull('parent_id')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Location::create($this->validated($request));

        return back()->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function update(Request $request, Location $location)
    {
        $data = $this->validated($request, $location);

        if ($data['parent_id'] && $location->children()->exists()) {
            return back()->with('error', 'Lokasi ini memiliki sub-lokasi sehingga tidak dapat dijadikan sub-lokasi.');
        }

        $location->update($data);

        return back()->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Location $location)
    {
        if ($location->assets()->withTrashed()->exists()) {
            return back()->with('error', 'Lokasi tidak dapat dihapus karena masih dipakai oleh aset.');
        }
        if ($location->children()->exists()) {
            return back()->with('error', 'Lokasi tidak dapat dihapus karena memiliki sub-lokasi.');
        }

        $location->delete();

        return back()->with('success', 'Lokasi berhasil dihapus.');
    }

    private function validated(Request $request, ?Location $location = null): array
    {
        $data = $request->validate([
            'parent_id'   => ['nullable', Rule::exists('locations', 'id')->whereNull('parent_id'), Rule::notIn([$location?->id])],
            'name'        => ['required', 'string', 'max:100',
                Rule::unique('locations', 'name')->where('parent_id', $request->parent_id)->ignore($location?->id)],
            'description' => 'nullable|string|max:255',
        ]);

        $data['parent_id'] = $data['parent_id'] ?? null;

        return $data;
    }
}