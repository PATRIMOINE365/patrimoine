<?php

namespace Tests\Feature;

use App\Models\Party;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Concerns\AuthenticatesApiUser;

/**
 * Verifies the Patrimoine Building REST API.
 */
class BuildingApiTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApiUser;

    protected function setUp(): void

    {

        parent::setUp();

        $this->authenticateApiUser();

    }

    /**
     * Create an owner Party for Building API tests.
     */
    private function createOwner(string $name): Party
    {
        return Party::create([
            'type' => 'person',
            'name' => $name,
            'phone' => '0200000300',
            'email' => strtolower(
                str_replace(' ', '-', $name)
            ).'@example.test',
        ]);
    }

    /**
     * A Building may be created with complete ownership.
     */
    public function test_building_can_be_created(): void
    {
        $firstOwner = $this->createOwner('Owner One');
        $secondOwner = $this->createOwner('Owner Two');

        $response = $this->postJson('/api/buildings', [
            'name' => 'Airport Residential Building',
            'location' => 'Accra',
            'owners' => [
                [
                    'party_id' => $firstOwner->id,
                    'ownership_percentage' => 60,
                ],
                [
                    'party_id' => $secondOwner->id,
                    'ownership_percentage' => 40,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'name',
                'Airport Residential Building'
            );

        $this->assertDatabaseHas('buildings', [
            'name' => 'Airport Residential Building',
        ]);

        $this->assertDatabaseCount(
            'building_owners',
            2
        );
    }

    /**
     * Ownership percentages must total 100%.
     */
    public function test_building_rejects_incomplete_ownership(): void
    {
        $owner = $this->createOwner('Incomplete Owner');

        $response = $this->postJson('/api/buildings', [
            'name' => 'Invalid Ownership Building',
            'owners' => [
                [
                    'party_id' => $owner->id,
                    'ownership_percentage' => 90,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'owners',
            ]);
    }

    /**
     * The same Party cannot appear twice in one Building ownership set.
     */
    public function test_building_rejects_duplicate_owner(): void
    {
        $owner = $this->createOwner('Duplicate Owner');

        $response = $this->postJson('/api/buildings', [
            'name' => 'Duplicate Ownership Building',
            'owners' => [
                [
                    'party_id' => $owner->id,
                    'ownership_percentage' => 50,
                ],
                [
                    'party_id' => $owner->id,
                    'ownership_percentage' => 50,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'owners.0.party_id',
                'owners.1.party_id',
            ]);
    }

    /**
     * Building ownership may be replaced during an update.
     */
    public function test_building_ownership_can_be_updated(): void
    {
        $firstOwner = $this->createOwner('Initial Owner');
        $secondOwner = $this->createOwner('Replacement Owner');

        $buildingResponse = $this->postJson(
            '/api/buildings',
            [
                'name' => 'Ownership Update Building',
                'owners' => [
                    [
                        'party_id' => $firstOwner->id,
                        'ownership_percentage' => 100,
                    ],
                ],
            ]
        );

        $buildingId = $buildingResponse->json('id');

        $this->putJson(
            "/api/buildings/{$buildingId}",
            [
                'name' => 'Ownership Update Building',
                'owners' => [
                    [
                        'party_id' => $firstOwner->id,
                        'ownership_percentage' => 50,
                    ],
                    [
                        'party_id' => $secondOwner->id,
                        'ownership_percentage' => 50,
                    ],
                ],
            ]
        )->assertOk();

        $this->assertDatabaseCount(
            'building_owners',
            2
        );

        $this->assertDatabaseHas('building_owners', [
            'building_id' => $buildingId,
            'party_id' => $secondOwner->id,
            'ownership_percentage' => 50.00,
        ]);
    }
}
