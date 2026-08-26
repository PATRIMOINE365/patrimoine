<?php

namespace Tests\Feature;

use App\Models\Lease;
use App\Services\LeaseDeletion\LeaseDeletionOperationalDeletionService;
use App\Services\LeaseDeletion\LeaseDeletionRestorationPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class LeaseDeletionOperationalDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_a_safe_operational_plan_in_fk_safe_order(): void
    {
        $lease = $this->makeLease();

        $plan = $this->safePlan($lease);

        $planner = Mockery::mock(
            LeaseDeletionRestorationPlanService::class
        );

        $planner->shouldReceive('plan')
            ->once()
            ->andReturn($plan);

        $service = new LeaseDeletionOperationalDeletionService(
            $planner
        );

        $visited = [];

        $result = $service->execute(
            $lease,
            function (string $table, array $ids) use (&$visited): void {
                $visited[] = $table;
            }
        );

        $this->assertSame(
            array_column(
                $plan['operational']['delete_in_order'],
                'table'
            ),
            $visited
        );

        $this->assertDatabaseMissing('leases', [
            'id' => $lease->id,
        ]);

        $this->assertSame(
            $lease->id,
            $result['lease_id']
        );

        $this->assertTrue(
            $result['preserved']['journal_entries']
        );

        $this->assertTrue(
            $result['preserved']['activity_logs']
        );
    }

    public function test_it_fails_closed_when_plan_is_not_safe(): void
    {
        $lease = $this->makeLease();

        $plan = $this->safePlan($lease);

        $plan['eligibility'] = [
            'safe_to_execute' => false,
            'blocking_reasons' => [
                'Shared financial effect exists.',
            ],
        ];

        $planner = Mockery::mock(
            LeaseDeletionRestorationPlanService::class
        );

        $planner->shouldReceive('plan')
            ->once()
            ->andReturn($plan);

        $service = new LeaseDeletionOperationalDeletionService(
            $planner
        );

        try {
            $service->execute($lease);

            $this->fail(
                'Unsafe deletion should have thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'Shared financial effect exists.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('leases', [
            'id' => $lease->id,
        ]);
    }

    public function test_it_rejects_a_reordered_plan(): void
    {
        $lease = $this->makeLease();

        $plan = $this->safePlan($lease);

        [$plan['operational']['delete_in_order'][0],
         $plan['operational']['delete_in_order'][1]]
            =
        [$plan['operational']['delete_in_order'][1],
         $plan['operational']['delete_in_order'][0]];

        $planner = Mockery::mock(
            LeaseDeletionRestorationPlanService::class
        );

        $planner->shouldReceive('plan')
            ->once()
            ->andReturn($plan);

        $service = new LeaseDeletionOperationalDeletionService(
            $planner
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'required FK-safe deletion order'
        );

        $service->execute($lease);
    }

    public function test_failure_injection_rolls_back_prior_deletions(): void
    {
        $lease = $this->makeLease();

        /*
         * A LeaseTermVersion gives us a real child row with a restrictive
         * Lease FK, allowing rollback to be proven rather than merely
         * checking deletion of the Lease itself.
         */
        $termId = DB::table('lease_term_versions')->insertGetId([
            'organisation_id' => $this->testOrganisation->id,
            'lease_id' =>
                $lease->id,

            'version_number' =>
                1,

            'event_type' =>
                'created',

            'effective_from' =>
                now()->toDateString(),

            'effective_to' =>
                null,

            'terms' =>
                json_encode([
                    'start_date' =>
                        $lease->start_date?->toDateString(),

                    'end_date' =>
                        $lease->end_date?->toDateString(),

                    'rent_amount' =>
                        (int) $lease->rent_amount,

                    'payment_frequency' =>
                        $lease->payment_frequency,

                    'due_day' =>
                        (int) $lease->due_day,

                    'vat_rate' =>
                        (string) $lease->vat_rate,

                    'proration_amount' =>
                        (int) $lease->proration_amount,

                    'security_deposit_amount' =>
                        (int) $lease->security_deposit_amount,

                    'advance_payment_amount' =>
                        (int) $lease->advance_payment_amount,

                    'rent_reserve_amount' =>
                        (int) $lease->rent_reserve_amount,

                    'management_fee_type' =>
                        $lease->management_fee_type,

                    'management_fee_value' =>
                        (string) $lease->management_fee_value,

                    'agent_commission_amount' =>
                        (int) $lease->agent_commission_amount,
                ]),

            'created_by_user_id' =>
                null,

            'created_at' =>
                now(),
        ]);

        $plan = $this->safePlan($lease);

        foreach ($plan['operational']['delete_in_order'] as &$step) {
            if ($step['table'] === 'lease_term_versions') {
                $step['ids'] = [$termId];
            }
        }
        unset($step);

        $planner = Mockery::mock(
            LeaseDeletionRestorationPlanService::class
        );

        $planner->shouldReceive('plan')
            ->once()
            ->andReturn($plan);

        $service = new LeaseDeletionOperationalDeletionService(
            $planner
        );

        try {
            $service->execute(
                $lease,
                function (string $table): void {
                    if ($table === 'lease_term_versions') {
                        throw new RuntimeException(
                            'Injected operational deletion failure.'
                        );
                    }
                }
            );

            $this->fail(
                'Injected failure should have thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Injected operational deletion failure.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('lease_term_versions', [
            'id' => $termId,
            'lease_id' => $lease->id,
        ]);

        $this->assertDatabaseHas('leases', [
            'id' => $lease->id,
        ]);
    }

    public function test_it_rejects_a_plan_targeting_another_lease(): void
    {
        $lease = $this->makeLease();
        $other = $this->makeLease();

        $plan = $this->safePlan($lease);

        $last = array_key_last(
            $plan['operational']['delete_in_order']
        );

        $plan['operational']['delete_in_order'][$last]['ids'] = [
            $other->id,
        ];

        $planner = Mockery::mock(
            LeaseDeletionRestorationPlanService::class
        );

        $planner->shouldReceive('plan')
            ->once()
            ->andReturn($plan);

        $service = new LeaseDeletionOperationalDeletionService(
            $planner
        );

        try {
            $service->execute($lease);

            $this->fail(
                'Cross-Lease plan should have thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'locked Lease',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('leases', [
            'id' => $lease->id,
        ]);

        $this->assertDatabaseHas('leases', [
            'id' => $other->id,
        ]);
    }

    private function makeLease(): Lease
    {
        $owner = DB::table('parties')->insertGetId([
            'organisation_id' => $this->testOrganisation->id,
            'type' => 'person',
            'name' => 'Operational Delete Owner '.uniqid(),
            'email' => uniqid().'@owner.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenant = DB::table('parties')->insertGetId([
            'organisation_id' => $this->testOrganisation->id,
            'type' => 'person',
            'name' => 'Operational Delete Tenant '.uniqid(),
            'email' => uniqid().'@tenant.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $building = DB::table('buildings')->insertGetId([
            'organisation_id' => $this->testOrganisation->id,
            'name' => 'Operational Delete Building '.uniqid(),
            'address' => 'Test Address',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('building_owners')->insert([
            'organisation_id' => $this->testOrganisation->id,
            'building_id' => $building,
            'party_id' => $owner,
            'ownership_percentage' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unit = DB::table('units')->insertGetId([
            'organisation_id' => $this->testOrganisation->id,
            'building_id' => $building,
            'name' => 'Unit '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $leaseId = DB::table('leases')->insertGetId([
            'organisation_id' => $this->testOrganisation->id,
            'unit_id' => $unit,
            'tenant_id' => $tenant,
            'start_date' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'due_day' => 1,
            'vat_rate' => 18,
            'proration_amount' => 0,
            'security_deposit_amount' => 0,
            'advance_payment_amount' => 0,
            'rent_reserve_amount' => 0,
            'agent_commission_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Lease::query()->findOrFail($leaseId);
    }

    /**
     * @return array<string, mixed>
     */
    private function safePlan(Lease $lease): array
    {
        $tables = [
            'security_deposit_applications',
            'owner_payout_allocations',
            'withdrawal_receipts',
            'security_deposit_deductions',
            'security_deposit_settlements',
            'owner_transactions',
            'tenant_fund_transactions',
            'payment_allocations',
            'payments',
            'invoices',
            'tenant_fund_accounts',
            'rent_increments',
            'lease_term_versions',
        ];

        $steps = array_map(
            fn (string $table): array => [
                'table' => $table,
                'ids' => [],
            ],
            $tables
        );

        $steps[] = [
            'table' => 'leases',
            'ids' => [$lease->id],
        ];

        return [
            'eligibility' => [
                'safe_to_execute' => true,
                'blocking_reasons' => [],
            ],
            'operational' => [
                'delete_in_order' => $steps,
            ],
            'documents' => [
                'action' => 'enumerate_before_execution',
                'delete_operational_documents' => true,
                'preserve_audit_evidence' => true,
            ],
        ];
    }
}
