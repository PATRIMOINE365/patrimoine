<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

class TenantAdjustmentStandardizationTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * @return array{
     *     lease: Lease,
     *     account: TenantFundAccount
     * }
     */
    private function context(
        string $fundType = 'rent_reserve',
        int $openingBalance = 0,
        string $status = 'active',
    ): array {
        $building = Building::create([
            'name' => 'Tenant Adjustment Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,

            'name' => 'Unit TA-1',
        ]);

        $tenant = Party::create([
            'type' => 'person',

            'name' => 'Tenant Adjustment Tenant',

            'phone' => '0200004400',

            'email' => 'tenant-adjustment@example.test',
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

            'status' => $status,
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

        return compact(
            'lease',
            'account'
        );
    }

    public function test_tenant_adjustment_uses_correct_balance_semantics(): void
    {
        $context =
            $this->context(
                openingBalance: 10000
            );

        $response =
            $this->postJson(
                "/api/tenant-funds/{$context['account']->id}/adjustments",
                [
                    'corrected_balance' => 6500,

                    'reason' => 'Correct overstated reserve balance.',

                    'reference' => 'TADJ-001',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'adjustment.previous_balance',
                10000
            )
            ->assertJsonPath(
                'adjustment.corrected_balance',
                6500
            )
            ->assertJsonPath(
                'adjustment.difference',
                -3500
            )
            ->assertJsonPath(
                'tenant_fund_account.balance',
                6500
            );

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
            3500,
            $transaction->amount
        );

        $this->assertSame(
            now()->toDateString(),
            $transaction
                ->transaction_date
                ->toDateString()
        );

        $this->assertSame(
            'Correct overstated reserve balance.',
            $transaction->notes
        );
    }

    public function test_tenant_adjustment_can_increase_balance(): void
    {
        $context =
            $this->context(
                fundType: 'consumable_advance',

                openingBalance: 2000,
            );

        $this->postJson(
            "/api/tenant-funds/{$context['account']->id}/adjustments",
            [
                'corrected_balance' => 7000,

                'reason' => 'Correct understated advance.',
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
            'credit',
            $transaction->direction
        );

        $this->assertSame(
            5000,
            $transaction->amount
        );

        $this->assertSame(
            7000,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    public function test_all_three_tenant_fund_types_are_adjustable(): void
    {
        foreach (
            [
                'rent_reserve',
                'consumable_advance',
                'security_deposit',
            ] as $index => $fundType
        ) {
            $context =
                $this->context(
                    fundType: $fundType,

                    openingBalance: 1000,
                );

            $this->postJson(
                "/api/tenant-funds/{$context['account']->id}/adjustments",
                [
                    'corrected_balance' => 1500,

                    'reason' => 'Fund correction '.$index,
                ]
            )->assertCreated();

            $this->assertSame(
                1500,
                $context['account']
                    ->fresh()
                    ->balance()
            );
        }
    }

    public function test_tenant_adjustment_cannot_create_negative_balance(): void
    {
        $context =
            $this->context(
                fundType: 'security_deposit',

                openingBalance: 5000,
            );

        $response =
            $this->postJson(
                "/api/tenant-funds/{$context['account']->id}/adjustments",
                [
                    'corrected_balance' => -1,

                    'reason' => 'Invalid negative correction.',
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'tenant_fund_account',
            ]);

        $this->assertDatabaseMissing(
            'tenant_fund_transactions',
            [
                'tenant_fund_account_id' => $context['account']->id,

                'category' => 'adjustment',
            ]
        );

        $this->assertSame(
            5000,
            $context['account']
                ->fresh()
                ->balance()
        );
    }

    public function test_zero_difference_does_not_create_adjustment(): void
    {
        $context =
            $this->context(
                openingBalance: 5000
            );

        $this->postJson(
            "/api/tenant-funds/{$context['account']->id}/adjustments",
            [
                'corrected_balance' => 5000,

                'reason' => 'No actual correction.',
            ]
        )
            ->assertUnprocessable();

        $this->assertDatabaseMissing(
            'tenant_fund_transactions',
            [
                'tenant_fund_account_id' => $context['account']->id,

                'category' => 'adjustment',
            ]
        );
    }

    public function test_closed_tenant_fund_account_cannot_be_adjusted(): void
    {
        $context =
            $this->context(
                status: 'closed'
            );

        $this->postJson(
            "/api/tenant-funds/{$context['account']->id}/adjustments",
            [
                'corrected_balance' => 1000,

                'reason' => 'Attempt closed account correction.',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.tenant_fund_account.0',
                'The selected Tenant fund account is closed.'
            );

        $this->assertDatabaseCount(
            'tenant_fund_transactions',
            0
        );
    }

    public function test_tenant_adjustment_requires_corrected_balance(): void
    {
        $context =
            $this->context();

        $this->postJson(
            "/api/tenant-funds/{$context['account']->id}/adjustments",
            [
                'reason' => 'Missing balance.',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'corrected_balance',
            ]);
    }

    public function test_tenant_adjustment_requires_reason(): void
    {
        $context =
            $this->context();

        $this->postJson(
            "/api/tenant-funds/{$context['account']->id}/adjustments",
            [
                'corrected_balance' => 1000,

                'reason' => '',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'reason',
            ]);
    }

    public function test_tenant_adjustment_is_today_only(): void
    {
        $context =
            $this->context();

        $this->postJson(
            "/api/tenant-funds/{$context['account']->id}/adjustments",
            [
                'corrected_balance' => 1000,

                'reason' => 'Today-only adjustment.',

                /*
                 * This field is deliberately not in the FormRequest contract.
                 * The client cannot backdate the Adjustment.
                 */
                'transaction_date' => '2000-01-01',
            ]
        )->assertCreated();

        $transaction =
            TenantFundTransaction::query()
                ->sole();

        $this->assertSame(
            now()->toDateString(),
            $transaction
                ->transaction_date
                ->toDateString()
        );
    }

    public function test_tenant_adjustment_activity_log_contains_frozen_context(): void
    {
        $context =
            $this->context(
                fundType: 'security_deposit',

                openingBalance: 5000,
            );

        $this->postJson(
            "/api/tenant-funds/{$context['account']->id}/adjustments",
            [
                'corrected_balance' => 3500,

                'reason' => 'Correct Security Deposit record.',

                'reference' => 'TADJ-ACT-001',
            ]
        )->assertCreated();

        $event =
            ActivityLog::query()
                ->sole();

        $this->assertSame(
            'tenant_adjustment.recorded',
            $event->action
        );

        $this->assertSame(
            'tenant_fund_transaction',
            $event->entity_type
        );

        $this->assertSame(
            'security_deposit',
            $event->snapshot[
                'fund_type'
            ]
        );

        $this->assertSame(
            5000,
            $event->snapshot[
                'previous_balance'
            ]
        );

        $this->assertSame(
            3500,
            $event->snapshot[
                'corrected_balance'
            ]
        );

        $this->assertSame(
            -1500,
            $event->snapshot[
                'difference'
            ]
        );

        $this->assertSame(
            'Correct Security Deposit record.',
            $event->snapshot[
                'reason'
            ]
        );

        $this->assertSame(
            $context['lease']->id,
            $event->snapshot[
                'lease_id'
            ]
        );

        $this->assertSame(
            'Tenant Adjustment Tenant',
            $event->snapshot[
                'tenant_name'
            ]
        );
    }

    public function test_second_adjustment_corrects_first_adjustment(): void
    {
        $context =
            $this->context();

        $url =
            "/api/tenant-funds/{$context['account']->id}/adjustments";

        $this->postJson(
            $url,
            [
                'corrected_balance' => 5000,

                'reason' => 'First correction.',
            ]
        )->assertCreated();

        $this->postJson(
            $url,
            [
                'corrected_balance' => 3200,

                'reason' => 'Correct the first correction.',
            ]
        )->assertCreated();

        $this->assertSame(
            3200,
            $context['account']
                ->fresh()
                ->balance()
        );

        $this->assertSame(
            2,
            TenantFundTransaction::query()
                ->where(
                    'category',
                    'adjustment'
                )
                ->count()
        );
    }
}
