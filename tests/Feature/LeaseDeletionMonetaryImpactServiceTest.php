<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\Party;
use App\Models\Unit;
use App\Services\LeaseDeletion\LeaseDeletionMonetaryImpactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeaseDeletionMonetaryImpactServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_monetary_impact_is_read_only(): void
    {
        $lease = $this->createLease();

        $before = $this->fingerprint();

        $impact = app(
            LeaseDeletionMonetaryImpactService::class
        )->calculate($lease);

        $after = $this->fingerprint();

        $this->assertSame(
            $before,
            $after
        );

        $this->assertSame(
            $lease->id,
            $impact['lease_id']
        );

        $this->assertArrayHasKey(
            'invoices',
            $impact
        );

        $this->assertArrayHasKey(
            'payments',
            $impact
        );

        $this->assertArrayHasKey(
            'tenant_funds',
            $impact
        );

        $this->assertArrayHasKey(
            'owner',
            $impact
        );

        $this->assertArrayHasKey(
            'fully_classified',
            $impact
        );
    }

    public function test_empty_lease_has_zero_monetary_impact(): void
    {
        $lease = $this->createLease();

        $impact = app(
            LeaseDeletionMonetaryImpactService::class
        )->calculate($lease);

        $this->assertSame(
            0,
            $impact['invoices']['total']
        );

        $this->assertSame(
            0,
            $impact['invoices']['paid']
        );

        $this->assertSame(
            0,
            $impact['invoices']
                ['settlement']
                ['payment_allocations']
        );

        $this->assertSame(
            0,
            $impact['invoices']
                ['settlement']
                ['rent_reserve']
        );

        $this->assertSame(
            0,
            $impact['invoices']
                ['settlement']
                ['consumable_advance']
        );

        $this->assertSame(
            0,
            $impact['invoices']
                ['settlement']
                ['security_deposit']
        );

        $this->assertSame(
            0,
            $impact['invoices']['outstanding']
        );

        $this->assertSame(
            0,
            $impact['payments']['total']
        );

        $this->assertSame(
            0,
            $impact['tenant_funds']['total_balance']
        );

        $this->assertSame(
            0,
            $impact['owner']['credits']
        );

        $this->assertSame(
            0,
            $impact['owner']['debits']
        );

        $this->assertSame(
            0,
            $impact['owner']['net_lease_effect']
        );
    }

    public function test_invoice_outstanding_is_total_less_allocations(): void
    {
        if (
            ! Schema::hasTable('invoices')
            || ! Schema::hasTable(
                'payment_allocations'
            )
            || ! Schema::hasColumn(
                'payment_allocations',
                'amount'
            )
        ) {
            $this->markTestSkipped(
                'Required invoice allocation schema is unavailable.'
            );
        }

        $lease = $this->createLease();

        $invoiceId =
            $this->insertInvoice(
                $lease,
                1000
            );

        $paymentId =
            $this->insertPayment(
                $lease,
                600
            );

        $this->insertPaymentAllocation(
            $paymentId,
            $invoiceId,
            600
        );

        $impact = app(
            LeaseDeletionMonetaryImpactService::class
        )->calculate($lease);

        $this->assertSame(
            1000,
            $impact['invoices']['total']
        );

        $this->assertSame(
            600,
            $impact['invoices']['paid']
        );

        $this->assertSame(
            600,
            $impact['invoices']
                ['settlement']
                ['payment_allocations']
        );

        $this->assertSame(
            400,
            $impact['invoices']['outstanding']
        );
    }

    public function test_tenant_fund_balance_uses_credit_minus_debit_ledger_semantics(): void
    {
        $lease = $this->createLease();

        $accountId =
            DB::table(
                'tenant_fund_accounts'
            )->insertGetId([
                'lease_id' =>
                    $lease->id,

                'type' =>
                    'rent_reserve',

                'status' =>
                    'active',

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        DB::table(
            'tenant_fund_transactions'
        )->insert([
            [
                'tenant_fund_account_id' =>
                    $accountId,

                'direction' =>
                    'credit',

                'category' =>
                    'reserve_funding',

                'amount' =>
                    1000,

                'transaction_date' =>
                    '2026-01-02',

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ],
            [
                'tenant_fund_account_id' =>
                    $accountId,

                'direction' =>
                    'debit',

                'category' =>
                    'rent_consumption',

                'amount' =>
                    400,

                'transaction_date' =>
                    '2026-02-01',

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ],
        ]);

        $impact = app(
            LeaseDeletionMonetaryImpactService::class
        )->calculate($lease);

        $this->assertSame(
            1000,
            $impact['tenant_funds']['credits']
        );

        $this->assertSame(
            400,
            $impact['tenant_funds']['debits']
        );

        $this->assertSame(
            600,
            $impact['tenant_funds']['total_balance']
        );
    }

    public function test_owner_lease_effect_uses_credit_minus_debit_semantics(): void
    {
        $lease = $this->createLease();

        /*
         * OwnerAccount is consolidated per Owner Party.
         *
         * LeaseDeletionMonetaryImpactService must calculate only the
         * Lease-specific contribution from OwnerTransaction rows that carry
         * this Lease ID.
         */
        $owner = Party::query()->create([
            'type' =>
                'person',

            'name' =>
                'Phase 10B2 Owner',
        ]);

        $ownerAccount =
            OwnerAccount::query()->create([
                'party_id' =>
                    $owner->id,

                'status' =>
                    'active',
            ]);

        DB::table(
            'owner_transactions'
        )->insert([
            [
                'owner_account_id' =>
                    $ownerAccount->id,

                'lease_id' =>
                    $lease->id,

                'direction' =>
                    'credit',

                'category' =>
                    'rent_entitlement',

                'amount' =>
                    1000,

                'transaction_date' =>
                    '2026-01-02',

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ],
            [
                'owner_account_id' =>
                    $ownerAccount->id,

                'lease_id' =>
                    $lease->id,

                'direction' =>
                    'debit',

                'category' =>
                    'management_fee',

                'amount' =>
                    250,

                'transaction_date' =>
                    '2026-01-02',

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ],
        ]);

        $impact = app(
            LeaseDeletionMonetaryImpactService::class
        )->calculate($lease);

        $this->assertSame(
            2,
            $impact['owner']['count']
        );

        $this->assertSame(
            1000,
            $impact['owner']['credits']
        );

        $this->assertSame(
            250,
            $impact['owner']['debits']
        );

        $this->assertSame(
            750,
            $impact['owner']['net_lease_effect']
        );

        /*
         * Confirm the calculation itself made no Owner ledger mutation.
         */
        $this->assertSame(
            750,
            $ownerAccount->fresh()->balance()
        );
    }

    public function test_security_deposit_monetary_history_is_reported(): void
    {
        $lease = $this->createLease();

        /*
         * Security Deposit application is not a standalone record.
         *
         * It references:
         * - the Lease;
         * - the Invoice being settled;
         * - the TenantFundTransaction that debited the Security Deposit.
         */
        $invoiceId =
            $this->insertInvoice(
                $lease,
                1000
            );

        $securityDepositAccountId =
            DB::table(
                'tenant_fund_accounts'
            )->insertGetId([
                'lease_id' =>
                    $lease->id,

                'type' =>
                    'security_deposit',

                'status' =>
                    'active',

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        /*
         * Funding first establishes money held.
         */
        DB::table(
            'tenant_fund_transactions'
        )->insert([
            'tenant_fund_account_id' =>
                $securityDepositAccountId,

            'direction' =>
                'credit',

            'category' =>
                'deposit_funding',

            'amount' =>
                1000,

            'transaction_date' =>
                '2026-02-01',

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);

        /*
         * Applying Security Deposit to debt creates the corresponding debit.
         */
        $applicationTransactionId =
            DB::table(
                'tenant_fund_transactions'
            )->insertGetId([
                'tenant_fund_account_id' =>
                    $securityDepositAccountId,

                'direction' =>
                    'debit',

                'category' =>
                    'deposit_deduction',

                'amount' =>
                    300,

                'transaction_date' =>
                    '2026-03-01',

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        DB::table(
            'security_deposit_applications'
        )->insert([
            'lease_id' =>
                $lease->id,

            'invoice_id' =>
                $invoiceId,

            'tenant_fund_transaction_id' =>
                $applicationTransactionId,

            'amount' =>
                300,

            'application_date' =>
                '2026-03-01',

            'notes' =>
                'Phase 10B2 debt application',

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);

        /*
         * Deductions and settlement are separate operational history.
         */
        $this->insertExistingColumns(
            'security_deposit_deductions',
            [
                'lease_id' =>
                    $lease->id,

                'amount' =>
                    150,

                'description' =>
                    'Phase 10B2 deduction',

                'reason' =>
                    'Phase 10B2 deduction',

                'deduction_date' =>
                    '2026-03-02',

                'reference' =>
                    'SDD-10B2',

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]
        );

        $this->insertExistingColumns(
            'security_deposit_settlements',
            [
                'lease_id' =>
                    $lease->id,

                'deposit_amount' =>
                    1000,

                'deduction_amount' =>
                    150,

                'refund_amount' =>
                    550,

                'tenant_debt_amount' =>
                    300,

                'settlement_date' =>
                    '2026-03-03',

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]
        );

        $impact = app(
            LeaseDeletionMonetaryImpactService::class
        )->calculate($lease);

        $this->assertSame(
            1,
            $impact['security_deposit']
                ['applications']['count']
        );

        $this->assertSame(
            300,
            $impact['security_deposit']
                ['applications']['amount']
        );

        $this->assertSame(
            1,
            $impact['security_deposit']
                ['deductions']['count']
        );

        $this->assertSame(
            150,
            $impact['security_deposit']
                ['deductions']['amount']
        );

        $this->assertSame(
            1,
            $impact['security_deposit']
                ['settlements']['count']
        );

        $this->assertSame(
            1000,
            $impact['security_deposit']
                ['settlements']['deposit_amount']
        );

        $this->assertSame(
            150,
            $impact['security_deposit']
                ['settlements']['deduction_amount']
        );

        $this->assertSame(
            550,
            $impact['security_deposit']
                ['settlements']['refund_amount']
        );

        $this->assertSame(
            300,
            $impact['security_deposit']
                ['settlements']['tenant_debt_amount']
        );

        /*
         * The current held balance is ledger-derived:
         *
         * 1000 funded - 300 applied = 700.
         */
        $this->assertSame(
            700,
            $impact['tenant_funds']
                ['total_balance']
        );
    }

    private function createLease(): Lease
    {
        $tenant = Party::query()->create([
            'type' => 'person',
            'name' =>
                'Phase 10B2 Tenant',
        ]);

        $building =
            Building::query()->create([
                'name' =>
                    'Phase 10B2 Building',
            ]);

        $unit = Unit::query()->create([
            'building_id' =>
                $building->id,

            'name' =>
                'Phase 10B2 Unit',
        ]);

        return Lease::query()->create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'draft',
            'rent_amount' => 1000,
            'payment_frequency' =>
                'monthly',
            'due_day' => 1,
            'vat_rate' => 0,
            'proration_amount' => 0,
            'security_deposit_amount' => 0,
            'advance_payment_amount' => 0,
            'rent_reserve_amount' => 0,
            'management_fee_type' =>
                'none',
            'management_fee_value' => 0,
            'agent_commission_amount' => 0,
        ]);
    }

    private function insertInvoice(
        Lease $lease,
        int $amount
    ): int {
        $values = [
            'lease_id' => $lease->id,
            'invoice_number' =>
                'INV-10B2-' . uniqid(),
            'type' => 'rent',
            'period_start' =>
                '2026-01-01',
            'period_end' =>
                '2026-01-31',
            'issue_date' =>
                '2026-01-01',
            'due_date' =>
                '2026-01-01',
            'status' => 'issued',
            'total_amount' => $amount,
            'net_amount' => $amount,
            'vat_amount' => 0,
            'vat_rate' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return (int) DB::table(
            'invoices'
        )->insertGetId(
            $this->existingColumns(
                'invoices',
                $values
            )
        );
    }

    private function insertPayment(
        Lease $lease,
        int $amount
    ): int {
        $values = [
            'lease_id' => $lease->id,
            'amount' => $amount,
            'payment_date' =>
                '2026-01-02',
            'payment_method' => 'cash',
            'method' => 'cash',
            'reference' =>
                'PAY-10B2-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return (int) DB::table(
            'payments'
        )->insertGetId(
            $this->existingColumns(
                'payments',
                $values
            )
        );
    }

    private function insertPaymentAllocation(
        int $paymentId,
        int $invoiceId,
        int $amount
    ): void {
        $values = [
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table(
            'payment_allocations'
        )->insert(
            $this->existingColumns(
                'payment_allocations',
                $values
            )
        );
    }

    /**
     * Insert only columns that exist in the current schema.
     *
     * @param array<string, mixed> $values
     */
    private function insertExistingColumns(
        string $table,
        array $values
    ): void {
        $filtered =
            $this->existingColumns(
                $table,
                $values
            );

        DB::table($table)
            ->insert($filtered);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function existingColumns(
        string $table,
        array $values
    ): array {
        return collect($values)
            ->filter(
                fn ($value, $column) =>
                    Schema::hasColumn(
                        $table,
                        $column
                    )
            )
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function fingerprint(): array
    {
        $tables = [
            'leases',
            'invoices',
            'payments',
            'payment_allocations',
            'tenant_fund_accounts',
            'tenant_fund_transactions',
            'owner_transactions',
            'owner_payout_allocations',
            'security_deposit_applications',
            'security_deposit_deductions',
            'security_deposit_settlements',
            'journal_entries',
            'journal_lines',
            'activity_logs',
        ];

        $result = [];

        foreach ($tables as $table) {
            $result[$table] =
                Schema::hasTable($table)
                    ? DB::table($table)->count()
                    : 0;
        }

        return $result;
    }
}
