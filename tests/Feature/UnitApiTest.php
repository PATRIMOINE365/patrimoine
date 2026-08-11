<?php

namespace Tests\Feature;

use App\Models\Building;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the Patrimoine Unit REST API.
 */
class UnitApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a Building for Unit API tests.
     */
    private function createBuilding(): Building
    {
        return Building::create([
            'name' => 'Unit Test Building',
        ]);
    }

    /**
     * A Unit may be created under an existing Building.
     */
    public function test_unit_can_be_created(): void
    {
        $building = $this->createBuilding();

        $response = $this->postJson('/api/units', [
            'building_id' => $building->id,
            'name' => 'Apartment 1',
            'description' => 'Two-bedroom unit',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'name',
                'Apartment 1'
            );

        $this->assertDatabaseHas('units', [
            'building_id' => $building->id,
            'name' => 'Apartment 1',
        ]);
    }

    /**
     * Unit names must be unique within the same Building.
     */
    public function test_duplicate_unit_name_is_rejected_within_building(): void
    {
        $building = $this->createBuilding();

        $this->postJson('/api/units', [
            'building_id' => $building->id,
            'name' => 'Shop 1',
        ])->assertCreated();

        $this->postJson('/api/units', [
            'building_id' => $building->id,
            'name' => 'Shop 1',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
            ]);
    }

    /**
     * The same Unit name may exist in different Buildings.
     */
    public function test_same_unit_name_is_allowed_in_different_buildings(): void
    {
        $firstBuilding = Building::create([
            'name' => 'Building A',
        ]);

        $secondBuilding = Building::create([
            'name' => 'Building B',
        ]);

        $this->postJson('/api/units', [
            'building_id' => $firstBuilding->id,
            'name' => 'Unit 1',
        ])->assertCreated();

        $this->postJson('/api/units', [
            'building_id' => $secondBuilding->id,
            'name' => 'Unit 1',
        ])->assertCreated();

        $this->assertDatabaseCount(
            'units',
            2
        );
    }

    /**
     * Unit listing may be filtered by Building.
     */
    public function test_units_can_be_filtered_by_building(): void
    {
        $firstBuilding = Building::create([
            'name' => 'Filter A',
        ]);

        $secondBuilding = Building::create([
            'name' => 'Filter B',
        ]);

        $this->postJson('/api/units', [
            'building_id' => $firstBuilding->id,
            'name' => 'A1',
        ]);

        $this->postJson('/api/units', [
            'building_id' => $secondBuilding->id,
            'name' => 'B1',
        ]);

        $this->getJson(
            "/api/units?building_id={$firstBuilding->id}"
        )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.name',
                'A1'
            );
    }
}
