<?php

namespace App\Support;

use Carbon\Carbon;

class CacheKeys
{
    public static function todayJakarta(): string
    {
        return Carbon::now('Asia/Jakarta')->toDateString(); // YYYY-MM-DD
    }

    public static function leaderboardRoster(string $dateJakarta): string
    {
        return "leaderboard:global_roster:{$dateJakarta}";
    }

    public static function leaderboardBadges(string $dateJakarta): string
    {
        return "leaderboard:global_badges:{$dateJakarta}";
    }

    public static function dashboardTopBadge(int $userId): string
    {
        return "dashboard:top_badge:{$userId}";
    }

    public static function navProfile(int $userId, string $dateJakarta): string
    {
        return "nav_profile:{$userId}:{$dateJakarta}";
    }

    public static function navUser(int $userId, string $dateJakarta): string
    {
        return "nav_user:{$userId}:{$dateJakarta}";
    }

    public static function dashboardActiveQuests(int $userId, string $dateJakarta): string
    {
        return "dashboard:active_quests:{$userId}:{$dateJakarta}";
    }

    public static function dashboardTimeblocks(int $userId, string $dateJakarta): string
    {
        return "dashboard:timeblocks:{$userId}:{$dateJakarta}";
    }

    public static function dashboardJournalDone(int $userId, string $dateJakarta): string
    {
        return "dashboard:journal_done:{$userId}:{$dateJakarta}";
    }

    public static function dashboardCustomQuestTypes(int $userId): string
    {
        return "dashboard:custom_quest_types:{$userId}";
    }

    // ── GOALS ─────────────────────────────────────────────────────

    public static function dashboardActiveGoals(int $userId): string
    {
        return "dashboard:active_goals:{$userId}";
    }

    public static function goalsIndex(int $userId): string
    {
        return "goals:index:{$userId}";
    }

    public static function goalShow(int $userId, int $goalId): string
    {
        return "goals:show:{$userId}:{$goalId}";
    }
}
