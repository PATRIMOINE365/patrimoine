<?php

namespace Tests\Concerns;

use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Provides an authenticated Patrimoine API user for feature tests.
 *
 * Business API tests should exercise the application through the same
 * Sanctum authentication middleware used in production, rather than
 * bypassing authentication entirely.
 */
trait AuthenticatesApiUser
{
    /**
     * Authenticate a fresh application user through Sanctum.
     */
    protected function authenticateApiUser(): User
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        return $user;
    }
}
