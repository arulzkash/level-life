<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestCompletion extends Model
{
    //
    protected $fillable = [
        'user_id',
        'quest_id',
        'xp_awarded',
        'coin_awarded',
        'completed_at',
        'note'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quest()
    {
        return $this->belongsTo(Quest::class);
    }
}
