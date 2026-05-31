<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    public const TYPE_MORNING_REMINDER = 'morning_reminder';
    public const TYPE_EVENING_STREAK_REMINDER = 'evening_streak_reminder';

    protected $fillable = [
        'user_id',
        'type',
        'date',
        'sent_at',
    ];

    protected $casts = [
        'date' => 'date',
        'sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
