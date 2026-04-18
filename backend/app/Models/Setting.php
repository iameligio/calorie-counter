<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static function booted()
    {
        static::saved(function ($setting) {
            \Illuminate\Support\Facades\Cache::forget($setting->key);
        });
    }
}
