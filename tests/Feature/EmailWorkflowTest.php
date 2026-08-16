<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Mail\ReceiptMail;
use App\Mail\RentReminderMail;
use App\Models\ApplicationSetting;
use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Verifies application-level email workflows.
 */
class EmailWorkflowTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Create a tenant Lease suitable for payment and reminder tests.
     *
     * @return array{
     *     lease: Lease,
     *     invoice: Invoice,
     *     tenant: Party
     * }
     */
    private function createContext(): array
    {
        $building = Building::create([
            'name' => 'Email Workflow Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Email Workflow Owner',
            'phone' => '0200001801',
            'email' => 'workflow-owner@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit W1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Workflow Tenant',
            'phone' => '0200001800',
            'email' => 'workflow-tenant@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'rent_amount' => 5000,
            'payment_frequency' => 'monthly',
            'due_day' => 1,
            'vat_rate' => 0,
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-WORKFLOW-001',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-10',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        return compact(
            'lease',
            'invoice',
            'tenant'
        );
    }

    /**
     * Creating a Payment automatically sends its receipt.
     */
    public function test_payment_creation_sends_receipt_email(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $response = $this->postJson(
            '/api/payments',
            [
                'lease_id' => $context['lease']->id,
                'amount' => 5000,
                'payment_date' => '2026-08-11',
                'payment_method' => 'bank_transfer',
                'reference' => 'AUTO-RECEIPT-001',
            ]
        );

        $response->assertCreated();

        Mail::assertSent(
            ReceiptMail::class,
            function (ReceiptMail $mail) use ($context): bool {
                return $mail->hasTo(
                    $context['tenant']->email
                );
            }
        );
    }

    /**
     * Invoice resend endpoint sends the current Invoice.
     */
    public function test_invoice_can_be_resent_through_api(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $this->postJson(
            "/api/invoices/{$context['invoice']->id}/send-email"
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Invoice email sent successfully.'
            );

        Mail::assertSent(
            InvoiceMail::class,
            function (InvoiceMail $mail) use ($context): bool {
                return $mail->invoice->is(
                    $context['invoice']
                )
                    && $mail->hasTo(
                        $context['tenant']->email
                    );
            }
        );
    }

    /**
     * Receipt resend endpoint sends an existing Payment receipt.
     */
    public function test_receipt_can_be_resent_through_api(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $paymentResponse = $this->postJson(
            '/api/payments',
            [
                'lease_id' => $context['lease']->id,
                'amount' => 2000,
                'payment_date' => '2026-08-11',
                'payment_method' => 'momo',
                'reference' => 'RESEND-RECEIPT-001',
            ]
        )->assertCreated();

        $paymentId = $paymentResponse->json('id');

        /*
         * Remove the automatic receipt from the fake's sent-mail history so
         * the assertion below verifies the resend endpoint specifically.
         */
        Mail::fake();

        $this->postJson(
            "/api/payments/{$paymentId}/send-receipt"
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Receipt email sent successfully.'
            );

        Mail::assertSent(
            ReceiptMail::class,
            1
        );
    }

    /**
     * Scheduled reminder command sends due and overdue Invoice reminders.
     */
    public function test_reminder_command_sends_due_invoice(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $this->artisan(
            'patrimoine:send-rent-reminders',
            [
                '--as-of' => '2026-08-11',
            ]
        )->assertExitCode(0);

        Mail::assertSent(
            RentReminderMail::class,
            function (RentReminderMail $mail) use ($context): bool {
                return $mail->invoice->is(
                    $context['invoice']
                )
                    && $mail->hasTo(
                        $context['tenant']->email
                    );
            }
        );

        /*
         * Background Activity Log exclusion: test_reminder_command_sends_due_invoice
         *
         * Scheduled rent reminders must not be represented as human Activity Log events.
         * Activity Log records meaningful human actions only.
         */
        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    /**
     * Rent reminder command must ignore Security Deposit debt invoices.
     *
     * Security Deposit close-out debt is collectible through the ordinary
     * tenant payment workflow but must never be presented as overdue rent.
     */
    public function test_reminder_command_skips_security_deposit_debt_invoice(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $context['invoice']->update([
            'type' => 'security_deposit_debt',
        ]);

        $this->artisan(
            'patrimoine:send-rent-reminders',
            [
                '--as-of' => '2026-08-11',
            ]
        )->assertExitCode(0);

        Mail::assertNothingSent();
    }

    /**
     * Reminder command ignores invoices not yet due.
     */
    public function test_reminder_command_skips_future_invoice(): void
    {
        Mail::fake();

        $context = $this->createContext();

        $context['invoice']->update([
            'due_date' => '2026-08-20',
        ]);

        $this->artisan(
            'patrimoine:send-rent-reminders',
            [
                '--as-of' => '2026-08-11',
            ]
        )->assertExitCode(0);

        Mail::assertNothingSent();
    }

    /**
     * Invalid reminder date is rejected.
     */
    public function test_reminder_command_rejects_invalid_as_of_date(): void
    {
        Mail::fake();

        $this->artisan(
            'patrimoine:send-rent-reminders',
            [
                '--as-of' => '2026-99-99',
            ]
        )
            ->expectsOutput(
                'The --as-of option must be a valid date in YYYY-MM-DD format.'
            )
            ->assertExitCode(1);

        Mail::assertNothingSent();
    }

    /**
     * Email resend success messages follow French.
     */
    public function test_email_resend_success_message_renders_in_french(): void
    {
        Mail::fake();

        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $context = $this->createContext();

        $this
            ->postJson(
                "/api/invoices/{$context['invoice']->id}/send-email"
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'L’e-mail de la facture a été envoyé avec succès.'
            );
    }

    /**
     * Email delivery business-rule failures follow the configured language.
     */
    public function test_missing_tenant_email_error_renders_in_french(): void
    {
        Mail::fake();

        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $context = $this->createContext();

        $context['tenant']->update([
            'email' => '',
        ]);

        $this
            ->postJson(
                "/api/invoices/{$context['invoice']->id}/send-email"
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.email.0',
                'Le locataire ne possède pas d’adresse e-mail.'
            );
    }
}
