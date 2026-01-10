<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class TreasuryLogPageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $period = $request->query('period', 'all'); // all|today|7d|month|custom
        $date = $request->query('date');            // YYYY-MM-DD
        $from = $request->query('from');            // YYYY-MM-DD
        $to = $request->query('to');                // YYYY-MM-DD
        $rewardId = $request->query('reward_id');   // optional

        $query = $user->treasuryPurchases()
            ->with(['reward' => function ($q) {
                $q->withTrashed()->select('id', 'name', 'cost_coin', 'deleted_at');
            }]);

        if ($rewardId) {
            $query->where('treasury_reward_id', $rewardId);
        }

        if ($date) {
            $query->whereDate('purchased_at', $date);
            $period = 'custom';
        } elseif ($from || $to) {
            $start = $from ? Carbon::parse($from)->startOfDay() : Carbon::minValue();
            $end   = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();
            $query->whereBetween('purchased_at', [$start, $end]);
            $period = 'custom';
        } else {
            if ($period === 'today') {
                $query->whereDate('purchased_at', now()->toDateString());
            } elseif ($period === '7d') {
                $start = now()->subDays(6)->startOfDay();
                $end = now()->endOfDay();
                $query->whereBetween('purchased_at', [$start, $end]);
            } elseif ($period === 'month') {
                $start = now()->startOfMonth();
                $end = now()->endOfDay();
                $query->whereBetween('purchased_at', [$start, $end]);
            }
        }

        $allowedSorts = ['purchased_at', 'cost_coin', 'qty', 'created_at'];
        $sort = $request->query('sort', 'purchased_at');
        $dir = $request->query('dir', 'desc');

        if (!in_array($sort, $allowedSorts, true)) $sort = 'purchased_at';
        if (!in_array($dir, ['asc', 'desc'], true)) $dir = 'desc';

        $query->orderBy($sort, $dir);

        return Inertia::render('Logs/Treasury', [
            'logs' => $query->paginate(20)->withQueryString(),
            'filters' => [
                'period' => $period,
                'date' => $date ?? '',
                'from' => $from ?? '',
                'to' => $to ?? '',
                'reward_id' => $rewardId ?? '',
                'sort' => $sort,
                'dir' => $dir,
            ],
            'rewardOptions' => $user->treasuryRewards()
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
