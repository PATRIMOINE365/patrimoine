<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\JournalEntry;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\User;
use App\Services\Accounting\AccountingEventMap;
use App\Services\Accounting\JournalPostingService;
use App\Services\Accounting\OwnerAdjustmentJournalService;
use App\Services\Accounting\SystemChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

class OwnerAdjustmentJournalIntegrationTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    private User $apiUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiUser =
            $this->authenticateApiUser();
    }

    private function ownerAccount(): OwnerAccount
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Adjustment Journal Owner',
            'phone' => '0200009999',
            'email' => 'adjustment-journal@example.test',
        ]);

        return OwnerAccount::create([
            'party_id' => $owner->id,
        ]);
    }

    private function activateAccounting(): void
    {
        AccountingCutover::create([
            'cutover_key' => AccountingCutover::V105_OPENING_BALANCE,

            'cutover_date' => now()->toDateString(),

            'status' => AccountingCutover::STATUS_COMPLETED,

            'completed_at' => now(),
        ]);
    }

    public function test_pre_cutover_owner_adjustment_does_not_post_journal(): void
    {
        $account = $this->ownerAccount();

        $response = $this->postJson(
            "/api/owner-accounts/{$account->id}/adjustments",
            [
                'corrected_balance' => 5000,
                'reason' => 'Correct opening owner balance.',
            ]
        );

        $response->assertCreated();

        $this->assertDatabaseCount('owner_transactions', 1);
        $this->assertDatabaseCount('journal_entries', 0);
        $this->assertDatabaseCount('journal_lines', 0);
    }

    public function test_owner_balance_increase_posts_balanced_adjustment_journal(): void
    {
        $this->activateAccounting();

        $account = $this->ownerAccount();

        $response = $this->postJson(
            "/api/owner-accounts/{$account->id}/adjustments",
            [
                'corrected_balance' => 5000,
                'reason' => 'Correct understated owner balance.',
                'reference' => 'ADJ-UP-001',
            ]
        );

        $response->assertCreated();

        $transaction = OwnerTransaction::query()->sole();

        $this->assertSame('credit', $transaction->direction);
        $this->assertSame(5000, $transaction->amount);

        $entry = JournalEntry::query()
            ->with('lines')
            ->sole();

        $this->assertSame(
            AccountingEventMap::EVENT_ADJUSTMENT,
            $entry->transaction_type
        );

        $this->assertSame(
            OwnerTransaction::class,
            $entry->source_type
        );

        $this->assertSame(
            $transaction->id,
            $entry->source_id
        );

        $this->assertSame(
            OwnerAdjustmentJournalService::idempotencyKey(
                $transaction
            ),
            $entry->idempotency_key
        );

        $this->assertTrue($entry->isBalanced());

        $debit = $entry->lines
            ->firstWhere('debit_amount', 5000);

        $credit = $entry->lines
            ->firstWhere('credit_amount', 5000);

        $this->assertSame(
            SystemChartOfAccounts::ADJUSTMENT_CLEARING,
            $debit->account_code_snapshot
        );

        $this->assertSame(
            SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,
            $credit->account_code_snapshot
        );

        $this->assertSame(
            0,
            $entry->snapshot['previous_balance']
        );

        $this->assertSame(
            5000,
            $entry->snapshot['corrected_balance']
        );

        $this->assertSame(
            5000,
            $entry->snapshot['difference']
        );

        $this->assertSame(
            'Correct understated owner balance.',
            $entry->snapshot['reason']
        );
    }

    public function test_owner_balance_decrease_posts_reverse_direction(): void
    {
        $this->activateAccounting();

        $account = $this->ownerAccount();

        OwnerTransaction::create([
            'owner_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'adjustment',
            'amount' => 10000,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Test opening position.',
        ]);

        $response = $this->postJson(
            "/api/owner-accounts/{$account->id}/adjustments",
            [
                'corrected_balance' => 4000,
                'reason' => 'Correct overstated owner balance.',
            ]
        );

        $response->assertCreated();

        $transaction = OwnerTransaction::query()
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('debit', $transaction->direction);
        $this->assertSame(6000, $transaction->amount);

        $entry = JournalEntry::query()
            ->with('lines')
            ->sole();

        $debit = $entry->lines
            ->firstWhere('debit_amount', 6000);

        $credit = $entry->lines
            ->firstWhere('credit_amount', 6000);

        $this->assertSame(
            SystemChartOfAccounts::OWNER_FUNDS_PAYABLE,
            $debit->account_code_snapshot
        );

        $this->assertSame(
            SystemChartOfAccounts::ADJUSTMENT_CLEARING,
            $credit->account_code_snapshot
        );
    }

    public function test_owner_adjustment_can_cross_into_negative_balance(): void
    {
        $this->activateAccounting();

        $account = $this->ownerAccount();

        $response = $this->postJson(
            "/api/owner-accounts/{$account->id}/adjustments",
            [
                'corrected_balance' => -2500,
                'reason' => 'Owner owes property account.',
            ]
        );

        $response->assertCreated();

        $this->assertSame(
            -2500,
            $account->fresh()->balance()
        );

        $entry = JournalEntry::query()
            ->with('lines')
            ->sole();

        $this->assertTrue($entry->isBalanced());

        $this->assertSame(
            -2500,
            $entry->snapshot['corrected_balance']
        );

        $this->assertSame(
            -2500,
            $entry->snapshot['difference']
        );
    }

    public function test_journal_failure_rolls_back_owner_adjustment(): void
    {
        $this->activateAccounting();

        $account =
            $this->ownerAccount();

        /*
         * Force the accounting boundary itself to fail after the
         * operational adapter has calculated the correction.
         *
         * ContextualAdjustmentService owns the surrounding database
         * transaction, so absolutely no Owner transaction may survive.
         */
        $this->mock(
            JournalPostingService::class,
            function ($mock): void {
                $mock
                    ->shouldReceive('post')
                    ->once()
                    ->andThrow(
                        new \RuntimeException(
                            'Forced Journal posting failure.'
                        )
                    );
            }
        );

        $response =
            $this->postJson(
                "/api/owner-accounts/{$account->id}/adjustments",
                [
                    'corrected_balance' => 5000,

                    'reason' => 'Atomic rollback test.',
                ]
            );

        /*
         * OwnerLedgerController translates financial RuntimeExceptions into
         * the established 422 business-validation response.
         *
         * The important invariant is not a 500 response; it is that the
         * surrounding transaction rolls back every operational/accounting
         * effect.
         */
        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.owner_account.0',
                'Forced Journal posting failure.'
            );

        /*
         * No partial operational or accounting state is permitted.
         */
        $this->assertDatabaseCount(
            'owner_transactions',
            0
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );

        $this->assertDatabaseCount(
            'journal_lines',
            0
        );

        /*
         * A failed financial action must not create a successful
         * Activity Log event either.
         */
        $this->assertDatabaseCount(
            'activity_logs',
            0
        );

        $this->assertSame(
            0,
            $account
                ->fresh()
                ->balance()
        );
    }

    public function test_owner_adjustment_journal_posting_is_idempotent(): void
    {
        $this->activateAccounting();

        $account = $this->ownerAccount();

        $this->postJson(
            "/api/owner-accounts/{$account->id}/adjustments",
            [
                'corrected_balance' => 3000,
                'reason' => 'Correction.',
            ]
        )->assertCreated();

        $transaction = OwnerTransaction::query()->sole();

        $entry = JournalEntry::query()->sole();

        app(OwnerAdjustmentJournalService::class)->post(
            transaction: $transaction,
            previousBalance: 0,
            correctedBalance: 3000,
            difference: 3000,
            reason: 'Correction.',
            actor: $this->apiUser,
        );

        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseCount('journal_lines', 2);

        $this->assertSame(
            $entry->id,
            JournalEntry::query()->sole()->id
        );
    }
}
