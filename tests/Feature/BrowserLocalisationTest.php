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

}
