<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEvent extends Model
{
    protected $guarded = ['id'];

    // Aktivitas ini dilakukan oleh satu user tertentu
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Aktivitas ini terkait dengan satu film tertentu
    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
}
