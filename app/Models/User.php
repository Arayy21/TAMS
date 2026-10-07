<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Tingkat admin: admin dan pengelola
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'pengelola'], true);
    }

    public function isPengelola(): bool
    {
        return $this->role === 'pengelola';
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin'     => 'Admin',
            'pengelola' => 'Pengelola',
            default     => 'Pengguna',
        };
    } 
    
    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: [];
        $first = mb_substr($words[0] ?? '', 0, 1);

        // Dua kata atau lebih: huruf pertama kata awal + kata akhir. Satu kata: dua huruf pertama
        $second = count($words) > 1
            ? mb_substr(end($words), 0, 1)
            : mb_substr($words[0] ?? '', 1, 1);

        return mb_strtoupper($first . $second);
    }

    public function getAvatarColorAttribute(): string
    {
        $colors = ['#DC143C', '#B1002C', '#5F5E5E', '#006622', '#303030', '#916F6E'];

        return $colors[abs(crc32($this->email)) % count($colors)];
    }
}
