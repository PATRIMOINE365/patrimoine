<?php

namespace Tests\Feature;

use App\Enums\UserCapability;
use App\Enums\UserRole;
use Tests\TestCase;

/**
 * V1.0.30 moved the Manage group out of the bottom flyout menu and into the
 * sidebar itself, as plain links beside Workspace and Finance.
 *
 * These assertions freeze both halves of that move: the flyout is gone for
 * good, and every link it used to hold is still gated by exactly the
 * capability it was gated by before.
 *
 * V1.0.32 took Users and Licence out of the group. They are tabs of
 * Settings now, and Settings is gated by manage_settings exactly as it
 * always was, so the group shrank without any gate changing hands. The
 * paths still work: /users and /license redirect to the tabs that hold
 * them, which is asserted below rather than left to a browser to find out.
 */
class ManageNavigationTest extends TestCase
{
    /**
     * The links the Manage group holds, and the capability each declares.
     *
     * @return array<string, string>
     */
    private function manageLinks(): array
    {
        return [
            '/activity-log' => 'view_activity_log',
            '/financial-journal' => 'view_financial_journal',
            '/settings' => 'manage_settings',
        ];
    }

    private function layout(): string
    {
        return file_get_contents(
            resource_path(
                'views/layouts/app.blade.php'
            )
        );
    }

    public function test_every_manage_link_lives_inside_the_sidebar_navigation(): void
    {
        $layout =
            $this->layout();

        $navigation =
            $this->sidebarNavigation(
                $layout
            );

        foreach ($this->manageLinks() as $href => $capability) {
            $this->assertStringContainsString(
                'href="'.$href.'"',
                $navigation,
                $href.' should be a link in the sidebar navigation.'
            );
        }

        /*
         * The platform console entry is revealed by auth.js rather than by a
         * capability, so it is asserted separately.
         */
        $this->assertStringContainsString(
            'href="/admin"',
            $navigation
        );

        $this->assertStringContainsString(
            'data-i18n="navigation.manage"',
            $navigation
        );
    }

    public function test_each_manage_link_still_declares_its_capability(): void
    {
        $layout =
            $this->layout();

        foreach ($this->manageLinks() as $href => $capability) {
            $anchor =
                $this->anchorFor(
                    $layout,
                    $href
                );

            $this->assertStringContainsString(
                'data-requires-capability="'.$capability.'"',
                $anchor,
                $href.' must keep its '.$capability.' gate.'
            );
        }
    }

    public function test_the_platform_console_link_stays_hidden_until_auth_reveals_it(): void
    {
        $anchor =
            $this->anchorFor(
                $this->layout(),
                '/admin'
            );

        $this->assertStringContainsString(
            'data-platform-admin-only',
            $anchor
        );

        /*
         * Both are required. auth.js clears the attribute and the class, and
         * Tailwind's flex utility would defeat the attribute on its own.
         */
        $this->assertStringContainsString(
            "data-platform-admin-only\n                        hidden\n",
            $anchor,
            'The console link needs the hidden attribute.'
        );

        $this->assertStringContainsString(
            "class=\"\n                            hidden\n",
            $anchor,
            'The console link needs the hidden class.'
        );
    }

    public function test_the_manage_group_is_gated_for_the_whole_shell(): void
    {
        $layout =
            $this->layout();

        $this->assertStringContainsString(
            'rbac-hidden shell-admin-only',
            $layout,
            'The group wrapper keeps the shell gate the flyout used to carry.'
        );

        /*
         * Every capability behind the group is Administrator-only, which is
         * what lets one wrapper gate stand for all five links.
         */
        foreach ([
            UserCapability::ViewActivityLog,
            UserCapability::ViewFinancialJournal,
            UserCapability::ManageUsers,
            UserCapability::ManageSettings,
        ] as $capability) {
            $this->assertTrue(
                UserRole::Administrator->allows(
                    $capability
                )
            );

            $this->assertFalse(
                UserRole::PropertyManager->allows(
                    $capability
                )
            );

            $this->assertFalse(
                UserRole::Viewer->allows(
                    $capability
                )
            );
        }
    }

