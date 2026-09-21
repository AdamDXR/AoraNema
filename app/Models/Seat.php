<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seat extends Model
{
    protected $guarded = ['id'];

    // Kursi ini milik satu studio tertentu
    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }
}
