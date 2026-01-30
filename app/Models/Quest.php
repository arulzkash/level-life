<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    // --- MUTATOR SAKTI ---
    protected $casts = [
        'id' => 'string',
    ];
    
    protected function isRepeatable(): Attribute
    {
        return Attribute::make(
            // GET (Dari DB ke Frontend): 
            // Pastikan apapun yang keluar dari DB ('t', 'TRUE', 1) jadi boolean true/false
            get: function ($value) {
                if (is_bool($value)) return $value;
                return in_array($value, [1, '1', 't', 'true', 'TRUE'], true);
            },

            // SET (Dari Controller ke DB):
            // Terima boolean true/false, ubah jadi string 't'/'f' buat Postgres
            set: function ($value) {
                // Handle false/0/'f' dengan tegas
                if ($value === false || $value === 0 || $value === '0' || $value === 'f' || $value === 'false') {
                    return 'f';
                }
                return 't';
            }
        );
    }


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
