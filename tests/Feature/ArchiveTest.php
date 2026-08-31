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

        $this->postJson('/api/archive/building/'.$building->id)
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

        $this->postJson('/api/archive/party/'.$party->id)
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

        $this->postJson('/api/archive/building/'.$building->id)
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

        $this->postJson('/api/archive/building/'.$building->id)
            ->assertOk();

        $this->getJson('/api/archive')
            ->assertOk()
            ->assertJsonFragment(['label' => 'Archive Court']);

        $this->deleteJson('/api/archive/building/'.$building->id)
            ->assertOk()
            ->assertJsonPath('restored', true);

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

        $this->postJson('/api/archive/building/'.$building->id)
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'record.archived',
        ]);

        $this->assertNotNull(
            $building->fresh()->archived_by_user_id
        );
    }

    /**
     * Every kind the service claims to handle must actually resolve.
     */
    public function test_an_unknown_kind_is_not_found(): void
    {
        $this->postJson('/api/archive/invoice/1')
            ->assertNotFound();

        $this->assertSame(
            ['party', 'building', 'unit', 'lease'],
            array_keys(ArchiveService::KINDS)
        );
    }
}
