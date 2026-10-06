<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameItem extends Model
{
    protected $fillable = [
        'stock_opname_id', 'asset_id', 'system_qty', 'borrowed_qty',
        'system_condition', 'physical_qty', 'physical_condition',
        'notes', 'checked_by', 'checked_at',
    ];

    protected $casts = ['checked_at' => 'datetime'];

    public function opname()
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }

    public function getIsCheckedAttribute(): bool
    {
        return $this->physical_qty !== null;
    }

    // Jumlah yang seharusnya ada di tempat = total - sedang dipinjam
    public function getExpectedQtyAttribute(): int
    {
        return max(0, $this->system_qty - $this->borrowed_qty);
    }

    // Selisih = fisik - seharusnya (null jika belum dicek)
    public function getDifferenceAttribute(): ?int
    {
        return $this->is_checked ? $this->physical_qty - $this->expected_qty : null;
    }
}