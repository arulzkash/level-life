<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Http\Controllers\ProfileController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- THE MAGIC SAUCE: AUTO CREATE PROFILE ---
    /**
     * Method ini jalan otomatis saat Model User di-boot.
     */
    protected static function booted()
    {
        static::created(function ($user) {
            // Setiap kali User baru berhasil dibuat (created),
            // Kita buatkan Profile default untuk user tersebut.
            $user->profile()->create([
                'xp_total' => 0,
                'coin_balance' => 0,
                'current_streak' => 0,
                'last_quest_completed_at' => null,
            ]);
        });
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function quests()
    {
        return $this->hasMany(Quest::class);
    }

    public function questCompletions()
    {
        return $this->hasMany(QuestCompletion::class);
    }

    public function treasuryRewards()
    {
        return $this->hasMany(TreasuryReward::class);
    }

    public function treasuryPurchases()
    {
        return $this->hasMany(TreasuryPurchase::class);
    }

    public function habits()
    {
        return $this->hasMany(Habit::class);
    }

    public function habitEntries()
    {
        return $this->hasMany(HabitEntry::class);
    }

    public function timeBlocks()
    {
        return $this->hasMany(TimeBlock::class);
    }
}
