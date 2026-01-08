<?php

namespace App\Models;

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
        'is_repeatable'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function completions()
    {
        return $this->hasMany(QuestCompletion::class);
    }
}
