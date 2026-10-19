<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    // Batas "segera jatuh tempo" (hari)
    public const DUE_SOON_DAYS = 3;

    protected $fillable = [
        'asset_id', 'borrower_name', 'borrower_contact', 'quantity', 'origin_location_id',
        'loaned_at', 'due_at', 'returned_at', 'notes', 'created_by',
        'last_location_id', 'last_location_note', 'last_location_at',
    ];

    protected $casts = [
        'loaned_at'        => 'date',
        'due_at'           => 'date',
        'returned_at'      => 'date',
        'last_location_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function originLocation()
    {
        return $this->belongsTo(Location::class, 'origin_location_id');
    }

    public function lastLocation()
    {
        return $this->belongsTo(Location::class, 'last_location_id');
    }

    public function photos()
    {
        return $this->hasMany(LoanPhoto::class)->orderBy('id');
    }

    // Peminjaman yang belum dikembalikan
    public function scopeActive($query)
    {
        return $query->whereNull('returned_at');
    }

    public function scopeOverdue($query)
    {
        return $query->whereNull('returned_at')
                     ->whereNotNull('due_at')
                     ->whereDate('due_at', '<', today());
    }

    public function scopeDueSoon($query)
    {
        return $query->whereNull('returned_at')
                     ->whereNotNull('due_at')
                     ->whereDate('due_at', '>=', today())
                     ->whereDate('due_at', '<=', today()->addDays(self::DUE_SOON_DAYS));
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->returned_at === null;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->returned_at === null
            && $this->due_at !== null
            && $this->due_at->lt(today());
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->returned_at) {
            return 'Dikembalikan';
        }

        return $this->is_overdue ? 'Terlambat' : 'Dipinjam';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status_label) {
            'Dikembalikan' => 'success',
            'Terlambat'    => 'danger',
            default        => 'primary',
        };
    }

    // Negatif = sudah terlambat, 0 = hari ini, null = tidak ada batas / sudah kembali
    public function getDaysLeftAttribute(): ?int
    {
        if ($this->returned_at || ! $this->due_at) {
            return null;
        }

        return (int) today()->diffInDays($this->due_at, false);
    }

    // Contoh: "Gedung Utama › Ruang Staf (Meja Pak Budi)"
    public function getLastLocationLabelAttribute(): ?string
    {
        if (! $this->last_location_id) {
            return null;
        }

        return $this->lastLocation->full_name . ($this->last_location_note ? " ({$this->last_location_note})" : '');
    }
}