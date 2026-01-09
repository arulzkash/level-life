<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasuryPurchase extends Model
{
    //
    protected $fillable = [
        'user_id',
        'treasury_reward_id',
        'cost_coin',
        'purchased_at',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reward()
    {
        return $this->belongsTo(TreasuryReward::class, 'treasury_reward_id');
    }
}
