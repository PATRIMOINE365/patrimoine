<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Freeze the V1.0.3 browser password ownership workflow.
 */
class PasswordBrowserWorkflowTest extends TestCase
{
    public function test_public_password_pages_are_available(): void
    {
        $this
            ->get('/forgot-password')
            ->assertOk()
            ->assertSee(
                'id="forgot-password-form"',
                false
            );

        $this
            ->get(
                '/reset-password?token=test&email=user@example.test'
            )
            ->assertOk()
            ->assertSee(
                'id="reset-password-form"',
                false
            );

        $this
            ->get(
                '/invitation?token=test&email=user@example.test'
            )
            ->assertOk()
            ->assertSee(
                'id="invitation-form"',
                false
            );
    }

    public function test_login_exposes_forgot_password_link(): void
    {
        $this
            ->get('/login')
            ->assertOk()
            ->assertSee(
                'href="/forgot-password"',
                false
            )
            ->assertSee(
                'data-i18n="password.forgot_link"',
                false
            );
    }

    public function test_authenticated_layout_exposes_profile_password_change_to_all_roles(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/layouts/app.blade.php'
                )
            );

        /*
         * V1.0.4 moved self-service password management into My Profile.
         *
         * My Profile is available to every authenticated user regardless of
         * application role.
         */
        $this->assertStringContainsString(
            'id="my-profile-open"',
            $view
        );

        $this->assertStringContainsString(
            'id="profile-modal"',
            $view
        );

        $this->assertStringContainsString(
            'id="profile-form"',
            $view
        );

        $this->assertStringContainsString(
            'id="profile-new-password"',
            $view
        );

        $this->assertStringContainsString(
            'id="profile-current-password"',
            $view
        );

        /*
         * Changing one's own password is not an Administrator capability.
         * The profile entry therefore must not be capability-gated.
         */
        $this->assertStringNotContainsString(
            'id="my-profile-open" data-requires',
            $view
        );

        /*
         * V1.0.4 no longer exposes a separate Change Password action in the
         * authenticated user menu.
         */
        $this->assertStringNotContainsString(
            'id="change-password-open"',
            $view
        );
    }

    public function test_password_browser_module_is_wired_to_all_api_workflows(): void
    {
        $app =
            file_get_contents(
                resource_path(
                    'js/app.js'
                )
            );

        $password =
            file_get_contents(
                resource_path(
                    'js/password.js'
                )
            );

        $auth =
            file_get_contents(
                resource_path(
                    'js/auth.js'
                )
            );

        $this->assertStringContainsString(
            "from './password.js'",
            $app
        );

        /*
         * password.js owns the three public (unauthenticated) workflows.
         */
        foreach (
            [
                '/api/auth/forgot-password',
                '/api/auth/reset-password',
                '/api/auth/invitations/accept',
            ] as $endpoint
        ) {
            $this->assertStringContainsString(
                $endpoint,
                $password
            );
        }

        /*
         * V1.0.6 removed the orphaned standalone Change Password dialog.
         * The authenticated user's own password change lives inline in the
         * profile drawer (auth.js) and submits through /api/auth/me.
         */
        $this->assertStringContainsString(
            '/api/auth/me',
            $auth
        );

        $this->assertStringContainsString(
            "'profile-current-password'",
            $auth
        );

        $this->assertStringContainsString(
            "'profile-new-password'",
            $auth
        );

        $this->assertStringContainsString(
            'clearToken();',
            $auth
        );
    }

    public function test_password_browser_translations_exist_in_both_languages(): void
    {
        $translations =
            file_get_contents(
                resource_path(
                    'js/translations.js'
                )
            );

        foreach (
            [
                "'password.forgot_link':",
                "'password.send_reset':",
                "'password.reset_action':",
                "'password.set_password':",
                "'password.change_action':",
                "'password.current_password':",
                "'password.confirm_password':",
                "'login.missing_api_token':",
            ] as $key
        ) {
            $this->assertSame(
                2,
                substr_count(
                    $translations,
                    $key
                ),
                "Expected {$key} in both browser catalogues."
            );
        }
    }
}
