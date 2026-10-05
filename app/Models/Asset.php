<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_code', 'name', 'category_id', 'location_id',
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

    // Warna badge Bootstrap
    public function getConditionColorAttribute(): string
    {
        return ['baik' => 'success', 'rusak' => 'danger', 'perbaikan' => 'warning'][$this->asset_condition] ?? 'secondary';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status === 'aktif' ? 'primary' : 'secondary';
    }

    // Kode berikutnya: AST-0001, AST-0002, ... (termasuk aset yang sudah dihapus)
    public static function generateCode(): string
    {
        $last = static::withTrashed()->orderByDesc('id')->value('asset_code');
        $next = $last ? ((int) substr($last, 4)) + 1 : 1;

        return 'AST-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}