<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $today = now()->toDateString();

        $habits = $user->habits()
            ->where('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
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


        return Inertia::render('Dashboard', [
            'profile' => $user->profile,
            'habits' => $habitsPayload,
            'activeQuests' => $user->quests()
                ->whereIn('status', ['todo', 'in_progress'])
                ->latest()
                ->get(),
        ]);
    }
}
