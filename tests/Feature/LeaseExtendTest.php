<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use App\Models\User;
use App\Services\LeaseTerms\LeaseTermVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaseExtendTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_lease_can_be_extended_without_changing_identity(): void
    {
        [$lease, $user] =
            $this->leaseFixture();

        Sanctum::actingAs($user);

        $service =
            app(
                LeaseTermVersionService::class
            );

        $service->ensureBaseline($lease);

        $response =
            $this->postJson(
                "/api/leases/{$lease->id}/extend",
                $this->payload([
                    'rent_amount' => 2500,
                    'end_date' => '2027-12-31',
                ])
            );

        $response->assertOk();

        $lease->refresh();

        $this->assertSame(
            2500,
            $lease->rent_amount
        );

        $this->assertSame(
            '2027-12-31',
            $lease->end_date?->format('Y-m-d')
        );

        $this->assertSame(
            2,
            $lease->termVersions()->count()
        );

        $versions =
            $lease
                ->termVersions()
                ->get();

        $this->assertSame(
            'baseline',
            $versions[0]->event_type
        );

        $this->assertSame(
            '2026-12-31',
            $versions[0]
                ->effective_to
                ?->format('Y-m-d')
        );

        $this->assertSame(
            'extension',
            $versions[1]->event_type
        );

        $this->assertSame(
            '2027-01-01',
            $versions[1]
                ->effective_from
                ->format('Y-m-d')
        );

        $this->assertSame(
            2500,
            $versions[1]->terms['rent_amount']
        );

        $this->assertSame(
            $lease->id,
            $versions[1]->lease_id
        );
    }

    public function test_extension_does_not_accept_proration_override(): void
    {
        [$lease, $user] =
            $this->leaseFixture();

        Sanctum::actingAs($user);

        app(
            LeaseTermVersionService::class
        )->ensureBaseline($lease);

        $originalProration =
            $lease->proration_amount;

        $payload =
            $this->payload([
                'rent_amount' => 2600,
            ]);

        $payload['proration_amount'] =
            999999;

        $this->postJson(
            "/api/leases/{$lease->id}/extend",
            $payload
        )->assertOk();

        $this->assertSame(
            $originalProration,
            $lease
                ->fresh()
                ->proration_amount
        );
    }

    public function test_extension_can_be_repeated(): void
    {
        [$lease, $user] =
            $this->leaseFixture();

        Sanctum::actingAs($user);

        app(
            LeaseTermVersionService::class
        )->ensureBaseline($lease);

        $this->postJson(
            "/api/leases/{$lease->id}/extend",
            $this->payload([
                'effective_from' => '2027-01-01',
                'rent_amount' => 2500,
            ])
        )->assertOk();

        $this->postJson(
            "/api/leases/{$lease->id}/extend",
            $this->payload([
                'effective_from' => '2028-01-01',
                'end_date' => '2028-12-31',
                'rent_amount' => 3000,
            ])
        )->assertOk();

        $versions =
            $lease
                ->termVersions()
                ->get();

        $this->assertCount(
            3,
            $versions
        );

        $this->assertSame(
            1,
            $versions[0]->version_number
        );

        $this->assertSame(
            2,
            $versions[1]->version_number
        );

        $this->assertSame(
            3,
            $versions[2]->version_number
        );

        $this->assertSame(
            '2027-12-31',
            $versions[1]
                ->effective_to
                ?->format('Y-m-d')
        );

        $this->assertSame(
            3000,
            $lease
                ->fresh()
                ->rent_amount
        );
    }

    public function test_lease_under_notice_cannot_be_extended(): void
    {
        [$lease, $user] =
            $this->leaseFixture([
                'status' => 'notice',
                'termination_notice_date' => '2026-08-01',
            ]);

        Sanctum::actingAs($user);

        app(
            LeaseTermVersionService::class
        )->ensureBaseline($lease);

        $this->postJson(
            "/api/leases/{$lease->id}/extend",
            $this->payload()
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors(
                'lease'
            );

        $this->assertSame(
            1,
            $lease
                ->termVersions()
                ->count()
        );
    }

    public function test_terminated_lease_is_not_reactivated_by_core_extend_step(): void
    {
        [$lease, $user] =
            $this->leaseFixture([
                'status' => 'terminated',
            ]);

        Sanctum::actingAs($user);

        app(
            LeaseTermVersionService::class
        )->ensureBaseline($lease);

        $this->postJson(
            "/api/leases/{$lease->id}/extend",
            $this->payload()
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors(
                'lease'
            );

        $this->assertSame(
            'terminated',
            $lease
                ->fresh()
                ->status
        );
    }

    public function test_viewer_cannot_extend_a_lease(): void
    {
        [$lease] =
            $this->leaseFixture();

        $viewer =
            User::factory()->create([
                'role' => UserRole::Viewer,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

        Sanctum::actingAs($viewer);

        $this->postJson(
            "/api/leases/{$lease->id}/extend",
            $this->payload()
        )->assertForbidden();
    }

    public function test_property_manager_can_extend_a_lease(): void
    {
        [$lease] =
            $this->leaseFixture();

        $propertyManager =
            User::factory()->create([
                'role' => UserRole::PropertyManager,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

        Sanctum::actingAs(
            $propertyManager
        );

        app(
            LeaseTermVersionService::class
        )->ensureBaseline(
            $lease
        );

        $this->postJson(
            "/api/leases/{$lease->id}/extend",
            $this->payload()
        )->assertOk();

        $this->assertSame(
            2,
            $lease
                ->termVersions()
                ->count()
        );
    }

    public function test_successful_extension_records_one_activity_log_event(): void
    {
        [$lease, $user] =
            $this->leaseFixture();

        Sanctum::actingAs(
            $user
        );

        app(
            LeaseTermVersionService::class
        )->ensureBaseline(
            $lease
        );

        $before =
            ActivityLog::query()
                ->where(
                    'action',
                    'lease.extended'
                )
                ->count();

        $this->postJson(
            "/api/leases/{$lease->id}/extend",
            $this->payload([
                'rent_amount' => 2750,
            ])
        )->assertOk();

        $events =
            ActivityLog::query()
                ->where(
                    'action',
                    'lease.extended'
                )
                ->where(
                    'entity_type',
                    'lease'
                )
                ->where(
                    'entity_id',
                    $lease->id
                )
                ->get();

        $this->assertCount(
            $before + 1,
            ActivityLog::query()
                ->where(
                    'action',
                    'lease.extended'
                )
                ->get()
        );

        $this->assertCount(
            1,
            $events
        );

        $event =
            $events->first();

        $this->assertNotNull(
            $event
        );

        $this->assertSame(
            $user->id,
            $event->user_id
        );

        $this->assertSame(
            '2027-01-01',
            data_get(
                $event->metadata,
                'effective_from'
            )
        );

        $this->assertSame(
            2750,
            data_get(
                $event->after_values,
                'rent_amount'
            )
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{Lease, User}
     */
    private function leaseFixture(
        array $overrides = []
    ): array {
        $tenant =
            Party::create([
                'name' => 'Extend Tenant',
                'type' => 'person',
            ]);

        $tenant
            ->roles()
            ->create([
                'role' => 'tenant',
            ]);

        $building =
            Building::create([
                'name' => 'Extend Building',
            ]);

        $unit =
            Unit::create([
                'building_id' => $building->id,
                'name' => 'Extend Unit',
            ]);

        $lease =
            Lease::create(
                array_merge(
                    [
                        'unit_id' => $unit->id,
                        'tenant_id' => $tenant->id,
                        'agent_id' => null,
                        'start_date' => '2026-01-01',
                        'end_date' => '2026-12-31',
                        'status' => 'active',
                        'termination_notice_date' => null,
                        'rent_amount' => 2000,
                        'payment_frequency' => 'monthly',
                        'due_day' => 1,
                        'vat_rate' => 18,
                        'proration_amount' => 500,
                        'security_deposit_amount' => 2000,
                        'advance_payment_amount' => 4000,
                        'rent_reserve_amount' => 2000,
                        'rent_increment_type' => 'none',
                        'rent_increment_value' => 0,
                        'next_rent_increment_date' => null,
                        'management_fee_type' => 'none',
                        'management_fee_value' => 0,
                        'agent_commission_amount' => 0,
                        'notes' => 'Original terms',
                    ],
                    $overrides
                )
            );

        $user =
            User::factory()->create([
                'role' => UserRole::Administrator,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

        return [
            $lease,
            $user,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'effective_from' => '2027-01-01',
                'end_date' => '2027-12-31',
                'rent_amount' => 2400,
                'payment_frequency' => 'monthly',
                'due_day' => 1,
                'vat_rate' => 18,
                'rent_increment_type' => 'none',
                'rent_increment_value' => 0,
                'next_rent_increment_date' => null,
                'notes' => 'Extended terms',
            ],
            $overrides
        );
    }
}
