<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetRequest extends Model
{
    // Permintaan yang tidak diproses dalam sekian hari dianggap kedaluwarsa
    public const EXPIRE_DAYS = 3;

    protected $fillable = ['user_id', 'email', 'message', 'status', 'requested_ip', 'handled_by', 'handled_at'];

    protected $casts = ['handled_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    // Masih menunggu dan belum lewat batas hari
    public function scopePending($query)
    {
        return $query->where('status', 'menunggu')
                     ->where('created_at', '>=', now()->subDays(self::EXPIRE_DAYS));
    }

    public static function expireOld(): int
    {
        return static::where('status', 'menunggu')
            ->where('created_at', '<', now()->subDays(self::EXPIRE_DAYS))
            ->update(['status' => 'kedaluwarsa']);
    }

    public function getStatusLabelAttribute(): string
    {
        return ['menunggu' => 'Menunggu', 'selesai' => 'Selesai', 'ditolak' => 'Ditolak', 'kedaluwarsa' => 'Kedaluwarsa'][$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return ['menunggu' => 'warning', 'selesai' => 'success', 'ditolak' => 'secondary', 'kedaluwarsa' => 'secondary'][$this->status] ?? 'secondary';
    }
}