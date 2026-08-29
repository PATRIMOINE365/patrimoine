<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\UserInvitationMail;
use App\Mail\UserPasswordResetMail;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Services\UserInvitationService;
use App\Services\UserPasswordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * V1.0.3 localisation completion acceptance tests.
 *
 * These tests freeze the English/French presentation contract introduced by
 * User Management, RBAC, password workflows and Activity Log without
 * translating internal role/action/entity codes.
 */
class V103LocalisationCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_v103_server_catalogues_have_matching_english_and_french_keys(): void
    {
        foreach ([
            'api.auth.account_disabled',
            'api.auth.setup_required',
            'api.auth.invalid_credentials',
            'api.auth.unauthenticated',
            'api.auth.forbidden',

            'api.user_management.cannot_change_own_role',
            'api.user_management.cannot_disable_self',
            'api.user_management.cannot_delete_self',
            'api.user_management.last_active_administrator',
            'api.user_management.created',
            'api.user_management.updated',
            'api.user_management.deleted',

            'api.user_invitation.accepted',
            'api.user_invitation.resent',
            'api.user_invitation.inactive_user',
            'api.user_invitation.invalid',

            'api.password.reset_requested',
            'api.password.administrator_reset_requested',
            'api.password.reset_complete',
            'api.password.changed',
            'api.password.invalid_reset',
            'api.password.current_incorrect',
            'api.password.account_disabled',
        ] as $key) {
            $this->assertTranslatedInBothLanguages($key);
        }
    }

    public function test_v103_email_catalogues_have_matching_english_and_french_keys(): void
    {
        foreach ([
            'emails.user_invitation.subject',
            'emails.user_invitation.title',
            'emails.user_invitation.greeting',
            'emails.user_invitation.introduction',
            'emails.user_invitation.action',
            'emails.user_invitation.expiry',
            'emails.user_invitation.ignore',

            'emails.password_reset.subject',
            'emails.password_reset.title',
            'emails.password_reset.greeting',
            'emails.password_reset.introduction',
            'emails.password_reset.action',
            'emails.password_reset.expiry',
            'emails.password_reset.ignore',
        ] as $key) {
            $this->assertTranslatedInBothLanguages($key);
        }
    }

    public function test_v103_activity_log_catalogues_have_matching_leaf_keys(): void
    {
        $english = require lang_path('en/activity_log.php');
        $french = require lang_path('fr/activity_log.php');

        $this->assertSame(
            array_keys($this->flatten($english)),
            array_keys($this->flatten($french))
        );

        $this->assertNotEmpty(
            $this->flatten($english)
        );
    }

    public function test_v103_browser_catalogue_contains_each_required_key_in_both_languages(): void
    {
        $translations = file_get_contents(
            resource_path('js/translations.js')
        );

        foreach ([
            'navigation.users',
            'navigation.activity_log',

            'roles.administrator',
            'roles.property_manager',
            'roles.viewer',

            'users.title',
            'users.heading',
            'users.role',
            'users.status',
            'users.active',
            'users.inactive',
            'users.invitation_pending',
            'users.resend_invitation',
            'users.send_password_reset',
            'users.delete',
            'users.delete_confirmation',
            'users.unable_delete',

            'password.forgot_link',
            'password.forgot_heading',
            'password.reset_heading',
            'password.invitation_heading',
            'password.change_heading',
            'password.confirmation_mismatch',
            'password.request_failed',

            'activity_log.title',
            'activity_log.heading',
            'activity_log.search',
            'activity_log.role',
            'activity_log.action',
            'activity_log.entity_type',
            'activity_log.view_details',
            'activity_log.before_values',
            'activity_log.after_values',
            'activity_log.snapshot',
            'activity_log.metadata',
            'activity_log.export_pdf',
            'activity_log.export_csv',
            'activity_log.unable_export',
        ] as $key) {
            $this->assertSame(
                2,
                substr_count(
                    $translations,
                    "'{$key}':"
                ),
                "Expected {$key} exactly once in each browser catalogue."
            );
        }
    }

    public function test_disabled_account_message_follows_french_organisation_language(): void
    {
        $this->useFrench();

        $user = User::factory()->create([
            'email' => 'disabled-fr@example.test',
            'password' => 'Password123!',
            'is_active' => false,
            'email_verified_at' => now(),
        ]);

        $this
            ->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'Password123!',
            ])
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Ce compte a été désactivé.',
            ]);
    }

    public function test_user_administration_safeguard_follows_french_organisation_language(): void
    {
        $this->useFrench();

        $administrator = User::factory()->create([
            'role' => UserRole::Administrator,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($administrator);

        $this
            ->patchJson(
                "/api/users/{$administrator->id}",
                [
                    'name' => $administrator->name,
                    'email' => $administrator->email,
                    'phone' => $administrator->phone,
                    'role' => UserRole::Viewer->value,
                    'is_active' => true,
                ]
            )
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Le rôle d’un administrateur doit être modifié par un autre administrateur.',
            ]);
    }

    public function test_invitation_email_renders_french_content(): void
    {
        Mail::fake();

        $this->useFrench();

        $user = User::factory()->create([
            'name' => 'Jean Dupont',
            'email_verified_at' => null,
        ]);

        app(UserInvitationService::class)->send($user);

        Mail::assertSent(
            UserInvitationMail::class,
            function (UserInvitationMail $mail): bool {
                $this->assertSame('fr', $mail->locale);

                $rendered = $mail->render();

                $this->assertStringContainsString(
                    'Définir mon mot de passe',
                    $rendered
                );

                $this->assertStringContainsString(
                    'Ce lien expire dans 24 heures.',
                    $rendered
                );

                return true;
            }
        );
    }

    public function test_password_reset_email_renders_french_content(): void
    {
        Mail::fake();

        $this->useFrench();

        $user = User::factory()->create([
            'email' => 'reset-fr@example.test',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        app(UserPasswordService::class)
            ->sendResetLink($user->email);

        Mail::assertSent(
            UserPasswordResetMail::class,
            function (UserPasswordResetMail $mail): bool {
                $this->assertSame('fr', $mail->locale);

                $rendered = $mail->render();

                $this->assertStringContainsString(
                    'Réinitialiser mon mot de passe',
                    $rendered
                );

                $this->assertStringContainsString(
                    'Ce lien expire dans 24 heures.',
                    $rendered
                );

                return true;
            }
        );
    }

    private function assertTranslatedInBothLanguages(
        string $key
    ): void {
        foreach (['en', 'fr'] as $locale) {
            app()->setLocale($locale);

            $value = __($key);

            $this->assertNotSame(
                $key,
                $value,
                "Missing {$locale} translation for {$key}."
            );

            $this->assertNotSame(
                '',
                trim((string) $value),
                "Empty {$locale} translation for {$key}."
            );
        }
    }

    private function useFrench(): void
    {
        ApplicationSetting::query()->delete();

        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        app()->setLocale('fr');
    }

    /**
     * Flatten a nested translation catalogue while preserving key order.
     */
    private function flatten(
        array $values,
        string $prefix = ''
    ): array {
        $flattened = [];

        foreach ($values as $key => $value) {
            $path =
                $prefix === ''
                    ? (string) $key
                    : "{$prefix}.{$key}";

            if (is_array($value)) {
                $flattened +=
                    $this->flatten(
                        $value,
                        $path
                    );

                continue;
            }

            $flattened[$path] =
                $value;
        }

        return $flattened;
    }
}
