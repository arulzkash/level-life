<?php

namespace App\Http\Controllers;

use App\Models\Quest;
use App\Services\BadgeService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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

        $data['is_repeatable'] = $request->boolean('is_repeatable');

        // RULE: repeatable = unlimited submit => due_date harus null
        if ($data['is_repeatable']) {
            $data['due_date'] = null;
        }

        $maxPosition = Quest::where('user_id', $request->user()->id)->max('position');
        $data['position'] = $maxPosition + 1;

        $request->user()->quests()->create($data);

        return redirect()->back();
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ordered_ids' => 'required|array',
            'ordered_ids.*' => 'exists:quests,id',
        ]);

        DB::transaction(function () use ($request) {
            $user = $request->user();

            foreach ($request->ordered_ids as $index => $id) {
                DB::table('quests')
                    ->where('id', $id)
                    ->where('user_id', $user->id)
                    ->update(['position' => $index + 1]);
            }
        });

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

        // Prevent double counting for non-repeatables
        if (! $quest->is_repeatable && $quest->status === 'done') {
            return redirect()->back();
        }

        // Mark as done locally
        if (! $quest->is_repeatable) {
            $quest->update(['status' => 'done', 'completed_at' => now()]);
        }

        // Log completion
        $request->user()->questCompletions()->create([
            'quest_id' => $quest->id,
            'xp_awarded' => $quest->xp_reward,
            'coin_awarded' => $quest->coin_reward,
            'completed_at' => now(),
            'note' => $data['note'] ?? null,
        ]);

        // --- CORE LOGIC: STREAK & FREEZE SYSTEM (LAZY UPDATE) ---
        $profile = $request->user()->profile;

        // PENTING: Gunakan startOfDay untuk komparasi tanggal murni
        $today = now()->startOfDay();



        // 2. Streak Calculation
        if ($profile->last_active_date) {
            $lastActive = Carbon::parse($profile->last_active_date)->startOfDay();
            $diffInDays = $lastActive->diffInDays($today); // Selisih hari absolut

            if ($diffInDays == 0) {
                // Kasus A: Masih hari yang sama.
                // Tidak ada perubahan streak.
            } elseif ($diffInDays == 1) {
                // Kasus B: Login besoknya (Perfect Streak).
                $profile->streak_current++;
            } else {
                // Kasus C: Ada Gap (Bolong)
                $weekStartOf = function (Carbon $d) {
                    return $d->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
                };

                // rentang hari yang miss: (lastActive+1) .. (today-1)
                $missStart = $lastActive->copy()->addDay()->startOfDay();
                $missEnd = $today->copy()->subDay()->startOfDay();

                // Pastikan window freeze start ada (kalau null, anggap minggu lastActive)
                if (!$profile->freezes_used_week_start) {
                    $profile->freezes_used_week_start = $weekStartOf($lastActive);
                    $profile->freezes_used_count = (int) ($profile->freezes_used_count ?? 0);
                }

                $freezeFailed = false;

                if ($missStart->lte($missEnd)) {
                    $cursor = $missStart->copy();

                    while ($cursor->lte($missEnd)) {
                        $ws = $weekStartOf($cursor);
                        $weekStartDate = Carbon::parse($ws)->startOfDay();
                        $weekEndDate = $weekStartDate->copy()->addDays(6)->startOfDay();

                        // segment minggu ini: cursor .. min(missEnd, weekEnd)
                        $segEnd = $weekEndDate->lt($missEnd) ? $weekEndDate : $missEnd;
                        $daysInThisWeek = $cursor->diffInDays($segEnd) + 1; // inclusive

                        // kalau kita sedang “mengonsumsi” minggu berbeda, reset count untuk minggu tsb
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

                        // consume sekaligus untuk minggu ini
                        $profile->freezes_used_count = $used + $daysInThisWeek;
                        $profile->freezes_used_total = (int) ($profile->freezes_used_total ?? 0) + $daysInThisWeek;

                        // maju ke minggu berikutnya
                        $cursor = $segEnd->copy()->addDay();
                    }
                }

                if ($freezeFailed) {
                    // fail => streak putus, tapi karena hari ini aktif, restart ke 1
                    $profile->streak_current = 1;
                    $profile->streak_resets_total = (int) ($profile->streak_resets_total ?? 0) + 1;
                } else {
                    // succeed => streak lanjut, hari ini aktif => +1
                    $profile->streak_current++;
                    $profile->streak_maintained_through = $today->toDateString();
                }
            }
        } else {
            // Kasus D: User baru pertama kali
            $profile->streak_current = 1;
        }

        // Setelah streak dihitung, pastikan window freeze sekarang adalah minggu hari ini
        $currentWeekStart = $today->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        if ($profile->freezes_used_week_start !== $currentWeekStart) {
            $profile->freezes_used_week_start = $currentWeekStart;
            $profile->freezes_used_count = 0;
        }

        // 3. Save State
        $profile->last_active_date = $today->toDateString();

        // Update Best Streak Record
        if ($profile->streak_current > $profile->streak_best) {
            $profile->streak_best = $profile->streak_current;
        }

        // Legacy Sync (Opsional, buat jaga kompatibilitas UI lama)
        $profile->current_streak = $profile->streak_current;
        $profile->last_quest_completed_at = $today->toDateString();

        // Update Economy
        $profile->xp_total += $quest->xp_reward;
        $profile->coin_balance += $quest->coin_reward;

        $profile->save();
        // invalidate navbar profile cache
        Cache::forget("nav_profile:{$request->user()->id}");

        // 4. Badge Sync
        // (Pastikan BadgeService sudah ada logic cek streak_best)
        app(BadgeService::class)->syncForUser($request->user());

        return redirect()->back();
    }

    public function update(Request $request, Quest $quest)
    {
        $this->authorize('update', $quest);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:todo,in_progress,locked'], // done gak boleh dari edit
            'type' => ['required', 'string', 'max:100'],
            'xp_reward' => ['required', 'integer', 'min:0'],
            'coin_reward' => ['required', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'is_repeatable' => ['required', 'boolean'],
        ]);

        $data['is_repeatable'] = $request->boolean('is_repeatable');
        $data['due_date'] = $request->input('due_date') ?: null;

        $quest->update($data);

        return redirect()->back();
    }

    public function destroy(Request $request, Quest $quest)
    {
        $this->authorize('delete', $quest);

        // block delete kalau ada completion logs
        if ($quest->completions()->exists()) {
            return redirect()->back()->withErrors([
                'delete' => 'Quest sudah punya completion log, jadi tidak bisa dihapus.',
            ]);
        }

        $quest->delete();

        return redirect()->back();
    }
}
