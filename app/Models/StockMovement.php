<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'asset_id', 'type', 'reason', 'quantity', 'qty_before', 'qty_after',
        'moved_at', 'reference', 'notes', 'created_by',
    ];

    protected $casts = ['moved_at' => 'date'];

    public const REASONS = [
        'masuk'  => ['pembelian' => 'Pembelian'],
        'keluar' => ['rusak' => 'Rusak', 'dibuang' => 'Dibuang'],
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'masuk' ? 'Masuk' : 'Keluar';
    }

    public function getReasonLabelAttribute(): string
    {
        return ucfirst($this->reason);
    }
}