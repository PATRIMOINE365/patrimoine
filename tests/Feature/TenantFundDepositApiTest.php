<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Payment;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantFundDepositApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_creates_payment_and_fully_funds_selected_account(): void
    {
        $user = $this->createUser();
        $lease = $this->createLease();

        $response = $this
            ->actingAs($user)
            ->postJson(
                '/api/tenant-fund-deposits',
                [
                    'lease_id' =>
                        $lease->id,

                    'fund_type' =>
                        'rent_reserve',

                    'amount' =>
                        700,

                    'transaction_date' =>
                        '2026-08-19',

                    'payment_method' =>
                        'bank_transfer',

                    'reference' =>
                        'DEP-001',

                    'notes' =>
                        'Rent reserve deposit',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'payment.amount',
                700
            )
            ->assertJsonPath(
                'payment.payment_method',
                'bank_transfer'
            )
            ->assertJsonPath(
                'transaction.amount',
                700
            );

        $payment =
            Payment::query()
                ->firstOrFail();

        $this->assertSame(
            0,
            $payment->allocatedAmount()
        );

        $this->assertSame(
            700,
            $payment->unallocatedAmount()
        );

        $account =
            TenantFundAccount::query()
                ->where(
                    'lease_id',
                    $lease->id
                )
                ->where(
                    'type',
                    'rent_reserve'
                )
                ->firstOrFail();

        $this->assertSame(
            700,
            $account->balance()
        );

        $this->assertDatabaseHas(
            'tenant_fund_transactions',
            [
                'tenant_fund_account_id' =>
                    $account->id,

                'payment_id' =>
                    $payment->id,

                'direction' =>
                    'credit',

                'category' =>
                    'reserve_funding',

                'amount' =>
                    700,
            ]
        );
    }

    public function test_cash_deposit_uses_authenticated_user_as_receiver(): void
    {
        $user = $this->createUser();
        $lease = $this->createLease();

        $this
            ->actingAs($user)
            ->postJson(
                '/api/tenant-fund-deposits',
                [
                    'lease_id' =>
                        $lease->id,

                    'fund_type' =>
                        'security_deposit',

                    'amount' =>
                        500,

                    'transaction_date' =>
                        '2026-08-19',

                    'payment_method' =>
                        'cash',
                ]
            )
            ->assertCreated()
            ->assertJsonPath(
                'payment.cash_receiver_user_id',
                $user->id
            )
            ->assertJsonPath(
                'payment.cash_receiver_name',
                $user->name
            );

        $payment =
            Payment::query()
                ->firstOrFail();

        $this->assertSame(
            $user->id,
            $payment->cash_receiver_user_id
        );

        $this->assertSame(
            $user->name,
            $payment->cash_receiver_name
        );

        $this->assertNull(
            $payment->collector_name
        );
    }

    public function test_deposit_rejects_draft_lease(): void
    {
        $user = $this->createUser();

        $lease =
            $this->createLease(
                status: 'draft'
            );

        $this
            ->actingAs($user)
            ->postJson(
                '/api/tenant-fund-deposits',
                [
                    'lease_id' =>
                        $lease->id,

                    'fund_type' =>
                        'rent_reserve',

                    'amount' =>
                        500,

                    'transaction_date' =>
                        '2026-08-19',

                    'payment_method' =>
                        'bank_transfer',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'lease_id',
            ]);

        $this->assertSame(
            0,
            Payment::count()
        );

        $this->assertSame(
            0,
            TenantFundTransaction::count()
        );
    }

    public function test_deposit_rejects_invalid_fund_type(): void
    {
        $user = $this->createUser();
        $lease = $this->createLease();

        $this
            ->actingAs($user)
            ->postJson(
                '/api/tenant-fund-deposits',
                [
                    'lease_id' =>
                        $lease->id,

                    'fund_type' =>
                        'invalid_fund',

                    'amount' =>
                        500,

                    'transaction_date' =>
                        '2026-08-19',

                    'payment_method' =>
                        'bank_transfer',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'fund_type',
            ]);
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'role' =>
                'administrator',

            'is_active' =>
                true,
        ]);
    }

    private function createLease(
        string $status = 'active'
    ): Lease {
        $building =
            Building::create([
                'name' =>
                    'Phase 5 Deposit Building',
            ]);

        $unit =
            Unit::create([
                'building_id' =>
                    $building->id,

                'name' =>
                    'Unit 1',
            ]);

        $tenant =
            Party::create([
                'type' =>
                    'person',

                'name' =>
                    'Phase 5 Deposit Tenant',

                'phone' =>
                    '0200005000',

                'email' =>
                    'phase5-deposit-'
                    .uniqid()
                    .'@example.test',
            ]);

        return Lease::create([
            'unit_id' =>
                $unit->id,

            'tenant_id' =>
                $tenant->id,

            'start_date' =>
                '2026-08-01',

            'rent_amount' =>
                1000,

            'payment_frequency' =>
                'monthly',

            'due_day' =>
                1,

            'vat_rate' =>
                0,

            'proration_amount' =>
                null,

            'security_deposit_amount' =>
                1000,

            'management_fee_type' =>
                'none',

            'management_fee_value' =>
                0,

            'agent_commission_amount' =>
                0,

            'status' =>
                $status,
        ]);
    }
}
