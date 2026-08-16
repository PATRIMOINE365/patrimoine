<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\Payment;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Freeze V1.0.3 Activity M manual financial Activity Log semantics.
 *
 * Each HTTP action represents one meaningful human business action.
 * Internal allocations, ledger entries, owner entitlements and similar
 * accounting consequences must not create additional Activity Log events.
 */
class ManualFinancialActivityLogTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    public function test_tenant_payment_is_one_business_event(): void
    {
        $context = $this->financialContext();

        $this->rentInvoice(
            $context['lease'],
            'INV-ACT-M-001',
            5000
        );

        $response = $this->postJson(
            '/api/payments',
            [
                'lease_id' => $context['lease']->id,
                'amount' => 5000,
                'payment_date' => '2026-08-16',
                'payment_method' => 'bank_transfer',
                'reference' => 'PAY-ACT-M-001',
            ]
        )->assertCreated();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'payment.recorded',
            $event->action
        );

        $this->assertSame(
            'payment',
            $event->entity_type
        );

        $this->assertSame(
            (string) $response->json('id'),
            $event->entity_id
        );

        $this->assertSame(
            5000,
            $event->snapshot['amount']
        );

        /*
         * FIFO allocation and owner entitlement are downstream accounting
         * consequences of this one operator action.
         */
        $this->assertDatabaseCount(
            'activity_logs',
            1
        );
    }

    public function test_failed_payment_is_not_logged(): void
    {
        $context = $this->financialContext();

        $context['lease']->update([
            'status' => 'draft',
        ]);

        $this->postJson(
            '/api/payments',
            [
                'lease_id' => $context['lease']->id,
                'amount' => 5000,
                'payment_date' => '2026-08-16',
                'payment_method' => 'bank_transfer',
            ]
        )->assertUnprocessable();

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    public function test_tenant_fund_classification_is_logged(): void
    {
        $context = $this->financialContext();

        $payment = Payment::create([
            'lease_id' => $context['lease']->id,
            'amount' => 6000,
            'payment_date' => '2026-08-16',
            'payment_method' => 'bank_transfer',
        ]);

        $this->postJson(
            "/api/payments/{$payment->id}/tenant-funds",
            [
                'fund_type' => 'rent_reserve',
                'amount' => 2000,
                'transaction_date' => '2026-08-16',
                'reference' => 'FUND-ACT-M-001',
            ]
        )->assertCreated();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'tenant_fund.classified',
            $event->action
        );

        $this->assertSame(
            'reserve_funding',
            $event->snapshot['category']
        );

        $this->assertSame(
            2000,
            $event->snapshot['amount']
        );
    }

    public function test_rent_reserve_consumption_is_logged_once(): void
    {
        $context = $this->financialContext(
            leaseStatus: 'notice'
        );

        $context['lease']->update([
            'termination_notice_date' => '2026-07-15',
        ]);

        $invoice = $this->rentInvoice(
            $context['lease'],
            'INV-ACT-M-RESERVE',
            5000
        );

        $account = TenantFundAccount::create([
            'lease_id' => $context['lease']->id,
            'type' => 'rent_reserve',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'reserve_funding',
            'amount' => 10000,
            'transaction_date' => '2026-01-01',
        ]);

        $this->postJson(
            "/api/tenant-funds/{$account->id}/consume-rent",
            [
                'invoice_id' => $invoice->id,
                'amount' => 5000,
                'transaction_date' => '2026-08-16',
            ]
        )->assertCreated();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'rent_reserve.consumed',
            $event->action
        );

        $this->assertSame(
            'rent_consumption',
            $event->snapshot['category']
        );

        $this->assertDatabaseCount(
            'activity_logs',
            1
        );
    }

    public function test_consumable_advance_consumption_is_logged_once(): void
    {
        $context = $this->financialContext();

        $invoice = $this->rentInvoice(
            $context['lease'],
            'INV-ACT-M-ADVANCE',
            5000
        );

        $account = TenantFundAccount::create([
            'lease_id' => $context['lease']->id,
            'type' => 'consumable_advance',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'advance_funding',
            'amount' => 10000,
            'transaction_date' => '2026-01-01',
        ]);

        $this->postJson(
            "/api/tenant-funds/{$account->id}/consume-advance",
            [
                'invoice_id' => $invoice->id,
                'amount' => 3000,
                'transaction_date' => '2026-08-16',
            ]
        )->assertCreated();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'consumable_advance.consumed',
            $event->action
        );

        $this->assertSame(
            'advance_consumption',
            $event->snapshot['category']
        );

        $this->assertDatabaseCount(
            'activity_logs',
            1
        );
    }

    public function test_security_deposit_deduction_is_logged(): void
    {
        $context = $this->financialContext(
            leaseStatus: 'terminated'
        );

        $this->securityDepositAccount(
            $context['lease'],
            10000
        );

        $response = $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/deductions",
            [
                'description' => 'Damaged door',
                'amount' => 1500,
                'deduction_date' => '2026-08-16',
                'reference' => 'SD-ACT-M-001',
            ]
        )->assertCreated();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'security_deposit.deduction_added',
            $event->action
        );

        $this->assertSame(
            (string) $response->json('id'),
            $event->entity_id
        );

        $this->assertSame(
            1500,
            $event->snapshot['amount']
        );
    }

    public function test_security_deposit_settlement_is_one_event(): void
    {
        $context = $this->financialContext(
            leaseStatus: 'terminated'
        );

        $this->securityDepositAccount(
            $context['lease'],
            10000
        );

        $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/deductions",
            [
                'description' => 'Repairs',
                'amount' => 2500,
                'deduction_date' => '2026-08-15',
            ]
        )->assertCreated();

        /*
         * This test concerns settlement itself, not the preceding human
         * deduction action.
         */
        ActivityLog::query()->delete();

        $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/settle",
            [
                'settlement_date' => '2026-08-16',
                'notes' => 'Final close-out.',
            ]
        )->assertCreated();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'security_deposit.settled',
            $event->action
        );

        $this->assertSame(
            10000,
            $event->snapshot['deposit_amount']
        );

        $this->assertSame(
            2500,
            $event->snapshot['deduction_amount']
        );

        $this->assertSame(
            7500,
            $event->snapshot['refund_amount']
        );

        /*
         * Internal refund/deduction fund transactions are consequences of
         * settlement rather than separate human Activity Log events.
         */
        $this->assertDatabaseCount(
            'activity_logs',
            1
        );
    }

    public function test_owner_expense_is_logged_once(): void
    {
        $context = $this->financialContext();

        $response = $this->postJson(
            '/api/owner-expenses',
            [
                'building_id' => $context['building']->id,
                'unit_id' => $context['unit']->id,
                'description' => 'Generator servicing',
                'amount' => 2000,
                'expense_date' => '2026-08-16',
                'reference' => 'EXP-ACT-M-001',
            ]
        )->assertCreated();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'owner_expense.recorded',
            $event->action
        );

        $this->assertSame(
            (string) $response->json('expense.id'),
            $event->entity_id
        );

        $this->assertSame(
            2000,
            $event->snapshot['amount']
        );

        /*
         * Owner ledger allocation is automatic and must not create another
         * Activity Log event.
         */
        $this->assertDatabaseCount(
            'activity_logs',
            1
        );
    }

    public function test_owner_deposit_is_logged(): void
    {
        $context = $this->financialContext();

        $response = $this->postJson(
            "/api/owner-accounts/{$context['owner_account']->id}/deposits",
            [
                'amount' => 4000,
                'transaction_date' => '2026-08-16',
                'payment_method' => 'bank_transfer',
                'deposit_purpose' => 'general_funding',
                'reference' => 'DEP-ACT-M-001',
            ]
        )->assertCreated();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'owner_deposit.recorded',
            $event->action
        );

        $this->assertSame(
            (string) $response->json('transaction.id'),
            $event->entity_id
        );

        $this->assertSame(
            'owner_deposit',
            $event->snapshot['category']
        );
    }

    public function test_owner_adjustment_is_logged(): void
    {
        $context = $this->financialContext();

        $this->postJson(
            "/api/owner-accounts/{$context['owner_account']->id}/adjustments",
            [
                'direction' => 'debit',
                'amount' => 750,
                'transaction_date' => '2026-08-16',
                'reason' => 'Manual accounting correction.',
                'reference' => 'ADJ-ACT-M-001',
            ]
        )->assertCreated();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'owner_adjustment.recorded',
            $event->action
        );

        $this->assertSame(
            'adjustment',
            $event->snapshot['category']
        );

        $this->assertSame(
            'debit',
            $event->snapshot['direction']
        );
    }

    public function test_owner_payout_is_logged_once(): void
    {
        $context = $this->financialContext();

        OwnerTransaction::create([
            'owner_account_id' => $context['owner_account']->id,
            'direction' => 'credit',
            'category' => 'rent_entitlement',
            'amount' => 10000,
            'transaction_date' => '2026-08-01',
        ]);

        $response = $this->postJson(
            "/api/owner-accounts/{$context['owner_account']->id}/payouts",
            [
                'amount' => 6000,
                'payout_date' => '2026-08-16',
                'payment_method' => 'bank_transfer',
                'reference' => 'PAYOUT-ACT-M-001',
            ]
        )->assertCreated();

        $event = ActivityLog::query()->sole();

        $this->assertSame(
            'owner_payout.recorded',
            $event->action
        );

        $this->assertSame(
            (string) $response->json('payout.id'),
            $event->entity_id
        );

        /*
         * Payout allocation and ledger debit remain internal accounting
         * consequences of the one payout command.
         */
        $this->assertDatabaseCount(
            'activity_logs',
            1
        );
    }

    public function test_failed_financial_action_creates_no_success_event(): void
    {
        $context = $this->financialContext();

        $this->postJson(
            "/api/owner-accounts/{$context['owner_account']->id}/payouts",
            [
                'amount' => 5000,
                'payout_date' => '2026-08-16',
                'payment_method' => 'bank_transfer',
            ]
        )->assertUnprocessable();

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
    }

    /**
     * @return array{
     *   building: Building,
     *   unit: Unit,
     *   tenant: Party,
     *   owner: Party,
     *   owner_account: OwnerAccount,
     *   lease: Lease
     * }
     */
    private function financialContext(
        string $leaseStatus = 'active'
    ): array {
        $building = Building::create([
            'name' => 'Activity M Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Activity M Owner',
            'phone' => '0200005001',
            'email' => 'activity-m-owner@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100,
        ]);

        $ownerAccount =
            $owner->ownerAccount()->firstOrFail();

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Activity M Unit',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Activity M Tenant',
            'phone' => '0200005002',
            'email' => 'activity-m-tenant@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2025-01-01',
            'rent_amount' => 5000,
            'status' => $leaseStatus,
        ]);

        return [
            'building' => $building,
            'unit' => $unit,
            'tenant' => $tenant,
            'owner' => $owner,
            'owner_account' => $ownerAccount,
            'lease' => $lease,
        ];
    }

    private function rentInvoice(
        Lease $lease,
        string $number,
        int $amount
    ): Invoice {
        return Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => $number,
            'type' => 'rent',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'issue_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'issued',
            'total_amount' => $amount,
            'vat_rate' => 0,
            'net_amount' => $amount,
            'vat_amount' => 0,
        ]);
    }

    private function securityDepositAccount(
        Lease $lease,
        int $amount
    ): TenantFundAccount {
        $account = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'security_deposit',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'deposit_funding',
            'amount' => $amount,
            'transaction_date' => '2026-01-01',
        ]);

        return $account;
    }
}
