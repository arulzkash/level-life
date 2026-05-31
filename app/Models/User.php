<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasFactory, HasPushSubscriptions, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
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
            if (! $user->username) {
                $user->forceFill([
                    'username' => static::generateUniqueUsername($user->name, $user->id),
                ])->saveQuietly();
            }

            // Setiap kali User baru berhasil dibuat (created),
            // Kita buatkan Profile default untuk user tersebut.
            $user->profile()->create([
                'xp_total' => 0,
                'coin_balance' => 0,
                'current_streak' => 0,
                'last_quest_completed_at' => null,
                'streak_current' => 0,
                'streak_best' => 0,
                'streak_maintained_through' => null,
                'last_active_date' => null,
                'freezes_used_week_start' => null,
                'freezes_used_count' => 0,
                'freezes_used_total' => 0,
                'streak_resets_total' => 0,
                'bio' => null,
            ]);
        });
    }

    public static function generateUniqueUsername(?string $name, int $fallbackId, ?int $ignoreUserId = null): string
    {
        $base = Str::slug(Str::lower((string) $name), '_');
        $base = $base !== '' ? substr($base, 0, 30) : "user{$fallbackId}";

        $candidate = $base;
        $suffix = 1;

        while (
            static::query()
                ->where('username', $candidate)
                ->when($ignoreUserId, fn ($query) => $query->where('id', '!=', $ignoreUserId))
                ->exists()
        ) {
            $suffix++;
            $suffixText = "_{$suffix}";
            $candidate = substr($base, 0, max(1, 30 - strlen($suffixText))).$suffixText;
        }

        return $candidate;
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

    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    public function habitEntries()
    {
        return $this->hasMany(HabitEntry::class);
    }

    public function timeBlocks()
    {
        return $this->hasMany(TimeBlock::class);
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot(['earned_at'])
            ->withTimestamps();
    }

    public function questTypes()
    {
        return $this->hasMany(QuestType::class);
    }

    public function dailyActivities(): HasMany
    {
        return $this->hasMany(UserDailyActivity::class);
    }
}
