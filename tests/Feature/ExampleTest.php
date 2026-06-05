<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_root_route_renders_public_handbook_for_guests(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Handbook/Public')
                ->has('markdown')
                ->where('isMissing', false)
                ->has('handbooks.id.markdown')
                ->where('handbooks.id.isMissing', false)
                ->has('handbooks.en.markdown')
                ->where('handbooks.en.isMissing', false));
    }
}
