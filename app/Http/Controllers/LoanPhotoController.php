<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanPhoto;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LoanPhotoController extends Controller
{
    private const MAX_PHOTOS = 3;

    // Menyajikan foto (hanya untuk pengguna yang sudah login)
    public function show(LoanPhoto $photo)
    {
        abort_unless(Storage::disk('local')->exists($photo->path), 404);

        return response()->file(Storage::disk('local')->path($photo->path), [
            'Cache-Control'          => 'private, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // Menambah foto pada peminjaman (Admin dan Pengelola)
    public function store(Request $request, Loan $loan)
    {
        $request->validate([
            'stage'    => 'required|in:sebelum,sesudah',
            'photos'   => 'required|array|min:1|max:' . self::MAX_PHOTOS,
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=8000,max_height=8000',
        ], [
            'photos.required'      => 'Pilih minimal satu foto.',
            'photos.min'           => 'Pilih minimal satu foto.',
            'photos.max'           => 'Maksimal ' . self::MAX_PHOTOS . ' foto.',
            'photos.*.uploaded'    => 'Foto gagal diunggah. Ukurannya mungkin melebihi batas server.',
            'photos.*.image'       => 'File harus berupa gambar.',
            'photos.*.mimes'       => 'Format foto harus JPG, PNG, atau WebP.',
            'photos.*.max'         => 'Ukuran tiap foto maksimal 5 MB.',
            'photos.*.dimensions'  => 'Resolusi foto terlalu besar (maksimal 8000 x 8000 piksel).',
        ]);

        $stage = $request->input('stage');

        if ($stage === 'sesudah' && ! $loan->returned_at) {
            return back()->with('error', 'Foto sesudah hanya bisa ditambahkan setelah barang dikembalikan.');
        }

        $files    = array_filter((array) $request->file('photos', []));
        $existing = $loan->photos()->where('stage', $stage)->count();

        if ($existing + count($files) > self::MAX_PHOTOS) {
            throw ValidationException::withMessages([
                'photos' => 'Maksimal ' . self::MAX_PHOTOS . ' foto per tahap. Saat ini sudah ada ' . $existing . ' foto.',
            ]);
        }

        $paths = [];

        try {
            foreach ($files as $file) {
                $paths[] = ImageUpload::store($file, 'loans/' . now()->format('Y/m'), 'photos');
            }

            DB::transaction(function () use ($loan, $stage, $paths) {
                foreach ($paths as $path) {
                    LoanPhoto::create([
                        'loan_id'    => $loan->id,
                        'stage'      => $stage,
                        'path'       => $path,
                        'created_by' => auth()->id(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            foreach ($paths as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }

        return back()->with('success', count($paths) . ' foto berhasil ditambahkan.');
    }

    public function destroy(LoanPhoto $photo)
    {
        $path = $photo->path;
        $photo->delete();
        Storage::disk('local')->delete($path);

        return back()->with('success', 'Foto berhasil dihapus.');
    }
}