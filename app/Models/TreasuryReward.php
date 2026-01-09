<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasuryReward extends Model
{
    //
    protected $fillable = [
        'user_id',
        'name',
        'cost_coin',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchases()
    {
        return $this->hasMany(TreasuryPurchase::class);
    }
}
