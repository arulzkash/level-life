<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeBlock extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'title',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
