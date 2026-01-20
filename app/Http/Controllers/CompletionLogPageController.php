<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class CompletionLogPageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $period = $request->query('period', 'all'); // all|today|7d|month|custom
        $date = $request->query('date');            // YYYY-MM-DD
        $from = $request->query('from');            // YYYY-MM-DD
        $to = $request->query('to');                // YYYY-MM-DD

        $query = $user->questCompletions()->with('quest:id,name,type');

        // Priority: date > range > period
        if ($date) {
            $query->whereDate('completed_at', $date);
            $period = 'custom';
        } elseif ($from || $to) {
            $start = $from ? Carbon::parse($from)->startOfDay() : Carbon::minValue();
            $end   = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

            $query->whereBetween('completed_at', [$start, $end]);
            $period = 'custom';
        } else {
            if ($period === 'today') {
                $query->whereDate('completed_at', now()->toDateString());
            } elseif ($period === '7d') {
                $start = now()->subDays(6)->startOfDay();
                $end = now()->endOfDay();
                $query->whereBetween('completed_at', [$start, $end]);
            } elseif ($period === 'month') {
                $start = now()->startOfMonth();
                $end = now()->endOfDay();
                $query->whereBetween('completed_at', [$start, $end]);
            }
        }

        // SORT (whitelist)
        $allowedSorts = ['completed_at', 'xp_awarded', 'coin_awarded', 'created_at'];
        $sort = $request->query('sort', 'completed_at');
        $dir = $request->query('dir', 'desc');

        if (!in_array($sort, $allowedSorts, true)) $sort = 'completed_at';
        if (!in_array($dir, ['asc', 'desc'], true)) $dir = 'desc';

        $query->orderBy($sort, $dir);

        $groupSummaries = (clone $query)
            ->reorder()
            ->selectRaw('DATE(completed_at) as d, COUNT(*) as c, COALESCE(SUM(xp_awarded),0) as xp, COALESCE(SUM(coin_awarded),0) as gold')
            ->groupByRaw('DATE(completed_at)')
            ->get()
            ->keyBy('d')
            ->map(fn($r) => [
                'count' => (int) $r->c,
                'xp' => (int) $r->xp,
                'gold' => (int) $r->gold,
            ])
            ->toArray();


        return Inertia::render('Logs/Completions', [
            'logs' => $query->paginate(20)->withQueryString(),
            'filters' => [
                'period' => $period,
                'date' => $date ?? '',
                'from' => $from ?? '',
                'to' => $to ?? '',
                'sort' => $sort,
                'dir' => $dir,
            ],
            'group_summaries' => $groupSummaries,
        ]);
    }
}
