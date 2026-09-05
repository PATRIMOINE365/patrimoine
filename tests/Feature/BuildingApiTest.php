<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Models\Party;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Verifies the Patrimoine Building REST API.
 */
class BuildingApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

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
        $party = Party::create([
            'type' => 'person',
            'name' => $name,
            'phone' => '0200000300',
            'email' => strtolower(
                str_replace(' ', '-', $name)
            ).'@example.test',
        ]);

        $party->roles()->create([
            'role' => 'owner',
        ]);

        return $party;
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

    /**
     * Searching by Unit name returns the parent Building and its Units.
     */
    public function test_building_search_finds_unit_name(): void
    {
        $owner = $this->createOwner(
            'Unit Search Owner'
        );

        $buildingResponse = $this->postJson(
            '/api/buildings',
            [
                'name' => 'Search Parent Building',
                'owners' => [
                    [
                        'party_id' => $owner->id,
                        'ownership_percentage' => 100,
                    ],
                ],
            ]
        )->assertCreated();

        $buildingId =
            $buildingResponse->json('id');

        $this->postJson(
            '/api/units',
            [
                'building_id' => $buildingId,
                'name' => 'Penthouse Sapphire',
                'description' => 'Top floor residence',
            ]
        )->assertCreated();

        $this->getJson(
            '/api/buildings?search=Sapphire'
        )
            ->assertOk()
            ->assertJsonPath(
                'data.0.id',
                $buildingId
            )
            ->assertJsonPath(
                'data.0.units.0.name',
                'Penthouse Sapphire'
            );
    }

    /**
     * Searching by Unit description returns the parent Building.
     */
    public function test_building_search_finds_unit_description(): void
    {
        $owner = $this->createOwner(
            'Description Search Owner'
        );

        $buildingResponse = $this->postJson(
            '/api/buildings',
            [
                'name' => 'Description Parent Building',
                'owners' => [
                    [
                        'party_id' => $owner->id,
                        'ownership_percentage' => 100,
                    ],
                ],
            ]
        )->assertCreated();

        $buildingId =
            $buildingResponse->json('id');

        $this->postJson(
            '/api/units',
            [
                'building_id' => $buildingId,
                'name' => 'Unit 4B',
                'description' => 'Private rooftop terrace',
            ]
        )->assertCreated();

        $this->getJson(
            '/api/buildings?search=rooftop'
        )
            ->assertOk()
            ->assertJsonPath(
                'data.0.id',
                $buildingId
            )
            ->assertJsonPath(
                'data.0.units.0.name',
                'Unit 4B'
            );
    }

    /**
     * Custom FormRequest validation follows French.
     */
    public function test_building_ownership_validation_renders_in_french(): void
    {
        ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Propriétaire Test',
            'phone' => '0200000888',
            'email' => 'owner-fr@example.test',
        ]);

        $this
            ->postJson('/api/buildings', [
                'name' => 'Immeuble Test',
                'owners' => [
                    [
                        'party_id' => $owner->id,
                        'ownership_percentage' => 50,
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.owners.0',
                'La somme des pourcentages de propriété de l’immeuble doit être égale à 100 %.'
            );
    }

    /**
     * V1.0.50: a party without the owner role is not accepted as an
     * owner, however it reaches the API — and no owner account is opened
     * for it.
     */
    public function test_building_refuses_an_owner_without_the_owner_role(): void
    {
        $tenantOnly = Party::create([
            'type' => 'person',
            'name' => 'Tenant Only',
            'phone' => '0200000301',
            'email' => 'tenant-only@example.test',
        ]);

        $tenantOnly->roles()->create([
            'role' => 'tenant',
        ]);

        $this
            ->postJson('/api/buildings', [
                'name' => 'Role Gap Villa',
                'owners' => [
                    [
                        'party_id' => $tenantOnly->id,
                        'ownership_percentage' => 100,
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['owners.0.party_id']);

        $this->assertDatabaseMissing('buildings', [
            'name' => 'Role Gap Villa',
        ]);

        $this->assertDatabaseMissing('owner_accounts', [
            'party_id' => $tenantOnly->id,
        ]);
    }

    /**
     * V1.0.50: an owner account that never held anything leaves with the
     * party's last property, so the Owners list does not keep listing a
     * landlord who owns nothing.
     */
    public function test_an_unused_owner_account_is_released_with_the_last_property(): void
    {
        $owner = $this->createOwner('Sole Owner');
        $keeper = $this->createOwner('Second Owner');

        $created = $this
            ->postJson('/api/buildings', [
                'name' => 'Released Court',
                'owners' => [
                    [
                        'party_id' => $owner->id,
                        'ownership_percentage' => 60,
                    ],
                    [
                        'party_id' => $keeper->id,
                        'ownership_percentage' => 40,
                    ],
                ],
            ])
            ->assertCreated();

        $buildingId = $created->json('id');

        $this->assertDatabaseHas('owner_accounts', [
            'party_id' => $owner->id,
        ]);

        /*
         * Replacing the owners drops the first one: their empty account
         * goes with the ownership.
         */
        $this
            ->putJson("/api/buildings/{$buildingId}", [
                'name' => 'Released Court',
                'owners' => [
                    [
                        'party_id' => $keeper->id,
                        'ownership_percentage' => 100,
                    ],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('owner_accounts', [
            'party_id' => $owner->id,
        ]);

        $this->assertDatabaseHas('owner_accounts', [
            'party_id' => $keeper->id,
        ]);

        /*
         * Deleting the building releases the remaining owner too.
         */
        $this
            ->deleteJson("/api/buildings/{$buildingId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('owner_accounts', [
            'party_id' => $keeper->id,
        ]);
    }
}
