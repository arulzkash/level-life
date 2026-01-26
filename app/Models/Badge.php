<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Badge extends Model
{
    protected $fillable = ['key', 'name', 'category', 'description'];

    protected static function booted()
    {
        static::saved(fn() => Cache::forget('badge:key_map'));
        static::deleted(fn() => Cache::forget('badge:key_map'));
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withPivot(['earned_at'])
            ->withTimestamps();
    }
}
