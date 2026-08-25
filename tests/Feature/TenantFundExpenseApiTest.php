<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\TenantFundExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * V1.0.8: tenant expenses are recorded as unpaid EXP- Invoices and
 * settled explicitly from tenant fund accounts through the Invoice
 * account-payment flow, which can also be cancelled again.
 */
class TenantFundExpenseApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Build a leased unit with one funded tenant fund account and a
     * 100% Building owner (owner entitlement requires ownership).
     *
     * @return array{
     *     lease: Lease,
     *     account: TenantFundAccount,
     *     ownerAccount: OwnerAccount
     * }
     */
    private function fundedLease(
        string $type = 'consumable_advance',
        int $amount = 10000
    ): array {
        $building = Building::create([
            'name' => 'Tenant Expense Building '.uniqid(),
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Tenant Expense Owner',
            'phone' => '0200008000',
            'email' => 'expense-owner-'.uniqid().'@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        $ownerAccount = OwnerAccount::query()
            ->where('party_id', $owner->id)
            ->firstOrFail();

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit TE-1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Tenant Expense Tenant',
            'phone' => '0200008001',
            'email' => 'tenant-expense-'.uniqid().'@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'active',
        ]);

        $account = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => $type,
            'status' => 'active',
        ]);

        if ($amount > 0) {
            TenantFundTransaction::create([
                'tenant_fund_account_id' => $account->id,
                'direction' => 'credit',
                'category' => match ($type) {
                    'rent_reserve' => 'reserve_funding',
                    'consumable_advance' => 'advance_funding',
                    'security_deposit' => 'deposit_funding',
                },
                'amount' => $amount,
                'transaction_date' => '2026-08-01',
            ]);
        }

        return compact(
            'lease',
            'account',
            'ownerAccount'
        );
    }

    /**
     * Record a standard two-line expense Invoice through the API.
     */
    private function recordExpenseInvoice(
        Lease $lease
    ): Invoice {
        $this->postJson(
            '/api/tenant-expense-invoices',
            [
                'lease_id' => $lease->id,
                'transaction_date' => '2026-08-25',
                'lines' => [
                    [
                        'description' => 'Plumbing repair billed to tenant.',
                        'amount' => 2500,
                    ],
                    [
                        'description' => 'Replacement lock set.',
                        'amount' => 1500,
                    ],
                ],
            ]
        )->assertCreated();

        return Invoice::query()
            ->with('lines')
            ->latest('id')
            ->firstOrFail();
    }

    public function test_expense_records_an_unpaid_exp_invoice(): void
    {
        Mail::fake();

        $context = $this->fundedLease();

        $response = $this->postJson(
            '/api/tenant-expense-invoices',
            [
                'lease_id' => $context['lease']->id,
                'transaction_date' => '2026-08-25',
                'reference' => 'Job card 118',
                'lines' => [
                    [
                        'description' => 'Plumbing repair billed to tenant.',
                        'amount' => 2500,
                    ],
                    [
                        'description' => 'Replacement lock set.',
                        'amount' => 1500,
                    ],
                ],
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('invoice.invoice_number', 'EXP-000001')
            ->assertJsonPath('invoice.total_amount', 4000)
            ->assertJsonPath('invoice.line_count', 2)
            ->assertJsonPath('email_sent', true);

        $invoice = Invoice::query()->firstOrFail();

        $this->assertSame('expense', $invoice->type);
        $this->assertSame('issued', $invoice->status);
        $this->assertSame(4000, $invoice->outstandingAmount());

        /*
         * Recording touches no fund account: the money leaves only
         * when the Invoice is explicitly paid.
         */
        $this->assertSame(
            10000,
            $context['account']->fresh()->balance()
        );

        Mail::assertSent(InvoiceMail::class);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'expense_invoice.created',
        ]);
    }

    public function test_expense_invoice_is_paid_and_cancelled(): void
    {
        Mail::fake();

        $context = $this->fundedLease();

        $invoice = $this->recordExpenseInvoice($context['lease']);

        /*
         * Partial payment from the funded account.
         */
        $paymentId = $this->postJson(
            "/api/invoices/{$invoice->id}/account-payments",
            [
                'tenant_fund_account_id' => $context['account']->id,
                'amount' => 2500,
                'transaction_date' => '2026-08-25',
            ]
        )
            ->assertCreated()
            ->assertJsonPath('invoice.status', 'partial')
            ->assertJsonPath('invoice.outstanding', 1500)
            ->json('payment.id');

        $this->assertSame(
            7500,
            $context['account']->fresh()->balance()
        );

        /*
         * Second payment settles the Invoice completely.
         */
        $this->postJson(
            "/api/invoices/{$invoice->id}/account-payments",
            [
                'tenant_fund_account_id' => $context['account']->id,
                'amount' => 1500,
                'transaction_date' => '2026-08-25',
            ]
        )
            ->assertCreated()
            ->assertJsonPath('invoice.status', 'paid')
            ->assertJsonPath('invoice.outstanding', 0);

        /*
         * Expense settlements never create owner rent entitlement.
         */
        $this->assertSame(
            0,
            $context['ownerAccount']->fresh()->balance()
        );

        /*
         * The payment receipt lists the active payments.
         */
        $this->get(
            "/api/invoices/{$invoice->id}/payment-receipt"
        )
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        /*
         * Cancel the first payment: balance restored, Invoice partial
         * again, cancellation is one-shot.
         */
        $this->postJson(
            "/api/invoice-account-payments/{$paymentId}/cancel",
            [
                'reason' => 'Recorded against the wrong invoice.',
            ]
        )
            ->assertOk()
            ->assertJsonPath('invoice.status', 'partial')
            ->assertJsonPath('invoice.outstanding', 2500);

        $this->assertSame(
            8500,
            $context['account']->fresh()->balance()
        );

        $this->postJson(
            "/api/invoice-account-payments/{$paymentId}/cancel",
            [
                'reason' => 'Duplicate cancel attempt.',
            ]
        )->assertUnprocessable();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'invoice_account_payment.recorded',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'invoice_account_payment.cancelled',
        ]);
    }

    public function test_expense_invoice_payment_can_never_overdraw_the_account(): void
    {
        Mail::fake();

        $context = $this->fundedLease(
            type: 'security_deposit',
            amount: 3000
        );

        $invoice = $this->recordExpenseInvoice($context['lease']);

        $this->postJson(
            "/api/invoices/{$invoice->id}/account-payments",
            [
                'tenant_fund_account_id' => $context['account']->id,
                'amount' => 3001,
                'transaction_date' => '2026-08-25',
            ]
        )->assertUnprocessable();

        $this->assertSame(
            3000,
            $context['account']->fresh()->balance()
        );
    }

    public function test_rent_invoice_is_paid_from_advance_and_cancelled(): void
    {
        $context = $this->fundedLease();

        $invoice = Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-EXP-TEST-01',
            'type' => 'rent',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        $paymentId = $this->postJson(
            "/api/invoices/{$invoice->id}/account-payments",
            [
                'tenant_fund_account_id' => $context['account']->id,
                'amount' => 5000,
                'transaction_date' => '2026-08-25',
            ]
        )
            ->assertCreated()
            ->assertJsonPath('invoice.status', 'paid')
            ->json('payment.id');

        /*
         * Consumed advance released owner rent entitlement, tagged to
         * its source payment.
         */
        $this->assertSame(
            5000,
            $context['ownerAccount']->fresh()->balance()
        );

        $this->assertDatabaseHas('owner_transactions', [
            'category' => 'rent_entitlement',
            'direction' => 'credit',
            'amount' => 5000,
            'tenant_fund_transaction_id' => $paymentId,
        ]);

        /*
         * Cancelling unwinds the fund debit AND the entitlement.
         */
        $this->postJson(
            "/api/invoice-account-payments/{$paymentId}/cancel",
            [
                'reason' => 'Tenant disputes the charge.',
            ]
        )
            ->assertOk()
            ->assertJsonPath('invoice.status', 'issued');

        $this->assertSame(
            10000,
            $context['account']->fresh()->balance()
        );

        $this->assertSame(
            0,
            $context['ownerAccount']->fresh()->balance()
        );
    }

    public function test_rent_reserve_cannot_pay_rent_before_notice(): void
    {
        $context = $this->fundedLease(
            type: 'rent_reserve'
        );

        $invoice = Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-EXP-TEST-02',
            'type' => 'rent',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        $this->postJson(
            "/api/invoices/{$invoice->id}/account-payments",
            [
                'tenant_fund_account_id' => $context['account']->id,
                'amount' => 5000,
                'transaction_date' => '2026-08-25',
            ]
        )->assertUnprocessable();
    }

    public function test_security_deposit_cannot_pay_rent(): void
    {
        $context = $this->fundedLease(
            type: 'security_deposit'
        );

        $invoice = Invoice::create([
            'lease_id' => $context['lease']->id,
            'invoice_number' => 'INV-EXP-TEST-03',
            'type' => 'rent',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'issued',
            'total_amount' => 5000,
            'vat_rate' => 0,
            'net_amount' => 5000,
            'vat_amount' => 0,
        ]);

        $this->postJson(
            "/api/invoices/{$invoice->id}/account-payments",
            [
                'tenant_fund_account_id' => $context['account']->id,
                'amount' => 5000,
                'transaction_date' => '2026-08-25',
            ]
        )->assertUnprocessable();
    }

    public function test_historical_expense_vouchers_still_download(): void
    {
        Mail::fake();

        $context = $this->fundedLease();

        /*
         * A pre-V1.0.8 expense recorded under the old settle-at-once
         * model. The voucher document endpoints remain readable.
         */
        $transactions = app(TenantFundExpenseService::class)->expense(
            accountId: $context['account']->id,
            lines: [
                [
                    'description' => 'Historical voucher line.',
                    'amount' => 1000,
                ],
            ],
            transactionDate: '2026-08-20',
            paymentMethod: 'cash',
        );

        $this->get(
            '/api/tenant-fund-expenses/'
            .$transactions->first()->id
            .'/voucher'
        )
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_viewer_cannot_record_or_pay(): void
    {
        $context = $this->fundedLease();

        $invoice = $this->recordExpenseInvoice($context['lease']);

        $this->authenticateApiUser('viewer');

        $this->postJson(
            '/api/tenant-expense-invoices',
            [
                'lease_id' => $context['lease']->id,
                'transaction_date' => '2026-08-25',
                'lines' => [
                    [
                        'description' => 'Viewer attempt.',
                        'amount' => 1000,
                    ],
                ],
            ]
        )->assertForbidden();

        $this->postJson(
            "/api/invoices/{$invoice->id}/account-payments",
            [
                'tenant_fund_account_id' => $context['account']->id,
                'amount' => 1000,
                'transaction_date' => '2026-08-25',
            ]
        )->assertForbidden();
    }
}
