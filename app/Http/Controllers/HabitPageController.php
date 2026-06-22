<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class HabitPageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $view = $request->query('view', 'active'); // active|archived|all
        $today = now()->toDateString();

        $habitsQuery = $user->habits()->orderBy('name');

        if ($view === 'active') {
            $habitsQuery->whereNull('end_date');
        } elseif ($view === 'archived') {
            $habitsQuery->whereNotNull('end_date');
        }

        $habits = $habitsQuery->get();
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
                ->flip();

            $isDoneToday = isset($dates[$today]);

            // streak terakhir: kalau belum done today, hitung dari yesterday
            $streak = 0;
            $cursor = $isDoneToday ? now() : now()->subDay();

            if ($cursor->toDateString() >= $h->start_date) {
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

        return Inertia::render('Habits/Index', [
            'habits' => $habitsPayload,
            'filters' => [
                'view' => $view,
            ],
        ]);
    }

    public function show(Request $request, Habit $habit)
    {
        abort_unless($request->user()->can('view', $habit), 403);

        $month = $request->query('month'); // YYYY-MM
        $base = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : now()->startOfMonth();

        $startOfMonth = $base->copy()->startOfMonth();
        $endOfMonth = $base->copy()->endOfMonth();

        $today = now()->toDateString();

        // maxDate = min(today, end_date if exists and earlier)
        $maxDate = $today;
        if ($habit->end_date && $habit->end_date < $maxDate) $maxDate = $habit->end_date;

        $entries = $habit->entries()
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->pluck('date')
            ->map(fn($d) => (string) $d)
            ->flip(); // set

        // Build calendar weeks
        $weeks = [];
        $week = [];

        $firstDow = $startOfMonth->dayOfWeekIso; // 1..7 (Mon..Sun)
        for ($i = 1; $i < $firstDow; $i++) {
            $week[] = null;
        }

        $daysInMonth = $startOfMonth->daysInMonth;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $startOfMonth->copy()->day($day)->toDateString();

            $allowed = !($date < $habit->start_date || $date > $maxDate);

            $week[] = [
                'date' => $date,
                'day' => $day,
                'done' => isset($entries[$date]),
                'allowed' => $allowed,
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        if (count($week) > 0) {
            while (count($week) < 7) $week[] = null;
            $weeks[] = $week;
        }

        return Inertia::render('Habits/Show', [
            'habit' => [
                'id' => $habit->id,
                'name' => $habit->name,
                'start_date' => $habit->start_date,
                'end_date' => $habit->end_date,
            ],
            'month' => $startOfMonth->format('Y-m'),
            'weeks' => $weeks,
        ]);
    }
}
