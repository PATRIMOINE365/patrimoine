<?php

namespace Tests\Feature;

use App\Models\AccountingCutover;
use App\Models\ActivityLog;
use App\Models\AdjustmentVoucher;
use App\Models\Building;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use App\Services\Documents\AdjustmentVoucherDocumentService;
use App\Services\Documents\AdjustmentVoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

class AdjustmentVoucherIntegrationTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    private function ownerAccount(
        int $openingBalance = 0
    ): OwnerAccount {
        $owner = Party::create([
            'type' => 'person',

            'name' => 'Adjustment Voucher Owner',

            'phone' => '0200004600',

            'email' => 'adjustment-voucher-owner@example.test',
        ]);

        $account = OwnerAccount::create([
            'party_id' => $owner->id,
        ]);

        if ($openingBalance !== 0) {
            OwnerTransaction::create([
                'owner_account_id' => $account->id,

                'direction' => $openingBalance > 0
                        ? 'credit'
                        : 'debit',

                'category' => 'adjustment',

                'amount' => abs($openingBalance),

                'transaction_date' => '2026-01-01',

                'notes' => 'Opening fixture.',
            ]);
        }

        return $account;
    }

    private function tenantFundAccount(
        string $type = 'rent_reserve',
        int $openingBalance = 0,
    ): TenantFundAccount {
        $building = Building::create([
            'name' => 'Adjustment Voucher Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,

            'name' => 'Unit AV-1',
        ]);

        $tenant = Party::create([
            'type' => 'person',

            'name' => 'Adjustment Voucher Tenant',

            'phone' => '0200004700',

            'email' => 'adjustment-voucher-tenant@example.test',
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

            'type' => $type,

            'status' => 'active',
        ]);

        if ($openingBalance > 0) {
            TenantFundTransaction::create([
                'tenant_fund_account_id' => $account->id,

                'direction' => 'credit',

                'category' => match ($type) {
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

    public function test_owner_adjustment_generates_shared_voucher(): void
    {
        $account =
            $this->ownerAccount(
                2000
            );

        $response =
            $this->postJson(
                "/api/owner-accounts/{$account->id}/adjustments",
                [
                    'corrected_balance' => 7500,

                    'reason' => 'Correct Owner account balance.',

                    'reference' => 'OV-001',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'adjustment.previous_balance',
                2000
            )
            ->assertJsonPath(
                'adjustment.corrected_balance',
                7500
            )
            ->assertJsonPath(
                'adjustment.difference',
                5500
            );

        $voucher =
            AdjustmentVoucher::query()
                ->sole();

        $transaction =
            OwnerTransaction::query()
                ->where(
                    'category',
                    'adjustment'
                )
                ->latest('id')
                ->firstOrFail();

        $this->assertSame(
            'owner_account',
            $voucher->account_type
        );

        $this->assertSame(
            $account->id,
            $voucher->account_id
        );

        $this->assertSame(
            2000,
            $voucher->previous_balance
        );

        $this->assertSame(
            7500,
            $voucher->corrected_balance
        );

        $this->assertSame(
            5500,
            $voucher->difference
        );

        $this->assertSame(
            'Correct Owner account balance.',
            $voucher->reason
        );

        $this->assertSame(
            now()->toDateString(),
            $voucher
                ->adjustment_date
                ->toDateString()
        );

        $this->assertSame(
            OwnerTransaction::class,
            $voucher->source_type
        );

        $this->assertSame(
            $transaction->id,
            $voucher->source_id
        );

        $this->assertSame(
            $voucher->id,
            $response->json(
                'adjustment_voucher.id'
            )
        );

        $this->assertSame(
            $voucher->voucher_number,
            $response->json(
                'adjustment_voucher.voucher_number'
            )
        );

        $event =
            ActivityLog::query()
                ->sole();

        $this->assertSame(
            $voucher->voucher_number,
            $event->snapshot[
                'adjustment_voucher_number'
            ]
        );
    }

    public function test_tenant_adjustment_generates_shared_voucher_with_context(): void
    {
        $account =
            $this->tenantFundAccount(
                type: 'security_deposit',

                openingBalance: 8000,
            );

        $response =
            $this->postJson(
                "/api/tenant-funds/{$account->id}/adjustments",
                [
                    'corrected_balance' => 5000,

                    'reason' => 'Correct Security Deposit balance.',
                ]
            );

        $response
            ->assertCreated();

        $voucher =
            AdjustmentVoucher::query()
                ->sole();

        $transaction =
            TenantFundTransaction::query()
                ->where(
                    'category',
                    'adjustment'
                )
                ->sole();

        $this->assertSame(
            'security_deposit',
            $voucher->account_type
        );

        $this->assertSame(
            'Security Deposit',
            $voucher->account_label
        );

        $this->assertStringContainsString(
            'Adjustment Voucher Tenant',
            $voucher->entity_label
        );

        $this->assertStringContainsString(
            'Adjustment Voucher Building',
            $voucher->entity_label
        );

        $this->assertStringContainsString(
            'Unit AV-1',
            $voucher->entity_label
        );

        $this->assertSame(
            8000,
            $voucher->previous_balance
        );

        $this->assertSame(
            5000,
            $voucher->corrected_balance
        );

        $this->assertSame(
            -3000,
            $voucher->difference
        );

        $this->assertSame(
            TenantFundTransaction::class,
            $voucher->source_type
        );

        $this->assertSame(
            $transaction->id,
            $voucher->source_id
        );

        $this->assertSame(
            $voucher->id,
            $response->json(
                'adjustment_voucher.id'
            )
        );

        $event =
            ActivityLog::query()
                ->sole();

        $this->assertSame(
            $voucher->id,
            $event->snapshot[
                'adjustment_voucher_id'
            ]
        );
    }

    public function test_voucher_numbers_are_sequential_and_shared(): void
    {
        $owner =
            $this->ownerAccount();

        $tenant =
            $this->tenantFundAccount();

        $this->postJson(
            "/api/owner-accounts/{$owner->id}/adjustments",
            [
                'corrected_balance' => 1000,

                'reason' => 'Owner numbering test.',
            ]
        )->assertCreated();

        $this->postJson(
            "/api/tenant-funds/{$tenant->id}/adjustments",
            [
                'corrected_balance' => 2000,

                'reason' => 'Tenant numbering test.',
            ]
        )->assertCreated();

        $numbers =
            AdjustmentVoucher::query()
                ->orderBy('id')
                ->pluck('voucher_number')
                ->all();

        $year =
            now()->year;

        $this->assertSame(
            [
                sprintf(
                    'ADV-%d-000001',
                    $year
                ),
                sprintf(
                    'ADV-%d-000002',
                    $year
                ),
            ],
            $numbers
        );
    }

    public function test_adjustment_voucher_pdf_generates_and_download_is_logged(): void
    {
        $account =
            $this->ownerAccount();

        $response =
            $this->postJson(
                "/api/owner-accounts/{$account->id}/adjustments",
                [
                    'corrected_balance' => 2500,

                    'reason' => 'PDF generation test.',
                ]
            )
                ->assertCreated();

        $voucher =
            AdjustmentVoucher::query()
                ->sole();

        $contents =
            app(
                AdjustmentVoucherDocumentService::class
            )->pdf(
                $voucher
            );

        $this->assertStringStartsWith(
            '%PDF',
            $contents
        );

        $this->get(
            $response->json(
                'adjustment_voucher.pdf_endpoint'
            )
        )
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/pdf'
            );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' => 'adjustment_voucher.downloaded',

                'entity_type' => 'adjustment_voucher',

                'entity_id' => (string) $voucher->id,
            ]
        );
    }

    public function test_owner_voucher_failure_rolls_back_everything(): void
    {
        $this->activateAccounting();

        $account =
            $this->ownerAccount();

        $this->mock(
            AdjustmentVoucherService::class,
            function ($mock): void {
                $mock
                    ->shouldReceive('create')
                    ->once()
                    ->andThrow(
                        new \RuntimeException(
                            'Forced Adjustment Voucher failure.'
                        )
                    );
            }
        );

        $response =
            $this->postJson(
                "/api/owner-accounts/{$account->id}/adjustments",
                [
                    'corrected_balance' => 5000,

                    'reason' => 'Owner Voucher rollback.',
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.owner_account.0',
                'Forced Adjustment Voucher failure.'
            );

        $this->assertDatabaseCount(
            'owner_transactions',
            0
        );

        $this->assertDatabaseCount(
            'adjustment_vouchers',
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

    public function test_tenant_voucher_failure_rolls_back_everything(): void
    {
        $this->activateAccounting();

        $account =
            $this->tenantFundAccount();

        $this->mock(
            AdjustmentVoucherService::class,
            function ($mock): void {
                $mock
                    ->shouldReceive('create')
                    ->once()
                    ->andThrow(
                        new \RuntimeException(
                            'Forced Adjustment Voucher failure.'
                        )
                    );
            }
        );

        $response =
            $this->postJson(
                "/api/tenant-funds/{$account->id}/adjustments",
                [
                    'corrected_balance' => 5000,

                    'reason' => 'Tenant Voucher rollback.',
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.tenant_fund_account.0',
                'Forced Adjustment Voucher failure.'
            );

        $this->assertDatabaseCount(
            'tenant_fund_transactions',
            0
        );

        $this->assertDatabaseCount(
            'adjustment_vouchers',
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
}
