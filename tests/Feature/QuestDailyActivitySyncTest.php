<?php

namespace Tests\Feature;

use App\Models\Quest;
use App\Models\User;
use App\Models\UserDailyActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QuestDailyActivitySyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeatable_quest_completion_increments_the_same_daily_activity_row(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 12, 9, 30, 0, 'Asia/Jakarta'));

        $user = User::factory()->create();
        $quest = Quest::query()->create([
            'user_id' => $user->id,
            'name' => 'Daily Standup',
            'status' => 'todo',
            'type' => 'Daily Grind',
            'xp_reward' => 20,
            'coin_reward' => 10,
            'is_repeatable' => true,
        ]);

        $this->actingAs($user)
            ->from('/quests')
            ->patch("/quests/{$quest->id}/complete")
            ->assertRedirect('/quests');

        $this->actingAs($user)
            ->from('/quests')
            ->patch("/quests/{$quest->id}/complete")
            ->assertRedirect('/quests');

        $this->assertDatabaseHas('user_daily_activities', [
            'user_id' => $user->id,
            'activity_date' => '2026-04-12',
            'quest_completed_count' => 2,
        ]);

        $this->assertSame(
            1,
            UserDailyActivity::query()
                ->where('user_id', $user->id)
                ->where('activity_date', '2026-04-12')
                ->count()
        );

        Carbon::setTestNow();
    }
}
