<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiAccessTest extends TestCase
{
    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_protected_dashboard_route_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/dashboard/overview');

        $response->assertStatus(401);
    }

    public function test_auth_login_route_is_registered(): void
    {
        $response = $this->getJson('/api/v1/auth/login');

        $response->assertStatus(405);
    }
}
