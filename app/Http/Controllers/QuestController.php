<?php

namespace App\Http\Controllers;

use App\Models\Quest;
use App\Services\BadgeService;
use App\Support\CacheBuster;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class QuestController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:todo,in_progress,locked'],
            'type' => ['required', 'string', 'max:100'],
            'xp_reward' => ['required', 'integer', 'min:0'],
            'coin_reward' => ['required', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'is_repeatable' => ['required', 'boolean'],
        ]);

        $data['is_repeatable'] = $request->boolean('is_repeatable');

        if ($data['is_repeatable']) {
            $data['due_date'] = null;
        }

        $maxPosition = Quest::where('user_id', $request->user()->id)->max('position');
        $data['position'] = $maxPosition + 1;

        $request->user()->quests()->create($data);

        CacheBuster::onQuestMutate($request->user()->id);

        return redirect()->back();
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ordered_ids' => 'required|array',
            'ordered_ids.*' => 'exists:quests,id',
        ]);

        $user = $request->user();
        $ids = array_values($request->ordered_ids);

        if (count($ids) === 0) {
            return redirect()->back();
        }

        DB::transaction(function () use ($user, $ids) {
            $caseSql = 'CASE id ';
            $bindings = [];

            foreach ($ids as $index => $id) {
                $caseSql .= 'WHEN ? THEN ? ';
                $bindings[] = (int) $id;
                $bindings[] = $index + 1;
            }

            $caseSql .= 'END';

            $inPlaceholders = implode(',', array_fill(0, count($ids), '?'));
            $bindings[] = (int) $user->id;
            foreach ($ids as $id) {
                $bindings[] = (int) $id;
            }

            $sql = "
            UPDATE quests
            SET position = {$caseSql}
            WHERE user_id = ?
              AND id IN ({$inPlaceholders})
        ";

            DB::update($sql, $bindings);
        });

        CacheBuster::onQuestMutate($request->user()->id);
        return redirect()->back();
    }

    public function complete(Request $request, Quest $quest)
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->authorize('update', $quest);

        if ($quest->status === 'locked') {
            return redirect()->back()->withErrors(['complete' => 'Quest is locked.']);
        }

        if (! $quest->is_repeatable && $quest->status === 'done') {
            return redirect()->back();
        }

        $user = $request->user();
        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        $todayStr = $today->toDateString();

        $profile = null;

        DB::transaction(function () use ($user, $quest, $data, $today, $todayStr, &$profile) {
            // ============================================
            // OPTIMASI 1: Fetch MINIMAL columns dulu
            // ============================================
            $profile = $user->profile()
                ->lockForUpdate()
                ->first([
                    'id',
                    'user_id',
                    'xp_total',
                    'coin_balance',
                    'streak_current',
                    'streak_best',
                    'last_active_date',
                    'current_streak',
                    'last_quest_completed_at',
                ]);

            if (! $profile) {
                $profile = $user->profile()->create([
                    'xp_total' => 0,
                    'coin_balance' => 0,
                    'streak_current' => 0,
                    'streak_best' => 0,
                ]);
            }

            // Mark quest done
            if (! $quest->is_repeatable && $quest->status !== 'done') {
                $quest->update([
                    'status' => 'done',
                    'completed_at' => now(),
                ]);
            }

            // Log completion
            $user->questCompletions()->create([
                'quest_id' => $quest->id,
                'xp_awarded' => $quest->xp_reward,
                'coin_awarded' => $quest->coin_reward,
                'completed_at' => now(),
                'note' => $data['note'] ?? null,
            ]);

            // ============================================
            // OPTIMASI 2: Cek apakah perlu freeze logic
            // ============================================
            $needsFreezeCheck = false;

            if ($profile->last_active_date) {
                $lastActive = Carbon::parse($profile->last_active_date, 'Asia/Jakarta')->startOfDay();
                $diffInDays = $lastActive->diffInDays($today);

                if ($diffInDays == 0) {
                    // Same day: NO FREEZE CHECK NEEDED
                    // Just update last_active_date (no change in streak)
                } elseif ($diffInDays == 1) {
                    // Consecutive day: NO FREEZE CHECK NEEDED
                    $profile->streak_current++;
                } else {
                    // Gap detected: NEED FREEZE CHECK
                    $needsFreezeCheck = true;
                }
            } else {
                // First time: NO FREEZE CHECK NEEDED
                $profile->streak_current = 1;
            }

            // ============================================
            // OPTIMASI 3: Lazy load freeze columns ONLY if needed
            // ============================================
            if ($needsFreezeCheck) {
                // Fetch freeze-related columns ONLY when needed
                $freezeData = DB::table('profiles')
                    ->where('id', $profile->id)
                    ->first([
                        'freezes_used_week_start',
                        'freezes_used_count',
                        'freezes_used_total',
                        'streak_resets_total',
                        'streak_maintained_through',
                    ]);

                // Merge freeze data into profile
                $profile->freezes_used_week_start = $freezeData->freezes_used_week_start;
                $profile->freezes_used_count = $freezeData->freezes_used_count;
                $profile->freezes_used_total = $freezeData->freezes_used_total;
                $profile->streak_resets_total = $freezeData->streak_resets_total;
                $profile->streak_maintained_through = $freezeData->streak_maintained_through;

                // Run freeze logic
                $lastActive = Carbon::parse($profile->last_active_date, 'Asia/Jakarta')->startOfDay();

                $weekStartOf = function (Carbon $d) {
                    return $d->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
                };

                $missStart = $lastActive->copy()->addDay()->startOfDay();
                $missEnd = $today->copy()->subDay()->startOfDay();

                if (! $profile->freezes_used_week_start) {
                    $profile->freezes_used_week_start = $weekStartOf($lastActive);
                    $profile->freezes_used_count = (int) ($profile->freezes_used_count ?? 0);
                }

                $freezeFailed = false;

                if ($missStart->lte($missEnd)) {
                    $cursor = $missStart->copy();

                    while ($cursor->lte($missEnd)) {
                        $ws = $weekStartOf($cursor);
                        $weekStartDate = Carbon::parse($ws, 'Asia/Jakarta')->startOfDay();
                        $weekEndDate = $weekStartDate->copy()->addDays(6)->startOfDay();

                        $segEnd = $weekEndDate->lt($missEnd) ? $weekEndDate : $missEnd;
                        $daysInThisWeek = $cursor->diffInDays($segEnd) + 1;

                        if ($profile->freezes_used_week_start !== $ws) {
                            $profile->freezes_used_week_start = $ws;
                            $profile->freezes_used_count = 0;
                        }

                        $used = (int) ($profile->freezes_used_count ?? 0);
                        $left = max(0, 2 - $used);

                        if ($daysInThisWeek > $left) {
                            $freezeFailed = true;
                            break;
                        }

                        $profile->freezes_used_count = $used + $daysInThisWeek;
                        $profile->freezes_used_total = (int) ($profile->freezes_used_total ?? 0) + $daysInThisWeek;

                        $cursor = $segEnd->copy()->addDay();
                    }
                }

                if ($freezeFailed) {
                    $profile->streak_current = 1;
                    $profile->streak_resets_total = (int) ($profile->streak_resets_total ?? 0) + 1;
                } else {
                    $profile->streak_current++;
                    $profile->streak_maintained_through = $todayStr;
                }

                // Update freeze window for current week
                $currentWeekStart = $today->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
                if ($profile->freezes_used_week_start !== $currentWeekStart) {
                    $profile->freezes_used_week_start = $currentWeekStart;
                    $profile->freezes_used_count = 0;
                }
            } else {
                // No freeze check needed, ensure current week is set
                $currentWeekStart = $today->copy()->startOfWeek(Carbon::MONDAY)->toDateString();

                // Only update if we have freeze data already loaded
                if (
                    isset($profile->freezes_used_week_start) &&
                    $profile->freezes_used_week_start !== $currentWeekStart
                ) {
                    $profile->freezes_used_week_start = $currentWeekStart;
                    $profile->freezes_used_count = 0;
                }
            }

            // ============================================
            // SAVE PROFILE (Conditional columns)
            // ============================================
            $profile->last_active_date = $todayStr;

            if ($profile->streak_current > (int)($profile->streak_best ?? 0)) {
                $profile->streak_best = $profile->streak_current;
            }

            $profile->current_streak = $profile->streak_current;
            $profile->last_quest_completed_at = $todayStr;

            // Economy
            $profile->xp_total = (int)$profile->xp_total + (int)$quest->xp_reward;
            $profile->coin_balance = (int)$profile->coin_balance + (int)$quest->coin_reward;

            // OPTIMASI 4: Save only modified columns
            if ($needsFreezeCheck) {
                // Full save including freeze columns
                $profile->save();
            } else {
                // Partial save (exclude freeze columns if not loaded)
                $profile->save();
            }
        });

        $user->setRelation('profile', $profile);

        app(BadgeService::class)->syncForUser($user);

        CacheBuster::onQuestComplete($user->id);

        return redirect()->back();
    }

    public function update(Request $request, Quest $quest)
    {
        $this->authorize('update', $quest);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:todo,in_progress,locked'],
            'type' => ['required', 'string', 'max:100'],
            'xp_reward' => ['required', 'integer', 'min:0'],
            'coin_reward' => ['required', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'is_repeatable' => ['required', 'boolean'],
        ]);

        $data['is_repeatable'] = $request->boolean('is_repeatable');
        $data['due_date'] = $request->input('due_date') ?: null;

        $quest->update($data);

        CacheBuster::onQuestMutate($request->user()->id);

        return redirect()->back();
    }

    public function destroy(Request $request, Quest $quest)
    {
        $this->authorize('delete', $quest);

        if ($quest->completions()->exists()) {
            return redirect()->back()->withErrors([
                'delete' => 'Quest sudah punya completion log, jadi tidak bisa dihapus.',
            ]);
        }

        $quest->delete();

        CacheBuster::onQuestMutate($request->user()->id);

        return redirect()->back();
    }
}
