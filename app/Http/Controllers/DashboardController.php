<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // PROFILE
        $profile = $user->profile;

        // HABIT
        $today = now()->toDateString();

        $habits = $user->habits()
            ->active($today)
            ->orderBy('name')
            ->get();

        $habitIds = $habits->pluck('id');

        $entriesByHabit = $user->habitEntries()
            ->whereIn('habit_id', $habitIds)
            ->whereDate('date', '<=', $today)
            ->get()
            ->groupBy('habit_id');

        $habitsPayload = $habits->map(function ($h) use ($entriesByHabit, $today) {
            $dates = ($entriesByHabit[$h->id] ?? collect())
                ->pluck('date')
                ->map(fn($d) => (string) $d)
                ->flip(); // jadi set buat lookup cepat

            $isDoneToday = isset($dates[$today]);

            $streak = 0;

            // start cursor: today kalau done, kalau belum -> yesterday
            $cursor = $isDoneToday ? now() : now()->subDay();

            // kalau habit baru mulai hari ini dan belum done today, streak harus 0
            if ($cursor->toDateString() < $h->start_date) {
                $streak = 0;
            } else {
                while (true) {
                    $d = $cursor->toDateString();
                    if (!isset($dates[$d])) break;

                    $streak++;
                    $cursor = $cursor->subDay();

                    if ($cursor->toDateString() < $h->start_date) break;
                }
            }

            return [
                'id' => $h->id,
                'name' => $h->name,
                'start_date' => $h->start_date,
                'end_date' => $h->end_date,
                'done_today' => $isDoneToday,
                'streak' => $streak,
            ];
        });

        $habitsPayload = $habitsPayload->sortByDesc('streak')->values();

        $doneCount = $habitsPayload->where('done_today', true)->count();
        $totalCount = $habitsPayload->count();

        // TIMEBLOCK
        $todayBlocks = $user->timeBlocks()
            ->whereDate('date', $today)
            ->orderBy('start_time')
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'start_time' => substr($b->start_time, 0, 5),
                'end_time' => substr($b->end_time, 0, 5),
                'title' => $b->title,
                'note' => $b->note,
            ]);

        $now = now();
        $ghostThresholdDate = $now->copy()->subDays(4)->toDateString();

        // 1) last_active_at (timestamp detik)
        $lastActiveQuery = DB::table('quest_completions')
            ->select('user_id', DB::raw('MAX(completed_at) as last_active_at'))
            ->groupBy('user_id');

        // 2) active_days_last_7d
        $active7Query = DB::table('quest_completions')
            ->select('user_id', DB::raw('COUNT(DISTINCT DATE(completed_at)) as active_days_last_7d'))
            ->where('completed_at', '>=', $now->copy()->subDays(6)->startOfDay())
            ->groupBy('user_id');

        $leaderboard = DB::table('profiles')
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
                'profiles.last_active_date',
                'last_log.last_active_at',
            ])
            ->selectRaw('COALESCE(active7.active_days_last_7d, 0) as active_days_last_7d')
            ->selectRaw("
        CASE
            WHEN profiles.last_active_date < ? THEN 0
            ELSE COALESCE(profiles.streak_current, 0)
        END as effective_streak
    ", [$ghostThresholdDate])
            ->orderByDesc('effective_streak')
            ->orderByDesc('profiles.streak_best')
            ->orderByDesc('active_days_last_7d')
            ->orderByDesc('last_active_at')
            ->limit(100) // tetap boleh 100, ranking urutannya sudah sama
            ->get();

        // Find Me
        $myIndex = $leaderboard->search(fn($item) => $item->user_id === $user->id);

        $leaderboardData = [
            'rank' => $myIndex !== false ? $myIndex + 1 : '-',
            'rival' => null,
        ];

        // Find Rival (The person 1 rank above me)
        if ($myIndex !== false && $myIndex > 0) {
            $rival = $leaderboard[$myIndex - 1];
            $diff = ($rival->effective_streak) - ($leaderboard[$myIndex]->effective_streak);

            $leaderboardData['rival'] = [
                'name' => $rival->name,
                'gap' => $diff + 1, // Points needed to overtake (beat them by 1)
            ];
        } elseif ($myIndex === 0) {
            // You are #1
            $leaderboardData['rival'] = [
                'name' => 'No one',
                'gap' => 0,
                'is_king' => true
            ];
        }

        // --- BADGE SNIPPET ---
        // Fetch the "Top" badge (Highest ID usually means latest, 
        // or filter by 'rarity' if you have that column)
        $topBadge = DB::table('user_badges')
            ->join('badges', 'badges.id', '=', 'user_badges.badge_id')
            ->where('user_badges.user_id', $user->id)
            ->select('badges.name', 'badges.key', 'badges.description')
            ->orderBy('user_badges.created_at', 'desc') // Get the Latest one
            ->first();


        return Inertia::render('Dashboard', [
            'profile' => $profile,
            'today' => $today,
            'habits' => $habitsPayload,
            'habitSummary' => [
                'done_today' => $doneCount,
                'total' => $totalCount,
            ],
            'activeQuests' => $user->quests()
                ->active()
                ->get(),
            'todayBlocks' => $todayBlocks,
            'leaderboardData' => $leaderboardData,
            'topBadge' => $topBadge,
        ]);
    }
}
