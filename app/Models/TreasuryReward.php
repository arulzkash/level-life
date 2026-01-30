<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreasuryReward extends Model
{
    //
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'cost_coin',
    ];

    protected $casts = [
        'id' => 'string',
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
