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
            \App\Models\User::factory()
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
            ->assertSee(
                'data-i18n="leases.property_unit"',
                false
            )
            ->assertSee(
                'data-i18n="leases.monthly_rent"',
                false
            )
            ->assertSee(
                'data-i18n="leases.advance_payment"',
                false
            )
            ->assertSee(
                'data-i18n="leases.rent_increment"',
                false
            )
            ->assertSee(
                'data-i18n="leases.security_closeout"',
                false
            )
            ->assertSee(
                'data-i18n="leases.tenant_money"',
                false
            )
            ->assertSee(
                'data-i18n-aria-label="leases.close"',
                false
            )
            ->assertSeeText(
                'Lease Register'
            );
    }


    public function test_payments_exposes_translation_hooks(): void
    {
        $response = $this->get('/payments');

        $response
            ->assertOk()
            ->assertSee(
                'data-i18n="payments.heading"',
                false
            )
            ->assertSee(
                'data-i18n="payments.received_this_month"',
                false
            )
            ->assertSee(
                'data-i18n="payments.register"',
                false
            )
            ->assertSee(
                'data-i18n="payments.payment_source"',
                false
            )
            ->assertSee(
                'data-i18n="payments.record_payment"',
                false
            )
            ->assertSee(
                'data-i18n="payments.payment_details"',
                false
            )
            ->assertSee(
                'data-i18n="payments.manage_funds"',
                false
            )
            ->assertSee(
                'data-i18n="payments.classify_remaining_money"',
                false
            )
            ->assertSee(
                'data-i18n="payments.allocate_funds"',
                false
            )
            ->assertSee(
                'data-i18n-placeholder="payments.search_party_placeholder"',
                false
            )
            ->assertSee(
                'data-i18n-aria-label="payments.close"',
                false
            )
            ->assertSee(
                'data-currency-display',
                false
            )
            ->assertSeeText(
                'Payment Register'
            );
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
                'data-i18n="owners.record_property_expense"',
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
            ]
            as $expected
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
            ]
            as $expected
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
