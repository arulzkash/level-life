<?php

namespace App\Policies;

use App\Models\TimeBlock;
use App\Models\User;

class TimeBlockPolicy
{
    public function update(User $user, TimeBlock $timeBlock): bool
    {
        return $timeBlock->user_id === $user->id;
    }

    public function delete(User $user, TimeBlock $timeBlock): bool
    {
        return $timeBlock->user_id === $user->id;
    }
}
