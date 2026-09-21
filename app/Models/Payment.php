<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    // Membuka gembok keamanan pengisian data dari Midtrans
    protected $guarded = ['id'];

    // Relasi balik ke tabel bookings (Satu tagihan hanya untuk satu pemesanan)
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}