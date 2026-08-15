<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ApplicationSetting;

/**
 * Verify Patrimoine API authentication through Laravel Sanctum.
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A valid Patrimoine user can authenticate and receive an API token.
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'name' => 'Property Manager',
            'email' => 'manager@example.test',
            'password' => 'secure-password',
        ]);

        $response = $this->postJson(
            '/api/auth/login',
            [
                'email' => 'manager@example.test',
                'password' => 'secure-password',
                'device_name' => 'Test Client',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'token_type',
                'Bearer'
            )
            ->assertJsonPath(
                'user.id',
                $user->id
            )
            ->assertJsonPath(
                'user.name',
                'Property Manager'
            )
            ->assertJsonPath(
                'user.email',
                'manager@example.test'
            )
            ->assertJsonStructure([
                'access_token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ]);

        /*
         * Sanctum stores the token record but never the plain-text token
         * returned to the client.
         */
        $this->assertDatabaseCount(
            'personal_access_tokens',
            1
        );

        $this->assertDatabaseHas(
            'personal_access_tokens',
            [
                'tokenable_type' => User::class,
                'tokenable_id' => $user->id,
                'name' => 'Test Client',
            ]
        );
    }

    /**
     * Incorrect credentials must not create an access token.
     */
    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'manager@example.test',
            'password' => 'correct-password',
        ]);

        $response = $this->postJson(
            '/api/auth/login',
            [
                'email' => 'manager@example.test',
                'password' => 'wrong-password',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );
    }

    /**
     * Login requires both an email address and password.
     */
    public function test_login_requires_credentials(): void
    {
        $response = $this->postJson(
            '/api/auth/login',
            []
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
                'password',
            ]);
    }

    /**
     * An authenticated API user can retrieve their own account.
     */
    public function test_authenticated_user_can_be_retrieved(): void
    {
        $user = User::factory()->create([
            'name' => 'Property Manager',
            'email' => 'manager@example.test',
        ]);

        $token = $user
            ->createToken('Test Client')
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson('/api/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath(
                'id',
                $user->id
            )
            ->assertJsonPath(
                'email',
                'manager@example.test'
            );
    }



    /**
     * The current API token can be revoked through logout.
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $token = $user
            ->createToken('Test Client')
            ->plainTextToken;

        $this->assertDatabaseCount(
            'personal_access_tokens',
            1
        );

        $response = $this
            ->withToken($token)
            ->postJson('/api/auth/logout');

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Logged out successfully.'
            );

        /*
        * Revocation is represented by deletion of the Sanctum personal
        * access token. This is the authoritative logout assertion.
        *
        * We deliberately do not perform a second authenticated request in
        * this test because Laravel's test process may retain the resolved
        * authentication guard between simulated requests.
        */
        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );
    }



    /**
     * A Sanctum token that has been revoked cannot authenticate a new request.
     */
    public function test_revoked_token_cannot_authenticate(): void
    {
        $user = User::factory()->create();

        $newAccessToken = $user->createToken(
            'Revoked Client'
        );

        $plainTextToken = $newAccessToken->plainTextToken;

        /*
        * Deleting the persisted PersonalAccessToken is Sanctum's token
        * revocation mechanism.
        */
        $newAccessToken->accessToken->delete();

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );

        /*
        * Clear resolved authentication state before simulating a genuinely
        * new request carrying the previously issued bearer token.
        */
        $this->app['auth']->forgetGuards();

        $this
            ->withToken($plainTextToken)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }



    /**
     * Authentication-protected endpoints reject anonymous requests.
     */
    public function test_me_requires_authentication(): void
    {
        $this
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    /**
     * Logout also requires an authenticated user.
     */
    public function test_logout_requires_authentication(): void
    {
        $this
            ->postJson('/api/auth/logout')
            ->assertUnauthorized();
    }

    /**
     * Authentication messages follow the configured French language.
     */
    public function test_authentication_messages_render_in_french(): void
    {
        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $user = User::factory()->create([
            'email' => 'french-auth@example.test',
            'password' => 'correct-password',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'french-auth@example.test',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.email.0',
                'Les identifiants fournis sont incorrects.'
            );

        $this->postJson('/api/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.email.0',
                'Le champ email est obligatoire.'
            )
            ->assertJsonPath(
                'errors.password.0',
                'Le champ password est obligatoire.'
            );

        $token = $user
            ->createToken('French Client')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Déconnexion effectuée avec succès.'
            );
    }

}
