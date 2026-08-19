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
use App\Services\Accounting\AccountingEventMap;
use App\Services\Accounting\JournalPostingService;
use App\Services\Accounting\SystemChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

class TenantFundWithdrawalApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();

        app(
            SystemChartOfAccounts::class
        )->install();
    }

    public function test_rent_reserve_can_be_withdrawn(): void
    {
        $account =
            $this->fundAccount(
                'rent_reserve',
                30000
            );

        $response =
            $this->postJson(
                '/api/tenant-fund-withdrawals',
                [
                    'tenant_fund_account_id' => $account->id,

                    'amount' => 5000,

                    'transaction_date' => '2026-08-19',

                    'payment_method' => 'cash',

                    'reference' => 'WD-001',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'transaction.direction',
                'debit'
            )
            ->assertJsonPath(
                'transaction.category',
                'withdrawal'
            )
            ->assertJsonPath(
                'transaction.payment_method',
                'cash'
            )
            ->assertJsonPath(
                'tenant_fund_account.balance',
                25000
            );

        $this->assertDatabaseHas(
            'tenant_fund_transactions',
            [
                'tenant_fund_account_id' => $account->id,

                'direction' => 'debit',

                'category' => 'withdrawal',

                'amount' => 5000,

                'payment_method' => 'cash',

                'reference' => 'WD-001',
            ]
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' => 'tenant_withdrawal.recorded',

                'entity_type' => 'tenant_fund_transaction',
            ]
        );
    }

    public function test_consumable_advance_can_be_withdrawn(): void
    {
        $account =
            $this->fundAccount(
                'consumable_advance',
                20000
            );

        $this->postJson(
            '/api/tenant-fund-withdrawals',
            [
                'tenant_fund_account_id' => $account->id,

                'amount' => 7500,

                'transaction_date' => '2026-08-19',

                'payment_method' => 'bank_transfer',
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'tenant_fund_account.balance',
                12500
            );
    }

    public function test_withdrawal_cannot_overdraw_account(): void
    {
        $account =
            $this->fundAccount(
                'rent_reserve',
                5000
            );

        $this->postJson(
            '/api/tenant-fund-withdrawals',
            [
                'tenant_fund_account_id' => $account->id,

                'amount' => 5001,

                'transaction_date' => '2026-08-19',

                'payment_method' => 'cash',
            ]
        )->assertUnprocessable();

        $this->assertSame(
            5000,
            $account->fresh()->balance()
        );

        $this->assertSame(
            1,
            TenantFundTransaction::count()
        );
    }

    public function test_security_deposit_is_not_yet_general_withdrawal_eligible(): void
    {
        $account =
            $this->fundAccount(
                'security_deposit',
                10000
            );

        $this->postJson(
            '/api/tenant-fund-withdrawals',
            [
                'tenant_fund_account_id' => $account->id,

                'amount' => 1000,

                'transaction_date' => '2026-08-19',

                'payment_method' => 'cash',
            ]
        )->assertUnprocessable();

        $this->assertSame(
            10000,
            $account->fresh()->balance()
        );
    }

    public function test_withdrawal_posts_balanced_journal(): void
    {
        $this->createCompletedCutover();

        $account =
            $this->fundAccount(
                'rent_reserve',
                10000
            );

        $this->postJson(
            '/api/tenant-fund-withdrawals',
            [
                'tenant_fund_account_id' => $account->id,

                'amount' => 4000,

                'transaction_date' => '2026-08-19',

                'payment_method' => 'momo',
            ]
        )->assertCreated();

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    AccountingEventMap::EVENT_TENANT_WITHDRAWAL
                )
                ->sole();

        $this->assertTrue(
            $entry->isBalanced()
        );

        $this->assertSame(
            4000,
            (int) $entry
                ->lines
                ->sum('debit_amount')
        );

        $this->assertSame(
            4000,
            (int) $entry
                ->lines
                ->sum('credit_amount')
        );

        $this->assertTrue(
            $entry->lines->contains(
                fn ($line): bool => $line->account_code_snapshot
                        === SystemChartOfAccounts::RENT_RESERVE_HELD
                    && (int) $line->debit_amount
                        === 4000
            )
        );

        $this->assertTrue(
            $entry->lines->contains(
                fn ($line): bool => $line->account_code_snapshot
                        === SystemChartOfAccounts::MOBILE_PAYMENT_CLEARING
                    && (int) $line->credit_amount
                        === 4000
            )
        );
    }

    public function test_journal_failure_rolls_back_withdrawal_and_activity_log(): void
    {
        $this->createCompletedCutover();

        $account =
            $this->fundAccount(
                'rent_reserve',
                10000
            );

        $this->mock(
            JournalPostingService::class,
            function ($mock): void {
                $mock
                    ->shouldReceive('post')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Forced Withdrawal Journal failure.'
                        )
                    );
            }
        );

        $this->postJson(
            '/api/tenant-fund-withdrawals',
            [
                'tenant_fund_account_id' => $account->id,

                'amount' => 4000,

                'transaction_date' => '2026-08-19',

                'payment_method' => 'bank_transfer',
            ]
        )->assertUnprocessable();

        /*
         * Only the opening funding transaction remains.
         */
        $this->assertSame(
            1,
            TenantFundTransaction::count()
        );

        $this->assertSame(
            10000,
            $account->fresh()->balance()
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
    }

    private function createCompletedCutover(): void
    {
        AccountingCutover::create([
            'cutover_key' => AccountingCutover::V105_OPENING_BALANCE,

            'cutover_date' => '2026-08-19',

            'status' => AccountingCutover::STATUS_COMPLETED,

            'position_count' => 0,

            'journal_entry_count' => 0,

            'completed_at' => now(),

            'metadata' => [],
        ]);
    }

    private function fundAccount(
        string $type,
        int $amount
    ): TenantFundAccount {
        $building =
            Building::create([
                'name' => 'Withdrawal Test Building',
            ]);

        $unit =
            Unit::create([
                'building_id' => $building->id,

                'name' => 'Unit W-1',
            ]);

        $tenant =
            Party::create([
                'type' => 'person',

                'name' => 'Withdrawal Test Tenant',

                'phone' => '0200005800',

                'email' => 'withdrawal-'
                    .uniqid()
                    .'@example.test',
            ]);

        $lease =
            Lease::create([
                'unit_id' => $unit->id,

                'tenant_id' => $tenant->id,

                'start_date' => '2026-01-01',

                'rent_amount' => 5000,

                'status' => 'active',
            ]);

        $account =
            TenantFundAccount::create([
                'lease_id' => $lease->id,

                'type' => $type,

                'status' => 'active',
            ]);

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

        return $account;
    }
}
