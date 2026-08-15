<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Protect browser translation hooks and English compatibility content.
 */
class BrowserLocalisationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_exposes_translation_hooks(): void
    {
        $this
            ->get('/login')
            ->assertOk()
            ->assertSee(
                'data-i18n-title="login.title"',
                false
            )
            ->assertSee(
                'data-i18n="login.welcome"',
                false
            )
            ->assertSee(
                'data-i18n-placeholder="login.password_placeholder"',
                false
            )
            ->assertSeeText(
                'Welcome back'
            );
    }

    public function test_application_shell_exposes_translation_hooks(): void
    {
        $this
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(
                'data-i18n="navigation.dashboard"',
                false
            )
            ->assertSee(
                'data-i18n="navigation.properties"',
                false
            )
            ->assertSee(
                'data-i18n="navigation.tenants"',
                false
            )
            ->assertSee(
                'data-i18n="navigation.sign_out"',
                false
            )
            ->assertSeeText(
                'Dashboard'
            )
            ->assertSeeText(
                'Sign out'
            );
    }


    public function test_dashboard_exposes_translation_hooks(): void
    {
        $this
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(
                'data-i18n="dashboard.overview"',
                false
            )
            ->assertSee(
                'data-i18n="dashboard.heading"',
                false
            )
            ->assertSee(
                'data-i18n="dashboard.rent_due"',
                false
            )
            ->assertSee(
                'data-i18n="dashboard.overdue_rent"',
                false
            )
            ->assertSee(
                'data-i18n="dashboard.upcoming_rent"',
                false
            )
            ->assertSeeText(
                'Current portfolio and financial position.'
            );
    }



    public function test_settings_exposes_translation_hooks(): void
    {
        $this
            ->get('/settings')
            ->assertOk()
            ->assertSee(
                'data-i18n-title="settings.title"',
                false
            )
            ->assertSee(
                'data-i18n="settings.heading"',
                false
            )
            ->assertSee(
                'data-i18n="settings.language"',
                false
            )
            ->assertSee(
                'data-i18n="settings.currency"',
                false
            )
            ->assertSee(
                'data-i18n="settings.save"',
                false
            )
            ->assertSee(
                'data-i18n-field-help="settings.vat_help_text"',
                false
            )
            ->assertSee(
                'data-i18n-aria-label="settings.vat_help_label"',
                false
            )
            ->assertSeeText(
                'Save Organisation'
            );
    }

}
