<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetHistory extends Model
{
    public $timestamps = false; // tabel hanya punya created_at

    protected $fillable = [
        'asset_id', 'user_id', 'action', 'field_name',
        'old_value', 'new_value', 'notes', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function asset()
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'create'    => 'Aset ditambahkan',
            'update'    => 'Data diperbarui',
            'location'  => 'Lokasi diubah',
            'condition' => 'Kondisi diubah',
            'status'    => 'Status diubah',
            'delete'    => 'Aset dihapus',
            default     => 'Aset dipulihkan',
        };
    }

    public function getChangeTextAttribute(): string
    {
        if ($this->old_value !== null || $this->new_value !== null) {
            return ($this->old_value ?? '-') . ' → ' . ($this->new_value ?? '-');
        }

        return $this->field_name ? 'Field: ' . $this->field_name : ($this->notes ?? '-');
    }
}