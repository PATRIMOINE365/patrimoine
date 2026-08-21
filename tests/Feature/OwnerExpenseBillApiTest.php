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
            'account'
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
                'OEB-000001'
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
            'bill_number' => 'OEB-000001',
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
                'building_id' => null,
                'unit_id' => null,
                'description' => $description,
                'amount' => $amount,
                'reference' => 'OEB-000001',
            ]);

            $this->assertDatabaseHas('owner_transactions', [
                'owner_account_id' => $context['account']->id,
                'building_id' => null,
                'direction' => 'debit',
                'category' => 'expense',
                'amount' => $amount,
                'reference' => 'OEB-000001',
                'notes' => $description,
            ]);
        }

        $this->assertSame(
            3,
            OwnerTransaction::query()
                ->where(
                    'owner_account_id',
                    $context['account']->id
                )
                ->where('category', 'expense')
                ->count()
        );

        /*
         * The full batch total debits the billed owner.
         */
        $this->assertSame(
            -4500,
            $context['account']
                ->fresh()
                ->balance()
        );

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

        $entries =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    'owner_expense'
                )
                ->orderBy('id')
                ->get();

        /*
         * One Journal entry per expense line.
         */
        $this->assertCount(
            2,
            $entries
        );

        $expenseIds = $bill
            ->expenses
            ->sortBy('id')
            ->pluck('id')
            ->values()
            ->all();

        foreach ($entries as $index => $entry) {
            $this->assertTrue(
                $entry->isBalanced()
            );

            $this->assertSame(
                'owner-expense:'.$expenseIds[$index],
                $entry->idempotency_key
            );
        }

        /*
         * The two entries together carry the full bill total.
         */
        $this->assertSame(
            3000,
            (int) $entries
                ->flatMap
                ->lines
                ->sum('debit_amount')
        );
    }

    /**
     * Record a standard two-line bill through the API.
     */
    private function recordBill(
        OwnerAccount $account
    ): OwnerExpenseBill {
        $this->postJson(
            "/api/owner-accounts/{$account->id}/expense-bills",
            [
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
}
