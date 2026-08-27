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

    /**
     * Menu items are ordinary links, so every navigation renders a fresh
     * Blade document with no API token and therefore no bound
     * organisation. Without a first-paint hint the server answered in
     * English and the browser repainted the whole interface in French a
     * network round trip later. The browser-published language cookie is
     * what lets the server render the right language immediately.
     */
    public function test_language_cookie_localises_a_request_without_an_organisation(): void
    {
        $this->actAsPublicVisitor();

        /*
         * JSON test requests drop cookies unless credentials are opted in;
         * a same-origin browser fetch always sends them.
         */
        $response = $this->withCredentials()->withUnencryptedCookie(
            \App\Services\ApplicationLocaleService::LANGUAGE_COOKIE,
            'fr'
        )->postJson(
            '/api/auth/register',
            [
                'email' => 'not-an-email',
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

    public function test_language_header_wins_over_the_language_cookie(): void
    {
        $this->actAsPublicVisitor();

        /*
         * JSON test requests drop cookies unless credentials are opted in;
         * a same-origin browser fetch always sends them.
         */
        $response = $this->withCredentials()->withUnencryptedCookie(
            \App\Services\ApplicationLocaleService::LANGUAGE_COOKIE,
            'fr'
        )->postJson(
            '/api/auth/register',
            [
                'email' => 'not-an-email',
            ],
            [
                'X-Patrimoine-Language' => 'en',
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

    public function test_invalid_language_cookie_is_ignored(): void
    {
        $this->actAsPublicVisitor();

        /*
         * JSON test requests drop cookies unless credentials are opted in;
         * a same-origin browser fetch always sends them.
         */
        $response = $this->withCredentials()->withUnencryptedCookie(
            \App\Services\ApplicationLocaleService::LANGUAGE_COOKIE,
            'xx'
        )->postJson(
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

    /**
     * The bug the cookie exists to fix: a Blade shell rendered in English
     * and then repainted in French by JavaScript. With the hint present
     * the very first byte is already French, so there is nothing to
     * repaint.
     */
    public function test_application_shell_is_rendered_in_the_cookie_language(): void
    {
        $this->actAsPublicVisitor();

        $response = $this->withUnencryptedCookie(
            \App\Services\ApplicationLocaleService::LANGUAGE_COOKIE,
            'fr'
        )->get('/owners');

        $response->assertOk();

        $response->assertSee(
            'Propriétaires',
            false
        );
    }

    public function test_application_shell_falls_back_to_english_without_the_cookie(): void
    {
        $this->actAsPublicVisitor();

        $response = $this->get('/owners');

        $response->assertOk();

        $response->assertSee(
            'Owners',
            false
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
