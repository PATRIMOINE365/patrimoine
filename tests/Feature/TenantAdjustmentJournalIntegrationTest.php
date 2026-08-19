<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\Building;
use App\Models\JournalEntry;
use App\Models\Lease;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Models\User;
use App\Services\Accounting\AccountingEventMap;
use App\Services\Accounting\JournalPostingService;
use App\Services\Accounting\SystemChartOfAccounts;
use App\Services\Accounting\TenantFundAdjustmentJournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

class TenantAdjustmentJournalIntegrationTest extends TestCase
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

    private function activateAccounting(): void
    {
        AccountingCutover::create([
            'cutover_key' => AccountingCutover::V105_OPENING_BALANCE,

            'cutover_date' => now()->toDateString(),

            'status' => AccountingCutover::STATUS_COMPLETED,

            'completed_at' => now(),
        ]);
    }

    private function account(
        string $fundType = 'rent_reserve',
        int $openingBalance = 0,
    ): TenantFundAccount {
        $building = Building::create([
            'name' => 'Tenant Journal Adjustment Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,

            'name' => 'Unit TJA-1',
        ]);

        $tenant = Party::create([
            'type' => 'person',

            'name' => 'Tenant Journal Adjustment Tenant',

            'phone' => '0200004500',

            'email' => 'tenant-journal-adjustment@example.test',
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

            'type' => $fundType,

            'status' => 'active',
        ]);

        if ($openingBalance > 0) {
            TenantFundTransaction::create([
                'tenant_fund_account_id' => $account->id,

                'direction' => 'credit',

                'category' => match ($fundType) {
                    'rent_reserve' => 'reserve_funding',

                    'consumable_advance' => 'advance_funding',

                    'security_deposit' => 'deposit_funding',
                },

                'amount' => $openingBalance,

                'transaction_date' => '2026-01-01',
            ]);
        }

        return $account;
    }

    public function test_pre_cutover_tenant_adjustment_does_not_post_journal(): void
    {
        $account =
            $this->account();

        $this->postJson(
            "/api/tenant-funds/{$account->id}/adjustments",
            [
                'corrected_balance' => 5000,

                'reason' => 'Pre-cutover correction.',
            ]
        )->assertCreated();

        $this->assertDatabaseCount(
            'tenant_fund_transactions',
            1
        );

        $this->assertDatabaseCount(
            'journal_entries',
            0
        );

        $this->assertDatabaseCount(
            'journal_lines',
            0
        );
    }

    public function test_rent_reserve_increase_posts_balanced_journal(): void
    {
        $this->activateAccounting();

        $account =
            $this->account(
                'rent_reserve'
            );

        $this->postJson(
            "/api/tenant-funds/{$account->id}/adjustments",
            [
                'corrected_balance' => 5000,

                'reason' => 'Increase Rent Reserve.',

                'reference' => 'TADJ-JRN-001',
            ]
        )->assertCreated();

        $transaction =
            TenantFundTransaction::query()
                ->sole();

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->sole();

        $this->assertTrue(
            $entry->isBalanced()
        );

        $this->assertSame(
            AccountingEventMap::EVENT_ADJUSTMENT,
            $entry->transaction_type
        );

        $this->assertSame(
            TenantFundTransaction::class,
            $entry->source_type
        );

        $this->assertSame(
            $transaction->id,
            $entry->source_id
        );

        $this->assertSame(
            TenantFundAdjustmentJournalService::idempotencyKey(
                $transaction
            ),
            $entry->idempotency_key
        );

        $debit =
            $entry->lines
                ->firstWhere(
                    'debit_amount',
                    5000
                );

        $credit =
            $entry->lines
                ->firstWhere(
                    'credit_amount',
                    5000
                );

        $this->assertSame(
            SystemChartOfAccounts::ADJUSTMENT_CLEARING,
            $debit->account_code_snapshot
        );

        $this->assertSame(
            SystemChartOfAccounts::RENT_RESERVE_HELD,
            $credit->account_code_snapshot
        );

        $this->assertSame(
            0,
            $entry->snapshot[
                'previous_balance'
            ]
        );

        $this->assertSame(
            5000,
            $entry->snapshot[
                'corrected_balance'
            ]
        );

        $this->assertSame(
            5000,
            $entry->snapshot[
                'difference'
            ]
        );
    }

    public function test_consumable_advance_uses_correct_liability_account(): void
    {
        $this->activateAccounting();

        $account =
            $this->account(
                'consumable_advance'
            );

        $this->postJson(
            "/api/tenant-funds/{$account->id}/adjustments",
            [
                'corrected_balance' => 4000,

                'reason' => 'Increase Consumable Advance.',
            ]
        )->assertCreated();

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->sole();

        $credit =
            $entry->lines
                ->firstWhere(
                    'credit_amount',
                    4000
                );

        $this->assertSame(
            SystemChartOfAccounts::CONSUMABLE_ADVANCE_HELD,
            $credit->account_code_snapshot
        );
    }

    public function test_security_deposit_uses_correct_liability_account(): void
    {
        $this->activateAccounting();

        $account =
            $this->account(
                'security_deposit'
            );

        $this->postJson(
            "/api/tenant-funds/{$account->id}/adjustments",
            [
                'corrected_balance' => 6000,

                'reason' => 'Increase Security Deposit.',
            ]
        )->assertCreated();

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->sole();

        $credit =
            $entry->lines
                ->firstWhere(
                    'credit_amount',
                    6000
                );

        $this->assertSame(
            SystemChartOfAccounts::SECURITY_DEPOSIT_HELD,
            $credit->account_code_snapshot
        );
    }

    public function test_tenant_fund_decrease_posts_reverse_direction(): void
    {
        $this->activateAccounting();

        $account =
            $this->account(
                'rent_reserve',
                10000
            );

        $this->postJson(
            "/api/tenant-funds/{$account->id}/adjustments",
            [
                'corrected_balance' => 3500,

                'reason' => 'Decrease Rent Reserve.',
            ]
        )->assertCreated();

        $transaction =
            TenantFundTransaction::query()
                ->where(
                    'category',
                    'adjustment'
                )
                ->sole();

        $this->assertSame(
            'debit',
            $transaction->direction
        );

        $this->assertSame(
            6500,
            $transaction->amount
        );

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->sole();

        $debit =
            $entry->lines
                ->firstWhere(
                    'debit_amount',
                    6500
                );

        $credit =
            $entry->lines
                ->firstWhere(
                    'credit_amount',
                    6500
                );

        $this->assertSame(
            SystemChartOfAccounts::RENT_RESERVE_HELD,
            $debit->account_code_snapshot
        );

        $this->assertSame(
            SystemChartOfAccounts::ADJUSTMENT_CLEARING,
            $credit->account_code_snapshot
        );

        $this->assertSame(
            3500,
            $account
                ->fresh()
                ->balance()
        );
    }

    public function test_journal_failure_rolls_back_tenant_adjustment_and_activity_log(): void
    {
        $this->activateAccounting();

        $account =
            $this->account();

        $this->mock(
            JournalPostingService::class,
            function ($mock): void {
                $mock
                    ->shouldReceive('post')
                    ->once()
                    ->andThrow(
                        new \RuntimeException(
                            'Forced Tenant Journal posting failure.'
                        )
                    );
            }
        );

        $response =
            $this->postJson(
                "/api/tenant-funds/{$account->id}/adjustments",
                [
                    'corrected_balance' => 5000,

                    'reason' => 'Atomic Tenant rollback test.',
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.tenant_fund_account.0',
                'Forced Tenant Journal posting failure.'
            );

        $this->assertDatabaseCount(
            'tenant_fund_transactions',
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

    public function test_tenant_adjustment_journal_posting_is_idempotent(): void
    {
        $this->activateAccounting();

        $account =
            $this->account();

        $this->postJson(
            "/api/tenant-funds/{$account->id}/adjustments",
            [
                'corrected_balance' => 3000,

                'reason' => 'Idempotency correction.',
            ]
        )->assertCreated();

        $transaction =
            TenantFundTransaction::query()
                ->sole();

        $entry =
            JournalEntry::query()
                ->sole();

        app(
            TenantFundAdjustmentJournalService::class
        )->post(
            transaction: $transaction,

            previousBalance: 0,

            correctedBalance: 3000,

            difference: 3000,

            reason: 'Idempotency correction.',

            actor: $this->apiUser,
        );

        $this->assertDatabaseCount(
            'journal_entries',
            1
        );

        $this->assertDatabaseCount(
            'journal_lines',
            2
        );

        $this->assertSame(
            $entry->id,
            JournalEntry::query()
                ->sole()
                ->id
        );
    }
}
