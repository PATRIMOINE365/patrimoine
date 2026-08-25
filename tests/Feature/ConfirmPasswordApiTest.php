<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * V1.0.8: the danger-confirmation dialog re-verifies the operator's
 * password server-side before any irreversible deletion.
 */
class ConfirmPasswordApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    public function test_correct_password_is_confirmed(): void
    {
        $this->authenticateApiUser();

        $this->postJson(
            '/api/auth/confirm-password',
            [
                'password' => 'password',
            ]
        )
            ->assertOk()
            ->assertJsonPath('confirmed', true);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->authenticateApiUser();

        $this->postJson(
            '/api/auth/confirm-password',
            [
                'password' => 'definitely-wrong',
            ]
        )->assertUnprocessable();
    }

    public function test_guest_cannot_confirm(): void
    {
        $this->postJson(
            '/api/auth/confirm-password',
            [
                'password' => 'password',
            ]
        )->assertUnauthorized();
    }
}
