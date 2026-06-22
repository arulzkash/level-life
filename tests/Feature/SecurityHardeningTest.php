<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\Habit;
use App\Models\JournalTemplate;
use App\Models\Note;
use App\Models\TimeBlock;
use App\Models\TreasuryPurchase;
use App\Models\TreasuryReward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_route_model_resources_reject_cross_user_access(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();

        $goal = $owner->goals()->create([
            'title' => 'Owner goal',
            'description' => null,
            'personal_reason' => 'Owner reason',
            'deadline' => '2026-12-31',
            'status' => 'active',
        ]);
        $milestone = $goal->milestones()->create([
            'title' => 'Owner milestone',
            'due_date' => '2026-12-01',
            'position' => 0,
        ]);
        $habit = $owner->habits()->create([
            'name' => 'Owner habit',
            'start_date' => now()->toDateString(),
        ]);
        $timeBlock = $owner->timeBlocks()->create([
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'title' => 'Owner block',
        ]);
        $reward = $owner->treasuryRewards()->create([
            'name' => 'Owner reward',
            'cost_coin' => 10,
        ]);
        $purchase = $owner->treasuryPurchases()->create([
            'treasury_reward_id' => $reward->id,
            'qty' => 1,
            'unit_cost_coin' => 10,
            'cost_coin' => 10,
            'purchased_at' => now(),
        ]);
        $template = JournalTemplate::query()->create([
            'user_id' => $owner->id,
            'name' => 'Owner template',
            'sections' => [['title' => 'Private']],
        ]);
        $note = Note::query()->create([
            'user_id' => $owner->id,
            'title' => 'Owner note',
            'body' => 'Private',
        ]);

        $this->actingAs($user)->get("/goals/{$goal->id}")->assertForbidden();
        $this->actingAs($user)->patch("/goals/{$goal->id}", [
            'title' => 'Changed',
            'description' => null,
            'personal_reason' => 'Changed reason',
            'deadline' => '2026-12-31',
        ])->assertForbidden();
        $this->actingAs($user)->post("/goals/{$goal->id}/complete")->assertForbidden();
        $this->actingAs($user)->patch("/goals/milestones/{$milestone->id}/toggle")->assertForbidden();
        $this->actingAs($user)->get("/habits/{$habit->id}")->assertForbidden();
        $this->actingAs($user)->patch("/habits/{$habit->id}", ['name' => 'Changed'])->assertForbidden();
        $this->actingAs($user)->patch("/timeblocks/{$timeBlock->id}", [
            'date' => now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'title' => 'Changed',
        ])->assertForbidden();
        $this->actingAs($user)->patch("/treasury/rewards/{$reward->id}", [
            'name' => 'Changed',
            'cost_coin' => 1,
        ])->assertForbidden();
        $this->actingAs($user)->patch("/treasury/rewards/{$reward->id}/buy", [
            'qty' => 1,
        ])->assertForbidden();
        $this->actingAs($user)->patch("/logs/treasury/{$purchase->id}", [
            'note' => 'Changed',
        ])->assertForbidden();
        $this->actingAs($user)->delete("/journal/templates/{$template->id}")->assertForbidden();
        $this->actingAs($user)->get("/notes/{$note->id}")->assertForbidden();
    }

    public function test_internal_reminder_tokens_support_legacy_query_and_preferred_headers(): void
    {
        config(['services.internal_reminders.token' => 'strong-test-token']);

        $this->postJson('/internal/reminders/morning', [], [
            'Authorization' => 'Bearer strong-test-token',
        ])->assertOk();

        $this->getJson('/internal/cron/notifications/morning', [
            'X-Internal-Token' => 'strong-test-token',
        ])->assertOk();

        $this->getJson('/internal/cron/notifications/evening?token=strong-test-token')->assertOk();

        $this->postJson('/internal/reminders/evening')->assertUnauthorized();
        $this->getJson('/internal/cron/notifications/evening?token=wrong')->assertForbidden();
    }

    public function test_internal_reminder_rate_limit_allows_expected_cron_retries_before_blocking_abuse(): void
    {
        config(['services.internal_reminders.token' => 'strong-test-token']);
        RateLimiter::clear('127.0.0.1|internal/cron/notifications/morning');

        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/internal/cron/notifications/morning?token=strong-test-token')->assertOk();
        }

        $this->getJson('/internal/cron/notifications/morning?token=strong-test-token')->assertStatus(429);
    }

    public function test_debug_badges_route_is_disabled_by_default_and_enabled_by_flag(): void
    {
        $user = User::factory()->create();

        config(['app.features.badge_debug' => false]);
        $this->actingAs($user)->get('/debug/badges')->assertNotFound();

        config(['app.features.badge_debug' => true]);
        $this->actingAs($user)->get('/debug/badges')->assertOk();
    }

    public function test_security_headers_are_present_and_hsts_is_production_https_only(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertTrue($response->headers->has('Permissions-Policy'));
        $this->assertTrue($response->headers->has('Content-Security-Policy-Report-Only'));
        $this->assertFalse($response->headers->has('Strict-Transport-Security'));

        $this->app->detectEnvironment(fn () => 'production');
        $secureResponse = $this->get('https://localhost/');
        $this->assertTrue($secureResponse->headers->has('Strict-Transport-Security'));
    }

    public function test_defensive_validation_limits_reject_oversized_inputs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/notes', [
            'title' => 'Large note',
            'body' => str_repeat('A', 50001),
        ])->assertSessionHasErrors('body');

        $this->actingAs($user)->put('/journal', [
            'date' => now('Asia/Jakarta')->toDateString(),
            'sections' => [[
                'id' => 'section-1',
                'title' => 'Oversized',
                'content' => str_repeat('B', 10001),
            ]],
        ])->assertSessionHasErrors('sections.0.content');

        $this->actingAs($user)->postJson('/push-subscriptions', [
            'endpoint' => 'https://example.com/push',
            'keys' => [
                'p256dh' => str_repeat('C', 501),
                'auth' => 'auth',
            ],
        ])->assertUnprocessable();
    }

    public function test_reward_flows_keep_existing_amounts_and_redirects(): void
    {
        $user = User::factory()->create();
        $user->profile()->update(['xp_total' => 0, 'coin_balance' => 100]);

        $this->actingAs($user)->put('/journal', [
            'date' => now('Asia/Jakarta')->toDateString(),
            'title' => 'Today',
            'xp_reward' => 12,
            'coin_reward' => 7,
        ])->assertRedirect('/journal?date='.now('Asia/Jakarta')->toDateString());

        $user->refresh();
        $this->assertSame(12, (int) $user->profile->xp_total);
        $this->assertSame(107, (int) $user->profile->coin_balance);

        $reward = $user->treasuryRewards()->create([
            'name' => 'Coffee',
            'cost_coin' => 10,
        ]);

        $this->actingAs($user)->patch("/treasury/rewards/{$reward->id}/buy", [
            'qty' => 2,
            'note' => 'Same flow',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame(87, (int) $user->profile->coin_balance);
        $this->assertDatabaseHas('treasury_purchases', [
            'user_id' => $user->id,
            'treasury_reward_id' => $reward->id,
            'qty' => 2,
            'unit_cost_coin' => 10,
            'cost_coin' => 20,
        ]);
    }
}