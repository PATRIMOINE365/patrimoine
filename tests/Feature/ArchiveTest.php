<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Party;
use App\Models\Unit;
use App\Services\ArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Archiving: for the records Patrimoine will not delete.
 *
 * The rule that matters is that archiving is the ALTERNATIVE to deletion,
 * never a second way to do it. A record that can still be removed outright
 * is refused, because offering both would leave two ways to make the same
 * thing disappear — one reversible and one not — and no way for a person
 * to tell which button they were pressing.
 *
 * The other rule that matters is that nothing else moves. An archived
 * record leaves the lists and the pickers and stays exactly where it is in
 * the ledger, the documents and the audit trail.
 */
class ArchiveTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser('administrator');
    }

    private function referencedBuilding(): Building
    {
        $building = Building::create([
            'name' => 'Archive Court',
            'address' => '1 Archive Road',
        ]);

        /*
         * A building with a unit cannot be deleted — the rule exists so a
         * cascade cannot quietly take the units with it — which makes it
         * exactly the sort of record archiving is for.
         */
        Unit::create([
            'building_id' => $building->id,
            'name' => 'Archive Unit 1',
        ]);

        return $building;
    }

    public function test_a_record_that_cannot_be_deleted_can_be_archived(): void
    {
        $building = $this->referencedBuilding();

        $this->postJson('/api/archive/building/'.$building->id, [
            'reason' => 'The block was sold in March.',
        ])
            ->assertOk()
            ->assertJsonPath('archived', true);

        $this->assertNotNull(
            $building->fresh()->archived_at
        );
    }

    /**
     * The two are alternatives, never both.
     */
    public function test_a_record_that_can_still_be_deleted_is_refused(): void
    {
        $party = Party::create([
            'type' => 'person',
            'name' => 'Nobody References Me',
            'phone' => '0200000123',
            'email' => 'unreferenced@example.test',
        ]);

        $this->postJson('/api/archive/party/'.$party->id, [
            'reason' => 'Nobody has ever used them.',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PM-3096');

        $this->assertNull(
            $party->fresh()->archived_at
        );
    }

    public function test_an_archived_record_leaves_the_list(): void
    {
        $building = $this->referencedBuilding();

        $this->getJson('/api/buildings')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Archive Court']);

        $this->postJson('/api/archive/building/'.$building->id, [
            'reason' => 'The block was sold in March.',
        ])
            ->assertOk();

        $this->getJson('/api/buildings')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Archive Court']);
    }

    /**
     * And comes back.
     */
    public function test_an_archived_record_can_be_restored(): void
    {
        $building = $this->referencedBuilding();

        $this->postJson('/api/archive/building/'.$building->id, [
            'reason' => 'The block was sold in March.',
        ])
            ->assertOk();

        $this->getJson('/api/archive')
            ->assertOk()
            ->assertJsonFragment(['label' => 'Archive Court'])
            ->assertJsonFragment(['reason' => 'The block was sold in March.']);

        $this->deleteJson('/api/archive/building/'.$building->id, [
            'reason' => 'The sale fell through.',
        ])
            ->assertOk()
            ->assertJsonPath('restored', true);

        /*
         * A record that is back in every list is not archived for any
         * reason at all, so the reason goes with it.
         */
        $this->assertNull(
            $building->fresh()->archived_reason
        );

        $this->assertNull(
            $building->fresh()->archived_at
        );

        $this->getJson('/api/buildings')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Archive Court']);
    }

    /**
     * The list tells each row which button it should carry.
     */
    public function test_the_list_says_whether_a_record_can_be_deleted(): void
    {
        $referenced = $this->referencedBuilding();

        $free = Building::create([
            'name' => 'Free Standing',
            'address' => '2 Archive Road',
        ]);

        $response = $this->getJson('/api/buildings')->assertOk();

        $rows = collect($response->json('data'))
            ->keyBy('id');

        $this->assertFalse(
            $rows[$referenced->id]['is_deletable'],
            'A building with units offers Archive, not Delete.'
        );

        $this->assertTrue(
            $rows[$free->id]['is_deletable'],
            'A building nothing refers to can still be deleted.'
        );
    }

    /**
     * Archiving is a decision worth being able to look up later.
     */
    public function test_archiving_records_who_did_it(): void
    {
        $building = $this->referencedBuilding();

        $this->postJson('/api/archive/building/'.$building->id, [
            'reason' => 'The block was sold in March.',
        ])
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'record.archived',
        ]);

        $this->assertNotNull(
            $building->fresh()->archived_by_user_id
        );
    }

    /**
     * Archiving asks why, and keeps the answer where it will be read.
     */
    public function test_archiving_requires_a_reason(): void
    {
        $building = $this->referencedBuilding();

        $this->postJson('/api/archive/building/'.$building->id)
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');

        $this->assertNull(
            $building->fresh()->archived_at
        );
    }

    public function test_restoring_requires_a_reason(): void
    {
        $building = $this->referencedBuilding();

        $this->postJson('/api/archive/building/'.$building->id, [
            'reason' => 'The block was sold in March.',
        ])->assertOk();

        $this->deleteJson('/api/archive/building/'.$building->id)
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');

        $this->assertNotNull(
            $building->fresh()->archived_at
        );
    }

    /**
     * -----------------------------------------------------------------
     * A live letting is not something to tidy away
     * -----------------------------------------------------------------
     *
     * Archiving a running lease hides a tenancy that is still invoicing,
     * still collecting rent and still holding a deposit. Termination is
     * the workflow that closes a letting; archiving is only what happens
     * to the record afterwards.
     */
    public function test_a_running_lease_cannot_be_archived(): void
    {
        $lease = $this->activeLease();

        $this->postJson('/api/archive/lease/'.$lease->id, [
            'reason' => 'Tidying the list.',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PM-3097');

        $this->assertNull(
            $lease->fresh()->archived_at
        );
    }

    /**
     * Notice is the period BEFORE the end. It is still billing.
     */
    public function test_a_lease_in_notice_cannot_be_archived(): void
    {
        $lease = $this->activeLease();

        $lease->forceFill([
            'status' => 'notice',
            'termination_notice_date' => now()->toDateString(),
        ])->save();

        $this->postJson('/api/archive/lease/'.$lease->id, [
            'reason' => 'It is nearly over.',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PM-3097');
    }

    public function test_a_terminated_lease_can_be_archived(): void
    {
        $lease = $this->activeLease();

        $lease->forceFill([
            'status' => 'terminated',
            'termination_date' => now()->toDateString(),
        ])->save();

        $this->postJson('/api/archive/lease/'.$lease->id, [
            'reason' => 'The tenancy ended last year.',
        ])
            ->assertOk()
            ->assertJsonPath('archived', true);

        $this->assertNotNull(
            $lease->fresh()->archived_at
        );
    }

    /**
     * A unit that has been let carries Archive, exactly as its building
     * does. Its rows had always drawn Delete, which the server was always
     * going to refuse.
     */
    public function test_the_list_says_whether_a_unit_can_be_deleted(): void
    {
        $lease = $this->activeLease();

        $response = $this->getJson('/api/buildings')->assertOk();

        $units = collect($response->json('data'))
            ->flatMap(fn (array $building): array => $building['units'] ?? [])
            ->keyBy('id');

        $this->assertFalse(
            $units[$lease->unit_id]['is_deletable'],
            'A unit that has been let offers Archive, not Delete.'
        );
    }

    /**
     * A lease with a tenant, a unit and a building behind it.
     */
    private function activeLease(): \App\Models\Lease
    {
        $building = Building::create([
            'name' => 'Letting Court',
            'address' => '3 Archive Road',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Letting Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Archive Tenant',
            'phone' => '0200000456',
            'email' => 'archive.tenant@example.test',
        ]);

        $tenant->roles()->create(['role' => 'tenant']);

        return \App\Models\Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => now()->subMonths(3)->toDateString(),
            'status' => 'active',
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'vat_rate' => 0,
            'security_deposit_amount' => 0,
            'advance_payment_amount' => 0,
            'rent_reserve_amount' => 0,
            'rent_increment_type' => 'none',
            'rent_increment_value' => 0,
            'management_fee_type' => 'none',
            'management_fee_value' => 0,
            'agent_commission_amount' => 0,
        ]);
    }

    /**
     * Every kind the service claims to handle must actually resolve.
     */
    public function test_an_unknown_kind_is_not_found(): void
    {
        $this->postJson('/api/archive/invoice/1', [
            'reason' => 'Whatever it is.',
        ])
            ->assertNotFound();

        $this->assertSame(
            ['party', 'building', 'unit', 'lease'],
            array_keys(ArchiveService::KINDS)
        );
    }
}
