<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodLog extends Model
{
    protected $fillable = [
        'user_id', 
        'food_id', 
        'grams', 
        'calories', 
        'consumed_at'
    ];
    
    protected $casts = [
        'consumed_at' => 'datetime'
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
    
    public function food(): BelongsTo {
        return $this->belongsTo(Food::class);
    }
}
