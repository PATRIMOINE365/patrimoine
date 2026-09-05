<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * V1.0.50: every throttled route counts on its own.
 *
 * A bare `throttle:N,M` is keyed on the caller alone, so fifteen limits
 * were one shared counter. These tests exhaust one route's limit and
 * prove its neighbour is untouched — for a signed-in user and for a
 * guest.
 */
class RateLimitBucketsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_signed_in_users_limits_are_separate_per_route(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        Sanctum::actingAs($user);

        /*
         * confirm-password allows five a minute. Spend them.
         */
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this
                ->postJson('/api/auth/confirm-password', [
                    'password' => 'not-it',
                ])
                ->assertStatus(422);
        }

        $this
            ->postJson('/api/auth/confirm-password', [
                'password' => 'not-it',
            ])
            ->assertStatus(429);

        /*
         * Changing the email allows three per ten minutes. On the shared
         * counter those three were already gone; on its own counter the
         * request is judged on its merits — here, a wrong password.
         */
        $this
            ->postJson('/api/auth/email-change', [
                'email' => 'new@example.test',
                'current_password' => 'not-it',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_a_guests_limits_are_separate_per_route(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->assertNotSame(
                429,
                $this
                    ->postJson('/api/auth/login', [
                        'email' => 'nobody@example.test',
                        'password' => 'not-it',
                    ])
                    ->status(),
                'login attempt '.$attempt.' was throttled early'
            );
        }

        $this
            ->postJson('/api/auth/login', [
                'email' => 'nobody@example.test',
                'password' => 'not-it',
            ])
            ->assertStatus(429);

        /*
         * Five failed sign-ins used to lock the same connection out of
         * password reset. Reset has its own five.
         */
        $this
            ->postJson('/api/auth/forgot-password', [
                'email' => 'nobody@example.test',
            ])
            ->assertOk();
    }
}
