<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    //
    protected $fillable = [
        'user_id',
        'xp_total',
        'coin_balance',
        'current_streak',
        'last_quest_completed_at',
        'streak_current',
        'streak_best',
        'streak_maintained_through',
        'last_active_date',
        'freezes_used_week_start',
        'freezes_used_count',
        'freezes_used_total',
        'streak_resets_total',
    ];

    // INI BARU: Memberitahu Laravel untuk selalu menyertakan atribut 'level_data' saat model diubah jadi JSON/Array
    protected $appends = ['level_data'];

    protected $casts = [
        'id' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* |--------------------------------------------------------------------------
    | Accessors & Game Logic
    |--------------------------------------------------------------------------
    */

    public function getLevelDataAttribute(): array
    {
        return $this->calculateLevel($this->xp_total);
    }

    /**
     * Core logic perhitungan level
     */
    public function calculateLevel(int $totalXp): array
    {
        $level = 1;
        $xpIntoLevel = $totalXp;

        while (true) {
            $need = $this->xpToNextLevel($level);

            if ($xpIntoLevel < $need) {
                break;
            }

            $xpIntoLevel -= $need;
            $level++;
        }

        $need = $this->xpToNextLevel($level);

        // Menghindari pembagian dengan nol (just in case)
        $progress = $need > 0 ? round(($xpIntoLevel / $need) * 100, 1) : 0;

        return [
            'current_level' => $level,
            'xp_total' => $totalXp,
            'xp_current' => $xpIntoLevel,      // XP yang sudah dikumpulkan di level ini
            'xp_needed' => $need,              // Target XP level ini
            'xp_remaining' => $need - $xpIntoLevel,
            'progress_percent' => $progress,   // 0 - 100
        ];
    }

    /**
     * Rumus: level^1.5 * 100
     */
    private function xpToNextLevel(int $level): int
    {
        return (int) floor(pow($level, 1.5) * 100);
    }
}
