<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Support\CacheBuster;
use App\Support\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class GoalController extends Controller
{
    private const TTL = 86400; // 1 day

    public function index(Request $request)
    {
        $user = $request->user();
        $goalsIndexKey = CacheKeys::goalsIndex($user->id);

        $payload = Cache::remember($goalsIndexKey, self::TTL, function () use ($user) {
            return [
                'activeGoals' => $user->goals()
                    ->where('status', 'active')
                    ->with('milestones')
                    ->get(),
                'completedGoals' => $user->goals()
                    ->where('status', 'completed')
                    ->with('milestones')
                    ->orderByDesc('completed_at')
                    ->get(),
            ];
        });

        return Inertia::render('Goals/Index', [
            'activeGoals'    => $payload['activeGoals'],
            'completedGoals' => $payload['completedGoals'],
        ]);
    }

    public function show(Request $request, Goal $goal)
    {
        if ($request->user()->id !== $goal->user_id) {
            abort(403);
        }

        $goalShowKey = CacheKeys::goalShow($request->user()->id, $goal->id);

        $cachedGoal = Cache::remember($goalShowKey, self::TTL, function () use ($goal) {
            $goal->load('milestones');
            return $goal;
        });

        return Inertia::render('Goals/Show', [
            'goal' => $cachedGoal,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'personal_reason' => 'required|string',
            'deadline' => 'required|date',
            'milestones' => 'nullable|array',
            'milestones.*.title' => 'required|string|max:255',
            'milestones.*.due_date' => 'required|date',
        ]);

        $goal = $request->user()->goals()->create([
            'title' => $data['title'],
            'description' => $data['description'],
            'personal_reason' => $data['personal_reason'],
            'deadline' => $data['deadline'],
            'status' => 'active',
        ]);

        if (!empty($data['milestones'])) {
            foreach ($data['milestones'] as $index => $m) {
                $goal->milestones()->create([
                    'title' => $m['title'],
                    'due_date' => $m['due_date'],
                    'position' => $index,
                ]);
            }
        }

        CacheBuster::onGoalMutate($request->user()->id);

        return redirect()->route('goals.index');
    }

    public function update(Request $request, Goal $goal)
    {
        if ($request->user()->id !== $goal->user_id) {
            abort(403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'personal_reason' => 'required|string',
            'deadline' => 'required|date',
            'milestones' => 'nullable|array',
            'milestones.*.id' => 'nullable',
            'milestones.*.title' => 'required|string|max:255',
            'milestones.*.due_date' => 'required|date',
        ]);

        $goal->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'personal_reason' => $data['personal_reason'],
            'deadline' => $data['deadline'],
        ]);

        if (array_key_exists('milestones', $data)) {
            $existingIds = $goal->milestones->pluck('id')->toArray();
            $keptIds = [];

            if (!empty($data['milestones'])) {
                foreach ($data['milestones'] as $index => $m) {
                    if (isset($m['id']) && in_array($m['id'], $existingIds)) {
                        $goal->milestones()->where('id', $m['id'])->update([
                            'title' => $m['title'],
                            'due_date' => $m['due_date'],
                            'position' => $index,
                        ]);
                        $keptIds[] = $m['id'];
                    } else {
                        $newM = $goal->milestones()->create([
                            'title' => $m['title'],
                            'due_date' => $m['due_date'],
                            'position' => $index,
                        ]);
                        $keptIds[] = $newM->id;
                    }
                }
            }

            $toDelete = array_diff($existingIds, $keptIds);
            if (!empty($toDelete)) {
                $goal->milestones()->whereIn('id', $toDelete)->delete();
            }
        }

        CacheBuster::onGoalMutate($request->user()->id, $goal->id);

        return redirect()->back();
    }

    public function destroy(Request $request, Goal $goal)
    {
        if ($request->user()->id !== $goal->user_id) {
            abort(403);
        }

        $userId = $request->user()->id;
        $goalId = $goal->id;

        $goal->delete();

        CacheBuster::onGoalMutate($userId, $goalId);

        return redirect()->route('dashboard');
    }

    public function toggleMilestone(Request $request, GoalMilestone $goalMilestone)
    {
        if ($request->user()->id !== $goalMilestone->goal->user_id) {
            abort(403);
        }

        $isCompleted = !$goalMilestone->is_completed;

        $goalMilestone->update([
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? now() : null,
        ]);

        // Progress changed → bust show + list caches
        CacheBuster::onGoalMutate($request->user()->id, $goalMilestone->goal_id);

        return redirect()->back();
    }

    public function complete(Request $request, Goal $goal)
    {
        if ($request->user()->id !== $goal->user_id) {
            abort(403);
        }

        $incompleteMilestones = $goal->milestones()->where('is_completed', false)->count();

        if ($incompleteMilestones > 0) {
            return redirect()->back()->withErrors(['goal' => 'All milestones must be completed first']);
        }

        $goal->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Goal moves from active → completed; bust everything
        CacheBuster::onGoalMutate($request->user()->id, $goal->id);

        return redirect()->route('goals.index');
    }
}
