<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Studio extends Model
{
    protected $guarded = ['id'];

    // Satu studio memiliki banyak kursi
    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    // Satu studio dipakai untuk banyak jadwal tayang
    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }
}
