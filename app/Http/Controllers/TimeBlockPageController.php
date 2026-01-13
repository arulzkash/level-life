<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class TimeBlockPageController extends Controller
{
    //
    public function index(Request $request)
    {
        $user = $request->user();

        // week_start = YYYY-MM-DD (optional), default minggu ini (Asia/Jakarta via app timezone)
        $weekStartParam = $request->query('week_start');

        $weekStart = $weekStartParam
            ? Carbon::createFromFormat('Y-m-d', $weekStartParam)->startOfDay()
            : now()->startOfDay();

        // pakai Senin sebagai start week
        $weekStart = $weekStart->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(6);

        $blocks = $user->timeBlocks()
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $grouped = $blocks->groupBy('date');

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $weekStart->copy()->addDays($i)->toDateString();
            $days[] = [
                'date' => $d,
                'items' => ($grouped[$d] ?? collect())->values()->map(fn($b) => [
                    'id' => $b->id,
                    'date' => $b->date,
                    'start_time' => substr($b->start_time, 0, 5), // HH:MM
                    'end_time' => substr($b->end_time, 0, 5),
                    'title' => $b->title,
                    'note' => $b->note,
                ]),
            ];
        }

        return Inertia::render('TimeBlocks/Index', [
            'week' => [
                'start' => $weekStart->toDateString(),
                'end' => $weekEnd->toDateString(),
            ],
            'days' => $days,
        ]);
    }
}
