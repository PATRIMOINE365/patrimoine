<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Freeze the browser integration surface for V1.0.3 User Management.
 *
 * API authorization is covered separately by UserManagementApiTest and
 * UserAuthorizationTest. These assertions protect the Blade/JS/i18n wiring.
 */
class UserManagementBrowserTest extends TestCase
{
    public function test_user_management_page_is_available(): void
    {
        $this
            ->get('/users')
            ->assertOk()
            ->assertSee(
                'id="users-workspace"',
                false
            )
            ->assertSee(
                'data-requires-role="administrator"',
                false
            )
            ->assertSee(
                'id="add-user-button"',
                false
            )
            ->assertSee(
                'id="users-list"',
                false
            )
            ->assertSee(
                'id="user-modal"',
                false
            );
    }

    public function test_layout_contains_hidden_administrator_user_navigation(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/layouts/app.blade.php'
                )
            );

        $this->assertStringContainsString(
            'href="/users"',
            $view
        );

        $this->assertStringContainsString(
            'data-requires-capability="manage_users"',
            $view
        );

        $this->assertStringContainsString(
            'data-i18n="navigation.users"',
            $view
        );
    }

    public function test_user_management_module_is_wired_into_browser_bundle(): void
    {
        $app =
            file_get_contents(
                resource_path(
                    'js/app.js'
                )
            );

        $users =
            file_get_contents(
                resource_path(
                    'js/users.js'
                )
            );

        $this->assertStringContainsString(
            "from './users.js'",
            $app
        );

        $this->assertStringContainsString(
            'await initializeUsers();',
            $app
        );

        $this->assertStringContainsString(
            '/api/users',
            $users
        );

        $this->assertStringContainsString(
            '/resend-invitation',
            $users
        );

        $this->assertStringContainsString(
            '/password-reset',
            $users
        );
    }

    public function test_browser_shell_applies_authenticated_role_visibility(): void
    {
        $auth =
            file_get_contents(
                resource_path(
                    'js/auth.js'
                )
            );

        $this->assertStringContainsString(
            'currentUserRole',
            $auth
        );

        $this->assertStringContainsString(
            'initializeBrowserAuthorization',
            $auth
        );

        $this->assertStringContainsString(
            'roles.${role}',
            $auth
        );
    }

    public function test_user_management_translations_exist_in_both_languages(): void
    {
        $translations =
            file_get_contents(
                resource_path(
                    'js/translations.js'
                )
            );

        /*
         * Each key must occur once in English and once in French.
         */
        foreach (
            [
                "'navigation.users':",
                "'roles.administrator':",
                "'roles.property_manager':",
                "'roles.viewer':",
                "'users.heading':",
                "'users.resend_invitation':",
                "'users.send_password_reset':",
                "'users.delete_confirmation':",
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

        $this->assertStringContainsString(
            "'User Management'",
            $translations
        );

        $this->assertStringContainsString(
            "'Gestion des utilisateurs'",
            $translations
        );

        $this->assertStringContainsString(
            "'Gestionnaire immobilier'",
            $translations
        );
    }
}
