<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'code_prefix', 'description'];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}