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

    /**
     * V1.0.35: money taken but not yet filed reaches the bell.
     *
     * A payment settles invoices oldest first and stops when they run
     * out. What is left over is not lost — it waits to be classified into
     * a tenant fund — but until somebody does that it sits on the payment
     * and appears on no balance, in no fund and nowhere in the ledger.
     *
     * Nothing used to chase it, so a tenant could be ahead without
     * looking it, and be asked again for money already handed over.
     */
    public function test_money_received_but_not_filed_reaches_the_bell(): void
    {
        Mail::fake();

        $building = Building::create([
            'name' => 'Unclassified Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Unclassified Owner',
            'phone' => '0200009100',
            'email' => 'unclassified-owner@example.test',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit U-1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Unclassified Tenant',
            'phone' => '0200009101',
            'email' => 'unclassified-tenant@example.test',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'active',
        ]);

        $invoice = \App\Models\Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => 'INV-UNCLASS-1',
            'type' => 'rent',
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
            'issue_date' => '2026-05-01',
            'due_date' => '2026-05-01',
            'status' => 'issued',
            'total_amount' => 5000,
            'net_amount' => 5000,
            'vat_amount' => 0,
            'vat_rate' => 0,
        ]);

        /*
         * Nothing unfiled yet.
         */
        $baseline = $this->getJson('/api/notifications')
            ->assertOk()
            ->json('notifications');

        $this->assertNotContains(
            'payments_unclassified',
            array_column($baseline, 'kind')
        );

        /*
         * 8,000 against a 5,000 invoice: 5,000 settles it, 3,000 is left
         * over and belongs nowhere yet.
         */
        $payment = $this->postJson('/api/payments', [
            'lease_id' => $lease->id,
            'amount' => 8000,
            'payment_date' => '2026-05-01',
            'payment_method' => 'bank_transfer',
        ])->assertCreated();

        $notifications = $this->getJson('/api/notifications')
            ->assertOk()
            ->json('notifications');

        $unclassified = collect($notifications)
            ->firstWhere('kind', 'payments_unclassified');

        $this->assertNotNull(
            $unclassified,
            'Money left unfiled must reach the bell.'
        );

        $this->assertSame(1, $unclassified['count']);
        $this->assertSame(3000, $unclassified['amount']);

        /*
         * Filing 2,000 of it into the rent reserve leaves 1,000 waiting,
         * and the bell must follow that down rather than latch.
         */
        $this->postJson(
            '/api/payments/'.$payment->json('id').'/tenant-funds',
            [
                'fund_type' => 'rent_reserve',
                'amount' => 2000,
                'transaction_date' => '2026-05-01',
            ]
        )->assertCreated();

        $after = collect(
            $this->getJson('/api/notifications')->json('notifications')
        )->firstWhere('kind', 'payments_unclassified');

        $this->assertNotNull($after);
        $this->assertSame(1000, $after['amount']);

        /*
         * Filing the rest clears it from the bell entirely.
         */
        $this->postJson(
            '/api/payments/'.$payment->json('id').'/tenant-funds',
            [
                'fund_type' => 'rent_reserve',
                'amount' => 1000,
                'transaction_date' => '2026-05-01',
            ]
        )->assertCreated();

        $cleared = collect(
            $this->getJson('/api/notifications')->json('notifications')
        )->firstWhere('kind', 'payments_unclassified');

        $this->assertNull(
            $cleared,
            'Once every penny is filed, nothing should still be chasing it.'
        );
    }
}
