<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Food extends Model
{
    protected $table = 'foods';

    protected $fillable = [
        'name', 
        'calories_per_100g', 
        'protein', 
        'carbs', 
        'fat', 
        'source', 
        'is_verified',
        'created_by'
    ];
    
    protected $casts = [
        'is_verified' => 'boolean'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to show system foods + the given user's custom foods.
     */
    public function scopeVisibleTo($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('source', '!=', 'user')
              ->orWhere('created_by', $userId);
        });
    }
}
