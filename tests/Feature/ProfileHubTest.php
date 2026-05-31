<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserDailyActivity;
use App\Support\CacheKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_page_is_guest_accessible_and_returns_compact_payload(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 12, 10, 0, 0, 'Asia/Jakarta'));
        Artisan::call('badges:seed');

        $user = User::factory()->create([
            'name' => 'Consistency Crafter',
            'username' => 'consistency_crafter',
        ]);
        $user->profile->update([
            'bio' => 'Quietly stacking quest clears every day.',
            'streak_current' => 9,
            'streak_best' => 14,
            'last_active_date' => '2026-04-12',
        ]);

        UserDailyActivity::query()->insert([
            [
                'user_id' => $user->id,
                'activity_date' => '2026-04-12',
                'quest_completed_count' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'activity_date' => '2026-04-07',
                'quest_completed_count' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'activity_date' => '2026-03-01',
                'quest_completed_count' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $badges = Badge::query()
            ->whereIn('key', ['streak_3', 'streak_7', 'streak_14', 'streak_30'])
            ->get()
            ->keyBy('key');

        $user->badges()->attach($badges['streak_3']->id, [
            'earned_at' => '2026-03-10',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->badges()->attach($badges['streak_7']->id, [
            'earned_at' => '2026-03-20',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->badges()->attach($badges['streak_14']->id, [
            'earned_at' => '2026-04-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->badges()->attach($badges['streak_30']->id, [
            'earned_at' => '2026-04-10',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get("/u/{$user->username}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Show')
                ->where('identity.name', 'Consistency Crafter')
                ->where('identity.username', 'consistency_crafter')
                ->where('identity.bio', 'Quietly stacking quest clears every day.')
                ->where('identity.is_owner', false)
                ->where('streakSummary.current_streak', 9)
                ->where('streakSummary.best_streak', 14)
                ->where('streakSummary.status', 'On Fire')
                ->where('stats.active_days_30d', 2)
                ->where('stats.total_quest_completions', 10)
                ->where('stats.best_day_count', 5)
                ->has('heatmap.weeks', 52)
                ->where('badgeVault.unlocked_count', 4)
                ->where('badgeVault.total_count', 12)
                ->has('badgeVault.items', 12)
                ->where('badgeVault.items.0.key', 'streak_3')
                ->where('badgeVault.items.0.is_unlocked', true));

        Carbon::setTestNow();
    }

    public function test_owner_view_marks_profile_as_owned(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 12, 10, 0, 0, 'Asia/Jakarta'));

        $user = User::factory()->create([
            'username' => 'owner_profile',
        ]);

        $this->actingAs($user)
            ->get("/u/{$user->username}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Show')
                ->where('identity.username', 'owner_profile')
                ->where('identity.is_owner', true));

        Carbon::setTestNow();
    }

    public function test_unknown_public_username_returns_not_found(): void
    {
        $this->get('/u/unknown_user')->assertNotFound();
    }

    public function test_profile_bio_updates_are_visible_on_public_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'bio_visible',
        ]);
        $user->profile->update([
            'bio' => 'This bio is visible on the public profile.',
        ]);

        $this->get('/u/bio_visible')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Show')
                ->where('identity.bio', 'This bio is visible on the public profile.'));
    }

    public function test_public_profile_rank_refreshes_even_when_profile_payload_is_already_cached(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 12, 10, 0, 0, 'Asia/Jakarta'));

        $user = User::factory()->create([
            'username' => 'rank_refresh',
        ]);

        $this->get('/u/rank_refresh')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Show')
                ->where('rankSummary.current_rank', '-'));

        Cache::put(CacheKeys::leaderboardRoster(CacheKeys::todayJakarta()), collect([
            (object) [
                'user_id' => $user->id,
                'rank' => 1,
            ],
        ]), 86400);

        $this->get('/u/rank_refresh')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Show')
                ->where('rankSummary.current_rank', 1));

        Carbon::setTestNow();
    }
}
