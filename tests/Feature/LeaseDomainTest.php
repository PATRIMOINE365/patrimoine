<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the fundamental Patrimoine lease rules.
 *
 * These tests protect the assumptions used later by invoicing,
 * rent collection, reserve consumption and occupancy reporting.
 */
class LeaseDomainTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build the minimum records required for a valid lease test.
     *
     * @return array{unit: Unit, tenant: Party}
     */
    private function createLeaseContext(): array
    {
        $building = Building::create([
            'name' => 'Test Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Test Tenant',
            'phone' => '0200000010',
            'email' => 'tenant@example.test',
        ]);

        return [
            'unit' => $unit,
            'tenant' => $tenant,
        ];
    }

    /**
     * A lease must belong to exactly one Unit and one tenant Party.
     */
    public function test_lease_belongs_to_one_unit_and_one_tenant(): void
    {
        $context = $this->createLeaseContext();

        $lease = Lease::create([
            'unit_id' => $context['unit']->id,
            'tenant_id' => $context['tenant']->id,
            'start_date' => '2026-08-15',
            'rent_amount' => 5000,
            'payment_frequency' => 'monthly',
            'status' => 'active',
        ]);

        $this->assertSame($context['unit']->id, $lease->unit->id);
        $this->assertSame($context['tenant']->id, $lease->tenant->id);

        $this->assertTrue(
            $context['unit']->fresh()->leases->contains($lease)
        );
    }

    /**
     * Without an explicit override, the rent due day is inherited
     * from the lease start date.
     */
    public function test_due_day_defaults_to_start_date_day(): void
    {
        $context = $this->createLeaseContext();

        $lease = Lease::create([
            'unit_id' => $context['unit']->id,
            'tenant_id' => $context['tenant']->id,
            'start_date' => '2026-08-15',
            'rent_amount' => 5000,
        ]);

        $this->assertSame(15, $lease->effectiveDueDay());
    }

    /**
     * A manually configured due day overrides the lease start-date day.
     */
    public function test_due_day_can_be_overridden(): void
    {
        $context = $this->createLeaseContext();

        $lease = Lease::create([
            'unit_id' => $context['unit']->id,
            'tenant_id' => $context['tenant']->id,
            'start_date' => '2026-08-15',
            'due_day' => 1,
            'rent_amount' => 5000,
        ]);

        $this->assertSame(1, $lease->effectiveDueDay());
    }

    /**
     * VAT is stored as a fixed-precision decimal rather than a
     * floating-point value.
     */
    public function test_lease_preserves_vat_precision(): void
    {
        $context = $this->createLeaseContext();

        $lease = Lease::create([
            'unit_id' => $context['unit']->id,
            'tenant_id' => $context['tenant']->id,
            'start_date' => '2026-08-15',
            'rent_amount' => 5000,
            'vat_rate' => 18.00,
        ]);

        $this->assertSame('18.00', $lease->vat_rate);
    }

    /**
     * NULL and zero proration have intentionally different meanings.
     *
     * NULL means Patrimoine should calculate proration automatically.
     * Zero means the user explicitly configured no prorated charge.
     */
    public function test_proration_distinguishes_automatic_from_zero(): void
    {
        $context = $this->createLeaseContext();

        $automatic = Lease::create([
            'unit_id' => $context['unit']->id,
            'tenant_id' => $context['tenant']->id,
            'start_date' => '2026-08-15',
            'rent_amount' => 5000,
            'proration_amount' => null,
        ]);

        $explicitZero = Lease::create([
            'unit_id' => $context['unit']->id,
            'tenant_id' => $context['tenant']->id,
            'start_date' => '2027-08-15',
            'rent_amount' => 5000,
            'proration_amount' => 0,
        ]);

        $this->assertNull($automatic->proration_amount);
        $this->assertSame(0, $explicitZero->proration_amount);
    }
}
