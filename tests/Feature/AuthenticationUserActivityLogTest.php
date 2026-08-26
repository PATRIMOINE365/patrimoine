<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\MfaCodeMail;
use App\Mail\UserInvitationMail;
use App\Mail\UserPasswordResetMail;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\UserInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Freeze V1.0.3 authentication and User Management Activity Log semantics.
 */
class AuthenticationUserActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_is_recorded_without_secrets(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'name' => 'Login User',
            'email' => 'login@example.test',
            'password' => 'Password123!',
            'email_verified_at' => now(),
        ]);

        $challenge = $this
            ->withServerVariables([
                'REMOTE_ADDR' => '192.0.2.10',
            ])
            ->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'Password123!',
                'device_name' => 'Activity Test',
            ]);

        $challenge->assertOk()
            ->assertJsonPath('mfa_required', true);

        $code = null;

        Mail::assertSent(
            MfaCodeMail::class,
            function (MfaCodeMail $mail) use (&$code): bool {
                $code = $mail->code;

                return true;
            }
        );

        $this
            ->withServerVariables([
                'REMOTE_ADDR' => '192.0.2.10',
            ])
            ->postJson('/api/auth/mfa/verify', [
                'challenge_token' => $challenge->json('challenge_token'),
                'code' => $code,
                'device_name' => 'Activity Test',
            ])
            ->assertOk();

        $event = ActivityLog::query()
            ->sole();

        $this->assertSame(
            'auth.login',
            $event->action
        );
        $this->assertSame(
            $user->id,
            $event->user_id
        );
        $this->assertSame(
            '192.0.2.10',
            $event->ip_address
        );
        $this->assertNull(
            $event->metadata
        );

        $encoded = json_encode(
            $event->toArray()
        );

        $this->assertStringNotContainsString(
            'Password123!',
            $encoded
        );
        $this->assertStringNotContainsString(
            'access_token',
            $encoded
        );
    }

    public function test_failed_login_is_recorded_without_credentials(): void
    {
        $user = User::factory()->create([
            'name' => 'Failed Login User',
            'email' => 'failed@example.test',
            'password' => 'CorrectPassword123!',
        ]);

        $this
            ->withServerVariables([
                'REMOTE_ADDR' => '198.51.100.25',
            ])
            ->postJson('/api/auth/login', [
                'email' => 'failed@example.test',
                'password' => 'WrongPassword123!',
            ])
            ->assertUnprocessable();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'auth.login_failed',
            $event->action
        );

        /*
         * A failed login has no authenticated actor. Even when the attempted
         * email belongs to a User, do not falsely attribute the attempt to
         * that User.
         */
        $this->assertNull(
            $event->user_id
        );

        $this->assertSame(
            'failed@example.test',
            $event->actor_email
        );

        $this->assertSame(
            'user',
            $event->entity_type
        );

        $this->assertSame(
            (string) $user->id,
            $event->entity_id
        );

        $this->assertSame(
            ['reason' => 'invalid_credentials'],
            $event->metadata
        );

        $this->assertSame(
            '198.51.100.25',
            $event->ip_address
        );

        $encoded = json_encode(
            $event->toArray()
        );

        $this->assertStringNotContainsString(
            'WrongPassword123!',
            $encoded
        );

        $this->assertStringNotContainsString(
            'CorrectPassword123!',
            $encoded
        );
    }

    public function test_failed_login_for_unknown_email_is_recorded_without_user_identity(): void
    {
        $this
            ->postJson('/api/auth/login', [
                'email' => 'unknown@example.test',
                'password' => 'AttemptedPassword123!',
            ])
            ->assertUnprocessable();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'auth.login_failed',
            $event->action
        );

        $this->assertNull(
            $event->user_id
        );

        $this->assertSame(
            'unknown@example.test',
            $event->actor_email
        );

        $this->assertNull(
            $event->entity_type
        );

        $this->assertNull(
            $event->entity_id
        );

        $this->assertSame(
            ['reason' => 'invalid_credentials'],
            $event->metadata
        );

        $this->assertStringNotContainsString(
            'AttemptedPassword123!',
            json_encode(
                $event->toArray()
            )
        );
    }

    public function test_disabled_account_login_failure_is_recorded(): void
    {
        $user = User::factory()->create([
            'email' => 'disabled-log@example.test',
            'password' => 'Password123!',
            'is_active' => false,
            'email_verified_at' => now(),
        ]);

        $this
            ->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'Password123!',
            ])
            ->assertUnprocessable();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'auth.login_failed',
            $event->action
        );

        $this->assertSame(
            ['reason' => 'account_disabled'],
            $event->metadata
        );
    }

    public function test_unaccepted_invitation_login_failure_is_recorded(): void
    {
        $user = User::factory()->create([
            'email' => 'pending-log@example.test',
            'password' => 'InternalPassword123!',
            'is_active' => true,
            'email_verified_at' => null,
        ]);

        $this
            ->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'InternalPassword123!',
            ])
            ->assertUnprocessable();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'auth.login_failed',
            $event->action
        );

        $this->assertSame(
            ['reason' => 'setup_required'],
            $event->metadata
        );
    }

    public function test_logout_is_recorded_once(): void
    {
        $user = User::factory()->create();

        $token = $user
            ->createToken('Activity Test')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'auth.logout',
            $event->action
        );
        $this->assertSame(
            $user->id,
            $event->user_id
        );
    }

    public function test_user_creation_and_initial_invitation_are_one_event(): void
    {
        Mail::fake();

        $administrator = $this->administrator();

        Sanctum::actingAs($administrator);

        $response = $this
            ->postJson('/api/users', [
                'name' => 'Created User',
                'email' => 'created@example.test',
                'role' => UserRole::Viewer->value,
            ])
            ->assertCreated();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'user.created',
            $event->action
        );
        $this->assertSame(
            (string) $response->json('id'),
            $event->entity_id
        );
        $this->assertSame(
            true,
            $event->metadata['invitation_sent']
        );
        $this->assertSame(
            'created@example.test',
            $event->snapshot['email']
        );

        Mail::assertSent(
            UserInvitationMail::class
        );
    }

    public function test_compound_user_update_is_one_changed_fields_event(): void
    {
        $administrator = $this->administrator();

        $target = User::factory()->create([
            'name' => 'Before',
            'email' => 'before@example.test',
            'phone' => null,
            'role' => UserRole::PropertyManager,
            'is_active' => true,
        ]);

        $target->createToken('Existing');

        Sanctum::actingAs($administrator);

        $this
            ->patchJson(
                "/api/users/{$target->id}",
                [
                    'name' => 'After',
                    'role' => UserRole::Viewer->value,
                    'is_active' => false,
                ]
            )
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'user.updated',
            $event->action
        );

        $this->assertSame(
            [
                'name' => 'Before',
                'role' => UserRole::PropertyManager->value,
                'is_active' => true,
            ],
            $event->before_values
        );

        $this->assertSame(
            [
                'name' => 'After',
                'role' => UserRole::Viewer->value,
                'is_active' => false,
            ],
            $event->after_values
        );

        $this->assertCount(
            0,
            $target->fresh()->tokens
        );
    }

    public function test_no_op_user_update_is_not_recorded(): void
    {
        $administrator = $this->administrator();

        $target = User::factory()->create([
            'name' => 'Same Name',
            'role' => UserRole::Viewer,
            'is_active' => true,
        ]);

        Sanctum::actingAs($administrator);

        $this
            ->patchJson(
                "/api/users/{$target->id}",
                [
                    'name' => 'Same Name',
                    'role' => UserRole::Viewer->value,
                    'is_active' => true,
                ]
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    public function test_user_deletion_preserves_target_snapshot(): void
    {
        $administrator = $this->administrator();

        $target = User::factory()->create([
            'name' => 'Deleted User',
            'email' => 'deleted@example.test',
            'role' => UserRole::Viewer,
        ]);

        $targetId = $target->id;

        Sanctum::actingAs($administrator);

        $this
            ->deleteJson(
                "/api/users/{$targetId}"
            )
            ->assertNoContent();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'user.deleted',
            $event->action
        );
        $this->assertSame(
            (string) $targetId,
            $event->entity_id
        );
        $this->assertSame(
            'Deleted User',
            $event->entity_label
        );
        $this->assertSame(
            'deleted@example.test',
            $event->snapshot['email']
        );
        $this->assertSame(
            $administrator->id,
            $event->user_id
        );
    }

    public function test_invitation_resend_is_recorded_once(): void
    {
        Mail::fake();

        $administrator = $this->administrator();

        $target = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Sanctum::actingAs($administrator);

        $this
            ->postJson(
                "/api/users/{$target->id}/resend-invitation"
            )
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'user.invitation_resent',
            $event->action
        );
    }

    public function test_administrator_password_reset_request_is_recorded(): void
    {
        Mail::fake();

        $administrator = $this->administrator();

        $target = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($administrator);

        $this
            ->postJson(
                "/api/users/{$target->id}/password-reset"
            )
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'user.password_reset_requested',
            $event->action
        );
        $this->assertSame(
            (string) $target->id,
            $event->entity_id
        );

        Mail::assertSent(
            UserPasswordResetMail::class
        );
    }

    public function test_invitation_acceptance_is_recorded_without_token_or_password(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'accept@example.test',
            'email_verified_at' => null,
        ]);

        app(UserInvitationService::class)
            ->send($user);

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

        parse_str(
            (string) parse_url(
                $mail->invitationUrl,
                PHP_URL_QUERY
            ),
            $query
        );

        $token = (string) $query['token'];

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

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'auth.invitation_accepted',
            $event->action
        );

        $encoded = json_encode(
            $event->toArray()
        );

        $this->assertStringNotContainsString(
            $token,
            $encoded
        );
        $this->assertStringNotContainsString(
            'NewPassword123!',
            $encoded
        );
    }

    public function test_forgot_password_request_is_not_recorded(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'forgot@example.test',
            'email_verified_at' => now(),
        ]);

        $this
            ->postJson(
                '/api/auth/forgot-password',
                [
                    'email' => 'forgot@example.test',
                ]
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    public function test_successful_password_reset_is_recorded(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.test',
            'email_verified_at' => now(),
        ]);

        $this
            ->postJson(
                '/api/auth/forgot-password',
                [
                    'email' => $user->email,
                ]
            )
            ->assertOk();

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

        parse_str(
            (string) parse_url(
                $mail->resetUrl,
                PHP_URL_QUERY
            ),
            $query
        );

        $this
            ->postJson(
                '/api/auth/reset-password',
                [
                    'email' => $user->email,
                    'token' => (string) $query['token'],
                    'password' => 'ResetPassword123!',
                    'password_confirmation' => 'ResetPassword123!',
                ]
            )
            ->assertOk();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'auth.password_reset',
            $event->action
        );
    }

    public function test_own_password_change_is_recorded_once(): void
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

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'auth.password_changed',
            $event->action
        );
        $this->assertSame(
            $user->id,
            $event->user_id
        );
    }

    public function test_passive_user_views_are_not_recorded(): void
    {
        $administrator = $this->administrator();

        $target = User::factory()->create();

        Sanctum::actingAs($administrator);

        $this
            ->getJson('/api/users')
            ->assertOk();

        $this
            ->getJson(
                "/api/users/{$target->id}"
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    private function administrator(): User
    {
        return User::factory()->create([
            'role' => UserRole::Administrator,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
