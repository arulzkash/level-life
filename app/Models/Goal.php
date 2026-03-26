<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    protected $fillable = ['title', 'description', 'personal_reason', 'deadline', 'status', 'completed_at'];
    
    protected $casts = [
        'deadline' => 'date',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function milestones()
    {
        return $this->hasMany(GoalMilestone::class)->orderBy('position')->orderBy('due_date');
    }
}
