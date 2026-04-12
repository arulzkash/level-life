<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_settings_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/settings/profile');

        $response->assertOk();
    }

    public function test_profile_route_redirects_to_settings_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertRedirect('/settings/profile');
    }

    public function test_profile_settings_page_requires_authentication(): void
    {
        $this->get('/settings/profile')->assertRedirect('/login');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'username' => 'test_user',
                'email' => 'test@example.com',
                'bio' => 'Building consistency one quest at a time.',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test_user', $user->username);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('Building consistency one quest at a time.', $user->profile->bio);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'username' => $user->username,
                'email' => $user->email,
                'bio' => null,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_username_must_be_unique(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['username' => 'taken_name']);

        $response = $this
            ->actingAs($user)
            ->from('/settings/profile')
            ->patch('/settings/profile', [
                'name' => $user->name,
                'username' => $otherUser->username,
                'email' => $user->email,
                'bio' => null,
            ]);

        $response
            ->assertSessionHasErrors('username')
            ->assertRedirect('/settings/profile');
    }

    public function test_username_must_match_public_format(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/profile')
            ->patch('/settings/profile', [
                'name' => $user->name,
                'username' => 'Invalid-Name',
                'email' => $user->email,
                'bio' => null,
            ]);

        $response
            ->assertSessionHasErrors('username')
            ->assertRedirect('/settings/profile');
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/settings/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/profile')
            ->delete('/settings/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->fresh());
    }
}
