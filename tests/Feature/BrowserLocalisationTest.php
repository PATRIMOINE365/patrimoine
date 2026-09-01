<?php

namespace Tests\Feature;

use App\Models\User;
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

    public function test_properties_exposes_translation_hooks(): void
    {
        $this
            ->get('/properties')
            ->assertOk()
            ->assertSee(
                'data-i18n-title="properties.title"',
                false
            )
            ->assertSee(
                'data-i18n="properties.heading"',
                false
            )
            ->assertSee(
                'data-i18n="properties.add_property"',
                false
            )
            ->assertSee(
                'data-i18n-placeholder="properties.search_placeholder"',
                false
            )
            ->assertSee(
                'data-i18n="properties.ownership"',
                false
            )
            ->assertSee(
                'data-i18n="properties.owner_type"',
                false
            )
            ->assertSee(
                'data-i18n="properties.unit_name_number"',
                false
            )
            ->assertSee(
                'data-i18n-aria-label="properties.close"',
                false
            )
            ->assertSeeText(
                'Property Portfolio'
            );
    }

    public function test_parties_exposes_translation_hooks(): void
    {
        $this
            ->get('/parties')
            ->assertOk()
            ->assertSee(
                'data-i18n-title="parties.title"',
                false
            )
            ->assertSee(
                'data-i18n="parties.heading"',
                false
            )
            ->assertSee(
                'data-i18n="parties.add_party"',
                false
            )
            ->assertSee(
                'data-i18n-placeholder="parties.search_placeholder"',
                false
            )
            ->assertSee(
                'data-i18n="parties.party_type"',
                false
            )
            ->assertSee(
                'data-i18n="parties.roles"',
                false
            )
            ->assertSee(
                'data-i18n="parties.banking_details"',
                false
            )
            ->assertSee(
                'data-i18n-aria-label="parties.close"',
                false
            )
            ->assertSeeText(
                'Party Directory'
            );
    }

    public function test_leases_exposes_translation_hooks(): void
    {
        $user =
            User::factory()
                ->create();

        $this
            ->actingAs($user)
            ->get('/leases')
            ->assertOk()
            ->assertSee(
                'data-i18n-title="leases.title"',
                false
            )
            ->assertSee(
                'data-i18n="leases.heading"',
                false
            )
            ->assertSee(
                'data-i18n="leases.register"',
                false
            )
            /*
             * V1.0.45: the hooks that used to be checked here belonged to
             * the Add lease drawer, which is retired - Add lease opens the
             * assistant now. What is left on the page is the register, the
             * drawers that act on a letting that already exists, and the
             * link out to the assistant.
             */
            ->assertSee(
                'data-i18n="leases.monthly_rent"',
                false
            )
            ->assertSee(
                'data-i18n="leases.rent_increment"',
                false
            )
            ->assertSee(
                'data-i18n="leases.add_lease"',
                false
            )
            ->assertSeeText(
                'Lease Register'
            );
    }

    /**
     * V1.0.29: the guided wizard is a page like any other and must be
     * translatable end to end, French included.
     */
    public function test_lease_wizard_exposes_translation_hooks(): void
    {
        $user =
            User::factory()
                ->create();

        $response = $this
            ->actingAs($user)
            ->get('/leases/wizard')
            ->assertOk();

        foreach (
            [
                'data-i18n-title="wizard.title"',
                'data-i18n="wizard.heading"',
                'data-i18n="wizard.step1_title"',

                /*
                 * V1.0.43: the assistant is the lease drawer paginated,
                 * so it is eight pages rather than ten. The last one is
                 * still the review.
                 */
                'data-i18n="wizard.step8_title"',
                'data-i18n="wizard.ownership"',
                'data-i18n="wizard.notes"',
                'data-i18n="wizard.consumable_advance"',
                'data-i18n="wizard.glossary_lease_term"',
                'data-i18n="wizard.duration_open"',
                'data-i18n="wizard.advance_received"',
                'data-i18n="wizard.fee_vat"',
                'data-i18n="wizard.create_activate"',
            ] as $hook
        ) {
            $response->assertSee($hook, false);
        }
    }

    public function test_owners_exposes_translation_hooks(): void
    {
        $response = $this->get('/owners');

        $response
            ->assertOk()
            ->assertSee(
                'data-i18n="owners.heading"',
                false
            )
            ->assertSee(
                'data-i18n="owners.property_owners"',
                false
            )
            ->assertSee(
                'data-i18n="owners.current_balance"',
                false
            )
            ->assertSee(
                'data-i18n="owners.owner_ledger"',
                false
            )
            ->assertSee(
                'data-i18n="owners.payout_history"',
                false
            )
            ->assertSee(
                'data-i18n="owners.record_owner_deposit"',
                false
            )
            ->assertSee(
                'data-i18n="owners.billing_mode"',
                false
            )
            ->assertSee(
                'data-i18n="owners.make_owner_payout"',
                false
            )
            ->assertSee(
                'data-i18n="owners.owner_account_adjustment"',
                false
            )
            ->assertSee(
                'data-i18n-placeholder="owners.search_placeholder"',
                false
            )
            ->assertSee(
                'data-i18n-aria-label="owners.close"',
                false
            )
            ->assertSee(
                'data-currency-display',
                false
            )
            ->assertSeeText(
                'Property Owners'
            );
    }

    public function test_tenants_exposes_translation_hooks(): void
    {
        $response = $this->get('/tenants');

        $response
            ->assertOk()
            ->assertSee(
                'data-i18n="tenants.heading"',
                false
            )
            ->assertSee(
                'data-i18n="tenants.directory"',
                false
            )
            ->assertSee(
                'data-i18n="tenants.search"',
                false
            )
            ->assertSee(
                'data-i18n-placeholder="tenants.search_placeholder"',
                false
            )
            ->assertSee(
                'data-i18n="tenants.loading"',
                false
            )
            ->assertSee(
                'data-i18n="tenants.select_tenant"',
                false
            )
            ->assertSeeText(
                'Tenants'
            );
    }

    /**
     * Protect against accidentally placing French application-page keys
     * inside the English browser catalogue.
     */
    public function test_browser_translation_catalogues_keep_languages_separate(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/translations.js'
                )
            );

        $this->assertIsString(
            $source
        );

        $englishStart =
            strpos(
                $source,
                "    en: {\n"
            );

        $frenchStart =
            strpos(
                $source,
                "    fr: {\n"
            );

        $this->assertNotFalse(
            $englishStart
        );

        $this->assertNotFalse(
            $frenchStart
        );

        $this->assertLessThan(
            $frenchStart,
            $englishStart
        );

        $english =
            substr(
                $source,
                $englishStart,
                $frenchStart
                - $englishStart
            );

        $french =
            substr(
                $source,
                $frenchStart
            );

        foreach (
            [
                "'parties.heading': 'Parties'",
                "'leases.heading': 'Leases'",
                "'payments.heading': 'Payments'",
                "'owners.heading': 'Owners'",
                "'tenants.heading': 'Tenants'",
                "'reports.heading': 'Reports'",
            ] as $expected
        ) {
            $this->assertStringContainsString(
                $expected,
                $english
            );
        }

        foreach (
            [
                "'leases.heading': 'Baux'",
                "'payments.heading': 'Paiements'",
                "'owners.heading': 'Propriétaires'",
                "'tenants.heading': 'Locataires'",
                "'reports.heading': 'Rapports'",
            ] as $expected
        ) {
            $this->assertStringNotContainsString(
                $expected,
                $english
            );

            $this->assertStringContainsString(
                $expected,
                $french
            );
        }
    }

    /**
     * Browser interpolation supports the Laravel-style placeholders already
     * used throughout the Patrimoine translation catalogue.
     */
    public function test_browser_translation_interpolates_laravel_style_placeholders(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/translations.js'
                )
            );

        $this->assertIsString(
            $source
        );

        /*
         * translationFor() must support both the original brace-style
         * placeholders and Laravel-style colon placeholders used by the
         * existing browser catalogue.
         */
        $this->assertStringContainsString(
            '`{${name}}`',
            $source
        );

        $this->assertStringContainsString(
            '`:${name}`',
            $source
        );

        $this->assertGreaterThanOrEqual(
            2,
            substr_count(
                $source,
                '.replaceAll('
            )
        );

        $this->assertStringContainsString(
            "'tenants.pagination_tenants': ':total tenants'",
            $source
        );

        $this->assertStringContainsString(
            "'tenants.pagination_tenants': ':total locataires'",
            $source
        );
    }
}
