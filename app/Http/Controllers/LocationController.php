<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        $data = $this->validated($request);

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->storePhoto($request->file('photo'));
        }

        Location::create($data);

        return back()->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function update(Request $request, Location $location)
    {
        $data = $this->validated($request, $location);

        if ($data['parent_id'] && $location->children()->exists()) {
            return back()->with('error', 'Lokasi ini memiliki sub-lokasi sehingga tidak dapat dijadikan sub-lokasi.');
        }

        $oldPhoto = $location->photo;

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->storePhoto($request->file('photo'));
        } elseif ($request->boolean('remove_photo')) {
            $data['photo'] = null;
        }

        $location->update($data);

        // Foto lama dihapus setelah data berhasil disimpan
        if (array_key_exists('photo', $data) && $oldPhoto) {
            Storage::disk('local')->delete($oldPhoto);
        }

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

        $photo = $location->photo;
        $location->delete();

        if ($photo) {
            Storage::disk('local')->delete($photo);
        }

        return back()->with('success', 'Lokasi berhasil dihapus.');
    }

    // Menyajikan foto (hanya untuk pengguna yang sudah login)
    public function photo(Location $location)
    {
        abort_unless($location->photo && Storage::disk('local')->exists($location->photo), 404);

        return response()->file(Storage::disk('local')->path($location->photo), [
            'Cache-Control'          => 'private, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function validated(Request $request, ?Location $location = null): array
    {
        $data = $request->validate([
            'parent_id'   => ['nullable', Rule::exists('locations', 'id')->whereNull('parent_id'), Rule::notIn([$location?->id])],
            'name'        => ['required', 'string', 'max:100',
                Rule::unique('locations', 'name')->where('parent_id', $request->parent_id)->ignore($location?->id)],
            'description' => 'nullable|string|max:255',
            'photo'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=6000,max_height=6000',
        ], [
            'photo.uploaded'   => 'Foto gagal diunggah. Ukurannya mungkin melebihi batas server.',
            'photo.image'      => 'File harus berupa gambar.',
            'photo.mimes'      => 'Format foto harus JPG, PNG, atau WebP.',
            'photo.max'        => 'Ukuran foto maksimal 5 MB.',
            'photo.dimensions' => 'Resolusi foto terlalu besar (maksimal 6000 x 6000 piksel).',
        ]);

        unset($data['photo']);
        $data['parent_id'] = $data['parent_id'] ?? null;

        return $data;
    }

    // Foto diperkecil (maks. 1280 px), diputar sesuai EXIF, lalu disimpan sebagai JPG dengan nama acak
    private function storePhoto(UploadedFile $file): string
    {
        // Tanpa ekstensi GD: simpan apa adanya
        if (! function_exists('imagecreatefromstring')) {
            return Storage::disk('local')->putFile('locations', $file);
        }

        @ini_set('memory_limit', '256M');

        $img = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($img === false) {
            throw ValidationException::withMessages([
                'photo' => 'Foto tidak dapat diproses. Gunakan file JPG, PNG, atau WebP yang valid.',
            ]);
        }

        // Foto dari HP sering tersimpan miring
        if (function_exists('exif_read_data') && in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg'], true)) {
            $exif        = @exif_read_data($file->getRealPath());
            $orientation = is_array($exif) ? ($exif['Orientation'] ?? 1) : 1;
            $angle       = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

            if ($angle !== 0 && ($rotated = imagerotate($img, $angle, 0)) !== false) {
                $img = $rotated;
            }
        }

        $w = imagesx($img);
        $h = imagesy($img);

        if (max($w, $h) > 1280) {
            $scaled = $w >= $h ? imagescale($img, 1280) : imagescale($img, (int) round($w * 1280 / $h));

            if ($scaled !== false) {
                $img = $scaled;
                $w   = imagesx($img);
                $h   = imagesy($img);
            }
        }

        // Latar putih agar PNG transparan tidak menjadi hitam
        $canvas = imagecreatetruecolor($w, $h);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $img, 0, 0, 0, 0, $w, $h);

        ob_start();
        imagejpeg($canvas, null, 82);
        $binary = (string) ob_get_clean();

        $path = 'locations/' . Str::random(40) . '.jpg';
        Storage::disk('local')->put($path, $binary);

        return $path;
    }
}