<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    protected $fillable = [
        'code', 'name', 'opname_date', 'location_id', 'status',
        'notes', 'adjustments_applied', 'created_by', 'finished_by', 'finished_at',
    ];

    protected $casts = [
        'opname_date' => 'date',
        'finished_at' => 'datetime',
        'adjustments_applied' => 'boolean',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function getIsRunningAttribute(): bool
    {
        return $this->status === 'berjalan';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_running ? 'Berjalan' : 'Selesai';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->is_running ? 'warning' : 'success';
    }

    // Kode berikutnya: OPN-0001, OPN-0002, ...
    public static function generateCode(): string
    {
        $last = static::orderByDesc('id')->value('code');
        $next = $last ? ((int) substr($last, 4)) + 1 : 1;

        return 'OPN-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function finisher()
    {
        return $this->belongsTo(User::class, 'finished_by');
    }
}