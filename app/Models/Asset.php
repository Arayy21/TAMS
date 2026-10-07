<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_code', 'name', 'category_id', 'location_id', 'quantity',
        'asset_condition', 'status', 'description',
        'serial_number', 'purchase_date', 'created_by', 'updated_by',
    ];

    protected $casts = ['purchase_date' => 'date'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function histories()
    {
        return $this->hasMany(AssetHistory::class)->orderByDesc('id');
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    // Jumlah unit yang sedang dipinjam
    public function getBorrowedAttribute(): int
    {
        return (int) $this->loans()->whereNull('returned_at')->sum('quantity');
    }

    // Sisa unit yang tersedia
    public function getAvailableAttribute(): int
    {
        return max(0, $this->quantity - $this->borrowed);
    }


    // Warna badge Bootstrap
    public function getConditionColorAttribute(): string
    {
        return ['baik' => 'success', 'rusak' => 'danger', 'perbaikan' => 'warning'][$this->asset_condition] ?? 'secondary';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status === 'aktif' ? 'primary' : 'secondary';
    }

    // Kode berikutnya, contoh TCH-0001. Nomor diambil dari angka terbesar yang pernah ada,
    // termasuk aset yang sudah dihapus, sehingga nomor tidak dipakai ulang.
    public static function generateCode(): string
    {
        $prefix = config('tams.asset_prefix', 'TCH');
        $digits = (int) config('tams.asset_digits', 4);

        $max = (int) static::withTrashed()
            ->max(DB::raw("CAST(SUBSTRING_INDEX(asset_code, '-', -1) AS UNSIGNED)"));

        return $prefix . '-' . str_pad($max + 1, $digits, '0', STR_PAD_LEFT);
    }

    // Kode aset boleh dijadikan barcode jika hanya berisi huruf besar, angka, dan tanda hubung
    public static function isValidCode(string $code): bool
    {
        return (bool) preg_match('/^[A-Z0-9-]{3,30}$/', $code);
    }

    // Gambar barcode Code 128 (SVG, vektor) dari kode aset
    public function barcodeSvg(int $widthFactor = 2, int $height = 70): string
    {
        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();

        return $generator->getBarcode($this->asset_code, $generator::TYPE_CODE_128, $widthFactor, $height);
    }

    // Barcode siap disisipkan ke HTML dan bisa diskalakan ke ukuran label (dipakai untuk cetak label)
    public function barcodeInline(): string
    {
        $svg = $this->barcodeSvg(2, 70);

        // Buang deklarasi XML dan DOCTYPE agar bisa disisipkan langsung ke halaman
        $svg = preg_replace('/<\?xml.*?\?>|<!DOCTYPE.*?>/s', '', $svg);

        if (preg_match('/<svg[^>]*\swidth="([\d.]+)/', $svg, $w) && preg_match('/<svg[^>]*\sheight="([\d.]+)/', $svg, $h)) {
            $svg = preg_replace(
                '/<svg[^>]*>/',
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w[1] . ' ' . $h[1] . '" preserveAspectRatio="none" shape-rendering="crispEdges">',
                $svg,
                1
            );
        }

        return trim($svg);
    }
}