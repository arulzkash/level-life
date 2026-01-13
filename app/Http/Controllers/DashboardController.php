<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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



        return Inertia::render('Dashboard', [
            'profile' => $profile,
            'today' => $today,
            'habits' => $habitsPayload,
            'habitSummary' => [
                'done_today' => $doneCount,
                'total' => $totalCount,
            ],
            'activeQuests' => $user->quests()
                ->whereIn('status', ['todo', 'in_progress'])
                ->orderByRaw('due_date is null, due_date asc')
                ->latest()
                ->get(),
            'todayBlocks' => $todayBlocks,
        ]);
    }

    
}
