<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'morning_enabled',
        'evening_enabled',
    ];

    protected $casts = [
        'morning_enabled' => 'boolean',
        'evening_enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
