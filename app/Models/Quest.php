<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Quest extends Model
{
    //
    protected $fillable = [
        'user_id',
        'name',
        'status',
        'type',
        'xp_reward',
        'coin_reward',
        'due_date',
        'completed_at',
        'is_repeatable',
        'position',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function completions()
    {
        return $this->hasMany(QuestCompletion::class);
    }

    /**
     * 1. Position (Manual user)
     * 2. Due Date (Deadline terdekat)
     * 3. Created At (newest)
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', ['todo', 'in_progress'])
            ->orderBy('position', 'asc')
            ->orderByRaw('due_date is null, due_date asc')
            ->latest();
    }
}
