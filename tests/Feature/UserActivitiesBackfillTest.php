<?php

namespace Tests\Feature;

use App\Models\Quest;
use App\Models\QuestCompletion;
use App\Models\User;
use App\Models\UserDailyActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UserActivitiesBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_aggregates_existing_completion_history_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $quest = Quest::query()->create([
            'user_id' => $user->id,
            'name' => 'Deep Work',
            'status' => 'todo',
            'type' => 'Main Quest',
            'xp_reward' => 100,
            'coin_reward' => 50,
            'is_repeatable' => true,
        ]);

        $otherQuest = Quest::query()->create([
            'user_id' => $otherUser->id,
            'name' => 'Warm Up',
            'status' => 'todo',
            'type' => 'Daily Grind',
            'xp_reward' => 25,
            'coin_reward' => 10,
            'is_repeatable' => true,
        ]);

        QuestCompletion::query()->create([
            'user_id' => $user->id,
            'quest_id' => $quest->id,
            'xp_awarded' => 100,
            'coin_awarded' => 50,
            'completed_at' => '2026-04-10 09:00:00',
        ]);

        QuestCompletion::query()->create([
            'user_id' => $user->id,
            'quest_id' => $quest->id,
            'xp_awarded' => 100,
            'coin_awarded' => 50,
            'completed_at' => '2026-04-10 18:15:00',
        ]);

        QuestCompletion::query()->create([
            'user_id' => $user->id,
            'quest_id' => $quest->id,
            'xp_awarded' => 100,
            'coin_awarded' => 50,
            'completed_at' => '2026-04-11 07:30:00',
        ]);

        QuestCompletion::query()->create([
            'user_id' => $otherUser->id,
            'quest_id' => $otherQuest->id,
            'xp_awarded' => 25,
            'coin_awarded' => 10,
            'completed_at' => '2026-04-11 08:45:00',
        ]);

        Artisan::call('user-activities:backfill');
        Artisan::call('user-activities:backfill');

        $this->assertDatabaseHas('user_daily_activities', [
            'user_id' => $user->id,
            'activity_date' => '2026-04-10',
            'quest_completed_count' => 2,
        ]);

        $this->assertDatabaseHas('user_daily_activities', [
            'user_id' => $user->id,
            'activity_date' => '2026-04-11',
            'quest_completed_count' => 1,
        ]);

        $this->assertDatabaseHas('user_daily_activities', [
            'user_id' => $otherUser->id,
            'activity_date' => '2026-04-11',
            'quest_completed_count' => 1,
        ]);

        $this->assertSame(3, UserDailyActivity::query()->count());
    }
}
