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
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;
use App\Models\ApplicationSetting;

/**
 * Verifies the Patrimoine Security Deposit operational API.
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
     * Build a Lease with a funded Security Deposit.
     *
     * @return array{
     *     lease: Lease,
     *     account: TenantFundAccount
     * }
     */
    private function createContext(
        int $depositAmount = 10000,
        string $status = 'terminated'
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
            'security_deposit_amount' => $depositAmount,
            'status' => $status,
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
     * Security Deposit endpoint returns current operational position.
     */
    public function test_security_deposit_position_can_be_retrieved(): void
    {
        $context =
            $this->createContext(
                10000
            );

        SecurityDepositDeduction::create([
            'lease_id' =>
                $context['lease']->id,

            'description' =>
                'Damaged lock',

            'amount' =>
                3000,

            'deduction_date' =>
                '2026-08-10',
        ]);

        $this->getJson(
            "/api/leases/{$context['lease']->id}/security-deposit"
        )
            ->assertOk()
            ->assertJsonPath(
                'contractual_amount',
                10000
            )
            ->assertJsonPath(
                'held_balance',
                10000
            )
            ->assertJsonPath(
                'deduction_total',
                3000
            )
            ->assertJsonPath(
                'estimated_refund',
                7000
            )
            ->assertJsonPath(
                'estimated_tenant_debt',
                0
            )
            ->assertJsonPath(
                'deductions.0.description',
                'Damaged lock'
            )
            ->assertJsonPath(
                'deductions.0.amount',
                3000
            );
    }

    /**
     * Itemized deduction can be recorded through the API.
     */
    public function test_security_deposit_deduction_can_be_created(): void
    {
        $context =
            $this->createContext();

        $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/deductions",
            [
                'description' =>
                    'Repainting',

                'amount' =>
                    2500,

                'deduction_date' =>
                    '2026-08-10',

                'reference' =>
                    'INSPECTION-001',

                'notes' =>
                    'Bedroom wall repainting.',
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'description',
                'Repainting'
            )
            ->assertJsonPath(
                'amount',
                2500
            )
            ->assertJsonPath(
                'reference',
                'INSPECTION-001'
            );

        $this->assertDatabaseHas(
            'security_deposit_deductions',
            [
                'lease_id' =>
                    $context['lease']->id,

                'description' =>
                    'Repainting',

                'amount' =>
                    2500,

                'reference' =>
                    'INSPECTION-001',
            ]
        );
    }

    /**
     * Deduction endpoint validates required itemized details.
     */
    public function test_security_deposit_deduction_requires_valid_fields(): void
    {
        $context =
            $this->createContext();

        $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/deductions",
            [
                'description' =>
                    '',

                'amount' =>
                    0,

                'deduction_date' =>
                    '',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'description',
                'amount',
                'deduction_date',
            ]);
    }

    /**
     * Final deductions cannot be added while the tenancy is still active.
     */
    public function test_security_deposit_deduction_requires_terminated_lease(): void
    {
        $context =
            $this->createContext(
                depositAmount:
                    10000,

                status:
                    'active'
            );

        $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/deductions",
            [
                'description' =>
                    'Damaged lock',

                'amount' =>
                    1000,

                'deduction_date' =>
                    '2026-08-10',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'security_deposit',
            ]);

        $this->assertDatabaseCount(
            'security_deposit_deductions',
            0
        );
    }

    /**
     * Deductions cannot be altered after final settlement.
     */
    public function test_security_deposit_deduction_cannot_be_added_after_settlement(): void
    {
        $context =
            $this->createContext();

        $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/settle",
            [
                'settlement_date' =>
                    '2026-08-11',
            ]
        )->assertCreated();

        $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/deductions",
            [
                'description' =>
                    'Late deduction',

                'amount' =>
                    1000,

                'deduction_date' =>
                    '2026-08-12',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'security_deposit',
            ]);

        $this->assertDatabaseCount(
            'security_deposit_deductions',
            0
        );
    }

    /**
     * Remaining deposit is refunded after itemized deductions.
     */
    public function test_security_deposit_can_be_settled_with_refund(): void
    {
        $context =
            $this->createContext(
                10000
            );

        SecurityDepositDeduction::create([
            'lease_id' =>
                $context['lease']->id,

            'description' =>
                'Damaged lock',

            'amount' =>
                3000,

            'deduction_date' =>
                '2026-08-10',
        ]);

        $response =
            $this->postJson(
                "/api/leases/{$context['lease']->id}/security-deposit/settle",
                [
                    'settlement_date' =>
                        '2026-08-11',

                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'deposit_amount',
                10000
            )
            ->assertJsonPath(
                'deduction_amount',
                3000
            )
            ->assertJsonPath(
                'refund_amount',
                7000
            )
            ->assertJsonPath(
                'tenant_debt_amount',
                0
            )
            ->assertJsonPath(
                'refund_voucher_number',
                sprintf(
                    'SDV-%06d',
                    $response->json('id')
                )
            );

        $this->assertSame(
            0,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    /**
     * Final settlement endpoint returns the immutable snapshot afterward.
     */
    public function test_security_deposit_position_returns_settlement_snapshot(): void
    {
        $context =
            $this->createContext(
                10000
            );

        SecurityDepositDeduction::create([
            'lease_id' =>
                $context['lease']->id,

            'description' =>
                'Cleaning',

            'amount' =>
                1500,

            'deduction_date' =>
                '2026-08-10',
        ]);

        $settlementResponse = $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/settle",
            [
                'settlement_date' =>
                    '2026-08-11',

            ]
        );

        $settlementResponse->assertCreated();

        $this->getJson(
            "/api/leases/{$context['lease']->id}/security-deposit"
        )
            ->assertOk()
            ->assertJsonPath(
                'held_balance',
                0
            )
            ->assertJsonPath(
                'settlement.deposit_amount',
                10000
            )
            ->assertJsonPath(
                'settlement.deduction_amount',
                1500
            )
            ->assertJsonPath(
                'settlement.refund_amount',
                8500
            )
            ->assertJsonPath(
                'settlement.tenant_debt_amount',
                0
            )
            ->assertJsonPath(
                'settlement.refund_voucher_number',
                sprintf(
                    'SDV-%06d',
                    $settlementResponse->json('id')
                )
            )
            /*
             * Preview values become the historical settlement values after
             * close-out rather than being recalculated from the zero balance.
             */
            ->assertJsonPath(
                'estimated_refund',
                8500
            );
    }

    /**
     * Deductions above the deposit create tenant debt.
     */
    public function test_security_deposit_settlement_can_create_tenant_debt(): void
    {
        $context =
            $this->createContext(
                10000
            );

        SecurityDepositDeduction::create([
            'lease_id' =>
                $context['lease']->id,

            'description' =>
                'Major repairs',

            'amount' =>
                13000,

            'deduction_date' =>
                '2026-08-10',
        ]);

        $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/settle",
            [
                'settlement_date' =>
                    '2026-08-11',
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'refund_amount',
                0
            )
            ->assertJsonPath(
                'tenant_debt_amount',
                3000
            );

        $this->assertSame(
            0,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    /**
     * Final settlement cannot be generated twice.
     */
    public function test_security_deposit_cannot_be_settled_twice(): void
    {
        $context =
            $this->createContext();

        $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/settle",
            [
                'settlement_date' =>
                    '2026-08-11',
            ]
        )->assertCreated();

        $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/settle",
            [
                'settlement_date' =>
                    '2026-08-12',
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
        $context =
            $this->createContext();

        /*
         * Remove the funded account and its ledger entry so the Lease
         * genuinely has no Security Deposit account.
         */
        TenantFundTransaction::query()
            ->delete();

        $context['account']
            ->delete();

        $this->postJson(
            "/api/leases/{$context['lease']->id}/security-deposit/settle",
            [
                'settlement_date' =>
                    '2026-08-11',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'security_deposit',
            ]);
    }

    /**
     * Security Deposit service failures follow the configured language.
     */
    public function test_security_deposit_business_error_renders_in_french(): void
    {
        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $context = $this->createContext();

        /*
         * Mirror the existing missing-account regression case so the
         * translated business rule is genuinely exercised.
         */
        TenantFundTransaction::query()
            ->delete();

        $context['account']
            ->delete();

        $this
            ->postJson(
                "/api/leases/{$context['lease']->id}/security-deposit/settle",
                [
                    'settlement_date' => now()->toDateString(),
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.security_deposit.0',
                'Aucun compte de dépôt de garantie n’existe pour ce bail.'
            );
    }

}
