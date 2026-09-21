<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $guarded = ['id'];

    // Tiket ini dipesan oleh satu user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Tiket ini untuk satu jadwal tayang spesifik
    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    // Tiket ini mengunci satu kursi spesifik
    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    // Satu tiket memiliki satu riwayat tagihan pembayaran (Midtrans)
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
