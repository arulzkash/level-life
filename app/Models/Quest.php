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
        'subtasks',
        'status',
        'type',
        'xp_reward',
        'coin_reward',
        'due_date',
        'completed_at',
        'is_repeatable',
        'position',
    ];

    protected $casts = [
        'subtasks' => 'array',
        'is_repeatable' => 'boolean',
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

    // --- HELPER / ACCESSOR ---

    // Logic: Cek apakah semua subtask sudah dicentang?
    // Return true jika tidak punya subtask ATAU semua subtask is_done = true
    public function getCanBeCompletedAttribute(): bool
    {
        // Kalau kosong, berarti bebas complete
        if (empty($this->subtasks)) {
            return true;
        }

        // Cek satu per satu
        foreach ($this->subtasks as $task) {
            // Jika ada satu saja yang is_done-nya false/null, maka GABISA complete
            if (empty($task['is_done'])) {
                return false;
            }
        }

        return true;
    }
}
