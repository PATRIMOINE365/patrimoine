<?php

namespace Tests\Feature;

use App\Mail\OwnerExpenseBillMail;
use App\Models\AccountingCutover;
use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\JournalEntry;
use App\Models\OwnerAccount;
use App\Models\OwnerExpenseBill;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\PartyRole;
use App\Services\Accounting\SystemChartOfAccounts;
use App\Services\OwnerPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Verifies the V1.0.7 owner expense billing workflow and the owner
 * payout receipt document.
 */
class OwnerExpenseBillApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Create an owner (with email) and the automatically provisioned
     * consolidated OwnerAccount.
     *
     * A Building ownership is still created because OwnerAccounts are
     * provisioned through BuildingOwner, but expense bills themselves
     * never reference the Building.
     *
     * @return array{
     *     owner: Party,
     *     account: OwnerAccount
     * }
     */
    private function createOwnerContext(
        ?string $email = 'expense-bill-owner@example.test'
    ): array {
        $building = Building::create([
            'name' => 'Owner Expense Bill Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Owner Expense Bill Owner',
            'phone' => '0200000910',
            'email' => $email,
        ]);

        /*
         * Creating Building ownership automatically provisions the
         * Owner's consolidated financial account.
         */
        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        $account = $owner
            ->ownerAccount()
            ->firstOrFail();

        return compact(
            'owner',
            'account',
            'building'
        );
    }

    /**
     * A multi-line bill creates the bill header, one direct expense and
     * one debit ledger transaction per line, and reduces the owner
     * balance by the batch total.
     */
    public function test_multi_line_expense_bill_is_recorded(): void
    {
        Mail::fake();

        $context = $this->createOwnerContext();

        $response = $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/expense-bills",
            [
                'building_id' => $context['building']->id,
                'split' => 'single',
                'bill_date' => '2026-08-21',
                'notes' => 'August direct billing batch.',
                'lines' => [
                    [
                        'description' => 'Legal consultation fees',
                        'amount' => 1500,
                    ],
                    [
                        'description' => 'Land title renewal',
                        'amount' => 2500,
                    ],
                    [
                        'description' => 'Courier charges',
                        'amount' => 500,
                    ],
                ],
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'expense_bill.bill_number',
                sprintf('OEB-%04d-000001', now()->year)
            )
            ->assertJsonPath(
                'expense_bill.total_amount',
                4500
            )
            ->assertJsonCount(
                3,
                'expense_bill.expenses'
            );

        $this->assertDatabaseHas('owner_expense_bills', [
            'owner_account_id' => $context['account']->id,
            'bill_number' => sprintf('OEB-%04d-000001', now()->year),
            'total_amount' => 4500,
            'notes' => 'August direct billing batch.',
        ]);

        /*
         * Every line is a direct-to-owner expense: no Building context.
         */
        foreach (
            [
                ['Legal consultation fees', 1500],
                ['Land title renewal', 2500],
                ['Courier charges', 500],
            ] as [$description, $amount]
        ) {
            $this->assertDatabaseHas('owner_expenses', [
                'building_id' => $context['building']->id,
                'unit_id' => null,
                'description' => $description,
                'amount' => $amount,
                'reference' => sprintf('OEB-%04d-000001', now()->year),
            ]);
        }

        /*
         * V1.0.8: recording a bill no longer touches the owner ledger.
         * The bill stays unpaid until settled through the Pay flow.
         */
        $this->assertSame(
            0,
            OwnerTransaction::query()
                ->where(
                    'owner_account_id',
                    $context['account']->id
                )
                ->where('category', 'expense')
                ->count()
        );

        $this->assertSame(
            0,
            $context['account']
                ->fresh()
                ->balance()
        );

        $bill = OwnerExpenseBill::query()->firstOrFail();

        $this->assertSame('unpaid', $bill->paymentStatus());
        $this->assertSame(4500, $bill->outstandingAmount());

        /*
         * The owner has an email address, so the bill is delivered
         * best-effort immediately after recording.
         */
        Mail::assertSent(
            OwnerExpenseBillMail::class
        );
    }

    /**
     * A bill without lines is rejected before anything is written.
     */
    public function test_expense_bill_requires_at_least_one_line(): void
    {
        $context = $this->createOwnerContext();

        $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/expense-bills",
            [
                'building_id' => $context['building']->id,
                'split' => 'single',
                'bill_date' => '2026-08-21',
                'lines' => [],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'lines',
            ]);

        $this->assertDatabaseCount(
            'owner_expense_bills',
            0
        );
    }

    /**
     * Line amounts must be whole amounts greater than zero.
     */
    public function test_expense_bill_rejects_invalid_line_amount(): void
    {
        $context = $this->createOwnerContext();

        $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/expense-bills",
            [
                'building_id' => $context['building']->id,
                'split' => 'single',
                'bill_date' => '2026-08-21',
                'lines' => [
                    [
                        'description' => 'Invalid line',
                        'amount' => 0,
                    ],
                ],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'lines.0.amount',
            ]);

        $this->assertDatabaseCount(
            'owner_expense_bills',
            0
        );
    }

    /**
     * Viewer is strictly read-only and cannot bill owners.
     */
    public function test_viewer_cannot_record_expense_bill(): void
    {
        $context = $this->createOwnerContext();

        $this->authenticateApiUser('viewer');

        $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/expense-bills",
            [
                'building_id' => $context['building']->id,
                'split' => 'single',
                'bill_date' => '2026-08-21',
                'lines' => [
                    [
                        'description' => 'Forbidden line',
                        'amount' => 1000,
                    ],
                ],
            ]
        )->assertForbidden();

        $this->assertDatabaseCount(
            'owner_expense_bills',
            0
        );
    }

    /**
     * The itemized bill PDF streams to the client.
     */
    public function test_expense_bill_pdf_can_be_downloaded(): void
    {
        Mail::fake();

        $context = $this->createOwnerContext();

        $bill =
            $this->recordBill(
                $context['account']
            );

        $this->getJson(
            "/api/owner-expense-bills/{$bill->id}/pdf"
        )
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/pdf'
            );
    }

    /**
     * The bill can be re-sent to an owner with an email address.
     */
    public function test_expense_bill_email_can_be_resent(): void
    {
        Mail::fake();

        $context = $this->createOwnerContext();

        $bill =
            $this->recordBill(
                $context['account']
            );

        $this->postJson(
            "/api/owner-expense-bills/{$bill->id}/send-email"
        )
            ->assertOk()
            ->assertJsonPath(
                'expense_bill_id',
                $bill->id
            );

        Mail::assertSent(
            OwnerExpenseBillMail::class
        );
    }

    /**
     * Re-sending fails cleanly when the billed owner has no email.
     */
    public function test_expense_bill_email_requires_owner_email(): void
    {
        Mail::fake();

        $context =
            $this->createOwnerContext(
                email: null
            );

        $bill =
            $this->recordBill(
                $context['account']
            );

        $this->postJson(
            "/api/owner-expense-bills/{$bill->id}/send-email"
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);

        Mail::assertNothingSent();
    }

    /**
     * The owner payout receipt PDF streams to the client.
     */
    public function test_owner_payout_receipt_can_be_downloaded(): void
    {
        $context = $this->createOwnerContext();

        /*
         * Fund the owner so a payout is permitted.
         */
        OwnerTransaction::create([
            'owner_account_id' => $context['account']->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 10000,
            'transaction_date' => '2026-08-01',
        ]);

        $payout = app(
            OwnerPayoutService::class
        )->create(
            account: $context['account'],
            amount: 7000,
            payoutDate: '2026-08-21',
            paymentMethod: 'bank_transfer',
            reference: 'POUT-RCT-001',
        );

        $this->getJson(
            "/api/owner-payouts/{$payout->id}/receipt"
        )
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/pdf'
            );
    }

    /**
     * After the accounting cutover, every bill line posts its own
     * balanced owner-expense Journal entry with the stable per-expense
     * idempotency key.
     */
    public function test_expense_bill_posts_journal_entries_when_accounting_enabled(): void
    {
        Mail::fake();

        app(
            SystemChartOfAccounts::class
        )->install();

        AccountingCutover::create([
            'cutover_key' =>
                AccountingCutover::V105_OPENING_BALANCE,

            'cutover_date' =>
                '2026-08-20',

            'status' =>
                AccountingCutover::STATUS_COMPLETED,

            'position_count' =>
                0,

            'journal_entry_count' =>
                0,

            'completed_at' =>
                now(),

            'metadata' => [
                'test_fixture' =>
                    true,
            ],
        ]);

        $context = $this->createOwnerContext();

        $bill =
            $this->recordBill(
                $context['account']
            );

        /*
         * V1.0.8: recording posts nothing. The Journal entry belongs
         * to the explicit payment.
         */
        $this->assertSame(
            0,
            JournalEntry::query()
                ->where('transaction_type', 'owner_expense')
                ->count()
        );

        $paymentId = $this->postJson(
            "/api/owner-expense-bills/{$bill->id}/payments",
            [
                'funding_source' => 'deposit_account',
                'amount' => 3000,
                'transaction_date' => '2026-08-22',
            ]
        )
            ->assertCreated()
            ->json('payment.id');

        $entries =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    'owner_expense'
                )
                ->orderBy('id')
                ->get();

        $this->assertCount(
            1,
            $entries
        );

        $this->assertTrue(
            $entries->first()->isBalanced()
        );

        $this->assertSame(
            'owner-expense-bill-payment:'.$paymentId,
            $entries->first()->idempotency_key
        );

        $this->assertSame(
            3000,
            (int) $entries
                ->flatMap
                ->lines
                ->sum('debit_amount')
        );

        /*
         * Cancelling the payment posts an immutable Journal reversal.
         */
        $this->postJson(
            "/api/owner-expense-bill-payments/{$paymentId}/cancel",
            [
                'reason' => 'Recorded against the wrong bill.',
            ]
        )->assertOk();

        $original = $entries->first();

        $this->assertTrue(
            JournalEntry::query()
                ->where('reversal_of_id', $original->id)
                ->exists()
        );

        $this->assertSame(
            'unpaid',
            $bill->fresh()->paymentStatus()
        );
    }

    /**
     * Record a standard two-line bill through the API.
     */
    private function recordBill(
        OwnerAccount $account
    ): OwnerExpenseBill {
        $building = Building::query()->firstOrFail();

        $this->postJson(
            "/api/owner-accounts/{$account->id}/expense-bills",
            [
                'building_id' => $building->id,
                'split' => 'single',
                'bill_date' => '2026-08-21',
                'lines' => [
                    [
                        'description' => 'Line one',
                        'amount' => 1000,
                    ],
                    [
                        'description' => 'Line two',
                        'amount' => 2000,
                    ],
                ],
            ]
        )->assertCreated();

        return OwnerExpenseBill::query()
            ->with('expenses')
            ->latest('id')
            ->firstOrFail();
    }
    /**
     * V1.0.8 split mode: lines are prorated across every owner of the
     * Building by ownership percentage with largest-remainder rounding.
     */
    public function test_split_mode_bills_every_owner_by_percentage(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $context = $this->createOwnerContext();

        $second = Party::create([
            'type' => 'person',
            'name' => 'Second Co-Owner',
            'phone' => '0200000911',
            'email' => 'second-co-owner@example.test',
        ]);

        PartyRole::create([
            'party_id' => $second->id,
            'role' => 'owner',
        ]);

        BuildingOwner::query()
            ->where('building_id', $context['building']->id)
            ->update(['ownership_percentage' => 60.00]);

        BuildingOwner::create([
            'building_id' => $context['building']->id,
            'party_id' => $second->id,
            'ownership_percentage' => 40.00,
        ]);

        $this->postJson(
            "/api/owner-accounts/{$context['account']->id}/expense-bills",
            [
                'building_id' => $context['building']->id,
                'split' => 'split',
                'bill_date' => '2026-08-21',
                'notes' => 'Roof repair',
                'lines' => [
                    [
                        'description' => 'Roofing sheets',
                        'amount' => 1001,
                    ],
                ],
            ]
        )
            ->assertCreated()
            ->assertJsonCount(2, 'bills');

        /*
         * 1001 at 60/40: floor gives 600 + 400, largest remainder gives
         * the extra 1 to the 60% owner (0.6 fraction beats 0.4).
         *
         * V1.0.8: each co-owner receives an UNPAID bill; no ledger
         * debit exists until each bill is explicitly paid.
         */
        $this->assertDatabaseHas('owner_expense_bills', [
            'owner_account_id' => $context['account']->id,
            'total_amount' => 601,
        ]);

        $secondAccount = OwnerAccount::query()
            ->where('party_id', $second->id)
            ->firstOrFail();

        $this->assertDatabaseHas('owner_expense_bills', [
            'owner_account_id' => $secondAccount->id,
            'total_amount' => 400,
        ]);

        $this->assertSame(
            0,
            OwnerTransaction::query()
                ->where('category', 'expense')
                ->count()
        );

        $this->assertSame(
            0,
            $context['account']->fresh()->depositAccountBalance()
        );
    }

    /**
     * V1.0.8: paying from the Deposit account may drive it negative by
     * design, and partial payments move the bill through its derived
     * lifecycle.
     */
    public function test_bill_is_paid_from_the_deposit_account_in_parts(): void
    {
        Mail::fake();

        $context = $this->createOwnerContext();

        $bill = $this->recordBill($context['account']);

        $this->postJson(
            "/api/owner-expense-bills/{$bill->id}/payments",
            [
                'funding_source' => 'deposit_account',
                'amount' => 1000,
                'transaction_date' => '2026-08-22',
            ]
        )
            ->assertCreated()
            ->assertJsonPath('bill.payment_status', 'partial')
            ->assertJsonPath('bill.outstanding', 2000);

        $this->postJson(
            "/api/owner-expense-bills/{$bill->id}/payments",
            [
                'funding_source' => 'deposit_account',
                'amount' => 2000,
                'transaction_date' => '2026-08-23',
            ]
        )
            ->assertCreated()
            ->assertJsonPath('bill.payment_status', 'paid')
            ->assertJsonPath('bill.outstanding', 0);

        /*
         * No deposits exist, so the Deposit account is now negative:
         * debt the owner owes the agency. The Payout account is
         * untouched.
         */
        $account = $context['account']->fresh();

        $this->assertSame(-3000, $account->depositAccountBalance());
        $this->assertSame(0, $account->payoutAccountBalance());

        /*
         * Overpaying the settled bill is refused.
         */
        $this->postJson(
            "/api/owner-expense-bills/{$bill->id}/payments",
            [
                'funding_source' => 'deposit_account',
                'amount' => 1,
                'transaction_date' => '2026-08-23',
            ]
        )->assertUnprocessable();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'owner_expense_bill_payment.recorded',
        ]);
    }

    /**
     * V1.0.8: the Payout account is rent-derived money and is strictly
     * capped at its available balance.
     */
    public function test_payout_account_payments_are_capped(): void
    {
        Mail::fake();

        $context = $this->createOwnerContext();

        $bill = $this->recordBill($context['account']);

        /*
         * No rent has ever been collected: the Payout account is 0 and
         * must refuse the payment outright.
         */
        $this->postJson(
            "/api/owner-expense-bills/{$bill->id}/payments",
            [
                'funding_source' => 'payout_account',
                'amount' => 500,
                'transaction_date' => '2026-08-22',
            ]
        )->assertUnprocessable();

        /*
         * Rent entitlement funds the Payout account; the payment then
         * draws from it.
         */
        OwnerTransaction::create([
            'owner_account_id' => $context['account']->id,
            'building_id' => $context['building']->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 800,
            'transaction_date' => '2026-08-21',
        ]);

        $this->postJson(
            "/api/owner-expense-bills/{$bill->id}/payments",
            [
                'funding_source' => 'payout_account',
                'amount' => 500,
                'transaction_date' => '2026-08-22',
            ]
        )->assertCreated();

        $account = $context['account']->fresh();

        $this->assertSame(300, $account->payoutAccountBalance());
        $this->assertSame(0, $account->depositAccountBalance());
    }

    /**
     * V1.0.8: cancelling a payment restores every derived balance and
     * the bill's lifecycle, and is itself one-shot.
     */
    public function test_cancelling_a_bill_payment_restores_balances(): void
    {
        Mail::fake();

        $context = $this->createOwnerContext();

        $bill = $this->recordBill($context['account']);

        $paymentId = $this->postJson(
            "/api/owner-expense-bills/{$bill->id}/payments",
            [
                'funding_source' => 'deposit_account',
                'amount' => 3000,
                'transaction_date' => '2026-08-22',
            ]
        )
            ->assertCreated()
            ->json('payment.id');

        $this->postJson(
            "/api/owner-expense-bill-payments/{$paymentId}/cancel",
            [
                'reason' => 'Wrong bill selected.',
            ]
        )
            ->assertOk()
            ->assertJsonPath('bill.payment_status', 'unpaid')
            ->assertJsonPath('bill.outstanding', 3000);

        $account = $context['account']->fresh();

        $this->assertSame(0, $account->depositAccountBalance());
        $this->assertSame(0, $account->balance());

        /*
         * The same payment can never be cancelled twice.
         */
        $this->postJson(
            "/api/owner-expense-bill-payments/{$paymentId}/cancel",
            [
                'reason' => 'Duplicate cancel attempt.',
            ]
        )->assertUnprocessable();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'owner_expense_bill_payment.cancelled',
        ]);
    }

    /**
     * V1.0.8: the bills listing carries derived payment state, and the
     * payment receipt lists active payments only.
     */
    public function test_bills_listing_and_payment_receipt(): void
    {
        Mail::fake();

        $context = $this->createOwnerContext();

        $bill = $this->recordBill($context['account']);

        /*
         * No payments yet: the receipt is refused.
         */
        $this->getJson(
            "/api/owner-expense-bills/{$bill->id}/payment-receipt"
        )->assertUnprocessable();

        $this->postJson(
            "/api/owner-expense-bills/{$bill->id}/payments",
            [
                'funding_source' => 'deposit_account',
                'amount' => 3000,
                'transaction_date' => '2026-08-22',
            ]
        )->assertCreated();

        $listing = $this->getJson(
            "/api/owner-accounts/{$context['account']->id}/expense-bills"
        )
            ->assertOk()
            ->assertJsonPath('expense_bills.0.payment_status', 'paid')
            ->assertJsonPath('expense_bills.0.paid', 3000)
            ->assertJsonPath('expense_bills.0.payments.0.cancellable', true);

        $this->assertSame(
            3000,
            $listing->json('expense_bills.0.total_amount')
        );

        $this->get(
            "/api/owner-expense-bills/{$bill->id}/payment-receipt"
        )
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}