    public function test_users_and_licence_left_the_sidebar_for_settings(): void
    {
        $navigation =
            $this->sidebarNavigation(
                $this->layout()
            );

        $this->assertStringNotContainsString(
            'href="/users"',
            $navigation,
            'Users is a tab of Settings now, not a sidebar link.'
        );

        $this->assertStringNotContainsString(
            'href="/license"',
            $navigation,
            'Licence is a tab of Settings now, not a sidebar link.'
        );

        $settings = file_get_contents(
            resource_path('views/app/settings.blade.php')
        );

        foreach (['users', 'license'] as $tab) {
            $this->assertStringContainsString(
                'id="settings-tab-'.$tab.'"',
                $settings,
                'Settings should carry the '.$tab.' pill.'
            );

            $this->assertStringContainsString(
                'id="settings-'.$tab.'-panel"',
                $settings,
                'Settings should carry the '.$tab.' panel.'
            );
        }
    }

    public function test_the_old_paths_still_reach_the_tabs_that_hold_them(): void
    {
        /*
         * Redirects rather than removals: links printed on documents, in
         * old e-mails and in anybody's bookmarks predate the move.
         */
        $this->get('/users')
            ->assertRedirect('/settings#users');

        $this->get('/license')
            ->assertRedirect('/settings#license');
    }

    public function test_the_flyout_menu_is_gone_from_every_layer(): void
    {
        $this->assertStringNotContainsString(
            'sidebar-manage-toggle',
            $this->layout()
        );

        $this->assertStringNotContainsString(
            'sidebar-manage-menu',
            $this->layout()
        );

        $this->assertStringNotContainsString(
            'pm-sidebar-manage',
            file_get_contents(
                resource_path(
                    'css/app.css'
                )
            ),
            'The flyout stylesheet should have been removed with it.'
        );

        $this->assertStringNotContainsString(
            'sidebar-manage',
            file_get_contents(
                resource_path(
                    'js/auth.js'
                )
            ),
            'The flyout open/close handlers should have been removed with it.'
        );
    }

    public function test_the_retired_description_lines_are_gone_from_both_languages(): void
    {
        $retired = [
            'activity_log_description',
            'financial_journal_description',
            'users_description',
            'settings_description',
            'license_description',
        ];

        $translations =
            file_get_contents(
                resource_path(
                    'js/translations.js'
                )
            );

        foreach (['en', 'fr'] as $locale) {
            $navigation =
                trans(
                    'ui.navigation',
                    [],
                    $locale
                );

            foreach ($retired as $key) {
                $this->assertArrayNotHasKey(
                    $key,
                    $navigation,
                    $key.' is no longer rendered and should not be carried.'
                );
            }

            /*
             * The console entry used to be hardcoded English. It is a real
             * translated string now, so it must exist in both catalogues.
             */
            $this->assertArrayHasKey(
                'platform_console',
                $navigation
            );
        }

        foreach ($retired as $key) {
            $this->assertStringNotContainsString(
                'navigation.'.$key,
                $translations
            );
        }

        $this->assertSame(
            2,
            substr_count(
                $translations,
                "'navigation.platform_console'"
            ),
            'The console label must be mirrored to both halves of translations.js.'
        );
    }

    /**
     * Return the sidebar's <nav> element only.
     */
    private function sidebarNavigation(string $layout): string
    {
        $start =
            strpos(
                $layout,
                '<nav'
            );

        $end =
            strpos(
                $layout,
                '</nav>'
            );

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr(
            $layout,
            $start,
            $end - $start
        );
    }

    /**
     * Return the markup of the anchor pointing at one href.
     */
    private function anchorFor(string $layout, string $href): string
    {
        $link =
            strpos(
                $layout,
                'href="'.$href.'"'
            );

        $this->assertNotFalse(
            $link,
            'No link found for '.$href
        );

        $anchor =
            strrpos(
                substr(
                    $layout,
                    0,
                    $link
                ),
                '<a'
            );

        $this->assertNotFalse(
            $anchor,
            'No anchor found for '.$href
        );

        $end =
            strpos(
                $layout,
                '</a>',
                $anchor
            );

        return substr(
            $layout,
            $anchor,
            $end - $anchor
        );
    }
}
