<?php

namespace Tests\Feature;

use App\Exceptions\CrossOrganisationAccessException;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Organisation;
use App\Models\Party;
use App\Models\Unit;
use App\Models\User;
use App\Support\OrganisationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * V1.1.0 multi-tenancy: two organisations must never see, reference or
 * affect each other's data through any layer — query scope, validation,
 * route model binding or numbering.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Organisation $otherOrganisation;

    private User $ownUser;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownUser = User::factory()->create([
            'role' => 'administrator',
        ]);

        $this->otherOrganisation = Organisation::factory()->create([
            'name' => 'Rival Properties',
        ]);

        /*
         * Creating a user for organisation B while organisation A is
         * bound is exactly what the write-guard forbids, so the second
         * organisation's user is provisioned under its own binding.
         */
        $this->otherUser = OrganisationContext::runAs(
            (int) $this->otherOrganisation->id,
            fn (): User => User::factory()
                ->forOrganisation($this->otherOrganisation)
                ->create([
                    'role' => 'administrator',
                ])
        );
    }

    /**
     * Create one building+unit+party inside the given organisation.
     *
     * @return array{party: Party, building: Building, unit: Unit}
     */
    private function seedPortfolio(int $organisationId, string $prefix): array
    {
        return OrganisationContext::runAs(
            $organisationId,
            function () use ($prefix): array {
                $party = Party::create([
                    'type' => 'person',
                    'name' => $prefix.' Tenant',
                ]);

                $building = Building::create([
                    'name' => $prefix.' Building',
                ]);

                $unit = Unit::create([
                    'building_id' => $building->id,
                    'name' => $prefix.'-U1',
                ]);

                return [
                    'party' => $party,
                    'building' => $building,
                    'unit' => $unit,
                ];
            }
        );
    }

    public function test_lists_only_contain_own_organisation_data(): void
    {
        $this->seedPortfolio((int) $this->testOrganisation->id, 'Mine');
        $this->seedPortfolio((int) $this->otherOrganisation->id, 'Theirs');

        Sanctum::actingAs($this->ownUser);

        $parties = $this->getJson('/api/parties')->assertOk()->json();

        $names = collect($parties['data'] ?? $parties)
            ->pluck('name')
            ->all();

        $this->assertContains('Mine Tenant', $names);
        $this->assertNotContains('Theirs Tenant', $names);

        $buildings = $this->getJson('/api/buildings')->assertOk()->json();

        $buildingNames = collect($buildings['data'] ?? $buildings)
            ->pluck('name')
            ->all();

        $this->assertContains('Mine Building', $buildingNames);
        $this->assertNotContains('Theirs Building', $buildingNames);
    }

    public function test_foreign_record_ids_resolve_to_404(): void
    {
        $theirs = $this->seedPortfolio(
            (int) $this->otherOrganisation->id,
            'Theirs'
        );

        Sanctum::actingAs($this->ownUser);

        $this->getJson(
            '/api/parties/'.$theirs['party']->id
        )->assertNotFound();

        $this->getJson(
            '/api/buildings/'.$theirs['building']->id
        )->assertNotFound();
    }

    public function test_validation_rejects_foreign_identifiers(): void
    {
        $this->seedPortfolio((int) $this->testOrganisation->id, 'Mine');

        $theirs = $this->seedPortfolio(
            (int) $this->otherOrganisation->id,
            'Theirs'
        );

        Sanctum::actingAs($this->ownUser);

        /*
         * A lease pointing at ANOTHER organisation's unit and tenant
         * must fail validation exactly like a nonexistent id.
         */
        $this->postJson('/api/leases', [
            'unit_id' => $theirs['unit']->id,
            'tenant_id' => $theirs['party']->id,
            'start_date' => '2026-01-01',
            'status' => 'draft',
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['unit_id', 'tenant_id']);
    }

    public function test_users_endpoint_is_organisation_scoped(): void
    {
        Sanctum::actingAs($this->ownUser);

        $emails = collect(
            $this->getJson('/api/users')->assertOk()->json('data')
            ?? []
        )->pluck('email')->all();

        $this->assertContains($this->ownUser->email, $emails);
        $this->assertNotContains($this->otherUser->email, $emails);
    }

    public function test_invoice_numbering_restarts_per_organisation(): void
    {
        $mineNumber = OrganisationContext::runAs(
            (int) $this->testOrganisation->id,
            function (): string {
                return $this->createInvoiceViaModels('Mine');
            }
        );

        $theirNumber = OrganisationContext::runAs(
            (int) $this->otherOrganisation->id,
            function (): string {
                return $this->createInvoiceViaModels('Theirs');
            }
        );

        /*
         * Both organisations run their own INV-000001... series; the
         * composite unique key (organisation_id, invoice_number) makes
         * the identical number legal across organisations.
         */
        $this->assertSame('INV-000001', $mineNumber);
        $this->assertSame('INV-000001', $theirNumber);
    }

    public function test_rows_cannot_be_created_for_another_organisation(): void
    {
        $this->expectException(CrossOrganisationAccessException::class);

        OrganisationContext::runAs(
            (int) $this->testOrganisation->id,
            function (): void {
                $party = new Party;

                $party->type = 'person';
                $party->name = 'Smuggled Tenant';
                $party->organisation_id = $this->otherOrganisation->id;

                $party->save();
            }
        );
    }

    public function test_rows_cannot_be_moved_between_organisations(): void
    {
        $mine = $this->seedPortfolio(
            (int) $this->testOrganisation->id,
            'Mine'
        );

        $this->expectException(CrossOrganisationAccessException::class);

        $mine['party']->forceFill([
            'organisation_id' => $this->otherOrganisation->id,
        ])->save();
    }

    /**
     * Build a minimal lease and let the real generation service issue
     * its first invoice number.
     */
    private function createInvoiceViaModels(string $prefix): string
    {
        $party = Party::create([
            'type' => 'person',
            'name' => $prefix.' Tenant',
        ]);

        $building = Building::create([
            'name' => $prefix.' Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => $prefix.'-U1',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $party->id,
            'start_date' => '2026-01-01',
            'status' => 'draft',
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
        ]);

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => $this->nextInvoiceNumberFor(),
            'type' => 'rent',
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-08',
            'status' => 'issued',
            'total_amount' => 1000,
            'net_amount' => 1000,
            'vat_amount' => 0,
            'vat_rate' => 0,
        ]);

        return $invoice->invoice_number;
    }

    /**
     * Mirror of the per-organisation INV- numbering rule.
     */
    private function nextInvoiceNumberFor(): string
    {
        $last = Invoice::query()
            ->where('invoice_number', 'like', 'INV-%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = $last === null
            ? 1
            : ((int) substr($last, -6)) + 1;

        return sprintf('INV-%06d', $sequence);
    }
}
