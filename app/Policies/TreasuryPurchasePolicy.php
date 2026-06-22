<?php

namespace App\Policies;

use App\Models\TreasuryPurchase;
use App\Models\User;

class TreasuryPurchasePolicy
{
    public function update(User $user, TreasuryPurchase $treasuryPurchase): bool
    {
        return $treasuryPurchase->user_id === $user->id;
    }
}
