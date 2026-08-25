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
use App\Services\Accounting\SystemChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

class TenantFundTransferApiTest extends TestCase
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

    public function test_funds_can_be_transferred_between_leases_of_the_same_tenant(): void
    {
        [$source, $destination] =
            $this->twoFundedAccountsForOneTenant(
                sourceAmount: 30000,

                destinationAmount: 10000
            );

        $response =
            $this->postJson(
                '/api/tenant-funds/transfers',
                [
                    'source_account_id' => $source->id,

                    'destination_account_id' => $destination->id,

                    'amount' => 12000,

                    'reason' => 'Reallocate held reserve to consumables.',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'transfer.debit_transaction.direction',
                'debit'
            )
            ->assertJsonPath(
                'transfer.debit_transaction.category',
                'transfer'
            )
            ->assertJsonPath(
                'transfer.credit_transaction.direction',
                'credit'
            )
            ->assertJsonPath(
                'transfer.credit_transaction.category',
                'transfer'
            )
            ->assertJsonPath(
                'transfer.source_balance',
                18000
            )
            ->assertJsonPath(
                'transfer.destination_balance',
                22000
            );

        /*
         * Both legs share one generated TRF voucher reference.
         */
        $reference =
            $response->json(
                'transfer.debit_transaction.reference'
            );

        $this->assertMatchesRegularExpression(
            '/^TRF-\d{6}$/',
            $reference
        );

        $this->assertSame(
            $reference,
            $response->json(
                'transfer.credit_transaction.reference'
            )
        );

        $this->assertDatabaseHas(
            'tenant_fund_transactions',
            [
                'tenant_fund_account_id' => $source->id,

                'direction' => 'debit',

                'category' => 'transfer',

                'amount' => 12000,

                'reference' => $reference,
            ]
        );

        $this->assertDatabaseHas(
            'tenant_fund_transactions',
            [
                'tenant_fund_account_id' => $destination->id,

                'direction' => 'credit',

                'category' => 'transfer',

                'amount' => 12000,

                'reference' => $reference,
            ]
        );

        $this->assertSame(
            18000,
            $source->fresh()->balance()
        );

        $this->assertSame(
            22000,
            $destination->fresh()->balance()
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' => 'tenant_fund.transfer_recorded',

                'entity_type' => 'tenant_fund_transaction',
            ]
        );
    }

    public function test_transfer_cannot_exceed_source_balance(): void
    {
        [$source, $destination] =
            $this->twoFundedAccountsForOneTenant(
                sourceAmount: 5000,

                destinationAmount: 0
            );

        $this->postJson(
            '/api/tenant-funds/transfers',
            [
                'source_account_id' => $source->id,

                'destination_account_id' => $destination->id,

                'amount' => 5001,

                'reason' => 'Overdraw attempt.',
            ]
        )->assertUnprocessable();

        $this->assertSame(
            5000,
            $source->fresh()->balance()
        );

        $this->assertSame(
            0,
            $destination->fresh()->balance()
        );

        $this->assertSame(
            0,
            TenantFundTransaction::query()
                ->where('category', 'transfer')
                ->count()
        );
    }

    public function test_transfer_is_rejected_between_different_tenants(): void
    {
        [$source] =
            $this->twoFundedAccountsForOneTenant(
                sourceAmount: 10000,

                destinationAmount: 0
            );

        $otherTenantAccount =
            $this->fundedAccountForSeparateTenant(
                8000
            );

        $this->postJson(
            '/api/tenant-funds/transfers',
            [
                'source_account_id' => $source->id,

                'destination_account_id' => $otherTenantAccount->id,

                'amount' => 1000,

                'reason' => 'Cross-tenant attempt.',
            ]
        )->assertUnprocessable();

        $this->assertSame(
            0,
            TenantFundTransaction::query()
                ->where('category', 'transfer')
                ->count()
        );
    }

    public function test_transfer_requires_a_reason(): void
    {
        [$source, $destination] =
            $this->twoFundedAccountsForOneTenant(
                sourceAmount: 10000,

                destinationAmount: 0
            );

        $this->postJson(
            '/api/tenant-funds/transfers',
            [
                'source_account_id' => $source->id,

                'destination_account_id' => $destination->id,

                'amount' => 1000,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'reason',
            ]);
    }

    public function test_viewer_cannot_record_a_transfer(): void
    {
        [$source, $destination] =
            $this->twoFundedAccountsForOneTenant(
                sourceAmount: 10000,

                destinationAmount: 0
            );

        $this->authenticateApiUser('viewer');

        $this->postJson(
            '/api/tenant-funds/transfers',
            [
                'source_account_id' => $source->id,

                'destination_account_id' => $destination->id,

                'amount' => 1000,

                'reason' => 'Viewer attempt.',
            ]
        )->assertForbidden();
    }

    public function test_transfer_voucher_pdf_downloads_for_the_debit_leg(): void
    {
        [$source, $destination] =
            $this->twoFundedAccountsForOneTenant(
                sourceAmount: 20000,

                destinationAmount: 0
            );

        $this->postJson(
            '/api/tenant-funds/transfers',
            [
                'source_account_id' => $source->id,

                'destination_account_id' => $destination->id,

                'amount' => 3000,

                'reason' => 'Voucher download test.',
            ]
        )->assertCreated();

        $debitTransaction =
            TenantFundTransaction::query()
                ->where('category', 'transfer')
                ->where('direction', 'debit')
                ->sole();

        $this->get(
            '/api/tenant-fund-transfers/'
            .$debitTransaction->id
            .'/voucher'
        )
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/pdf'
            );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' => 'tenant_fund_transfer_voucher.downloaded',

                'entity_type' => 'tenant_fund_transaction',
            ]
        );
    }

    public function test_transfer_voucher_rejects_a_non_transfer_transaction(): void
    {
        [$source] =
            $this->twoFundedAccountsForOneTenant(
                sourceAmount: 20000,

                destinationAmount: 0
            );

        /*
         * The opening funding credit is not the debit leg of a Transfer.
         */
        $fundingTransaction =
            TenantFundTransaction::query()
                ->where(
                    'tenant_fund_account_id',
                    $source->id
                )
                ->sole();

        $this->getJson(
            '/api/tenant-fund-transfers/'
            .$fundingTransaction->id
            .'/voucher'
        )->assertUnprocessable();
    }

    public function test_transfer_posts_balanced_journal(): void
    {
        $this->createCompletedCutover();

        [$source, $destination] =
            $this->twoFundedAccountsForOneTenant(
                sourceAmount: 10000,

                destinationAmount: 0
            );

        $this->postJson(
            '/api/tenant-funds/transfers',
            [
                'source_account_id' => $source->id,

                'destination_account_id' => $destination->id,

                'amount' => 4000,

                'reason' => 'Journal integration test.',
            ]
        )->assertCreated();

        $entry =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    AccountingEventMap::EVENT_TENANT_FUND_TRANSFER
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
                        === SystemChartOfAccounts::CONSUMABLE_ADVANCE_HELD
                    && (int) $line->credit_amount
                        === 4000
            )
        );
    }

    /**
     * V1.0.8: with fund accounts provisioned eagerly on the Lease, a
     * Transfer must work between EVERY ordered pair of the three account
     * types — Security Deposit included — with a balanced Journal entry
     * reclassifying between the correct liability accounts, and an
     * Activity Log record per transfer.
     */
    public function test_transfer_reaches_every_fund_account_pair_on_a_provisioned_lease(): void
    {
        $this->createCompletedCutover();

        $building =
            Building::create([
                'name' => 'Pairwise Transfer Building',
            ]);

        $tenant =
            Party::create([
                'type' => 'person',

                'name' => 'Pairwise Transfer Tenant',

                'phone' => '0200005902',

                'email' => 'pairwise-'
                    .uniqid()
                    .'@example.test',
            ]);

        $unit =
            Unit::create([
                'building_id' => $building->id,

                'name' => 'Unit P-1',
            ]);

        $lease =
            Lease::create([
                'unit_id' => $unit->id,

                'tenant_id' => $tenant->id,

                'start_date' => now()->startOfMonth()->toDateString(),

                'rent_amount' => 5000,

                'payment_frequency' => 'monthly',

                'status' => 'active',
            ]);

        app(
            \App\Services\LeaseInitializationService::class
        )->initialize($lease);

        $accounts =
            TenantFundAccount::where('lease_id', $lease->id)
                ->get()
                ->keyBy('type');

        $this->assertCount(3, $accounts);

        /*
         * Seed each provisioned account so every source can afford the
         * 1,000 moved along each of its two outgoing pairs.
         */
        foreach ([
            'rent_reserve' => 'reserve_funding',

            'consumable_advance' => 'advance_funding',

            'security_deposit' => 'deposit_funding',
        ] as $type => $category) {
            TenantFundTransaction::create([
                'tenant_fund_account_id' => $accounts[$type]->id,

                'direction' => 'credit',

                'category' => $category,

                'amount' => 6000,

                'transaction_date' => now()->toDateString(),
            ]);
        }

        $types = [
            'rent_reserve',
            'consumable_advance',
            'security_deposit',
        ];

        foreach ($types as $sourceType) {
            foreach ($types as $destinationType) {
                if ($sourceType === $destinationType) {
                    continue;
                }

                $this->postJson(
                    '/api/tenant-funds/transfers',
                    [
                        'source_account_id' => $accounts[$sourceType]->id,

                        'destination_account_id' => $accounts[$destinationType]->id,

                        'amount' => 1000,

                        'reason' => sprintf(
                            'Pairwise move %s to %s.',
                            $sourceType,
                            $destinationType
                        ),
                    ]
                )->assertCreated();
            }
        }

        /*
         * Six transfers of 1,000: every account was debited twice and
         * credited twice, so all balances end where they started.
         */
        foreach ($types as $type) {
            $this->assertSame(
                6000,
                $accounts[$type]->fresh()->balance()
            );
        }

        $entries =
            JournalEntry::query()
                ->with('lines')
                ->where(
                    'transaction_type',
                    AccountingEventMap::EVENT_TENANT_FUND_TRANSFER
                )
                ->get();

        $this->assertCount(6, $entries);

        foreach ($entries as $entry) {
            $this->assertTrue(
                $entry->isBalanced()
            );
        }

        /*
         * Spot-check the deposit-involving reclassification: money leaving
         * Security Deposit must debit its held-liability account and
         * credit the destination fund's.
         */
        $depositToReserve =
            $entries->first(
                fn ($entry): bool => $entry->lines->contains(
                    fn ($line): bool => $line->account_code_snapshot
                            === SystemChartOfAccounts::SECURITY_DEPOSIT_HELD
                        && (int) $line->debit_amount === 1000
                )
                && $entry->lines->contains(
                    fn ($line): bool => $line->account_code_snapshot
                            === SystemChartOfAccounts::RENT_RESERVE_HELD
                        && (int) $line->credit_amount === 1000
                )
            );

        $this->assertNotNull(
            $depositToReserve,
            'Expected a Journal entry reclassifying Security Deposit into Rent Reserve.'
        );

        $this->assertSame(
            6,
            \App\Models\ActivityLog::where(
                'action',
                'tenant_fund.transfer_recorded'
            )->count()
        );
    }

    private function createCompletedCutover(): void
    {
        AccountingCutover::create([
            'cutover_key' => AccountingCutover::V105_OPENING_BALANCE,

            'cutover_date' => '2026-08-20',

            'status' => AccountingCutover::STATUS_COMPLETED,

            'position_count' => 0,

            'journal_entry_count' => 0,

            'completed_at' => now(),

            'metadata' => [],
        ]);
    }

    /**
     * Create one tenant holding two funded fund accounts across two Leases:
     *
     * - a Rent Reserve on the first Lease (transfer source);
     * - a Consumable Advance on the second Lease (transfer destination).
     *
     * @return array{0: TenantFundAccount, 1: TenantFundAccount}
     */
    private function twoFundedAccountsForOneTenant(
        int $sourceAmount,
        int $destinationAmount
    ): array {
        $building =
            Building::create([
                'name' => 'Transfer Test Building',
            ]);

        $tenant =
            Party::create([
                'type' => 'person',

                'name' => 'Transfer Test Tenant',

                'phone' => '0200005900',

                'email' => 'transfer-'
                    .uniqid()
                    .'@example.test',
            ]);

        $source =
            $this->fundAccount(
                building: $building,

                tenant: $tenant,

                unitName: 'Unit T-1',

                type: 'rent_reserve',

                amount: $sourceAmount
            );

        $destination =
            $this->fundAccount(
                building: $building,

                tenant: $tenant,

                unitName: 'Unit T-2',

                type: 'consumable_advance',

                amount: $destinationAmount
            );

        return [
            $source,

            $destination,
        ];
    }

    private function fundedAccountForSeparateTenant(
        int $amount
    ): TenantFundAccount {
        $building =
            Building::create([
                'name' => 'Other Tenant Building',
            ]);

        $tenant =
            Party::create([
                'type' => 'person',

                'name' => 'Other Transfer Tenant',

                'phone' => '0200005901',

                'email' => 'other-transfer-'
                    .uniqid()
                    .'@example.test',
            ]);

        return $this->fundAccount(
            building: $building,

            tenant: $tenant,

            unitName: 'Unit O-1',

            type: 'consumable_advance',

            amount: $amount
        );
    }

    private function fundAccount(
        Building $building,
        Party $tenant,
        string $unitName,
        string $type,
        int $amount
    ): TenantFundAccount {
        $unit =
            Unit::create([
                'building_id' => $building->id,

                'name' => $unitName,
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

        return $account;
    }
}
