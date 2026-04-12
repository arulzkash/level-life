<?php

namespace App\Http\Controllers;

use App\Support\CacheKeys;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    const TTL_DAY = 86400; // 1 hari

    public function page(Request $request)
    {
        $data = $this->buildLeaderboardData($request);

        return Inertia::render('Leaderboard/Index', [
            'items' => $data['items'],
            'me' => $data['me'],
        ]);
    }

    public function index(Request $request)
    {
        return response()->json($this->buildLeaderboardData($request));
    }

    /**
     * Core Logic: Hybrid Caching
     * Menggabungkan Cached Roster (10m) + Realtime "Me" Data
     */
    private function buildLeaderboardData(Request $request): array
    {
        $userId = $request->user()->id;
        $dateKey = CacheKeys::todayJakarta();

        $rosterKey = CacheKeys::leaderboardRoster($dateKey);
        $badgesKey = CacheKeys::leaderboardBadges($dateKey);

        // Cache roster 1 hari (tapi akan di-forget setiap ada complete quest)
        $globalRoster = Cache::remember($rosterKey, self::TTL_DAY, function () {
            return $this->fetchGlobalRosterFromDB();
        });

        // Cache badges 1 hari (ikut invalidate bareng roster)
        $globalBadges = Cache::remember($badgesKey, self::TTL_DAY, function () use ($globalRoster) {
            return $this->fetchBadgesForUsers($globalRoster->pluck('user_id')->toArray());
        });

        // Hydrate rows (NO realtime "me")
        $hydratedList = $globalRoster->map(function ($item) use ($globalBadges) {
            $badges = $globalBadges->get($item->user_id);

            return $this->formatRow($item, $badges);
        });

        // 'me' object: cukup cari dari hydratedList
        $myFormattedData = $hydratedList->firstWhere('user.id', $userId);

        if (! $myFormattedData) {
            // kalau user tidak masuk top 50, bikin object minimal (tanpa query DB)
            $meUser = $request->user();

            $myFormattedData = [
                'rank' => '-',
                'user' => [
                    'id' => $meUser->id,
                    'name' => $meUser->name,
                    'username' => $meUser->username,
                ],
                'status' => 'Cold',
                'streak_current' => 0,
                'streak_best' => 0,
                'active_days_last_7d' => 0,
                'last_active_at' => null,
                'badge_top' => null,
            ];
        }

        return [
            'items' => $hydratedList->values()->toArray(),
            'me' => $myFormattedData,
        ];
    }

    // =========================================================================
    // PRIVATE METHODS (Database Queries & Helpers)
    // =========================================================================

    /**
     * Query Berat: Top 50 Global (Hanya jalan saat Cache Miss)
     */
    private function fetchGlobalRosterFromDB()
    {
        $limit = 50;
        $now = now();
        $today = $now->toDateString();
        $yesterday = $now->copy()->subDay()->toDateString();
        $ghostThresholdDate = $now->copy()->subDays(4)->toDateString();
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();

        // Subquery 1: Last Active Timestamp
        $lastActiveQuery = DB::table('quest_completions')
            ->select('user_id', DB::raw('MAX(completed_at) as last_active_at'))
            ->groupBy('user_id');

        // Subquery 2: Active 7 Days
        $active7Query = DB::table('quest_completions')
            ->select('user_id', DB::raw('COUNT(DISTINCT DATE(completed_at)) as active_days_last_7d'))
            ->where('completed_at', '>=', $weekStart)
            ->groupBy('user_id');

        // Main Query
        $rankedList = DB::table('profiles')
            ->join('users', 'users.id', '=', 'profiles.user_id')
            ->leftJoinSub($lastActiveQuery, 'last_log', function ($join) {
                $join->on('profiles.user_id', '=', 'last_log.user_id');
            })
            ->leftJoinSub($active7Query, 'active7', function ($join) {
                $join->on('profiles.user_id', '=', 'active7.user_id');
            })
            ->select([
                'profiles.user_id',
                'users.name',
                'users.username',
                'profiles.streak_current',
                'profiles.streak_best',
                'profiles.last_active_date',
                'last_log.last_active_at',
            ])
            ->selectRaw('COALESCE(active7.active_days_last_7d, 0) as active_days_last_7d')
            // Logic Effective Streak
            ->selectRaw('
                CASE 
                    WHEN profiles.last_active_date < ? THEN 0 
                    ELSE COALESCE(profiles.streak_current, 0) 
                END as effective_streak
            ', [$ghostThresholdDate])
            // Logic Status
            ->selectRaw("
                CASE 
                    WHEN profiles.last_active_date = ? THEN 'On Fire'
                    WHEN profiles.last_active_date = ? THEN 'Pending'
                    ELSE 'Cold'
                END as status
            ", [$today, $yesterday])
            ->orderByDesc('effective_streak')
            ->orderByDesc('profiles.streak_best')
            ->orderByDesc('active_days_last_7d')
            ->orderByDesc('last_active_at')
            ->limit($limit)
            ->get();

        // Pre-calculate Rank di dalam Cache
        $rankedList->transform(function ($item, $key) {
            $item->rank = $key + 1;

            return $item;
        });

        return $rankedList;
    }

    /**
     * Query Ringan: Statistik 1 User (Realtime)
     */

    /**
     * Query Batch Badges
     */
    private function fetchBadgesForUsers(array $userIds)
    {
        if (empty($userIds)) {
            return collect();
        }

        return DB::table('user_badges')
            ->join('badges', 'badges.id', '=', 'user_badges.badge_id')
            ->whereIn('user_badges.user_id', $userIds)
            ->whereIn('badges.category', ['recovery', 'streak'])
            ->select('user_badges.user_id', 'badges.name', 'badges.category', 'badges.key', 'badges.description')
            ->orderBy('badges.id', 'desc')
            ->get()
            ->groupBy('user_id');
    }

    /**
     * Formatting Logic (Standardisasi Output ke Vue)
     */
    private function formatRow($row, $badgeList)
    {
        $badge = null;
        if ($badgeList && $badgeList->isNotEmpty()) {
            // prioritas streak, kalau gak ada baru recovery
            $badge = $badgeList->firstWhere('category', 'streak') ?? $badgeList->firstWhere('category', 'recovery');
        }

        return [
            'rank' => $row->rank ?? '-',
            'user' => [
                'id' => $row->user_id,
                'name' => $row->name,
                'username' => $row->username ?? null,
            ],
            'status' => $row->status ?? 'Cold',
            'streak_current' => (int) $row->effective_streak,
            'streak_best' => (int) $row->streak_best,
            'active_days_last_7d' => (int) ($row->active_days_last_7d ?? 0),
            'last_active_at' => $row->last_active_at,
            'badge_top' => $badge ? [
                'name' => $badge->name,
                'category' => $badge->category,
                'key' => $badge->key,
                'description' => $badge->description,
            ] : null,
        ];
    }
}
