<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageUpload
{
    // Foto diperkecil (maks. 1600 px), diputar sesuai EXIF, lalu disimpan sebagai JPG dengan nama acak (disk privat)
    public static function store(UploadedFile $file, string $dir, string $errorKey = 'photo'): string
    {
        $dir = trim($dir, '/');

        // Tanpa ekstensi GD: simpan apa adanya
        if (! function_exists('imagecreatefromstring')) {
            return Storage::disk('local')->putFile($dir, $file);
        }

        @ini_set('memory_limit', '256M');

        $img = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($img === false) {
            throw ValidationException::withMessages([
                $errorKey => 'Foto tidak dapat diproses. Gunakan file JPG, PNG, atau WebP yang valid.',
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

        if (max($w, $h) > 1600) {
            $scaled = $w >= $h ? imagescale($img, 1600) : imagescale($img, (int) round($w * 1600 / $h));

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

        $path = $dir . '/' . Str::random(40) . '.jpg';
        Storage::disk('local')->put($path, $binary);

        return $path;
    }

    // Versi ringan untuk laporan (PDF dan Word): bingkai tetap (default 800 x 600), foto tidak dipotong dan tidak diperbesar
    public static function reportThumbnail(string $path, int $w = 800, int $h = 600, int $quality = 75): ?string
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            return null;
        }

        $raw = $disk->get($path);

        // Tanpa ekstensi GD: pakai berkas asli
        if (! function_exists('imagecreatefromstring')) {
            return $raw;
        }

        $src = @imagecreatefromstring($raw);

        if ($src === false) {
            return null;
        }

        $sw = imagesx($src);
        $sh = imagesy($src);
        $k  = min($w / $sw, $h / $sh, 1);
        $nw = max(1, (int) round($sw * $k));
        $nh = max(1, (int) round($sh * $k));

        $canvas = imagecreatetruecolor($w, $h);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 242, 242, 242));
        imagecopyresampled($canvas, $src, (int) (($w - $nw) / 2), (int) (($h - $nh) / 2), 0, 0, $nw, $nh, $sw, $sh);

        ob_start();
        imagejpeg($canvas, null, $quality);

        return (string) ob_get_clean();
    }
}