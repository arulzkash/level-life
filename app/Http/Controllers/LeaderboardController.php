<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    public function page(Request $request)
    {
        // Render page inertia, data ambil dari backend langsung (no UI fancy yet)
        $data = $this->buildLeaderboardData($request);

        return Inertia::render('Leaderboard/Index', [
            'items' => $data['items'],
            'me' => $data['me'],
        ]);
    }

    public function index(Request $request)
    {
        $data = $this->buildLeaderboardData($request);
        return response()->json($data);
    }

    private function buildLeaderboardData(Request $request): array
    {
        $userId = $request->user()->id;

        $limit = (int) $request->query('limit', 50);
        $limit = max(1, min($limit, 100));

        $orderSql = implode(', ', [
            'COALESCE(profiles.streak_current, 0) DESC',
            'COALESCE(profiles.streak_best, 0) DESC',
            'profiles.last_active_date DESC',
            'users.id ASC',
        ]);

        $sub = DB::table('profiles')
            ->join('users', 'users.id', '=', 'profiles.user_id')
            ->select([
                'profiles.user_id',
                'users.name',
                'profiles.streak_current',
                'profiles.streak_best',
                'profiles.last_active_date',
            ])
            ->selectRaw("ROW_NUMBER() OVER (ORDER BY {$orderSql}) as rank");

        $ranked = DB::query()->fromSub($sub, 'ranked');

        $rows = $ranked->orderBy('rank')->limit($limit)->get();
        $meRow = $ranked->where('user_id', $userId)->first();

        $items = $rows->map(fn ($r) => [
            'rank' => (int) $r->rank,
            'user' => [
                'id' => (int) $r->user_id,
                'name' => $r->name,
            ],
            'streak_current' => (int) ($r->streak_current ?? 0),
            'streak_best' => (int) ($r->streak_best ?? 0),
            'last_active_date' => $r->last_active_date,
        ])->values();

        $me = $meRow ? [
            'rank' => (int) $meRow->rank,
            'user' => [
                'id' => (int) $meRow->user_id,
                'name' => $meRow->name,
            ],
            'streak_current' => (int) ($meRow->streak_current ?? 0),
            'streak_best' => (int) ($meRow->streak_best ?? 0),
            'last_active_date' => $meRow->last_active_date,
        ] : null;

        return [
            'items' => $items,
            'me' => $me,
        ];
    }
}
