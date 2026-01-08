<?php

namespace App\Http\Controllers;

use App\Models\Quest;
use Illuminate\Http\Request;

class QuestController extends Controller
{
    //
    public function store(Request $request)
    {
        // Validate and store the quest
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:todo,in_progress,locked'],
            'type' => ['required', 'string', 'max:100'],
            'xp_reward' => ['required', 'integer', 'min:0'],
            'coin_reward' => ['required', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'is_repeatable' => ['required', 'boolean'],
        ]);

        $request->user()->quests()->create($data);

        return redirect()->back();
    }

    public function complete(Request $request, Quest $quest)
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->authorize('update', $quest);

        // Kalau non-repeatable dan sudah done: stop (biar gak double reward)
        if (! $quest->is_repeatable && $quest->status === 'done') {
            return redirect()->back();
        }

        // Untuk non-repeatable: set jadi done
        if (! $quest->is_repeatable) {
            $quest->update([
                'status' => 'done',
                'completed_at' => now(),
            ]);
        }

        // Log completion (repeatable: setiap submit bikin log baru)
        $request->user()->questCompletions()->create([
            'quest_id' => $quest->id,
            'xp_awarded' => $quest->xp_reward,
            'coin_awarded' => $quest->coin_reward,
            'completed_at' => now(),
            'note' => $data['note'] ?? null,
        ]);

        // Update profile + streak
        $profile = $request->user()->profile;

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $last = $profile->last_quest_completed_at;

        if ($last === $today) {
            // hari ini sudah ada completion -> streak tetap
        } elseif ($last === $yesterday) {
            $profile->current_streak += 1;
        } else {
            // bolong sehari atau belum pernah -> streak jadi 0 (sesuai request lo)
            $profile->current_streak = $last === null ? 1 : 0;
        }

        $profile->xp_total += $quest->xp_reward;
        $profile->coin_balance += $quest->coin_reward;
        $profile->last_quest_completed_at = $today;
        $profile->save();

        return redirect()->back();
    }
}
