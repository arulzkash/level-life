<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'title', // +++
        'mood_emoji',
        'is_favorite',
        'body',
        'sections',
        'xp_awarded',
        'coin_awarded',
        'rewarded_at',
    ];


    protected $casts = [
        'date' => 'date',
        'sections' => 'array',
        'is_favorite' => 'boolean',
        'rewarded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
