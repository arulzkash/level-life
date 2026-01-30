<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\JournalEntry;
use App\Support\CacheKeys;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // PROFILE
        $profile = $user->profile()
            ->select(['id', 'user_id', 'coin_balance', 'xp_total', 'streak_current'])
            ->first();


        $today = CacheKeys::todayJakarta();

        $journalTodayExists = Cache::remember(
            CacheKeys::dashboardJournalDone($user->id, $today),
            86400,
            fn() => JournalEntry::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->exists()
        );

        // HABIT
        // HABIT CACHE KEY: Unik per user & per hari
        // Kenapa per hari? Karena kalau ganti hari, status 'done_today' reset jadi false.
        $habitCacheKey = "dashboard:habits:{$user->id}:{$today}";

        $habitsPayload = Cache::remember($habitCacheKey, 86400, function () use ($user, $today) {
            // === LOGIC BERAT PINDAH KE SINI ===

            // 1. Ambil Habit Aktif
            $habits = $user->habits()
                ->active($today)
                ->orderBy('name')
                ->get();

            if ($habits->isEmpty()) return collect([]);

            $habitIds = $habits->pluck('id');

            // 2. Ambil Log (Query Berat)
            // Sekarang aman karena cuma jalan 1x sehari atau pas tombol dipencet
            $entriesByHabit = $user->habitEntries()
                ->whereIn('habit_id', $habitIds)
                ->whereDate('date', '<=', $today)
                ->get()
                ->groupBy('habit_id');

            // 3. Hitung Streak (Looping Berat)
            $processed = $habits->map(function ($h) use ($entriesByHabit, $today) {
                $dates = ($entriesByHabit[$h->id] ?? collect())
                    ->pluck('date')
                    ->map(fn($d) => (string) substr($d, 0, 10)) // Pastikan format Y-m-d
                    ->flip();

                $isDoneToday = isset($dates[$today]);
                $streak = 0;

                // Logic hitung mundur streak
                $cursor = $isDoneToday ? Carbon::parse($today) : Carbon::parse($today)->subDay();

                if ($cursor->toDateString() >= $h->start_date) {
                    while (true) {
                        $d = $cursor->toDateString();
                        if (!isset($dates[$d])) break;

                        $streak++;
                        $cursor->subDay();
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

            return $processed->sortByDesc('streak')->values();
        });

        // Hitung summary dari payload yang sudah di-cache (Ringan)
        $doneCount = $habitsPayload->where('done_today', true)->count();
        $totalCount = $habitsPayload->count();

        $dateKey = CacheKeys::todayJakarta();
        // TIMEBLOCK
        $timeblocksKey = CacheKeys::dashboardTimeblocks($user->id, $dateKey);

        $todayBlocks = Cache::remember($timeblocksKey, 86400, function () use ($user, $dateKey) {
            return $user->timeBlocks()
                ->whereDate('date', $dateKey)
                ->orderBy('start_time')
                ->get()
                ->map(fn($b) => [
                    'id' => $b->id,
                    'start_time' => substr($b->start_time, 0, 5),
                    'end_time' => substr($b->end_time, 0, 5),
                    'title' => $b->title,
                    'note' => $b->note,
                ]);
        });

        $now = now();
        $leaderboardData = [
            'rank' => '-',
            'rival' => null,
            'message' => 'View Leaderboard'
        ];


        $activeQuestsKey = CacheKeys::dashboardActiveQuests($user->id, $dateKey);

        $activeQuests = Cache::remember($activeQuestsKey, 86400, function () use ($user) {
            return $user->quests()
                ->active()
                ->orderBy('position')
                ->get([
                    'id',
                    'name',
                    'status',
                    'type',
                    'xp_reward',
                    'coin_reward',
                    'due_date',
                    'is_repeatable',
                    'position'
                ]);
        });


        $globalRoster = Cache::get(CacheKeys::leaderboardRoster($dateKey));

        if ($globalRoster) {
            $roster = collect($globalRoster)->values();

            $myIndex = $roster->search(fn($r) => (int)$r->user_id === (int)$user->id);

            if ($myIndex !== false && $myIndex < 50) {
                $leaderboardData['rank'] = $myIndex + 1;

                if ($myIndex > 0) {
                    $me = $roster[$myIndex];
                    $rival = $roster[$myIndex - 1];

                    $leaderboardData['rival'] = [
                        'name' => $rival->name,
                        'gap' => (($rival->effective_streak ?? 0) - ($me->effective_streak ?? 0)) + 1,
                    ];
                } else {
                    $leaderboardData['rival'] = [
                        'name' => 'No one',
                        'gap' => 0,
                        'is_king' => true,
                    ];
                }
            } else {
                $leaderboardData['rank'] = '50+';
                $leaderboardData['message'] = 'Keep grinding to enter top 50!';
            }
        }

        // --- BADGE SNIPPET ---
        // Fetch the "Top" badge (Highest ID usually means latest, 

        $topBadgeKey = CacheKeys::dashboardTopBadge($user->id);

        $topBadge = Cache::remember($topBadgeKey, 86400, function () use ($user) {
            return DB::table('user_badges')
                ->join('badges', 'badges.id', '=', 'user_badges.badge_id')
                ->where('user_badges.user_id', $user->id)
                ->select('badges.name', 'badges.key', 'badges.description')
                ->orderBy('user_badges.created_at', 'desc')
                ->limit(1)
                ->first();
        });

        return Inertia::render('Dashboard', [
            'profile' => $profile,
            'today' => $today,
            'journalTodayExists' => $journalTodayExists,
            'habits' => $habitsPayload,
            'habitSummary' => [
                'done_today' => $doneCount,
                'total' => $totalCount,
            ],
            'activeQuests' => $activeQuests,
            'todayBlocks' => $todayBlocks,
            'leaderboardData' => $leaderboardData,
            'topBadge' => $topBadge,
        ]);
    }
}
