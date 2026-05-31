<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_on_notes_routes(): void
    {
        $this->get('/notes')->assertRedirect('/login');
        $this->get('/notes/create')->assertRedirect('/login');
        $this->post('/notes', [])->assertRedirect('/login');
    }

    public function test_user_can_view_notes_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/notes');

        $response->assertOk();
    }

    public function test_user_can_create_a_note(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/notes', [
            'title' => 'My Test Note',
            'body' => 'Note body text',
            'color' => 'indigo',
            'is_pinned' => true,
            'sections' => [
                ['id' => '1', 'title' => 'Task List', 'content' => 'Item A']
            ]
        ]);

        $note = Note::where('user_id', $user->id)->first();
        $response->assertRedirect("/notes/{$note->id}");
        $this->assertDatabaseHas('notes', [
            'user_id' => $user->id,
            'title' => 'My Test Note',
            'body' => 'Note body text',
            'color' => 'indigo',
            'is_pinned' => true,
        ]);

        // Verify JSON cast is working
        $this->assertCount(1, $note->sections);
        $this->assertEquals('Task List', $note->sections[0]['title']);
    }

    public function test_user_can_view_their_own_note(): void
    {
        $user = User::factory()->create();
        $note = Note::create([
            'user_id' => $user->id,
            'title' => 'Own Note',
            'body' => 'Confidential thoughts'
        ]);

        $response = $this->actingAs($user)->get("/notes/{$note->id}");

        $response->assertOk();
    }

    public function test_user_cannot_view_others_note(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $note = Note::create([
            'user_id' => $otherUser->id,
            'title' => 'Others Note',
            'body' => 'Confidential thoughts'
        ]);

        $response = $this->actingAs($user)->get("/notes/{$note->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_their_own_note(): void
    {
        $user = User::factory()->create();
        $note = Note::create([
            'user_id' => $user->id,
            'title' => 'Original Title',
            'body' => 'Original Body',
            'color' => 'slate',
            'is_pinned' => false
        ]);

        $response = $this->actingAs($user)->put("/notes/{$note->id}", [
            'title' => 'Updated Title',
            'body' => 'Updated Body',
            'color' => 'rose',
            'is_pinned' => true
        ]);

        $response->assertRedirect("/notes/{$note->id}");
        $note->refresh();
        $this->assertEquals('Updated Title', $note->title);
        $this->assertEquals('Updated Body', $note->body);
        $this->assertEquals('rose', $note->color);
        $this->assertTrue($note->is_pinned);
    }

    public function test_user_cannot_update_others_note(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $note = Note::create([
            'user_id' => $otherUser->id,
            'title' => 'Original Title',
            'body' => 'Original Body'
        ]);

        $response = $this->actingAs($user)->put("/notes/{$note->id}", [
            'title' => 'Updated Title',
            'body' => 'Updated Body'
        ]);

        $response->assertStatus(403);
        $note->refresh();
        $this->assertEquals('Original Title', $note->title);
    }

    public function test_user_can_delete_their_own_note(): void
    {
        $user = User::factory()->create();
        $note = Note::create([
            'user_id' => $user->id,
            'title' => 'Own Note'
        ]);

        $response = $this->actingAs($user)->delete("/notes/{$note->id}");

        $response->assertRedirect('/notes');
        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_user_cannot_delete_others_note(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $note = Note::create([
            'user_id' => $otherUser->id,
            'title' => 'Others Note'
        ]);

        $response = $this->actingAs($user)->delete("/notes/{$note->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('notes', ['id' => $note->id]);
    }

    public function test_searching_notes_filters_results(): void
    {
        $user = User::factory()->create();

        // Note 1 matches in title
        Note::create([
            'user_id' => $user->id,
            'title' => 'Secret Recipe',
            'body' => 'Mix water and salt.'
        ]);

        // Note 2 matches in body
        Note::create([
            'user_id' => $user->id,
            'title' => 'Shopping List',
            'body' => 'Remember to buy secret sauce.'
        ]);

        // Note 3 matches in sections
        Note::create([
            'user_id' => $user->id,
            'title' => 'Work Outline',
            'body' => 'Work tasks.',
            'sections' => [
                ['id' => '1', 'title' => 'Project', 'content' => 'Find the secret key']
            ]
        ]);

        // Note 4 does not match
        Note::create([
            'user_id' => $user->id,
            'title' => 'Garbage Note',
            'body' => 'No special keyword here.'
        ]);

        $response = $this->actingAs($user)->get('/notes?q=secret');

        $response->assertOk();
        
        $notes = $response->viewData('page')['props']['notes'];
        $this->assertCount(3, $notes['data']);

        $titles = collect($notes['data'])->pluck('title')->toArray();
        $this->assertContains('Secret Recipe', $titles);
        $this->assertContains('Shopping List', $titles);
        $this->assertContains('Work Outline', $titles);
        $this->assertNotContains('Garbage Note', $titles);
    }
}
