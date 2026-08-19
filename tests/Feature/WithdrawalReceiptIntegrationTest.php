<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Models\WithdrawalReceipt;
use App\Services\Accounting\SystemChartOfAccounts;
use App\Services\Documents\WithdrawalReceiptDocumentService;
use App\Services\Documents\WithdrawalReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

class WithdrawalReceiptIntegrationTest extends TestCase
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

    public function test_withdrawal_creates_receipt_with_frozen_context(): void
    {
        $account =
            $this->fundAccount(
                'rent_reserve',
                10000
            );

        $response =
            $this->postJson(
                '/api/tenant-fund-withdrawals',
                [
                    'tenant_fund_account_id' => $account->id,

                    'amount' => 4000,

                    'transaction_date' => '2026-08-19',

                    'payment_method' => 'bank_transfer',

                    'reference' => 'WD-RCP-001',

                    'notes' => 'Withdrawal Receipt test.',
                ]
            )
                ->assertCreated()
                ->assertJsonPath(
                    'tenant_fund_account.balance',
                    6000
                );

        $receipt =
            WithdrawalReceipt::query()
                ->sole();

        $transaction =
            TenantFundTransaction::query()
                ->where(
                    'category',
                    'withdrawal'
                )
                ->sole();

        $this->assertSame(
            $transaction->id,
            $receipt
                ->tenant_fund_transaction_id
        );

        $this->assertSame(
            $account->id,
            $receipt
                ->tenant_fund_account_id
        );

        $this->assertSame(
            4000,
            $receipt->amount
        );

        $this->assertSame(
            'bank_transfer',
            $receipt->payment_method
        );

        $this->assertSame(
            'Withdrawal Receipt Test Tenant',
            $receipt->tenant_name
        );

        $this->assertSame(
            'Withdrawal Receipt Test Building',
            $receipt->building_label
        );

        $this->assertSame(
            'Unit WR-1',
            $receipt->unit_label
        );

        $this->assertSame(
            $receipt->id,
            $response->json(
                'withdrawal_receipt.id'
            )
        );

        $this->assertSame(
            $receipt->receipt_number,
            $response->json(
                'withdrawal_receipt.receipt_number'
            )
        );

        $activity =
            ActivityLog::query()
                ->sole();

        $this->assertSame(
            $receipt->id,
            $activity->snapshot[
                'withdrawal_receipt_id'
            ]
        );

        $this->assertSame(
            $receipt->receipt_number,
            $activity->snapshot[
                'withdrawal_receipt_number'
            ]
        );
    }

    public function test_withdrawal_receipt_pdf_generates(): void
    {
        $account =
            $this->fundAccount(
                'consumable_advance',
                5000
            );

        $response =
            $this->postJson(
                '/api/tenant-fund-withdrawals',
                [
                    'tenant_fund_account_id' => $account->id,

                    'amount' => 1000,

                    'transaction_date' => '2026-08-19',

                    'payment_method' => 'cash',
                ]
            )
                ->assertCreated();

        $receipt =
            WithdrawalReceipt::query()
                ->sole();

        $contents =
            app(
                WithdrawalReceiptDocumentService::class
            )->pdf(
                $receipt
            );

        $this->assertStringStartsWith(
            '%PDF',
            $contents
        );

        $this->get(
            $response->json(
                'withdrawal_receipt.pdf_endpoint'
            )
        )
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/pdf'
            );
    }

    public function test_receipt_numbers_are_sequential(): void
    {
        $first =
            $this->fundAccount(
                'rent_reserve',
                5000
            );

        $second =
            $this->fundAccount(
                'consumable_advance',
                5000
            );

        $this->withdraw(
            $first,
            'cash'
        );

        $this->withdraw(
            $second,
            'momo'
        );

        $year =
            now()->year;

        $this->assertSame(
            [
                sprintf(
                    'WDR-%d-000001',
                    $year
                ),

                sprintf(
                    'WDR-%d-000002',
                    $year
                ),
            ],
            WithdrawalReceipt::query()
                ->orderBy('id')
                ->pluck(
                    'receipt_number'
                )
                ->all()
        );
    }

    public function test_receipt_creation_failure_rolls_back_withdrawal_journal_and_activity(): void
    {
        $this->createCompletedCutover();

        $account =
            $this->fundAccount(
                'rent_reserve',
                10000
            );

        $this->mock(
            WithdrawalReceiptService::class,
            function ($mock): void {
                $mock
                    ->shouldReceive('create')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Forced Withdrawal Receipt failure.'
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
        )
            ->assertUnprocessable();

        /*
         * Only the original funding transaction survives.
         */
        $this->assertSame(
            1,
            TenantFundTransaction::count()
        );

        $this->assertSame(
            10000,
            $account
                ->fresh()
                ->balance()
        );

        $this->assertDatabaseCount(
            'withdrawal_receipts',
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
    }

    private function withdraw(
        TenantFundAccount $account,
        string $method
    ): void {
        $this->postJson(
            '/api/tenant-fund-withdrawals',
            [
                'tenant_fund_account_id' => $account->id,

                'amount' => 500,

                'transaction_date' => '2026-08-19',

                'payment_method' => $method,
            ]
        )->assertCreated();
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
                'name' => 'Withdrawal Receipt Test Building',
            ]);

        $unit =
            Unit::create([
                'building_id' => $building->id,

                'name' => 'Unit WR-1',
            ]);

        $tenant =
            Party::create([
                'type' => 'person',

                'name' => 'Withdrawal Receipt Test Tenant',

                'phone' => '0200005900',

                'email' => 'withdrawal-receipt-'
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
            },

            'amount' => $amount,

            'transaction_date' => '2026-08-01',
        ]);

        return $account;
    }
}
