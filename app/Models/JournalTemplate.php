<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalTemplate extends Model
{
    protected $fillable = ['user_id', 'name', 'sections'];

    protected $casts = [
        'sections' => 'array',
        'id' => 'string',
    ];
}

