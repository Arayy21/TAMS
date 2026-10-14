<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['parent_id', 'name', 'description', 'photo'];

    public function parent()
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    // Alamat foto (relatif, mengikuti host yang sedang dipakai). Angka ?v= memaksa browser memuat ulang saat foto diganti.
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo
            ? route('locations.photo', ['location' => $this->id], false) . '?v=' . ($this->updated_at?->timestamp ?? 0)
            : null;
    }

        // Nama lengkap, contoh: Gedung Utama › Ruang Rapat
    public function getFullNameAttribute(): string
    {
        return ($this->parent ? $this->parent->name . ' › ' : '') . $this->name;
    }
}