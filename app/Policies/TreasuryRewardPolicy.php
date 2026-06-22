<?php

namespace App\Policies;

use App\Models\TreasuryReward;
use App\Models\User;

class TreasuryRewardPolicy
{
    public function update(User $user, TreasuryReward $treasuryReward): bool
    {
        return $treasuryReward->user_id === $user->id;
    }

    public function delete(User $user, TreasuryReward $treasuryReward): bool
    {
        return $treasuryReward->user_id === $user->id;
    }

    public function buy(User $user, TreasuryReward $treasuryReward): bool
    {
        return $treasuryReward->user_id === $user->id;
    }
}
