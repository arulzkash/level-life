<?php

namespace App\Services;

use App\Models\UserDailyActivity;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserDailyActivityService
{
    public function incrementQuestCompletion(int $userId, string $activityDate): void
    {
        $now = now();
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                'INSERT INTO user_daily_activities (user_id, activity_date, quest_completed_count, created_at, updated_at)
                 VALUES (?, ?, 1, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    quest_completed_count = quest_completed_count + 1,
                    updated_at = VALUES(updated_at)',
                [$userId, $activityDate, $now, $now]
            );

            return;
        }

        DB::statement(
            'INSERT INTO user_daily_activities (user_id, activity_date, quest_completed_count, created_at, updated_at)
             VALUES (?, ?, 1, ?, ?)
             ON CONFLICT(user_id, activity_date) DO UPDATE SET
                quest_completed_count = user_daily_activities.quest_completed_count + 1,
                updated_at = excluded.updated_at',
            [$userId, $activityDate, $now, $now]
        );
    }

    public function buildHeatmap(int $userId, Carbon $today): array
    {
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $startDate = $weekStart->copy()->subWeeks(51);
        $endDate = $startDate->copy()->addDays((52 * 7) - 1);

        $counts = UserDailyActivity::query()
            ->where('user_id', $userId)
            ->whereBetween('activity_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('activity_date')
            ->get(['activity_date', 'quest_completed_count'])
            ->mapWithKeys(fn (UserDailyActivity $activity) => [
                $activity->activity_date->toDateString() => (int) $activity->quest_completed_count,
            ]);

        $weeks = [];
        $cursor = $startDate->copy();

        for ($week = 0; $week < 52; $week++) {
            $days = [];
            $monthLabel = null;

            for ($day = 0; $day < 7; $day++) {
                $date = $cursor->toDateString();
                $count = (int) ($counts[$date] ?? 0);

                if ($cursor->day === 1) {
                    $monthLabel = $cursor->format('M');
                }

                $days[] = [
                    'date' => $date,
                    'count' => $count,
                    'level' => $this->heatLevel($count),
                    'is_today' => $date === $today->toDateString(),
                    'is_future' => $cursor->gt($today),
                    'day_label' => $cursor->format('D'),
                ];

                $cursor->addDay();
            }

            $weeks[] = [
                'week_start' => $days[0]['date'],
                'month_label' => $monthLabel,
                'days' => $days,
            ];
        }

        return [
            'weeks' => $weeks,
            'legend' => [
                ['level' => 0, 'label' => '0'],
                ['level' => 1, 'label' => '1'],
                ['level' => 2, 'label' => '2-3'],
                ['level' => 3, 'label' => '4-6'],
                ['level' => 4, 'label' => '7+'],
            ],
        ];
    }

    public function buildStats(int $userId, Carbon $today, int $currentStreak, int $bestStreak): array
    {
        $todayYmd = $today->toDateString();
        $sevenDayStart = $today->copy()->subDays(6)->toDateString();
        $thirtyDayStart = $today->copy()->subDays(29)->toDateString();

        $aggregate = UserDailyActivity::query()
            ->where('user_id', $userId)
            ->selectRaw('COALESCE(SUM(quest_completed_count), 0) as total_quest_completions')
            ->selectRaw('COALESCE(MAX(quest_completed_count), 0) as best_day_count')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN activity_date BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) as active_days_7d',
                [$sevenDayStart, $todayYmd]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN activity_date BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) as active_days_30d',
                [$thirtyDayStart, $todayYmd]
            )
            ->first();

        return [
            'current_streak' => $currentStreak,
            'best_streak' => $bestStreak,
            'active_days_7d' => (int) ($aggregate->active_days_7d ?? 0),
            'active_days_30d' => (int) ($aggregate->active_days_30d ?? 0),
            'total_quest_completions' => (int) ($aggregate->total_quest_completions ?? 0),
            'best_day_count' => (int) ($aggregate->best_day_count ?? 0),
        ];
    }

    public function groupedCompletionCountsQuery(?int $userId = null)
    {
        return DB::table('quest_completions')
            ->selectRaw('user_id, DATE(completed_at) as activity_date, COUNT(*) as quest_completed_count')
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->groupBy('user_id')
            ->groupByRaw('DATE(completed_at)')
            ->orderBy('user_id')
            ->orderByRaw('DATE(completed_at)');
    }

    public function upsertAggregates(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $timestamp = now();

        $payload = $rows->map(fn ($row) => [
            'user_id' => (int) $row->user_id,
            'activity_date' => (string) $row->activity_date,
            'quest_completed_count' => (int) $row->quest_completed_count,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->all();

        UserDailyActivity::query()->upsert(
            $payload,
            ['user_id', 'activity_date'],
            ['quest_completed_count', 'updated_at']
        );
    }

    private function heatLevel(int $count): int
    {
        if ($count <= 0) {
            return 0;
        }

        if ($count === 1) {
            return 1;
        }

        if ($count <= 3) {
            return 2;
        }

        if ($count <= 6) {
            return 3;
        }

        return 4;
    }
}
