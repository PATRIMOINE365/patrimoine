<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Verify that Patrimoine business endpoints require authentication.
 *
 * These tests deliberately use anonymous requests and therefore must not
 * use the authenticated API test concern.
 */
class ApiAuthenticationProtectionTest extends TestCase
{
    public function test_core_resources_require_authentication(): void
    {
        $this
            ->getJson('/api/parties')
            ->assertUnauthorized();

        $this
            ->getJson('/api/buildings')
            ->assertUnauthorized();

        $this
            ->getJson('/api/units')
            ->assertUnauthorized();

        $this
            ->getJson('/api/leases')
            ->assertUnauthorized();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this
            ->getJson('/api/dashboard')
            ->assertUnauthorized();
    }

    public function test_financial_operations_require_authentication(): void
    {
        $this
            ->getJson('/api/payments')
            ->assertUnauthorized();

        $this
            ->postJson('/api/owner-expenses', [])
            ->assertUnauthorized();
    }

    public function test_reports_require_authentication(): void
    {
        $this
            ->getJson('/api/reports/managing-organisation')
            ->assertUnauthorized();

        $this
            ->get('/api/reports/managing-organisation/pdf')
            ->assertUnauthorized();
    }

    public function test_login_remains_public(): void
    {
        $response = $this->postJson(
            '/api/auth/login',
            []
        );

        /*
         * A validation response proves the request reached the login
         * endpoint rather than being blocked by authentication middleware.
         */
        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
                'password',
            ]);
    }
}
