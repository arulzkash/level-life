<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_status_is_scoped_to_authenticated_user(): void
    {
        $endpoint = 'https://example.com/push/shared-browser-endpoint';
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $owner->updatePushSubscription($endpoint, 'public', 'auth', 'aes128gcm');

        $this->actingAs($owner)
            ->postJson('/push-subscriptions/status', ['endpoint' => $endpoint])
            ->assertOk()
            ->assertJson([
                'endpointSubscribed' => true,
                'subscriptionCount' => 1,
            ]);

        $this->actingAs($otherUser)
            ->postJson('/push-subscriptions/status', ['endpoint' => $endpoint])
            ->assertOk()
            ->assertJson([
                'endpointSubscribed' => false,
                'subscriptionCount' => 0,
            ]);
    }
}
