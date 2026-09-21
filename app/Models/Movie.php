<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    // membuka gembok pengisian data banyak atau massal
    protected $guarded = ['id'];

    // relasi ke tabel genre (many-to-many)
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    // relasi ke tabel showtimes (One-to-many)
    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    // relasi ke tabel log activity machine learning (One-to-many)
    public function userEvents(): HasMany
    {
        return $this->hasMany(UserEvent::class);
    }
}
