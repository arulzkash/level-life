<?php

namespace App\Http\Controllers;

use App\Models\Quest;
use App\Services\BadgeService;
use App\Support\CacheBuster;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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

            // Validasi Baru untuk Subtasks
            'subtasks' => ['nullable', 'array'],
            'subtasks.*.title' => ['required', 'string', 'max:100'],
            'subtasks.*.is_done' => ['boolean'],
        ]);

        $data['is_repeatable'] = $request->boolean('is_repeatable');

        // Logic Subtask: Pastikan punya ID unik (penting buat FE nanti)
        if (!empty($data['subtasks'])) {
            $data['subtasks'] = array_map(function ($task) {
                return [
                    'id' => $task['id'] ?? Str::uuid()->toString(),
                    'title' => $task['title'],
                    'is_done' => $task['is_done'] ?? false,
                ];
            }, $data['subtasks']);
        }

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
        // 1. Validasi Input
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->authorize('update', $quest);

        // --- GATEKEEPER BARU: SUBTASK CHECK ---
        // Kita pakai Accessor yg dibuat di Langkah 2
        if (!$quest->can_be_completed) {
            return redirect()->back()->withErrors([
                'complete' => 'Complete all subtasks first to claim rewards!',
            ]);
        }

        if ($quest->status === 'locked') {
            return redirect()->back()->withErrors(['complete' => 'Quest is locked.']);
        }

        // Prevent double reward kalau quest tidak repeatable
        if (! $quest->is_repeatable && $quest->status === 'done') {
            return redirect()->back();
        }

        $user = $request->user();
        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        $todayStr = $today->toDateString();

        // 2. Mulai Transaksi (Tanpa Locking Row untuk Hemat RU)
        DB::transaction(function () use ($user, $quest, $data, $today, $todayStr) {

            // =========================================================
            // STEP A: Fetch Profile "Ringan" (Hanya kolom krusial)
            // =========================================================
            $profile = $user->profile()
                ->select([
                    'id',
                    'user_id',
                    'xp_total',
                    'coin_balance',
                    'streak_current',
                    'streak_best',
                    'last_active_date',
                    // ambil ringan supaya bisa compare tanpa blind write
                    'freezes_used_week_start',
                    'freezes_used_count',
                ])
                ->first();

            // Handle jika user baru (belum punya profile)
            if (! $profile) {
                $profile = $user->profile()->create([
                    'xp_total' => 0,
                    'coin_balance' => 0,
                    'streak_current' => 0,
                    'streak_best' => 0,
                ]);
            }

            // =========================================================
            // STEP B: Update Quest & Log Completion
            // =========================================================
            if (! $quest->is_repeatable && $quest->status !== 'done') {
                $quest->update([
                    'status' => 'done',
                    'completed_at' => now(),
                ]);
            }

            $user->questCompletions()->create([
                'quest_id' => $quest->id,
                'xp_awarded' => $quest->xp_reward,
                'coin_awarded' => $quest->coin_reward,
                'completed_at' => now(),
                'note' => $data['note'] ?? null,
            ]);

            if ($quest->is_repeatable && !empty($quest->subtasks)) {
                // Loop semua subtask, paksa is_done jadi false
                $resetSubtasks = array_map(function ($task) {
                    $task['is_done'] = false;
                    return $task;
                }, $quest->subtasks);

                // Update kolom subtasks di DB
                $quest->subtasks = $resetSubtasks;

                // Jangan lupa save perubahan ini
                $quest->save();
            }

            // =========================================================
            // STEP C: Logic Streak & Freeze (Optimized Lazy Load + minimal writes)
            // =========================================================
            $needsFreezeCheck = false;
            $diffInDays = null;

            if ($profile->last_active_date) {
                $lastActive = Carbon::parse($profile->last_active_date, 'Asia/Jakarta')->startOfDay();
                $diffInDays = $lastActive->diffInDays($today);

                if ($diffInDays == 0) {
                    // Same day: streak & freeze window tidak perlu disentuh sama sekali
                } elseif ($diffInDays == 1) {
                    $profile->streak_current++;
                } else {
                    $needsFreezeCheck = true;
                }
            } else {
                // User pertama kali
                $profile->streak_current = 1;
            }

            // Week start string (Senin 00:00 Asia/Jakarta)
            $currentWeekStart = $today->copy()->startOfWeek(Carbon::MONDAY)->toDateString();

            // --- LAZY LOAD: hanya saat ada gap (bolong) ---
            if ($needsFreezeCheck) {
                // Fetch kolom freeze "berat" hanya saat dibutuhkan
                $freezeData = DB::table('profiles')
                    ->where('id', $profile->id)
                    ->first([
                        'freezes_used_total',
                        'streak_resets_total',
                        'streak_maintained_through',
                    ]);

                // hydrate fields berat
                $profile->freezes_used_total = $freezeData->freezes_used_total;
                $profile->streak_resets_total = $freezeData->streak_resets_total;
                $profile->streak_maintained_through = $freezeData->streak_maintained_through;

                $lastActive = Carbon::parse($profile->last_active_date, 'Asia/Jakarta')->startOfDay();

                $weekStartOf = function ($d) {
                    return $d->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
                };

                $missStart = $lastActive->copy()->addDay()->startOfDay();
                $missEnd = $today->copy()->subDay()->startOfDay();

                // Init week start jika null
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

                        // Reset count kalau ganti minggu
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

                // ✅ Pastikan window mingguan align dengan minggu sekarang (hanya kalau beda)
                if ($profile->freezes_used_week_start !== $currentWeekStart) {
                    $profile->freezes_used_week_start = $currentWeekStart;
                    $profile->freezes_used_count = 0;
                }
            } else {
                // ✅ Non-gap path: only maintain weekly window when day advanced OR first time
                // Same-day completion: jangan sentuh freezes_used_* sama sekali (hemat RU besar)
                if ($diffInDays === null || $diffInDays >= 1) {
                    if ($profile->freezes_used_week_start !== $currentWeekStart) {
                        $profile->freezes_used_week_start = $currentWeekStart;
                        $profile->freezes_used_count = 0;
                    }
                }
            }

            // =========================================================
            // STEP D: Final Update & Save (Atomic Batch)
            // =========================================================
            $profile->last_active_date = $todayStr;

            if ($profile->streak_current > (int)($profile->streak_best ?? 0)) {
                $profile->streak_best = $profile->streak_current;
            }

            // Update Economy (In Memory)
            $profile->xp_total = (int)$profile->xp_total + (int)$quest->xp_reward;
            $profile->coin_balance = (int)$profile->coin_balance + (int)$quest->coin_reward;

            // Save perubahan sekaligus (1x Query Update)
            $profile->save();

            // Update relasi object user
            $user->setRelation('profile', $profile);
        });

        // =========================================================
        // STEP E: Post-Transaction (Side Effects)
        // =========================================================
        // Sync Badge
        app(BadgeService::class)->syncForUser($user);

        // Invalidate Cache
        CacheBuster::onQuestComplete($user->id);

        return redirect()->back();
    }

    public function update(Request $request, Quest $quest)
    {
        $this->authorize('update', $quest);

        $onlySubtasks = collect($request->keys())->diff(['subtasks'])->isEmpty();

        if ($onlySubtasks) {
            $data = $request->validate([
                'subtasks' => ['nullable', 'array'],
                'subtasks.*.id' => ['nullable', 'string'],
                'subtasks.*.title' => ['required', 'string', 'max:100'],
                'subtasks.*.is_done' => ['boolean'],
            ]);

            $subtasks = $data['subtasks'] ?? null;

            if (!empty($subtasks)) {
                $subtasks = array_map(function ($task) {
                    return [
                        'id' => $task['id'] ?? Str::uuid()->toString(),
                        'title' => $task['title'],
                        'is_done' => $task['is_done'] ?? false,
                    ];
                }, $subtasks);
            }

            $quest->timestamps = false;
            $quest->subtasks = $subtasks;
            $quest->save();

            CacheBuster::onQuestMutate($request->user()->id);
            return redirect()->back();
        }

        // Full update (nama/status/reward/dll)
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
