<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Habit extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entries()
    {
        return $this->hasMany(HabitEntry::class);
    }

    public function scopeActive(Builder $query, string $date): void
    {
        $query->where('start_date', '<=', $date)
            ->whereNull('end_date');
    }
}
