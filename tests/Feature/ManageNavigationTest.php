<?php

namespace Tests\Feature;

use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Support\Icons;
use Illuminate\Support\Facades\Blade;
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
            /*
             * V1.0.38: the activity monitor and the financial journal became
             * the two tabs of Audit, so the group holds two links rather
             * than three. Audit is gated by view_activity_log — the tab it
             * opens on; the journal tab declares view_financial_journal
             * inside the page and the server enforces both per route.
             */
            '/audit' => 'view_activity_log',
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
                'capability="'.$capability.'"',
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

        $this->assertMatchesRegularExpression(
            '/\bhidden\b/',
            $anchor,
            'The console link needs the hidden attribute.'
        );

        /*
         * The attribute is enough on its own now. It used not to be: a
         * component's display rule is un-layered and beat [hidden], so the
         * old markup carried Tailwind's hidden class as well. The component
         * honours the attribute explicitly instead, and THAT rule is what
         * this assertion protects — without it the console link would be
         * visible to every customer between first paint and the
         * /api/auth/me response that hides it again.
         */
        $this->assertStringContainsString(
            '.pm-nav-item[hidden]',
            file_get_contents(
                resource_path('css/components.css')
            ),
            'The navigation component must honour the hidden attribute.'
        );
    }

    /**
     * The layout declares; the component emits. Everything the sidebar tests
     * above read out of the Blade source has to survive rendering, and until
     * the links became a component nothing checked that it did.
     */
    public function test_the_component_renders_what_the_layout_declares(): void
    {
        $html = Blade::render(
            '<x-nav-item'
            .' href="/audit"'
            .' icon="clock-rewind"'
            .' label="navigation.activity_log"'
            .' capability="view_activity_log" />'
        );

        $this->assertStringContainsString(
            'href="/audit"',
            $html
        );

        $this->assertStringContainsString(
            'data-requires-capability="view_activity_log"',
            $html,
            'The declared capability must reach the DOM for permissions.js.'
        );

        $this->assertStringContainsString(
            'data-i18n="navigation.activity_log"',
            $html,
            'The declared label must reach the DOM for the language switch.'
        );

        $this->assertStringContainsString(
            'pm-nav-item',
            $html
        );

        /*
         * The icon comes from resources/icons/untitled-ui.json rather than
         * from an <svg> pasted into the layout — there were twenty-one of
         * those in this file alone.
         */
        $this->assertStringContainsString(
            '<svg',
            $html
        );

        $this->assertStringContainsString(
            'aria-hidden="true"',
            $html,
            'An icon beside its own label is noise to a screen reader.'
        );
    }

    public function test_the_current_page_is_marked_for_assistive_technology(): void
    {
        $this->get('/dashboard');

        $current = Blade::render(
            '<x-nav-item href="/dashboard" icon="grid-01"'
            .' label="navigation.dashboard" />'
        );

        $this->assertStringContainsString(
            'aria-current="page"',
            $current
        );

        $this->assertStringContainsString(
            'pm-nav-active',
            $current
        );

        $other = Blade::render(
            '<x-nav-item href="/reports" icon="bar-chart-square"'
            .' label="navigation.reports" />'
        );

        $this->assertStringNotContainsString(
            'aria-current',
            $other
        );
    }

    /**
     * Every icon the sidebar asks for has to exist. An unknown name throws
     * in local and testing and renders nothing in production, so without
     * this a typo would ship as a silently missing icon.
     */
    public function test_every_icon_the_sidebar_names_exists(): void
    {
        preg_match_all(
            '/icon="([a-z0-9-]+)"/',
            $this->layout(),
            $matches
        );

        $this->assertNotEmpty($matches[1]);

        foreach (array_unique($matches[1]) as $name) {
            $this->assertTrue(
                Icons::has($name),
                $name.' is not in resources/icons/untitled-ui.json.'
            );
        }
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
     * Return the markup of the link element pointing at one href.
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

        /*
         * Sidebar links are <x-nav-item> elements rather than hand-written
         * anchors: eleven copies of the same twelve utility classes, the
         * same active-state ternary and the same inline <svg> became one
         * component. A plain <a> is still recognised, so this helper keeps
         * working for anything outside the navigation.
         */
        $before =
            substr(
                $layout,
                0,
                $link
            );

        $start = false;

        foreach (['<x-nav-item', '<a'] as $opening) {
            $at =
                strrpos(
                    $before,
                    $opening
                );

            if ($at !== false && ($start === false || $at > $start)) {
                $start = $at;
            }
        }

        $this->assertNotFalse(
            $start,
            'No link element found for '.$href
        );

        /*
         * A self-closing component ends at its own '>'. An anchor runs to
         * its closing tag. Take whichever arrives first, so the markup
         * returned never swallows the link that follows.
         */
        $end =
            $start + strcspn(
                $layout,
                '>',
                $start
            );

        $closing =
            strpos(
                $layout,
                '</a>',
                $start
            );

        if ($closing !== false && $closing < $end) {
            $end = $closing;
        }

        return substr(
            $layout,
            $start,
            $end - $start
        );
    }
}
