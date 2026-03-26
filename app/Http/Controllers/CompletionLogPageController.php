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
        $date   = $request->query('date');           // YYYY-MM-DD
        $from   = $request->query('from');           // YYYY-MM-DD
        $to     = $request->query('to');             // YYYY-MM-DD
        $search = trim($request->query('search', '')); // quest name
        $type   = $request->query('type', '');         // quest type

        $query = $user->questCompletions()->with('quest:id,name,type');

        // ── Search by quest name ────────────────────────────────
        if ($search !== '') {
            $query->whereHas('quest', fn($q) => $q->where('name', 'like', '%' . $search . '%'));
        }

        // ── Filter by quest type ────────────────────────────────
        if ($type !== '') {
            $query->whereHas('quest', fn($q) => $q->where('type', $type));
        }

        // ── Date filters (priority: date > range > period) ──────
        if ($date) {
            $query->whereDate('completed_at', $date);
            $period = 'custom';
        } elseif ($from || $to) {
            $start = $from ? Carbon::parse($from)->startOfDay() : Carbon::minValue();
            $end   = $to   ? Carbon::parse($to)->endOfDay()     : now()->endOfDay();
            $query->whereBetween('completed_at', [$start, $end]);
            $period = 'custom';
        } else {
            if ($period === 'today') {
                $query->whereDate('completed_at', now()->toDateString());
            } elseif ($period === '7d') {
                $query->whereBetween('completed_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()]);
            } elseif ($period === 'month') {
                $query->whereBetween('completed_at', [now()->startOfMonth(), now()->endOfDay()]);
            }
        }

        // ── Sort (whitelist) ────────────────────────────────────
        $allowedSorts = ['completed_at', 'xp_awarded', 'coin_awarded', 'created_at'];
        $sort = $request->query('sort', 'completed_at');
        $dir  = $request->query('dir', 'desc');
        if (!in_array($sort, $allowedSorts, true)) $sort = 'completed_at';
        if (!in_array($dir, ['asc', 'desc'], true)) $dir = 'desc';
        $query->orderBy($sort, $dir);

        // ── Group summaries ─────────────────────────────────────
        $groupSummaries = (clone $query)
            ->reorder()
            ->selectRaw('DATE(completed_at) as d, COUNT(*) as c, COALESCE(SUM(xp_awarded),0) as xp, COALESCE(SUM(coin_awarded),0) as gold')
            ->groupByRaw('DATE(completed_at)')
            ->get()
            ->keyBy('d')
            ->map(fn($r) => ['count' => (int) $r->c, 'xp' => (int) $r->xp, 'gold' => (int) $r->gold])
            ->toArray();

        // ── Available quest types for dropdown ──────────────────
        $availableTypes = $user->questCompletions()
            ->join('quests', 'quest_completions.quest_id', '=', 'quests.id')
            ->distinct()
            ->pluck('quests.type')
            ->filter()
            ->sort()
            ->values();

        return Inertia::render('Logs/Completions', [
            'logs'            => $query->paginate(20)->onEachSide(1)->withQueryString(),
            'filters'         => [
                'period' => $period,
                'date'   => $date   ?? '',
                'from'   => $from   ?? '',
                'to'     => $to     ?? '',
                'sort'   => $sort,
                'dir'    => $dir,
                'search' => $search,
                'type'   => $type,
            ],
            'group_summaries'  => $groupSummaries,
            'customQuestTypes' => $user->questTypes()->select('id', 'name', 'color')->get(),
            'availableTypes'   => $availableTypes,
        ]);
    }
}
