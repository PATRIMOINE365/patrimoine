<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Mail\ReceiptMail;
use App\Models\ApplicationSetting;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Payment;
use App\Models\Unit;
use App\Services\Notifications\EmailDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * V1.0.29: what Patrimoine sends to Parties, and what it withholds.
 *
 * Two controls decide it — the organisation-wide switch in Settings and
 * each Party's own policy — and this test pins the full matrix, both
 * through the delivery service and through the API an operator clicks.
 */
class PartyEmailSuppressionTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    /**
     * A tenant, a lease, an issued invoice, a payment and the managing
     * organisation that signs the correspondence.
     *
     * @return array{
     *     invoice: Invoice,
     *     payment: Payment,
     *     tenant: Party,
     *     settings: ApplicationSetting
     * }
     */
    private function createContext(): array
    {
        $building = Building::create([
            'name' => 'Suppression Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit S1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Silenced Tenant',
            'phone' => '0200002100',
            'email' => 'silenced-tenant@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'rent_amount' => 10000,
            'payment_frequency' => 'monthly',
            'due_day' => 1,
            'vat_rate' => 0,
            'status' => 'active',
        ]);

        $managingOrganisation = Party::create([
            'type' => 'organisation',
            'legal_name' => 'Suppression Management Limited',
            'address' => 'Accra, Ghana',
            'email' => 'accounts@suppression.example',
            'contact_person_name' => 'Accounts Manager',
            'contact_person_phone' => '0200002101',
            'contact_person_email' => 'manager@suppression.example',
        ]);

        PartyRole::create([
            'party_id' => $managingOrganisation->id,
            'role' => 'managing_organisation',
        ]);

        $settings = ApplicationSetting::create([
            'managing_organisation_party_id' => $managingOrganisation->id,
        ]);

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-SUP-000001',
            'type' => 'rent',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-15',
            'status' => 'issued',
            'total_amount' => 10000,
            'vat_rate' => 0,
            'net_amount' => 10000,
            'vat_amount' => 0,
        ]);

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => 4000,
            'payment_date' => '2026-08-05',
            'payment_method' => 'cash',
            'reference' => 'SUP-PAYMENT-001',
        ]);

        return compact(
            'invoice',
            'payment',
            'tenant',
            'settings'
        );
    }

    /**
     * Switch the organisation's party emails off.
     */
    private function silenceOrganisation(
        ApplicationSetting $settings
    ): void {
        $settings->update([
            'party_emails_enabled' => false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | The policy matrix
    |--------------------------------------------------------------------------
    */

    /**
     * Sending is the default: nothing about an untouched installation
     * changes when the columns are added.
     */
    public function test_invoice_is_sent_by_default(): void
    {
        Mail::fake();

        $context = $this->createContext();

        app(EmailDeliveryService::class)
            ->sendInvoice($context['invoice']);

        Mail::assertSent(InvoiceMail::class);
    }

    /**
     * The organisation switch silences an ordinary Party.
     */
    public function test_organisation_switch_withholds_the_invoice(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $this->silenceOrganisation(
            $context['settings']
        );

        $this->expectException(
            \App\Exceptions\PartyEmailSuppressedException::class
        );

        try {
            app(EmailDeliveryService::class)
                ->sendInvoice($context['invoice']);
        } finally {
            Mail::assertNothingSent();
        }
    }

    /**
     * A Party marked `always` keeps receiving mail while the rest of the
     * organisation is silent — the whitelist case.
     */
    public function test_party_marked_always_still_receives_mail(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $this->silenceOrganisation(
            $context['settings']
        );

        $context['tenant']->update([
            'email_policy' => 'always',
        ]);

        app(EmailDeliveryService::class)
            ->sendInvoice(
                $context['invoice']->fresh()
            );

        Mail::assertSent(InvoiceMail::class);
    }

    /**
     * A Party marked `never` is excluded while the organisation is
     * otherwise sending — the single difficult tenant.
     */
    public function test_party_marked_never_is_excluded(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $context['tenant']->update([
            'email_policy' => 'never',
        ]);

        $this->expectException(
            \App\Exceptions\PartyEmailSuppressedException::class
        );

        try {
            app(EmailDeliveryService::class)
                ->sendInvoice(
                    $context['invoice']->fresh()
                );
        } finally {
            Mail::assertNothingSent();
        }
    }

    /**
     * Receipts follow the same rule as invoices.
     */
    public function test_receipt_is_withheld(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $this->silenceOrganisation(
            $context['settings']
        );

        $this->expectException(
            \App\Exceptions\PartyEmailSuppressedException::class
        );

        try {
            app(EmailDeliveryService::class)
                ->sendReceipt($context['payment']);
        } finally {
            Mail::assertNothingSent();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Consequences of a withheld message
    |--------------------------------------------------------------------------
    */

    /**
     * A message that was never sent must not be charged against the
     * plan's monthly email allowance.
     */
    public function test_withheld_mail_is_not_counted_against_the_allowance(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $this->silenceOrganisation(
            $context['settings']
        );

        try {
            app(EmailDeliveryService::class)
                ->sendInvoice($context['invoice']);
        } catch (\App\Exceptions\PartyEmailSuppressedException) {
            // expected
        }

        $this->assertDatabaseCount(
            'organisation_email_counters',
            0
        );
    }

    /**
     * The activity log records what was withheld, and why.
     */
    public function test_withheld_document_is_recorded_in_the_activity_log(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $context['tenant']->update([
            'email_policy' => 'never',
        ]);

        try {
            app(EmailDeliveryService::class)
                ->sendInvoice(
                    $context['invoice']->fresh()
                );
        } catch (\App\Exceptions\PartyEmailSuppressedException) {
            // expected
        }

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' => 'email.suppressed',
                'entity_type' => 'party',
                'entity_id' => (string) $context['tenant']->id,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | What the operator sees
    |--------------------------------------------------------------------------
    */

    /**
     * Clicking Send on a silenced Party explains itself rather than
     * failing silently or pretending to have sent something.
     */
    public function test_manual_resend_is_refused_with_an_explanation(): void
    {
        Mail::fake();

        $this->authenticateApiUser('administrator');

        $context = $this->createContext();

        $this->silenceOrganisation(
            $context['settings']
        );

        $this
            ->postJson(
                '/api/invoices/'.$context['invoice']->id.'/send-email'
            )
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.email.0',
                'Emails to parties are switched off in your organisation settings, so nothing was sent.'
            );

        Mail::assertNothingSent();
    }

    /**
     * An individually excluded Party gets its own explanation, so the
     * operator knows where to go and change it.
     */
    public function test_manual_resend_names_the_party_exclusion(): void
    {
        Mail::fake();

        $this->authenticateApiUser('administrator');

        $context = $this->createContext();

        $context['tenant']->update([
            'email_policy' => 'never',
        ]);

        $this
            ->postJson(
                '/api/invoices/'.$context['invoice']->id.'/send-email'
            )
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.email.0',
                'This party is excluded from Patrimoine emails, so nothing was sent.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    */

    /**
     * The switch is saved from Settings and read back.
     */
    public function test_organisation_switch_is_configurable(): void
    {
        $this->authenticateApiUser('administrator');

        $context = $this->createContext();

        $this
            ->putJson(
                '/api/managing-organisation',
                [
                    'legal_name' => 'Suppression Management Limited',
                    'address' => 'Accra, Ghana',
                    'phone' => '0200002101',
                    'email' => 'accounts@suppression.example',
                    'contact_person_name' => 'Accounts Manager',
                    'contact_person_phone' => '0200002101',
                    'contact_person_email' => 'manager@suppression.example',
                    'default_vat_rate' => 0,
                    'language' => 'en',
                    'currency' => 'GHS',
                    'party_emails_enabled' => false,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'party_emails_enabled',
                false
            );

        $this->assertFalse(
            (bool) $context['settings']
                ->fresh()
                ->party_emails_enabled
        );
    }

    /**
     * An older client that does not know the field leaves it alone
     * rather than silently switching emails back on.
     */
    public function test_settings_update_without_the_field_preserves_it(): void
    {
        $this->authenticateApiUser('administrator');

        $context = $this->createContext();

        $this->silenceOrganisation(
            $context['settings']
        );

        $this
            ->putJson(
                '/api/managing-organisation',
                [
                    'legal_name' => 'Suppression Management Limited',
                    'address' => 'Accra, Ghana',
                    'phone' => '0200002101',
                    'email' => 'accounts@suppression.example',
                    'contact_person_name' => 'Accounts Manager',
                    'contact_person_phone' => '0200002101',
                    'contact_person_email' => 'manager@suppression.example',
                    'default_vat_rate' => 0,
                    'language' => 'en',
                    'currency' => 'GHS',
                ]
            )
            ->assertOk();

        $this->assertFalse(
            (bool) $context['settings']
                ->fresh()
                ->party_emails_enabled
        );
    }

    /**
     * Every workspace can see whether the organisation is sending, so it
     * can say so before anybody clicks Send.
     */
    public function test_presentation_configuration_exposes_the_switch(): void
    {
        $context = $this->createContext();

        $this->silenceOrganisation(
            $context['settings']
        );

        $this->authenticateApiUser('viewer');

        $this
            ->getJson('/api/presentation-config')
            ->assertOk()
            ->assertJsonPath(
                'party_emails_enabled',
                false
            );
    }

    /**
     * The nightly reminder sweep treats a silenced tenant as a
     * configuration, not a failure: it skips, stays green, and does not
     * write one activity entry per invoice.
     */
    public function test_scheduled_reminders_skip_a_silenced_tenant(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $this->silenceOrganisation(
            $context['settings']
        );

        $this
            ->artisan(
                'patrimoine:send-rent-reminders',
                [
                    '--as-of' => '2026-08-16',
                ]
            )
            ->assertExitCode(0);

        Mail::assertNothingSent();

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' => 'email.suppressed',
            ]
        );

        $this->assertDatabaseCount(
            'organisation_email_counters',
            0
        );
    }

    /**
     * The same sweep still emails a tenant who was individually allowed
     * while the organisation is otherwise silent.
     */
    public function test_scheduled_reminders_still_reach_an_allowed_tenant(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $this->silenceOrganisation(
            $context['settings']
        );

        $context['tenant']->update([
            'email_policy' => 'always',
        ]);

        $this
            ->artisan(
                'patrimoine:send-rent-reminders',
                [
                    '--as-of' => '2026-08-16',
                ]
            )
            ->assertExitCode(0);

        Mail::assertSent(
            \App\Mail\RentReminderMail::class
        );
    }

    /**
     * A Party's own policy is set through the ordinary Party form.
     */
    public function test_party_policy_is_editable(): void
    {
        $this->authenticateApiUser('administrator');

        $response = $this->postJson(
            '/api/parties',
            [
                'type' => 'person',
                'given_names' => 'Excluded',
                'surname' => 'Owner',
                'phone' => '0200002102',
                'email' => 'excluded-owner@example.test',
                'email_policy' => 'never',
                'roles' => ['owner'],
            ]
        );

        $response->assertCreated();

        $party = Party::query()
            ->where('email', 'excluded-owner@example.test')
            ->firstOrFail();

        $this->assertSame(
            'never',
            $party->email_policy
        );

        $this
            ->putJson(
                '/api/parties/'.$party->id,
                [
                    'type' => 'person',
                    'given_names' => 'Excluded',
                    'surname' => 'Owner',
                    'phone' => '0200002102',
                    'email' => 'excluded-owner@example.test',
                    'email_policy' => 'always',
                    'roles' => ['owner'],
                ]
            )
            ->assertOk();

        $this->assertSame(
            'always',
            $party->fresh()->email_policy
        );
    }

    /**
     * An unknown policy value is refused rather than stored.
     */
    public function test_party_policy_is_validated(): void
    {
        $this->authenticateApiUser('administrator');

        $this
            ->postJson(
                '/api/parties',
                [
                    'type' => 'person',
                    'given_names' => 'Bad',
                    'surname' => 'Policy',
                    'phone' => '0200002103',
                    'email' => 'bad-policy@example.test',
                    'email_policy' => 'sometimes',
                    'roles' => ['tenant'],
                ]
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors('email_policy');
    }
}
