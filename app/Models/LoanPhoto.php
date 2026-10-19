<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanPhoto extends Model
{
    protected $fillable = ['loan_id', 'stage', 'path', 'created_by'];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    // Alamat foto (relatif, hanya bisa dibuka setelah login)
    public function getUrlAttribute(): string
    {
        return route('loans.photo', ['photo' => $this->id], false);
    }
}