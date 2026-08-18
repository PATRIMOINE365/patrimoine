<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Mail\ReceiptMail;
use App\Mail\RentReminderMail;
use App\Models\ApplicationSetting;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Unit;
use App\Services\Notifications\EmailDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Verifies Patrimoine financial email delivery.
 *
 * Mail::fake() prevents actual external delivery while still allowing us
 * to assert recipient, mailable type and PDF attachments.
 */
class EmailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build an Invoice and partially allocated Payment.
     *
     * @return array{
     *     invoice: Invoice,
     *     payment: Payment,
     *     tenant: Party,
     *     managingOrganisation: Party
     * }
     */
    private function createContext(): array
    {
        $building = Building::create([
            'name' => 'Email Test Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit E1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Email Test Tenant',
            'phone' => '0200001700',
            'email' => 'tenant-email@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'rent_amount' => 11800,
            'payment_frequency' => 'monthly',
            'due_day' => 1,
            'vat_rate' => 18,
            'status' => 'active',
        ]);

        /*
         * Configure the single-tenant business identity used on outgoing
         * Patrimoine correspondence.
         */
        $managingOrganisation = Party::create([
            'type' => 'organisation',
            'legal_name' => 'Email Management Limited',
            'address' => 'Accra, Ghana',
            'email' => 'accounts@email-management.example',
            'contact_person_name' => 'Accounts Manager',
            'contact_person_phone' => '0200001701',
            'contact_person_email' => 'manager@email-management.example',
        ]);

        PartyRole::create([
            'party_id' => $managingOrganisation->id,
            'role' => 'managing_organisation',
        ]);

        ApplicationSetting::create([
            'managing_organisation_party_id' => $managingOrganisation->id,
        ]);

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-EMAIL-000001',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-15',
            'status' => 'partial',
            'total_amount' => 11800,
            'vat_rate' => 18,
            'net_amount' => 10000,
            'vat_amount' => 1800,
        ]);

        $payment = Payment::create([
            'lease_id' => $lease->id,
            'amount' => 5000,
            'payment_date' => '2026-08-05',
            'payment_method' => 'bank_transfer',
            'reference' => 'EMAIL-PAYMENT-001',
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 5000,
        ]);

        return compact(
            'invoice',
            'payment',
            'tenant',
            'managingOrganisation'
        );
    }

    /**
     * Invoice email is sent to the Lease tenant with a PDF attachment.
     */
    public function test_invoice_email_can_be_sent(): void
    {
        Mail::fake();

        $context = $this->createContext();

        app(EmailDeliveryService::class)
            ->sendInvoice($context['invoice']);

        Mail::assertSent(
            InvoiceMail::class,
            function (InvoiceMail $mail) use ($context): bool {
                $attachments = $mail->attachments();

                return $mail->hasTo(
                    $context['tenant']->email
                )
                    && $mail->invoice->is(

                        $context['invoice']

                    )

                    && $mail->managingOrganisation?->is(

                        $context['managingOrganisation']

                    )

                    && str_contains(

                        $mail->envelope()->subject,

                        'Email Management Limited'

                    )
                    && count($attachments) === 1;
            }
        );
    }

    /**
     * Payment receipt email is sent to the tenant with its PDF receipt.
     */
    public function test_receipt_email_can_be_sent(): void
    {
        Mail::fake();

        $context = $this->createContext();

        app(EmailDeliveryService::class)
            ->sendReceipt($context['payment']);

        Mail::assertSent(
            ReceiptMail::class,
            function (ReceiptMail $mail) use ($context): bool {
                return $mail->hasTo(
                    $context['tenant']->email
                )
                    && $mail->payment->is(

                        $context['payment']

                    )

                    && $mail->managingOrganisation?->is(

                        $context['managingOrganisation']

                    )

                    && str_contains(

                        $mail->envelope()->subject,

                        'Email Management Limited'

                    )
                    && count($mail->attachments()) === 1;
            }
        );
    }

    /**
     * Rent reminder sends the outstanding Invoice to the tenant.
     */
    public function test_rent_reminder_can_be_sent(): void
    {
        Mail::fake();

        $context = $this->createContext();

        app(EmailDeliveryService::class)
            ->sendRentReminder(
                $context['invoice']
            );

        Mail::assertSent(
            RentReminderMail::class,
            function (RentReminderMail $mail) use ($context): bool {
                return $mail->hasTo(
                    $context['tenant']->email
                )
                    && $mail->invoice->is(

                        $context['invoice']

                    )

                    && $mail->managingOrganisation?->is(

                        $context['managingOrganisation']

                    )

                    && str_contains(

                        $mail->envelope()->subject,

                        'Email Management Limited'

                    )
                    && count($mail->attachments()) === 1;
            }
        );
    }

    public function test_invoice_email_renders_french_date_and_fcfa_currency(): void
    {
        Mail::fake();

        $context =
            $this->createContext();

        ApplicationSetting::query()
            ->firstOrFail()
            ->update([
                'language' => 'fr',
                'currency' => 'FCFA',
            ]);

        app(
            EmailDeliveryService::class
        )->sendInvoice(
            $context['invoice']
        );

        Mail::assertSent(
            InvoiceMail::class,
            function ($mail): bool {
                $previousLocale =
                    app()->getLocale();

                app()->setLocale('fr');

                try {
                    $html =
                        $mail->render();

                    $subject =
                        $mail->envelope()->subject;
                } finally {
                    app()->setLocale(
                        $previousLocale
                    );
                }

                return str_contains(
                    $subject,
                    'Facture INV-EMAIL-000001'
                )

                    && str_contains(
                        $html,
                        'Veuillez trouver ci-joint votre facture de loyer'
                    )
                    && str_contains(
                        $html,
                        'Montant de la facture'
                    )
                    && str_contains(
                        $html,
                        'Solde à payer'
                    )
                    && str_contains(
                        $html,
                        '11 800 FCFA'
                    )
                    && str_contains(
                        $html,
                        '15-08-2026'
                    );
            }
        );
    }

    /**
     * Receipt email localises prose and persisted payment-method codes.
     */
    public function test_receipt_email_renders_french_content(): void
    {
        Mail::fake();

        $context =
            $this->createContext();

        ApplicationSetting::query()
            ->firstOrFail()
            ->update([
                'language' => 'fr',
                'currency' => 'FCFA',
            ]);

        app(
            EmailDeliveryService::class
        )->sendReceipt(
            $context['payment']
        );

        Mail::assertSent(
            ReceiptMail::class,
            function (ReceiptMail $mail): bool {
                $previousLocale =
                    app()->getLocale();

                app()->setLocale('fr');

                try {
                    $html =
                        $mail->render();

                    $subject =
                        $mail->envelope()->subject;
                } finally {
                    app()->setLocale(
                        $previousLocale
                    );
                }

                return str_contains(
                    $subject,
                    'Reçu de paiement'
                )

                    && str_contains(
                        $html,
                        'Nous confirmons la réception de votre paiement'
                    )
                    && str_contains(
                        $html,
                        'Montant reçu'
                    )
                    && str_contains(
                        $html,
                        'Mode de paiement'
                    )
                    && str_contains(
                        $html,
                        'Virement bancaire'
                    )
                    && str_contains(
                        $html,
                        '5 000 FCFA'
                    )
                    && str_contains(
                        $html,
                        '05-08-2026'
                    );
            }
        );
    }

    /**
     * Rent reminder localises its subject and tenant-facing prose.
     */
    public function test_rent_reminder_renders_french_content(): void
    {
        Mail::fake();

        $context =
            $this->createContext();

        ApplicationSetting::query()
            ->firstOrFail()
            ->update([
                'language' => 'fr',
                'currency' => 'FCFA',
            ]);

        app(
            EmailDeliveryService::class
        )->sendRentReminder(
            $context['invoice']
        );

        Mail::assertSent(
            RentReminderMail::class,
            function (RentReminderMail $mail): bool {
                $previousLocale =
                    app()->getLocale();

                app()->setLocale('fr');

                try {
                    $html =
                        $mail->render();

                    $subject =
                        $mail->envelope()->subject;
                } finally {
                    app()->setLocale(
                        $previousLocale
                    );
                }

                return str_contains(
                    $subject,
                    'Rappel de loyer'
                )

                    && str_contains(
                        $html,
                        'Ceci est un rappel concernant la facture de loyer'
                    )
                    && str_contains(
                        $html,
                        'Solde à payer'
                    )
                    && str_contains(
                        $html,
                        'Selon nos registres'
                    )
                    && str_contains(
                        $html,
                        '6 800 FCFA'
                    )
                    && str_contains(
                        $html,
                        '15-08-2026'
                    );
            }
        );
    }
}
