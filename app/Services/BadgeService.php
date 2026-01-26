<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class BadgeService
{
    private function badgeKeyMap(): array
    {
        // key => id
        return Cache::rememberForever('badge:key_map', function () {
            return Badge::query()
                ->pluck('id', 'key')
                ->toArray();
        });
    }

    public function syncForUser(User $user): void
    {
        $profile = $user->profile;
        if (!$profile) return;

        $streakBest   = (int) ($profile->streak_best ?? 0);
        $freezesTotal = (int) ($profile->freezes_used_total ?? 0);
        $resetsTotal  = (int) ($profile->streak_resets_total ?? 0);

        $keys = [];

        if ($streakBest >= 3)   $keys[] = 'streak_3';
        if ($streakBest >= 7)   $keys[] = 'streak_7';
        if ($streakBest >= 14)  $keys[] = 'streak_14';
        if ($streakBest >= 30)  $keys[] = 'streak_30';
        if ($streakBest >= 60)  $keys[] = 'streak_60';
        if ($streakBest >= 100) $keys[] = 'streak_100';

        if ($freezesTotal > 0) $keys[] = 'second_wind';
        if ($resetsTotal > 0 && $streakBest >= 7) $keys[] = 'comeback_kid';

        if (!$keys) return;

        // 1) ambil badge_id dari cache (tanpa query)
        $map = $this->badgeKeyMap();
        $badgeIds = [];
        foreach ($keys as $k) {
            if (isset($map[$k])) $badgeIds[] = (int) $map[$k];
        }
        $badgeIds = array_values(array_unique($badgeIds));
        if (!$badgeIds) return;

        // 2) sekali query: mana yang sudah dimiliki user
        $owned = $user->badges()
            ->whereIn('badge_id', $badgeIds)
            ->pluck('badge_id')
            ->all();
        $ownedSet = array_flip($owned);

        // 3) attach batch sekali (kalau ada yang baru)
        $earnedAt = now()->toDateString();
        $attach = [];
        foreach ($badgeIds as $bid) {
            if (!isset($ownedSet[$bid])) {
                $attach[$bid] = ['earned_at' => $earnedAt];
            }
        }

        if ($attach) {
            $user->badges()->attach($attach);
        }
    }
}
