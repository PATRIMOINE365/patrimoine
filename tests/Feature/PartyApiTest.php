<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Models\Party;
use App\Models\PartyRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Verifies the Patrimoine Party REST API.
 */
class PartyApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * A valid person may be created with several functional roles.
     */
    public function test_person_can_be_created_through_api(): void
    {
        $response = $this->postJson('/api/parties', [
            'type' => 'person',
            'name' => 'Ama Mensah',
            'phone' => '0200000200',
            'email' => 'ama@example.test',
            'roles' => [
                'owner',
                'agent',
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('type', 'person')
            ->assertJsonPath('name', 'Ama Mensah');

        $party = Party::query()->firstOrFail();

        $this->assertSame(
            'Ama Mensah',
            $party->name
        );

        $this->assertCount(
            2,
            $party->roles
        );

        $this->assertTrue(
            $party->roles->contains('role', 'owner')
        );

        $this->assertTrue(
            $party->roles->contains('role', 'agent')
        );
    }

    /**
     * Person creation enforces mandatory V1 contact fields.
     */
    public function test_person_requires_name_phone_and_email(): void
    {
        $response = $this->postJson('/api/parties', [
            'type' => 'person',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'phone',
                'email',
            ]);
    }

    /**
     * Organisation creation enforces legal and contact information.
     */
    public function test_organisation_requires_legal_and_contact_information(): void
    {
        $response = $this->postJson('/api/parties', [
            'type' => 'organisation',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'legal_name',
                'contact_person_name',
                'contact_person_phone',
                'contact_person_email',
            ]);
    }

    /**
     * Organisation records may be created successfully.
     */
    public function test_organisation_can_be_created(): void
    {
        $response = $this->postJson('/api/parties', [
            'type' => 'organisation',
            'legal_name' => 'Patrimoine Test Limited',
            'address' => 'Accra',
            'contact_person_name' => 'Kwame Test',
            'contact_person_phone' => '0200000201',
            'contact_person_email' => 'kwame@example.test',
            'registration_number' => 'CS123456',
            'roles' => [
                'managing_organisation',
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'legal_name',
                'Patrimoine Test Limited'
            );

        $this->assertDatabaseHas('parties', [
            'legal_name' => 'Patrimoine Test Limited',
        ]);

        $this->assertDatabaseHas('party_roles', [
            'role' => 'managing_organisation',
        ]);
    }

    /**
     * Party listing supports filtering by role.
     */
    public function test_parties_can_be_filtered_by_role(): void
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Owner Party',
            'phone' => '0200000202',
            'email' => 'owner-api@example.test',
        ]);

        PartyRole::create([
            'party_id' => $owner->id,
            'role' => 'owner',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Tenant Party',
            'phone' => '0200000203',
            'email' => 'tenant-api@example.test',
        ]);

        PartyRole::create([
            'party_id' => $tenant->id,
            'role' => 'tenant',
        ]);

        $response = $this->getJson(
            '/api/parties?role=owner'
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.name',
                'Owner Party'
            );
    }

    /**
     * Updating Party roles replaces the previous role set when roles are
     * explicitly supplied in the request.
     */
    public function test_party_roles_can_be_replaced_during_update(): void
    {
        $party = Party::create([
            'type' => 'person',
            'name' => 'Role Test',
            'phone' => '0200000204',
            'email' => 'role-test@example.test',
        ]);

        PartyRole::create([
            'party_id' => $party->id,
            'role' => 'tenant',
        ]);

        $response = $this->putJson(
            "/api/parties/{$party->id}",
            [
                'type' => 'person',
                'name' => 'Role Test',
                'phone' => '0200000204',
                'email' => 'role-test@example.test',
                'roles' => [
                    'owner',
                    'agent',
                ],
            ]
        );

        $response->assertOk();

        $party->refresh();

        $this->assertCount(2, $party->roles);

        $this->assertFalse(
            $party->roles->contains('role', 'tenant')
        );

        $this->assertTrue(
            $party->roles->contains('role', 'owner')
        );

        $this->assertTrue(
            $party->roles->contains('role', 'agent')
        );
    }

    /**
     * The show endpoint returns Party roles with the Party.
     */
    public function test_party_can_be_retrieved(): void
    {
        $party = Party::create([
            'type' => 'person',
            'name' => 'Show Test',
            'phone' => '0200000205',
            'email' => 'show-test@example.test',
        ]);

        PartyRole::create([
            'party_id' => $party->id,
            'role' => 'tenant',
        ]);

        $this->getJson("/api/parties/{$party->id}")
            ->assertOk()
            ->assertJsonPath('name', 'Show Test')
            ->assertJsonPath(
                'roles.0.role',
                'tenant'
            );
    }

    /**
     * Parties without protected related records may be deleted.
     */
    public function test_unreferenced_party_can_be_deleted(): void
    {
        $this->authenticateApiUser('administrator');
        $party = Party::create([
            'type' => 'person',
            'name' => 'Delete Test',
            'phone' => '0200000206',
            'email' => 'delete-test@example.test',
        ]);

        $this->deleteJson("/api/parties/{$party->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('parties', [
            'id' => $party->id,
        ]);
    }

    /**
     * Managing organisation protection messages follow French.
     */
    public function test_managing_organisation_protection_message_renders_in_french(): void
    {
        $this->authenticateApiUser('administrator');
        $settings = ApplicationSetting::create([
            'language' => 'fr',
            'currency' => 'GHS',
        ]);

        $party = Party::create([
            'type' => 'organisation',
            'legal_name' => 'Organisation Gestionnaire',
            'address' => 'Accra',
            'contact_person_name' => 'Gestionnaire',
            'contact_person_phone' => '0200000999',
            'contact_person_email' => 'gestion@example.test',
        ]);

        $party->roles()->create([
            'role' => 'managing_organisation',
        ]);

        $settings->update([
            'managing_organisation_party_id' => $party->id,
        ]);

        $this
            ->deleteJson("/api/parties/{$party->id}")
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'L’organisation gestionnaire configurée ne peut pas être supprimée. Modifiez plutôt la configuration de l’organisation gestionnaire.'
            );
    }
}
