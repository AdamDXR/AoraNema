<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Showtime extends Model
{
    // biar kolom 'id' gak bisa diisi sembarangan misalnya lewat form input
    protected $guarded = ['id'];

    // biar nanti kolom show_time otomatis diubah jadi tipe datetime (Carbon PHP)
    protected $casts = [
        'show_time' => 'datetime',
    ];

    // relasi ke tabel movie (1 jadwal hanya untuk 1 film)
    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    // relasi ke tabel studios (1 jadwal hanya bisa di 1 ruangan studio)
    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    // relasi ke tabel bookings (1 jadwal bisa dibooking banyak tiket)
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }    
}
