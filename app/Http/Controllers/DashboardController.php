<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\JournalEntry;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // PROFILE
        $profile = $user->profile;

        // HABIT
        $today = now()->toDateString();
        $journalTodayExists = JournalEntry::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->exists();

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
        $leaderboardData = [
            'rank' => '-',
            'rival' => null,
            'message' => 'View Leaderboard'
        ];

        // 1. Ambil Global Roster (Cached 10m)
        $globalRoster = Cache::get('leaderboard:global_roster'); // Returns Collection/Array of Objects

        if ($globalRoster) {
            // 2. Ambil Data Realtime Saya (1 Query Ringan - Point Get)
            $myRealtimeStats = $this->fetchSingleUserStats($user->id);

            // 3. Logic "Injection & Re-Sort" di Memori PHP
            // Kita gabungkan user lain (dari cache) dengan data saya (realtime)
            // Lalu kita sort ulang untuk tau posisi 'asli' saya detik ini.

            $rosterCollection = collect($globalRoster);

            // Hapus entry saya yang lama (kalau ada di cache), ganti dengan yang baru
            $rosterCollection = $rosterCollection->filter(fn($item) => $item->user_id !== $user->id);
            $rosterCollection->push($myRealtimeStats);

            // Sort Ulang (Sesuai logic Leaderboard)
            $sortedRoster = $rosterCollection->sort(function ($a, $b) {
                // Logic sort descending (Return 1 jika A < B, -1 jika A > B)
                // Prioritas 1: Effective Streak
                if (($b->effective_streak ?? 0) !== ($a->effective_streak ?? 0)) {
                    return ($b->effective_streak ?? 0) <=> ($a->effective_streak ?? 0);
                }
                // Prioritas 2: Best Streak
                if (($b->streak_best ?? 0) !== ($a->streak_best ?? 0)) {
                    return ($b->streak_best ?? 0) <=> ($a->streak_best ?? 0);
                }
                // Prioritas 3: Active Days
                if (($b->active_days_last_7d ?? 0) !== ($a->active_days_last_7d ?? 0)) {
                    return ($b->active_days_last_7d ?? 0) <=> ($a->active_days_last_7d ?? 0);
                }
                // Prioritas 4: Last Active At (Siapa yang duluan aktif/selesai)
                return strcmp($b->last_active_at ?? '', $a->last_active_at ?? '');
            })->values();

            // 4. Cari Posisi Saya Setelah Sort
            $myIndex = $sortedRoster->search(fn($item) => $item->user_id === $user->id);

            // Cek apakah saya masuk Top 50 setelah update realtime?
            if ($myIndex !== false && $myIndex < 50) {
                $myRank = $myIndex + 1;
                $leaderboardData['rank'] = $myRank;

                // Cari Rival (Orang di atas saya)
                if ($myIndex > 0) {
                    $rival = $sortedRoster[$myIndex - 1];
                    $gap = ($rival->effective_streak ?? 0) - ($myRealtimeStats->effective_streak ?? 0);

                    $leaderboardData['rival'] = [
                        'name' => $rival->name,
                        'gap' => $gap + 1,
                    ];
                } else {
                    // Rank 1
                    $leaderboardData['rival'] = [
                        'name' => 'No one',
                        'gap' => 0,
                        'is_king' => true
                    ];
                }
            } else {
                // Masih di luar Top 50
                $leaderboardData['rank'] = '50+';
                $leaderboardData['message'] = 'Keep grinding to enter top 50!';
            }
        }

        // --- BADGE SNIPPET ---
        // Fetch the "Top" badge (Highest ID usually means latest, 

        $topBadge = DB::table('user_badges')
            ->join('badges', 'badges.id', '=', 'user_badges.badge_id')
            ->where('user_badges.user_id', $user->id)
            ->select('badges.name', 'badges.key', 'badges.description')
            ->orderBy('user_badges.created_at', 'desc')
            ->limit(1)
            ->first();

        return Inertia::render('Dashboard', [
            'profile' => $profile,
            'today' => $today,
            'journalTodayExists' => $journalTodayExists,
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


    /**
     * Helper: Query 1 User Stats (Copy logic dari LeaderboardController biar konsisten)
     * Sangat ringan karena filter WHERE user_id
     */
    private function fetchSingleUserStats($userId)
    {
        $now = now();
        $today = $now->toDateString();
        $yesterday = $now->copy()->subDay()->toDateString();
        $ghostThresholdDate = $now->copy()->subDays(4)->toDateString();
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();

        $lastActiveQuery = DB::table('quest_completions')
            ->select('user_id', DB::raw('MAX(completed_at) as last_active_at'))
            ->where('user_id', $userId)
            ->groupBy('user_id');

        $active7Query = DB::table('quest_completions')
            ->select('user_id', DB::raw('COUNT(DISTINCT DATE(completed_at)) as active_days_last_7d'))
            ->where('user_id', $userId)
            ->where('completed_at', '>=', $weekStart)
            ->groupBy('user_id');

        $data = DB::table('profiles')
            ->join('users', 'users.id', '=', 'profiles.user_id')
            ->leftJoinSub($lastActiveQuery, 'last_log', function ($join) {
                $join->on('profiles.user_id', '=', 'last_log.user_id');
            })
            ->leftJoinSub($active7Query, 'active7', function ($join) {
                $join->on('profiles.user_id', '=', 'active7.user_id');
            })
            ->where('profiles.user_id', $userId)
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
            ->first();

        // Handle jika user baru (belum ada profile) -> Return dummy object
        if (!$data) {
            $user = DB::table('users')->find($userId);
            return (object) [
                'user_id' => $userId,
                'name' => $user->name ?? 'You',
                'effective_streak' => 0,
                'streak_best' => 0,
                'active_days_last_7d' => 0,
                'last_active_at' => null
            ];
        }

        return $data;
    }
}
