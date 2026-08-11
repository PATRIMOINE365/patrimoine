<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\SecurityDepositDeduction;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Concerns\AuthenticatesApiUser;

/**
 * Verifies the Patrimoine Security Deposit settlement API.
 */
class SecurityDepositApiTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApiUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Build a terminated Lease with a funded Security Deposit.
     *
     * @return array{
     *     lease: Lease,
     *     account: TenantFundAccount
     * }
     */
    private function createContext(
        int $depositAmount = 10000
    ): array {
        $building = Building::create([
            'name' => 'Security Deposit API Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Security Deposit API Tenant',
            'phone' => '0200000800',
            'email' => 'security-deposit-api@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'terminated',
        ]);

        $account = TenantFundAccount::create([
            'lease_id' => $lease->id,
            'type' => 'security_deposit',
            'status' => 'active',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,
            'direction' => 'credit',
            'category' => 'deposit_funding',
            'amount' => $depositAmount,
            'transaction_date' => '2026-01-01',
        ]);

        return compact(
            'lease',
            'account'
        );
    }

    /**
     * Remaining deposit is refunded after itemized deductions.
     */
    public function test_security_deposit_can_be_settled_with_refund(): void
    {
        $context = $this->createContext(10000);

        SecurityDepositDeduction::create([
            'lease_id' => $context['lease']->id,
            'description' => 'Damaged lock',
            'amount' => 3000,
            'deduction_date' => '2026-08-10',
        ]);

        $response = $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/settle",
            [
                'settlement_date' => '2026-08-11',
                'refund_voucher_number' => 'REF-API-001',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('deposit_amount', 10000)
            ->assertJsonPath('deduction_amount', 3000)
            ->assertJsonPath('refund_amount', 7000)
            ->assertJsonPath('tenant_debt_amount', 0)
            ->assertJsonPath(
                'refund_voucher_number',
                'REF-API-001'
            );

        $this->assertSame(
            0,
            $context['account']->fresh()->balance()
        );
    }

    /**
     * Deductions above the deposit create tenant debt.
     */
    public function test_security_deposit_settlement_can_create_tenant_debt(): void
    {
        $context = $this->createContext(10000);

        SecurityDepositDeduction::create([
            'lease_id' => $context['lease']->id,
            'description' => 'Major repairs',
            'amount' => 13000,
            'deduction_date' => '2026-08-10',
        ]);

        $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/settle",
            [
                'settlement_date' => '2026-08-11',
            ]
        )
            ->assertCreated()
            ->assertJsonPath('refund_amount', 0)
            ->assertJsonPath('tenant_debt_amount', 3000);

        $this->assertSame(
            0,
            $context['account']->fresh()->balance()
        );
    }

    /**
     * Final settlement cannot be generated twice.
     */
    public function test_security_deposit_cannot_be_settled_twice(): void
    {
        $context = $this->createContext();

        $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/settle",
            [
                'settlement_date' => '2026-08-11',
            ]
        )->assertCreated();

        $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/settle",
            [
                'settlement_date' => '2026-08-12',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'security_deposit',
            ]);
    }

    /**
     * Settlement requires an existing Security Deposit account.
     */
    public function test_security_deposit_account_is_required(): void
    {
        $context = $this->createContext();

        /*
         * Remove the empty funded account and its transaction so the Lease
         * genuinely has no Security Deposit account.
         */
        TenantFundTransaction::query()->delete();
        $context['account']->delete();

        $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/settle",
            [
                'settlement_date' => '2026-08-11',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'security_deposit',
            ]);
    }
}
