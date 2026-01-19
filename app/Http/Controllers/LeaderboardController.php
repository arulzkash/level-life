<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class LeaderboardController extends Controller
{
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

    private function buildLeaderboardData(Request $request): array
    {
        $userId = $request->user()->id;
        $limit = 50;

        $now = now();
        $today = $now->toDateString();
        $yesterday = $now->copy()->subDay()->toDateString();

        // Batas toleransi "Ghost" (AFK)
        $ghostThresholdDate = $now->copy()->subDays(4)->toDateString();

        // 1. Ambil Timestamp TERAKHIR (Realtime detik)
        $lastActiveQuery = DB::table('quest_completions')
            ->select('user_id', DB::raw('MAX(completed_at) as last_active_at'))
            ->groupBy('user_id');


        // 2. Hitung Konsistensi 7 Hari

        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();

        $active7Query = DB::table('quest_completions')
            ->select('user_id', DB::raw('COUNT(DISTINCT DATE(completed_at)) as active_days_last_7d'))
            ->where('completed_at', '>=', $weekStart)
            ->groupBy('user_id');

        // 3. Main Query
        $query = DB::table('profiles')
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
                'profiles.streak_current',
                'profiles.streak_best',
                'profiles.last_active_date', // Tanggal (untuk status On Fire)
                'last_log.last_active_at',   // Timestamp (untuk perhitungan "13h ago")
            ])
            ->selectRaw('COALESCE(active7.active_days_last_7d, 0) as active_days_last_7d')
            // Logic Effective Streak (Ghost Buster)
            ->selectRaw("
                CASE 
                    WHEN profiles.last_active_date < ? THEN 0 
                    ELSE COALESCE(profiles.streak_current, 0) 
                END as effective_streak
            ", [$ghostThresholdDate])
            // Logic Status Badge
            ->selectRaw("
                CASE 
                    WHEN profiles.last_active_date = ? THEN 'On Fire'
                    WHEN profiles.last_active_date = ? THEN 'Pending'
                    ELSE 'Cold'
                END as status
            ", [$today, $yesterday]);

        // Sorting Default (Current Streak)
        $rankedList = $query
            ->orderByDesc('effective_streak')
            ->orderByDesc('profiles.streak_best')
            ->orderByDesc('active_days_last_7d')
            ->orderByDesc('last_active_at') // Tie breaker pakai timestamp detik
            ->limit($limit)
            ->get();

        // Assign Rank Manual
        $rankedList->transform(function ($item, $key) {
            $item->rank = $key + 1;
            return $item;
        });

        // Cari Data "Saya"
        $myRank = $rankedList->firstWhere('user_id', $userId);

        if (!$myRank) {
            // Kalau gak masuk top 50, query manual
            $myRank = DB::table('profiles')
                ->join('users', 'users.id', '=', 'profiles.user_id')
                ->leftJoinSub($lastActiveQuery, 'last_log', function ($join) {
                    $join->on('profiles.user_id', '=', 'last_log.user_id');
                })
                ->where('profiles.user_id', $userId)
                ->select([
                    'profiles.user_id',
                    'users.name',
                    'profiles.streak_current as effective_streak',
                    'profiles.streak_best',
                    'profiles.last_active_date',
                    'last_log.last_active_at'
                ])
                ->addSelect(DB::raw("'Unknown' as status"))
                ->first();

            if ($myRank) {
                $myRank->rank = '-';
                $myRank->active_days_last_7d = 0;
            }
        }

        // --- BADGES ---
        $visibleUserIds = $rankedList->pluck('user_id')->push($userId)->unique()->toArray();
        $badges = DB::table('user_badges')
            ->join('badges', 'badges.id', '=', 'user_badges.badge_id')
            ->whereIn('user_badges.user_id', $visibleUserIds)
            ->where('badges.category', 'streak')
            ->select('user_badges.user_id', 'badges.name', 'badges.key', 'badges.description')
            ->orderBy('badges.id', 'desc')
            ->get()
            ->groupBy('user_id');

        $mapFunction = function ($row) use ($badges) {
            if (!$row) return null;
            $badge = isset($badges[$row->user_id]) ? $badges[$row->user_id]->first() : null;

            return [
                'rank' => $row->rank,
                'user' => [
                    'id' => $row->user_id,
                    'name' => $row->name,
                ],
                'status' => $row->status ?? 'Cold',
                'streak_current' => (int) $row->effective_streak,
                'streak_best' => (int) $row->streak_best,
                'active_days_last_7d' => (int) ($row->active_days_last_7d ?? 0),
                'last_active_at' => $row->last_active_at, // Ini yang penting buat time ago
                'badge_top' => $badge ? [
                    'name' => $badge->name,
                    'category' => 'streak',
                    'key' => $badge->key,
                    'description' => $badge->description,
                ] : null,
            ];
        };

        return [
            'items' => $rankedList->map($mapFunction)->toArray(),
            'me' => $mapFunction($myRank),
        ];
    }
}
