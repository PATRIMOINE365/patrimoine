<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\UserInvitationMail;
use App\Mail\UserPasswordResetMail;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\UserInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Freeze the V1.0.3 invitation and password security workflows.
 */
class UserPasswordWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_expiry_is_exactly_twenty_four_hours(): void
    {
        $this->assertSame(
            1440,
            config('auth.passwords.users.expire')
        );
    }

    public function test_creating_user_sends_invitation(): void
    {
        Mail::fake();

        Sanctum::actingAs(
            $this->administrator()
        );

        $this
            ->postJson('/api/users', [
                'name' => 'Invited User',
                'email' => 'invited@example.test',
                'role' => UserRole::Viewer->value,
            ])
            ->assertCreated();

        $user = User::query()
            ->where('email', 'invited@example.test')
            ->firstOrFail();

        $this->assertNull(
            $user->email_verified_at
        );

        $this->assertDatabaseCount(
            'user_invitations',
            1
        );

        Mail::assertSent(
            UserInvitationMail::class,
            fn (UserInvitationMail $mail): bool => $mail->hasTo('invited@example.test')
        );
    }

    public function test_invitation_token_is_stored_only_as_hash(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        app(UserInvitationService::class)
            ->send($user);

        $invitation =
            UserInvitation::query()->firstOrFail();

        $this->assertSame(
            64,
            strlen($invitation->token_hash)
        );

        $this->assertDoesNotMatchRegularExpression(
            '/token=[A-Za-z0-9]{64}/',
            $invitation->token_hash
        );
    }

    public function test_resend_invalidates_previous_invitation(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $service =
            app(UserInvitationService::class);

        $first = $service->send($user);

        $second = $service->send($user);

        $this->assertNotNull(
            $first->fresh()->invalidated_at
        );

        $this->assertNull(
            $second->fresh()->invalidated_at
        );
    }

    public function test_administrator_can_resend_invitation(): void
    {
        Mail::fake();

        $administrator =
            $this->administrator();

        $target = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Sanctum::actingAs($administrator);

        $this
            ->postJson(
                "/api/users/{$target->id}/resend-invitation"
            )
            ->assertOk();

        Mail::assertSent(
            UserInvitationMail::class
        );
    }

    public function test_non_administrator_cannot_resend_invitation(): void
    {
        Mail::fake();

        $actor = User::factory()->create([
            'role' => UserRole::PropertyManager,
        ]);

        $target = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Sanctum::actingAs($actor);

        $this
            ->postJson(
                "/api/users/{$target->id}/resend-invitation"
            )
            ->assertForbidden();

        Mail::assertNothingOutgoing();
    }

    public function test_invitation_can_establish_password(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'setup@example.test',
            'email_verified_at' => null,
        ]);

        app(UserInvitationService::class)
            ->send($user);

        $mail = $this->invitationMail();

        $token =
            $this->queryValue(
                $mail->invitationUrl,
                'token'
            );

        $this
            ->postJson(
                '/api/auth/invitations/accept',
                [
                    'email' => $user->email,
                    'token' => $token,
                    'password' => 'NewPassword123!',
                    'password_confirmation' => 'NewPassword123!',
                ]
            )
            ->assertOk();

        $user->refresh();

        $this->assertTrue(
            Hash::check(
                'NewPassword123!',
                $user->password
            )
        );

        $this->assertNotNull(
            $user->email_verified_at
        );

        $this->assertNotNull(
            UserInvitation::query()
                ->firstOrFail()
                ->accepted_at
        );
    }

    public function test_invitation_is_single_use(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'single@example.test',
            'email_verified_at' => null,
        ]);

        app(UserInvitationService::class)
            ->send($user);

        $mail = $this->invitationMail();

        $token =
            $this->queryValue(
                $mail->invitationUrl,
                'token'
            );

        $payload = [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ];

        $this
            ->postJson(
                '/api/auth/invitations/accept',
                $payload
            )
            ->assertOk();

        $this
            ->postJson(
                '/api/auth/invitations/accept',
                $payload
            )
            ->assertUnprocessable();
    }

    public function test_expired_invitation_is_rejected(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'expired@example.test',
            'email_verified_at' => null,
        ]);

        app(UserInvitationService::class)
            ->send($user);

        $mail = $this->invitationMail();

        $token =
            $this->queryValue(
                $mail->invitationUrl,
                'token'
            );

        UserInvitation::query()->update([
            'expires_at' => now()->subSecond(),
        ]);

        $this
            ->postJson(
                '/api/auth/invitations/accept',
                [
                    'email' => $user->email,
                    'token' => $token,
                    'password' => 'NewPassword123!',
                    'password_confirmation' => 'NewPassword123!',
                ]
            )
            ->assertUnprocessable();
    }

    public function test_old_invitation_link_fails_after_resend(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'resend@example.test',
            'email_verified_at' => null,
        ]);

        $service =
            app(UserInvitationService::class);

        $service->send($user);

        $firstMail =
            $this->invitationMail();

        $oldToken =
            $this->queryValue(
                $firstMail->invitationUrl,
                'token'
            );

        Mail::fake();

        $service->send($user);

        $this
            ->postJson(
                '/api/auth/invitations/accept',
                [
                    'email' => $user->email,
                    'token' => $oldToken,
                    'password' => 'NewPassword123!',
                    'password_confirmation' => 'NewPassword123!',
                ]
            )
            ->assertUnprocessable();
    }

    public function test_forgot_password_does_not_reveal_unknown_account(): void
    {
        Mail::fake();

        $this
            ->postJson(
                '/api/auth/forgot-password',
                [
                    'email' => 'unknown@example.test',
                ]
            )
            ->assertOk();

        Mail::assertNothingOutgoing();
    }

    public function test_forgot_password_sends_reset_for_active_verified_user(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'reset@example.test',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this
            ->postJson(
                '/api/auth/forgot-password',
                [
                    'email' => 'reset@example.test',
                ]
            )
            ->assertOk();

        Mail::assertSent(
            UserPasswordResetMail::class
        );
    }

    public function test_password_reset_changes_password_and_revokes_tokens(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'resetme@example.test',
            'password' => 'OldPassword123!',
            'email_verified_at' => now(),
        ]);

        $user->createToken('existing');

        $this
            ->postJson(
                '/api/auth/forgot-password',
                [
                    'email' => $user->email,
                ]
            )
            ->assertOk();

        $mail = $this->passwordResetMail();

        $token =
            $this->queryValue(
                $mail->resetUrl,
                'token'
            );

        $this
            ->postJson(
                '/api/auth/reset-password',
                [
                    'email' => $user->email,
                    'token' => $token,
                    'password' => 'ResetPassword123!',
                    'password_confirmation' => 'ResetPassword123!',
                ]
            )
            ->assertOk();

        $user->refresh();

        $this->assertTrue(
            Hash::check(
                'ResetPassword123!',
                $user->password
            )
        );

        $this->assertCount(
            0,
            $user->tokens
        );
    }

    public function test_expired_password_reset_token_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'oldtoken@example.test',
            'email_verified_at' => now(),
        ]);

        $token =
            Password::broker()->createToken($user);

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update([
                'created_at' => now()->subHours(25),
            ]);

        $this
            ->postJson(
                '/api/auth/reset-password',
                [
                    'email' => $user->email,
                    'token' => $token,
                    'password' => 'ResetPassword123!',
                    'password_confirmation' => 'ResetPassword123!',
                ]
            )
            ->assertUnprocessable();
    }

    public function test_authenticated_user_can_change_own_password(): void
    {
        $user = User::factory()->create([
            'password' => 'OldPassword123!',
        ]);

        Sanctum::actingAs($user);

        $this
            ->postJson(
                '/api/auth/change-password',
                [
                    'current_password' => 'OldPassword123!',
                    'password' => 'NewPassword123!',
                    'password_confirmation' => 'NewPassword123!',
                ]
            )
            ->assertOk();

        $this->assertTrue(
            Hash::check(
                'NewPassword123!',
                $user->fresh()->password
            )
        );
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'OldPassword123!',
        ]);

        Sanctum::actingAs($user);

        $this
            ->postJson(
                '/api/auth/change-password',
                [
                    'current_password' => 'WrongPassword!',
                    'password' => 'NewPassword123!',
                    'password_confirmation' => 'NewPassword123!',
                ]
            )
            ->assertUnprocessable();
    }

    public function test_disabled_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'disabled@example.test',
            'password' => 'Password123!',
            'is_active' => false,
            'email_verified_at' => now(),
        ]);

        $this
            ->postJson(
                '/api/auth/login',
                [
                    'email' => $user->email,
                    'password' => 'Password123!',
                ]
            )
            ->assertUnprocessable();
    }

    public function test_unaccepted_invited_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'pending@example.test',
            'password' => 'UnknownInternalPassword123!',
            'is_active' => true,
            'email_verified_at' => null,
        ]);

        $this
            ->postJson(
                '/api/auth/login',
                [
                    'email' => $user->email,
                    'password' => 'UnknownInternalPassword123!',
                ]
            )
            ->assertUnprocessable();
    }

    public function test_disabling_user_revokes_existing_tokens(): void
    {
        $administrator =
            $this->administrator();

        $target = User::factory()->create([
            'role' => UserRole::Viewer,
            'is_active' => true,
        ]);

        $target->createToken('existing');

        Sanctum::actingAs($administrator);

        $this
            ->patchJson(
                "/api/users/{$target->id}",
                [
                    'is_active' => false,
                ]
            )
            ->assertOk();

        $this->assertCount(
            0,
            $target->fresh()->tokens
        );
    }

    public function test_administrator_can_initiate_reset_without_setting_password(): void
    {
        Mail::fake();

        $administrator =
            $this->administrator();

        $target = User::factory()->create([
            'email' => 'managed@example.test',
            'email_verified_at' => now(),
        ]);

        $originalPassword =
            $target->password;

        Sanctum::actingAs($administrator);

        $this
            ->postJson(
                "/api/users/{$target->id}/password-reset"
            )
            ->assertOk();

        $this->assertSame(
            $originalPassword,
            $target->fresh()->password
        );

        Mail::assertSent(
            UserPasswordResetMail::class
        );
    }

    public function test_invitation_email_uses_french_application_language(): void
    {
        Mail::fake();

        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        app(UserInvitationService::class)
            ->send($user);

        Mail::assertSent(
            UserInvitationMail::class,
            fn (UserInvitationMail $mail): bool => $mail->locale === 'fr'
        );
    }

    public function test_v102_upgrade_user_remains_login_eligible(): void
    {
        /*
         * V1.0.2 operational accounts were already real authenticated users.
         * The V1.0.3 upgrade must not reinterpret them as pending invitations.
         */
        $user = User::factory()->create([
            'email' => 'existing@example.test',
            'password' => 'ExistingPassword123!',
            'role' => UserRole::Administrator,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this
            ->postJson(
                '/api/auth/login',
                [
                    'email' => $user->email,
                    'password' => 'ExistingPassword123!',
                ]
            )
            ->assertOk();
    }

    /**
     * Return the invitation mail captured by Mail::fake().
     */
    private function invitationMail(): UserInvitationMail
    {
        $mail = null;

        Mail::assertSent(
            UserInvitationMail::class,
            function (
                UserInvitationMail $sent
            ) use (&$mail): bool {
                $mail = $sent;

                return true;
            }
        );

        $this->assertInstanceOf(
            UserInvitationMail::class,
            $mail
        );

        return $mail;
    }

    /**
     * Return the reset mail captured by Mail::fake().
     */
    private function passwordResetMail(): UserPasswordResetMail
    {
        $mail = null;

        Mail::assertSent(
            UserPasswordResetMail::class,
            function (
                UserPasswordResetMail $sent
            ) use (&$mail): bool {
                $mail = $sent;

                return true;
            }
        );

        $this->assertInstanceOf(
            UserPasswordResetMail::class,
            $mail
        );

        return $mail;
    }

    /**
     * Extract one query-string value from a generated browser URL.
     */
    private function queryValue(
        string $url,
        string $key
    ): string {
        parse_str(
            (string) parse_url(
                $url,
                PHP_URL_QUERY
            ),
            $query
        );

        $this->assertArrayHasKey(
            $key,
            $query
        );

        return (string) $query[$key];
    }

    /**
     * Create an active Administrator.
     */
    private function administrator(): User
    {
        return User::factory()->create([
            'role' => UserRole::Administrator,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
