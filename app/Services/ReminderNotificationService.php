<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\EveningStreakReminderNotification;
use App\Notifications\MorningReminderNotification;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReminderNotificationService
{
    public function sendMorningReminders(?CarbonImmutable $date = null): array
    {
        $date ??= CarbonImmutable::now('Asia/Jakarta');

        return $this->sendReminders(
            type: NotificationLog::TYPE_MORNING_REMINDER,
            date: $date,
            candidates: $this->baseCandidateQuery($date, 'morning_enabled', NotificationLog::TYPE_MORNING_REMINDER),
            notificationFactory: fn () => new MorningReminderNotification,
        );
    }

    public function sendEveningReminders(?CarbonImmutable $date = null): array
    {
        $date ??= CarbonImmutable::now('Asia/Jakarta');
        $dateString = $date->toDateString();
        $nextDateString = $date->addDay()->toDateString();

        $candidates = $this->baseCandidateQuery($date, 'evening_enabled', NotificationLog::TYPE_EVENING_STREAK_REMINDER)
            ->whereDoesntHave('dailyActivities', function (Builder $query) use ($dateString, $nextDateString) {
                $query
                    ->where('activity_date', '>=', $dateString)
                    ->where('activity_date', '<', $nextDateString)
                    ->where('quest_completed_count', '>', 0);
            });

        return $this->sendReminders(
            type: NotificationLog::TYPE_EVENING_STREAK_REMINDER,
            date: $date,
            candidates: $candidates,
            notificationFactory: fn () => new EveningStreakReminderNotification,
        );
    }

    private function baseCandidateQuery(CarbonImmutable $date, string $settingColumn, string $type): Builder
    {
        $dateString = $date->toDateString();

        return User::query()
            ->whereHas('notificationSetting', fn (Builder $query) => $query->where($settingColumn, true))
            ->whereHas('pushSubscriptions')
            ->whereDoesntHave('notificationLogs', function (Builder $query) use ($dateString, $type) {
                $query
                    ->where('type', $type)
                    ->where('date', $dateString);
            });
    }

    private function sendReminders(string $type, CarbonImmutable $date, Builder $candidates, callable $notificationFactory): array
    {
        if (! config('webpush.vapid.public_key') || ! config('webpush.vapid.private_key')) {
            return [
                'date' => $date->toDateString(),
                'type' => $type,
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
                'message' => 'VAPID keys are not configured.',
            ];
        }

        $stats = [
            'date' => $date->toDateString(),
            'type' => $type,
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $now = now();
        $dateString = $date->toDateString();

        $candidates
            ->select(['id'])
            ->chunkById(200, function ($users) use (&$stats, $type, $dateString, $now, $notificationFactory) {
                foreach ($users as $user) {
                    $inserted = DB::table('notification_logs')->insertOrIgnore([
                        'user_id' => $user->id,
                        'type' => $type,
                        'date' => $dateString,
                        'sent_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    if ($inserted !== 1) {
                        $stats['skipped']++;
                        continue;
                    }

                    try {
                        $user->notify($notificationFactory());
                        $stats['sent']++;
                    } catch (Throwable $exception) {
                        report($exception);
                        $stats['failed']++;
                    }
                }
            });

        return $stats;
    }
}
