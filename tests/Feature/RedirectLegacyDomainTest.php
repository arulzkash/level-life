<?php

namespace Tests\Feature;

use Tests\TestCase;

class RedirectLegacyDomainTest extends TestCase
{
    public function test_legacy_domain_redirects_to_net_domain(): void
    {
        $response = $this->call(
            'GET',
            'https://levellife.my.id/journal?date=2026-06-04',
            server: [
                'HTTP_HOST' => 'levellife.my.id',
                'SERVER_NAME' => 'levellife.my.id',
                'HTTPS' => 'on',
            ],
        );

        $response
            ->assertStatus(301)
            ->assertRedirect('https://levellife.net/journal?date=2026-06-04');
    }

    public function test_legacy_www_domain_redirects_to_net_domain(): void
    {
        $response = $this->call(
            'GET',
            'https://www.levellife.my.id/dashboard',
            server: [
                'HTTP_HOST' => 'www.levellife.my.id',
                'SERVER_NAME' => 'www.levellife.my.id',
                'HTTPS' => 'on',
            ],
        );

        $response
            ->assertStatus(301)
            ->assertRedirect('https://levellife.net/dashboard');
    }
}
