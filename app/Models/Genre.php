<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    // buat membuka izin pengisian data massal lagi dari API TMDB
    protected $guarded = ['id'];

    // relasi ke tabel movies (Many-to-Many)
    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class);
    }
    
}
