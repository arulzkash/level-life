<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestPageController extends Controller
{
    //
    public function index(Request $request)
    {
        $user = $request->user();

        $query = $user->quests();

        // FILTERS
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if (!is_null($request->query('repeatable'))) {
            $query->where('is_repeatable', $request->boolean('repeatable'));
        }

        // SORT (whitelist)
        $allowedSorts = ['name', 'due_date', 'xp_reward', 'coin_reward', 'completed_at', 'created_at'];
        $sort = $request->query('sort', 'created_at');
        $dir = $request->query('dir', 'desc');

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }
        if (!in_array($dir, ['asc', 'desc'], true)) {
            $dir = 'desc';
        }

        $query->orderBy($sort, $dir);

        return Inertia::render('Quests/Index', [
            'quests' => $query->paginate(20)->withQueryString(),
            'filters' => [
                'status' => $request->query('status', ''),
                'type' => $request->query('type', ''),
                'repeatable' => $request->query('repeatable', ''), // '' | '1' | '0'
                'sort' => $sort,
                'dir' => $dir,
            ],
            'typeOptions' => $user->quests()
                ->select('type')
                ->distinct()
                ->orderBy('type')
                ->pluck('type'),
        ]);
    }
}
