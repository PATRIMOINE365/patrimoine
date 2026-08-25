<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\Party;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * V1.0.8: the notification bell surfaces unpaid expense invoices and
 * unpaid owner expense bills, because expenses no longer settle
 * themselves at recording time.
 */
class NotificationApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    public function test_unpaid_expenses_and_owner_bills_reach_the_bell(): void
    {
        Mail::fake();

        $building = Building::create([
            'name' => 'Notification Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Notification Owner',
            'phone' => '0200009000',
            'email' => 'notification-owner@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        $account = OwnerAccount::query()
            ->where('party_id', $owner->id)
            ->firstOrFail();

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit N-1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Notification Tenant',
            'phone' => '0200009001',
            'email' => 'notification-tenant@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'active',
        ]);

        /*
         * No open receivables yet: neither kind may appear.
         */
        $baseline = $this->getJson('/api/notifications')
            ->assertOk()
            ->json('notifications');

        $this->assertNotContains(
            'expenses_unpaid',
            array_column($baseline, 'kind')
        );

        $this->assertNotContains(
            'owner_bills_unpaid',
            array_column($baseline, 'kind')
        );

        $this->postJson(
            '/api/tenant-expense-invoices',
            [
                'lease_id' => $lease->id,
                'transaction_date' => '2026-08-25',
                'lines' => [
                    [
                        'description' => 'Unpaid tenant expense.',
                        'amount' => 4000,
                    ],
                ],
            ]
        )->assertCreated();

        $this->postJson(
            "/api/owner-accounts/{$account->id}/expense-bills",
            [
                'building_id' => $building->id,
                'split' => 'single',
                'bill_date' => '2026-08-25',
                'lines' => [
                    [
                        'description' => 'Unpaid owner expense.',
                        'amount' => 2500,
                    ],
                ],
            ]
        )->assertCreated();

        $notifications = $this->getJson('/api/notifications')
            ->assertOk()
            ->json('notifications');

        $byKind = collect($notifications)->keyBy('kind');

        $this->assertSame(1, $byKind['expenses_unpaid']['count']);
        $this->assertSame(4000, $byKind['expenses_unpaid']['amount']);

        $this->assertSame(1, $byKind['owner_bills_unpaid']['count']);
        $this->assertSame(2500, $byKind['owner_bills_unpaid']['amount']);
    }
}
