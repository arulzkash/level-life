<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class CacheBuster
{
    public static function onQuestMutate(int $userId): void
    {
        $dateKey = CacheKeys::todayJakarta();

        // Dashboard: active quests list
        Cache::forget(CacheKeys::dashboardActiveQuests($userId, $dateKey));
    }

    public static function onQuestComplete(int $userId): void
    {
        $dateKey = CacheKeys::todayJakarta();

        // global leaderboard cache (per-hari)
        Cache::forget(CacheKeys::leaderboardRoster($dateKey));
        Cache::forget(CacheKeys::leaderboardBadges($dateKey));

        // dashboard badge snippet
        Cache::forget(CacheKeys::dashboardTopBadge($userId));

        // navbar profile (coin/xp berubah)
        Cache::forget(CacheKeys::navProfile($userId, $dateKey));

        // dashboard: active quests list (quest bisa keluar/masuk list)
        Cache::forget(CacheKeys::dashboardActiveQuests($userId, $dateKey));
    }

    public static function onTimeblockMutate(int $userId): void
    {
        $dateKey = CacheKeys::todayJakarta();
        Cache::forget(CacheKeys::dashboardTimeblocks($userId, $dateKey));
    }

    public static function onJournalSave(int $userId, string $dateYmd): void
    {
        Cache::forget(CacheKeys::dashboardJournalDone($userId, $dateYmd));
    }

    public static function invalidateNavProfile(int $userId): void
    {
        $dateKey = CacheKeys::todayJakarta();
        Cache::forget(CacheKeys::navProfile($userId, $dateKey));
    }
    public static function invalidateNavUser(int $userId): void
    {
        $dateKey = CacheKeys::todayJakarta();
        Cache::forget(CacheKeys::navUser($userId, $dateKey));
    }

    public static function onQuestTypeMutate(int $userId): void
    {
        Cache::forget(CacheKeys::dashboardCustomQuestTypes($userId));
    }
}
