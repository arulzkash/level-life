<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Models\UserDailyActivity;
use App\Notifications\EveningStreakReminderNotification;
use App\Notifications\MorningReminderNotification;
use App\Services\ReminderNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReminderNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_morning_reminder_is_idempotent_per_user_type_and_date(): void
    {
        Notification::fake();
        config([
            'webpush.vapid.public_key' => 'public-key',
            'webpush.vapid.private_key' => 'private-key',
        ]);

        $user = User::factory()->create();
        NotificationSetting::query()->create([
            'user_id' => $user->id,
            'morning_enabled' => true,
            'evening_enabled' => false,
        ]);
        $user->updatePushSubscription('https://example.com/push/1', 'public', 'auth', 'aes128gcm');

        $date = CarbonImmutable::parse('2026-05-31', 'Asia/Jakarta');
        $service = app(ReminderNotificationService::class);

        $first = $service->sendMorningReminders($date);
        $second = $service->sendMorningReminders($date);

        $this->assertSame(1, $first['sent']);
        $this->assertSame(0, $second['sent']);
        $this->assertDatabaseCount('notification_logs', 1);
        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $user->id,
            'type' => NotificationLog::TYPE_MORNING_REMINDER,
            'date' => '2026-05-31',
        ]);
        Notification::assertSentTo($user, MorningReminderNotification::class, 1);
    }

    public function test_evening_reminder_skips_users_who_completed_a_quest_today(): void
    {
        Notification::fake();
        config([
            'webpush.vapid.public_key' => 'public-key',
            'webpush.vapid.private_key' => 'private-key',
        ]);

        $completedUser = User::factory()->create();
        $incompleteUser = User::factory()->create();

        foreach ([$completedUser, $incompleteUser] as $user) {
            NotificationSetting::query()->create([
                'user_id' => $user->id,
                'morning_enabled' => false,
                'evening_enabled' => true,
            ]);
            $user->updatePushSubscription("https://example.com/push/{$user->id}", 'public', 'auth', 'aes128gcm');
        }

        UserDailyActivity::query()->create([
            'user_id' => $completedUser->id,
            'activity_date' => '2026-05-31',
            'quest_completed_count' => 1,
        ]);

        $result = app(ReminderNotificationService::class)
            ->sendEveningReminders(CarbonImmutable::parse('2026-05-31', 'Asia/Jakarta'));

        $this->assertSame(1, $result['sent']);
        $this->assertDatabaseMissing('notification_logs', [
            'user_id' => $completedUser->id,
            'type' => NotificationLog::TYPE_EVENING_STREAK_REMINDER,
            'date' => '2026-05-31',
        ]);
        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $incompleteUser->id,
            'type' => NotificationLog::TYPE_EVENING_STREAK_REMINDER,
            'date' => '2026-05-31',
        ]);
        Notification::assertNotSentTo($completedUser, EveningStreakReminderNotification::class);
        Notification::assertSentTo($incompleteUser, EveningStreakReminderNotification::class, 1);
    }
}
