<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalMilestone extends Model
{
    protected $fillable = ['title', 'due_date', 'is_completed', 'completed_at', 'position'];
    
    protected $casts = [
        'due_date' => 'date',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function goal()
    {
        return $this->belongsTo(Goal::class);
    }
}
