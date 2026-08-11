<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Concerns\AuthenticatesApiUser;

/**
 * Verifies the Patrimoine Lease REST API.
 */
class LeaseApiTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesApiUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Build the minimum Unit and Party context required by Lease tests.
     *
     * @return array{
     *     unit: Unit,
     *     tenant: Party,
     *     agent: Party
     * }
     */
    private function createContext(): array
    {
        $building = Building::create([
            'name' => 'Lease API Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Lease API Tenant',
            'phone' => '0200000400',
            'email' => 'lease-tenant@example.test',
        ]);

        PartyRole::create([
            'party_id' => $tenant->id,
            'role' => 'tenant',
        ]);

        $agent = Party::create([
            'type' => 'person',
            'name' => 'Lease API Agent',
            'phone' => '0200000401',
            'email' => 'lease-agent@example.test',
        ]);

        PartyRole::create([
            'party_id' => $agent->id,
            'role' => 'agent',
        ]);

        return compact(
            'unit',
            'tenant',
            'agent'
        );
    }

    /**
     * Return a complete valid Lease payload.
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validPayload(
        array $context,
        array $overrides = []
    ): array {
        return array_merge([
            'unit_id' => $context['unit']->id,
            'tenant_id' => $context['tenant']->id,
            'agent_id' => $context['agent']->id,
            'start_date' => '2026-08-01',
            'end_date' => null,
            'status' => 'active',
            'termination_notice_date' => null,
            'rent_amount' => 10000,
            'payment_frequency' => 'monthly',
            'due_day' => 1,
            'vat_rate' => 18,
            'proration_amount' => null,
            'security_deposit_amount' => 10000,
            'management_fee_type' => 'percentage',
            'management_fee_value' => 10,
            'agent_commission_amount' => 5000,
            'notes' => 'Lease API test.',
        ], $overrides);
    }

    /**
     * A valid Lease may be created through the API.
     */
    public function test_lease_can_be_created(): void
    {
        $context = $this->createContext();

        $response = $this->postJson(
            '/api/leases',
            $this->validPayload($context)
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'tenant_id',
                $context['tenant']->id
            )
            ->assertJsonPath(
                'unit_id',
                $context['unit']->id
            );

        $this->assertDatabaseHas('leases', [
            'unit_id' => $context['unit']->id,
            'tenant_id' => $context['tenant']->id,
            'status' => 'active',
        ]);
    }

    /**
     * Selected tenant must carry the tenant role.
     */
    public function test_lease_rejects_party_without_tenant_role(): void
    {
        $context = $this->createContext();

        $invalidTenant = Party::create([
            'type' => 'person',
            'name' => 'Not A Tenant',
            'phone' => '0200000402',
            'email' => 'not-tenant@example.test',
        ]);

        $response = $this->postJson(
            '/api/leases',
            $this->validPayload(
                $context,
                [
                    'tenant_id' => $invalidTenant->id,
                ]
            )
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'tenant_id',
            ]);
    }

    /**
     * Selected Agent must carry the agent role.
     */
    public function test_lease_rejects_party_without_agent_role(): void
    {
        $context = $this->createContext();

        $invalidAgent = Party::create([
            'type' => 'person',
            'name' => 'Not An Agent',
            'phone' => '0200000403',
            'email' => 'not-agent@example.test',
        ]);

        $response = $this->postJson(
            '/api/leases',
            $this->validPayload(
                $context,
                [
                    'agent_id' => $invalidAgent->id,
                ]
            )
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'agent_id',
            ]);
    }

    /**
     * Due-day override must be a valid day-of-month value.
     */
    public function test_lease_rejects_invalid_due_day(): void
    {
        $context = $this->createContext();

        $this->postJson(
            '/api/leases',
            $this->validPayload(
                $context,
                ['due_day' => 32]
            )
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'due_day',
            ]);
    }

    /**
     * Notice-stage Leases require a termination notice date.
     */
    public function test_notice_lease_requires_notice_date(): void
    {
        $context = $this->createContext();

        $this->postJson(
            '/api/leases',
            $this->validPayload(
                $context,
                [
                    'status' => 'notice',
                    'termination_notice_date' => null,
                ]
            )
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'termination_notice_date',
            ]);
    }

    /**
     * Agent commission cannot be configured without an Agent.
     */
    public function test_agent_commission_requires_agent(): void
    {
        $context = $this->createContext();

        $this->postJson(
            '/api/leases',
            $this->validPayload(
                $context,
                [
                    'agent_id' => null,
                    'agent_commission_amount' => 5000,
                ]
            )
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'agent_id',
            ]);
    }

    /**
     * Management fee value must be zero when no fee is configured.
     */
    public function test_none_management_fee_requires_zero_value(): void
    {
        $context = $this->createContext();

        $this->postJson(
            '/api/leases',
            $this->validPayload(
                $context,
                [
                    'management_fee_type' => 'none',
                    'management_fee_value' => 1000,
                ]
            )
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'management_fee_value',
            ]);
    }

    /**
     * A Unit cannot have two simultaneously active Leases.
     */
    public function test_unit_cannot_have_multiple_active_leases(): void
    {
        $context = $this->createContext();

        $this->postJson(
            '/api/leases',
            $this->validPayload($context)
        )->assertCreated();

        $secondTenant = Party::create([
            'type' => 'person',
            'name' => 'Second Tenant',
            'phone' => '0200000404',
            'email' => 'second-tenant@example.test',
        ]);

        PartyRole::create([
            'party_id' => $secondTenant->id,
            'role' => 'tenant',
        ]);

        $this->postJson(
            '/api/leases',
            $this->validPayload(
                $context,
                [
                    'tenant_id' => $secondTenant->id,
                    'agent_commission_amount' => 0,
                    'agent_id' => null,
                ]
            )
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'unit_id',
            ]);
    }

    /**
     * Draft Leases do not block an active Lease on the same Unit.
     */
    public function test_draft_lease_does_not_block_active_lease(): void
    {
        $context = $this->createContext();

        $this->postJson(
            '/api/leases',
            $this->validPayload(
                $context,
                [
                    'status' => 'draft',
                ]
            )
        )->assertCreated();

        $secondTenant = Party::create([
            'type' => 'person',
            'name' => 'Active Tenant',
            'phone' => '0200000405',
            'email' => 'active-tenant@example.test',
        ]);

        PartyRole::create([
            'party_id' => $secondTenant->id,
            'role' => 'tenant',
        ]);

        $this->postJson(
            '/api/leases',
            $this->validPayload(
                $context,
                [
                    'tenant_id' => $secondTenant->id,
                    'agent_id' => null,
                    'agent_commission_amount' => 0,
                    'status' => 'active',
                ]
            )
        )->assertCreated();

        $this->assertSame(
            2,
            Lease::query()
                ->where('unit_id', $context['unit']->id)
                ->count()
        );
    }

    /**
     * Existing Lease can be moved into termination notice state.
     */
    public function test_lease_can_be_updated_to_notice(): void
    {
        $context = $this->createContext();

        $leaseResponse = $this->postJson(
            '/api/leases',
            $this->validPayload($context)
        );

        $leaseId = $leaseResponse->json('id');

        $response = $this->putJson(
            "/api/leases/{$leaseId}",
            $this->validPayload(
                $context,
                [
                    'status' => 'notice',
                    'termination_notice_date' => '2026-08-15',
                ]
            )
        );

        $response
            ->assertOk()
            ->assertJsonPath('status', 'notice');

        /*
        * MySQL stores the date-cast value with a midnight time component,
        * while Laravel exposes it as a date through the Lease model.
        */
        $this->assertDatabaseHas('leases', [
            'id' => $leaseId,
            'status' => 'notice',
            'termination_notice_date' => '2026-08-15 00:00:00',
        ]);
    }
}
