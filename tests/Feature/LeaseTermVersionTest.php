<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\LeaseTermVersion;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Unit;
use App\Services\LeaseTerms\LeaseTermVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

class LeaseTermVersionTest extends TestCase
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
     *     unit: Unit,
     *     tenant: Party
     * }
     */
    private function context(): array
    {
        $building =
            Building::create([
                'name' => 'Lease Terms Building',
            ]);

        $unit =
            Unit::create([
                'building_id' => $building->id,

                'name' => 'Lease Terms Unit',
            ]);

        $tenant =
            Party::create([
                'type' => 'person',

                'name' => 'Lease Terms Tenant',

                'phone' => '0200000888',

                'email' => 'lease-terms@example.test',
            ]);

        PartyRole::create([
            'party_id' => $tenant->id,

            'role' => 'tenant',
        ]);

        return compact(
            'unit',
            'tenant'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(
        array $context
    ): array {
        return [
            'unit_id' => $context['unit']->id,

            'tenant_id' => $context['tenant']->id,

            'agent_id' => null,

            'start_date' => '2026-08-01',

            'end_date' => '2027-07-31',

            'status' => 'active',

            'termination_notice_date' => null,

            'rent_amount' => 10000,

            'payment_frequency' => 'monthly',

            'due_day' => 1,

            'vat_rate' => 18,

            'proration_amount' => null,

            'security_deposit_amount' => 10000,

            'advance_payment_amount' => 0,

            'rent_reserve_amount' => 0,

            'advance_received' => false,

            'rent_increment_type' => 'percentage',

            'rent_increment_value' => 5,

            'next_rent_increment_date' => '2027-08-01',

            'management_fee_type' => 'percentage',

            'management_fee_value' => 10,

            'agent_commission_amount' => 0,

            'notes' => 'Original contractual terms.',
        ];
    }

    public function test_new_lease_receives_baseline_term_version(): void
    {
        $context =
            $this->context();

        $response =
            $this->postJson(
                '/api/leases',
                $this->payload(
                    $context
                )
            );

        $response->assertCreated();

        $lease =
            Lease::findOrFail(
                $response->json('id')
            );

        $version =
            LeaseTermVersion::query()
                ->where(
                    'lease_id',
                    $lease->id
                )
                ->firstOrFail();

        $this->assertSame(
            1,
            $version->version_number
        );

        $this->assertSame(
            'baseline',
            $version->event_type
        );

        $this->assertSame(
            '2026-08-01',
            $version
                ->effective_from
                ->toDateString()
        );

        $this->assertNull(
            $version->effective_to
        );

        $this->assertSame(
            10000,
            $version->terms[
                'rent_amount'
            ]
        );

        $this->assertSame(
            '2027-07-31',
            $version->terms[
                'end_date'
            ]
        );

        $this->assertSame(
            'monthly',
            $version->terms[
                'payment_frequency'
            ]
        );

        $this->assertArrayHasKey(
            'proration_amount',
            $version->terms
        );

        $this->assertSame(
            'Original contractual terms.',
            $version->terms[
                'notes'
            ]
        );
    }

    public function test_baseline_creation_is_idempotent(): void
    {
        $context =
            $this->context();

        $response =
            $this->postJson(
                '/api/leases',
                $this->payload(
                    $context
                )
            );

        $response->assertCreated();

        $lease =
            Lease::findOrFail(
                $response->json('id')
            );

        $service =
            app(
                LeaseTermVersionService::class
            );

        $first =
            $service->ensureBaseline(
                $lease
            );

        $second =
            $service->ensureBaseline(
                $lease
            );

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertSame(
            1,
            LeaseTermVersion::query()
                ->where(
                    'lease_id',
                    $lease->id
                )
                ->count()
        );
    }

    public function test_term_version_preserves_complete_contractual_snapshot(): void
    {
        $context =
            $this->context();

        $response =
            $this->postJson(
                '/api/leases',
                $this->payload(
                    $context
                )
            );

        $response->assertCreated();

        $lease =
            Lease::findOrFail(
                $response->json('id')
            );

        $version =
            $lease
                ->termVersions()
                ->firstOrFail();

        $this->assertSame(
            LeaseTermVersionService::TERM_FIELDS,
            array_keys(
                $version->terms
            )
        );

        /*
         * Lifecycle state deliberately does not belong to the contractual
         * term snapshot.
         */
        $this->assertArrayNotHasKey(
            'status',
            $version->terms
        );

        $this->assertArrayNotHasKey(
            'termination_notice_date',
            $version->terms
        );
    }

    public function test_term_versions_are_immutable(): void
    {
        $context =
            $this->context();

        $response =
            $this->postJson(
                '/api/leases',
                $this->payload(
                    $context
                )
            );

        $response->assertCreated();

        $version =
            LeaseTermVersion::query()
                ->firstOrFail();

        try {
            $version->update([
                'event_type' => 'tampered',
            ]);

            $this->fail(
                'Updating a Lease term version should fail.'
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                'Lease term versions are immutable.',
                $exception->getMessage()
            );
        }

        try {
            $version->delete();

            $this->fail(
                'Deleting a Lease term version should fail.'
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                'Lease term versions cannot be deleted.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas(
            'lease_term_versions',
            [
                'id' => $version->id,

                'event_type' => 'baseline',
            ]
        );
    }

    public function test_append_uses_next_version_number_without_changing_baseline(): void
    {
        $context =
            $this->context();

        $response =
            $this->postJson(
                '/api/leases',
                $this->payload(
                    $context
                )
            );

        $response->assertCreated();

        $lease =
            Lease::findOrFail(
                $response->json('id')
            );

        $service =
            app(
                LeaseTermVersionService::class
            );

        $baseline =
            $lease
                ->termVersions()
                ->firstOrFail();

        $newTerms =
            $service->snapshot(
                $lease
            );

        $newTerms['rent_amount'] =
            12000;

        $extension =
            $service->append(
                lease: $lease,
                eventType: 'extension',
                effectiveFrom: '2027-08-01',
                terms: $newTerms
            );

        $this->assertSame(
            2,
            $extension->version_number
        );

        $this->assertSame(
            12000,
            $extension->terms[
                'rent_amount'
            ]
        );

        $this->assertSame(
            10000,
            $baseline
                ->fresh()
                ->terms[
                    'rent_amount'
                ]
        );

        $this->assertSame(
            2,
            $lease
                ->termVersions()
                ->count()
        );
    }
}
