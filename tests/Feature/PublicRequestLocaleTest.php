<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * V1.0.15: public (unauthenticated) requests localize their responses in
 * the language the visitor declares through the X-Patrimoine-Language
 * header — sign-in errors and validation messages included. Without the
 * header, the platform default (English) applies as before.
 */
class PublicRequestLocaleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Public visitors have no organisation context. The base TestCase
     * binds one for the legacy suite; each test releases it right before
     * the request so the real unauthenticated state is exercised, after
     * any test data has been arranged under the bound context.
     */
    private function actAsPublicVisitor(): void
    {
        \App\Support\OrganisationContext::forget();
    }

    public function test_signup_validation_errors_follow_the_declared_language(): void
    {
        $this->actAsPublicVisitor();

        $response = $this->postJson(
            '/api/auth/register',
            [
                'email' => 'not-an-email',
            ],
            [
                'X-Patrimoine-Language' => 'fr',
            ]
        );

        $response->assertStatus(422);

        $this->assertStringContainsString(
            'adresse e-mail valide',
            implode(
                ' ',
                $response->json('errors.email') ?? []
            )
        );
    }

    public function test_signup_validation_errors_default_to_english_without_header(): void
    {
        $this->actAsPublicVisitor();

        $response = $this->postJson(
            '/api/auth/register',
            [
                'email' => 'not-an-email',
            ]
        );

        $response->assertStatus(422);

        $this->assertStringContainsString(
            'valid email address',
            implode(
                ' ',
                $response->json('errors.email') ?? []
            )
        );
    }

    public function test_unverified_login_carries_a_machine_readable_code_and_localizes(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'email_verification_token_hash' => hash('sha256', 'pending-token'),
        ]);

        $this->actAsPublicVisitor();

        $response = $this->postJson(
            '/api/auth/login',
            [
                'email' => $user->email,
                'password' => 'password',
                'device_name' => 'test',
            ],
            [
                'X-Patrimoine-Language' => 'fr',
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonPath(
            'code',
            'verification_required'
        );

        $this->assertSame(
            trans(
                'api.auth.verification_required',
                [],
                'fr'
            ),
            $response->json('message')
        );
    }

    public function test_invalid_language_header_is_ignored(): void
    {
        $this->actAsPublicVisitor();

        $response = $this->postJson(
            '/api/auth/register',
            [
                'email' => 'not-an-email',
            ],
            [
                'X-Patrimoine-Language' => 'xx',
            ]
        );

        $response->assertStatus(422);

        $this->assertStringContainsString(
            'valid email address',
            implode(
                ' ',
                $response->json('errors.email') ?? []
            )
        );
    }
}
