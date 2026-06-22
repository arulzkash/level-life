<?php

namespace App\Policies;

use App\Models\GoalMilestone;
use App\Models\User;

class GoalMilestonePolicy
{
    public function update(User $user, GoalMilestone $goalMilestone): bool
    {
        return $goalMilestone->goal?->user_id === $user->id;
    }
}
