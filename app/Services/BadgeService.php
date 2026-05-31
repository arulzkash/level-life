<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\User;
use App\Support\CacheBuster;
use Illuminate\Support\Facades\Cache;

class BadgeService
{
    private const STREAK_MILESTONES = [
        ['key' => 'streak_3', 'threshold' => 3],
        ['key' => 'streak_7', 'threshold' => 7],
        ['key' => 'streak_14', 'threshold' => 14],
        ['key' => 'streak_30', 'threshold' => 30],
        ['key' => 'streak_60', 'threshold' => 60],
        ['key' => 'streak_100', 'threshold' => 100],
        ['key' => 'streak_150', 'threshold' => 150],
        ['key' => 'streak_200', 'threshold' => 200],
        ['key' => 'streak_365', 'threshold' => 365],
        ['key' => 'streak_500', 'threshold' => 500],
    ];

    public static function streakMilestones(): array
    {
        return self::STREAK_MILESTONES;
    }

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
        if (! $profile) {
            return;
        }

        $streakBest = (int) ($profile->streak_best ?? 0);
        $freezesTotal = (int) ($profile->freezes_used_total ?? 0);
        $resetsTotal = (int) ($profile->streak_resets_total ?? 0);

        $keys = [];

        foreach (self::STREAK_MILESTONES as $milestone) {
            if ($streakBest >= $milestone['threshold']) {
                $keys[] = $milestone['key'];
            }
        }

        if ($freezesTotal > 0) {
            $keys[] = 'second_wind';
        }
        if ($resetsTotal > 0 && $streakBest >= 7) {
            $keys[] = 'comeback_kid';
        }

        if (! $keys) {
            return;
        }

        // 1) ambil badge_id dari cache (tanpa query)
        $map = $this->badgeKeyMap();
        $badgeIds = [];
        foreach ($keys as $k) {
            if (isset($map[$k])) {
                $badgeIds[] = (int) $map[$k];
            }
        }
        $badgeIds = array_values(array_unique($badgeIds));
        if (! $badgeIds) {
            return;
        }

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
            if (! isset($ownedSet[$bid])) {
                $attach[$bid] = ['earned_at' => $earnedAt];
            }
        }

        if ($attach) {
            $user->badges()->syncWithoutDetaching($attach);
            CacheBuster::invalidatePublicProfileBadges($user->id);
        }
    }
}
