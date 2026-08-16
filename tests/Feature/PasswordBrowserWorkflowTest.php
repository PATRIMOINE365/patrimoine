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

    public function test_authenticated_layout_exposes_change_password_to_all_roles(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/layouts/app.blade.php'
                )
            );

        $this->assertStringContainsString(
            'id="change-password-open"',
            $view
        );

        $this->assertStringContainsString(
            'id="change-password-form"',
            $view
        );

        /*
         * Changing one's own password is not an Administrator capability.
         */
        $this->assertStringNotContainsString(
            'id="change-password-open" data-requires',
            $view
        );
    }

    public function test_password_browser_module_is_wired_to_all_four_api_workflows(): void
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

        $this->assertStringContainsString(
            "from './password.js'",
            $app
        );

        foreach (
            [
                '/api/auth/forgot-password',
                '/api/auth/reset-password',
                '/api/auth/invitations/accept',
                '/api/auth/change-password',
            ] as $endpoint
        ) {
            $this->assertStringContainsString(
                $endpoint,
                $password
            );
        }

        $this->assertStringContainsString(
            'clearToken();',
            $password
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
