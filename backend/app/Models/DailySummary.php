<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySummary extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'total_calories',
        'calorie_target'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
