<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Payment;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Verifies the Patrimoine Payment transactional API.
 */
class PaymentApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateApiUser();
    }

    /**
     * Create a complete financial context containing:
     * - Building;
     * - Owner;
     * - Unit;
     * - Tenant;
     * - active Lease.
     *
     * @return array{
     *     building: Building,
     *     owner: Party,
     *     unit: Unit,
     *     tenant: Party,
     *     lease: Lease
     * }
     */
    private function createContext(): array
    {
        $building = Building::create([
            'name' => 'Payment API Building',
        ]);

        $owner = Party::create([
            'type' => 'person',
            'name' => 'Payment API Owner',
            'phone' => '0200000500',
            'email' => 'payment-api-owner@example.test',
        ]);

        PartyRole::create([
            'party_id' => $owner->id,
            'role' => 'owner',
        ]);

        BuildingOwner::create([
            'building_id' => $building->id,
            'party_id' => $owner->id,
            'ownership_percentage' => 100.00,
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unit 1',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Payment API Tenant',
            'phone' => '0200000501',
            'email' => 'payment-api-tenant@example.test',
        ]);

        PartyRole::create([
            'party_id' => $tenant->id,
            'role' => 'tenant',
        ]);

        $lease = Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'active',
        ]);

        return compact(
            'building',
            'owner',
            'unit',
            'tenant',
            'lease'
        );
    }

    /**
     * Create an issued Invoice.
     */
    private function createInvoice(
        Lease $lease,
        string $number,
        string $dueDate,
        int $amount
    ): Invoice {
        return Invoice::create([
            'lease_id' => $lease->id,
            'invoice_number' => $number,
            'period_start' => $dueDate,
            'period_end' => $dueDate,
            'issue_date' => $dueDate,
            'due_date' => $dueDate,
            'status' => 'issued',
            'total_amount' => $amount,
            'vat_rate' => 0,
            'net_amount' => $amount,
            'vat_amount' => 0,
        ]);
    }

    /**
     * Recording a Payment automatically allocates it to the oldest Invoice.
     */
    public function test_payment_is_created_and_allocated_fifo(): void
    {
        $context = $this->createContext();

        $oldest = $this->createInvoice(
            $context['lease'],
            'INV-PAY-001',
            '2026-01-01',
            5000
        );

        $newer = $this->createInvoice(
            $context['lease'],
            'INV-PAY-002',
            '2026-02-01',
            5000
        );

        $response = $this->postJson('/api/payments', [
            'lease_id' => $context['lease']->id,
            'amount' => 7000,
            'payment_date' => '2026-02-10',
            'payment_method' => 'bank_transfer',
            'reference' => 'BANK-PAY-001',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('amount', 7000)
            ->assertJsonPath('allocated_amount', 7000)
            ->assertJsonPath('unallocated_amount', 0);

        $this->assertSame(
            5000,
            $oldest->fresh()->paidAmount()
        );

        $this->assertSame(
            2000,
            $newer->fresh()->paidAmount()
        );

        $this->assertSame(
            'paid',
            $oldest->fresh()->status
        );

        $this->assertSame(
            'partial',
            $newer->fresh()->status
        );
    }

    /**
     * Owner entitlement is created automatically only for rent collected.
     */
    public function test_payment_automatically_creates_owner_entitlement(): void
    {
        $context = $this->createContext();

        $this->createInvoice(
            $context['lease'],
            'INV-PAY-003',
            '2026-03-01',
            10000
        );

        $this->postJson('/api/payments', [
            'lease_id' => $context['lease']->id,
            'amount' => 4000,
            'payment_date' => '2026-03-05',
            'payment_method' => 'momo',
            'reference' => 'MOMO-PAY-001',
        ])->assertCreated();

        $ownerAccount = OwnerAccount::query()
            ->where('party_id', $context['owner']->id)
            ->firstOrFail();

        /*
         * Only GHS 4,000 was actually collected, therefore owner funds held
         * increase by exactly GHS 4,000 rather than the GHS 10,000 Invoice.
         */
        $this->assertSame(
            4000,
            $ownerAccount->creditedAmount()
        );
    }

    /**
     * Partial Payment leaves the remaining Invoice balance outstanding.
     */
    public function test_partial_payment_is_supported(): void
    {
        $context = $this->createContext();

        $invoice = $this->createInvoice(
            $context['lease'],
            'INV-PAY-004',
            '2026-04-01',
            10000
        );

        $response = $this->postJson('/api/payments', [
            'lease_id' => $context['lease']->id,
            'amount' => 3000,
            'payment_date' => '2026-04-05',
            'payment_method' => 'bank_transfer',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('allocated_amount', 3000);

        $this->assertSame(
            7000,
            $invoice->fresh()->outstandingAmount()
        );

        $this->assertSame(
            'partial',
            $invoice->fresh()->status
        );
    }

    /**
     * Overpayment remains unapplied after all outstanding Invoices are paid.
     */
    public function test_payment_may_have_unallocated_excess(): void
    {
        $context = $this->createContext();

        $this->createInvoice(
            $context['lease'],
            'INV-PAY-005',
            '2026-05-01',
            5000
        );

        $response = $this->postJson('/api/payments', [
            'lease_id' => $context['lease']->id,
            'amount' => 8000,
            'payment_date' => '2026-05-01',
            'payment_method' => 'bank_transfer',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('allocated_amount', 5000)
            ->assertJsonPath('unallocated_amount', 3000);
    }

    /**
     * Cash payments automatically use the authenticated User
     * as the immutable Cash Receiver.
     */
    public function test_cash_payment_uses_authenticated_user_as_cash_receiver(): void
    {
        $context = $this->createContext();

        $user = \App\Models\User::factory()->create([
            'name' => 'Ama Receiver',
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/payments', [
            'lease_id' => $context['lease']->id,
            'amount' => 5000,
            'payment_date' => '2026-06-01',
            'payment_method' => 'cash',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'cash_receiver_user_id',
                $user->id
            )
            ->assertJsonPath(
                'cash_receiver_name',
                'Ama Receiver'
            );

        $payment = \App\Models\Payment::query()
            ->findOrFail(
                $response->json('id')
            );

        $this->assertSame(
            $user->id,
            $payment->cash_receiver_user_id
        );

        $this->assertSame(
            'Ama Receiver',
            $payment->cash_receiver_name
        );

        $this->assertNull(
            $payment->collector_name
        );
    }

    /**
     * Typed legacy collector information cannot override
     * the authenticated Cash Receiver.
     */
    public function test_cash_payment_ignores_typed_legacy_collector_name(): void
    {
        $context = $this->createContext();

        $user = \App\Models\User::factory()->create([
            'name' => 'Authenticated Receiver',
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/payments', [
            'lease_id' => $context['lease']->id,
            'amount' => 5000,
            'payment_date' => '2026-06-01',
            'payment_method' => 'cash',
            'collector_name' => 'Fake Typed Collector',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'cash_receiver_user_id',
                $user->id
            )
            ->assertJsonPath(
                'cash_receiver_name',
                'Authenticated Receiver'
            );

        $payment = \App\Models\Payment::query()
            ->findOrFail(
                $response->json('id')
            );

        $this->assertSame(
            $user->id,
            $payment->cash_receiver_user_id
        );

        $this->assertSame(
            'Authenticated Receiver',
            $payment->cash_receiver_name
        );

        $this->assertNull(
            $payment->collector_name
        );
    }

    /**
     * Payments cannot be recorded against draft Leases.
     */
    public function test_payment_rejects_draft_lease(): void
    {
        $context = $this->createContext();

        $context['lease']->update([
            'status' => 'draft',
        ]);

        $this->postJson('/api/payments', [
            'lease_id' => $context['lease']->id,
            'amount' => 5000,
            'payment_date' => '2026-07-01',
            'payment_method' => 'bank_transfer',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'lease_id',
            ]);
    }

    /**
     * Payments may be backdated.
     */
    public function test_payment_can_be_backdated(): void
    {
        $context = $this->createContext();

        $response = $this->postJson('/api/payments', [
            'lease_id' => $context['lease']->id,
            'amount' => 5000,
            'payment_date' => '2026-01-15',
            'payment_method' => 'momo',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'payment_date',
                '2026-01-15'
            );

        /*
        * MySQL stores the date-cast value with a midnight time component,
        * while the Payment model exposes it as a date.
        */
        $this->assertDatabaseHas('payments', [
            'lease_id' => $context['lease']->id,
            'amount' => 5000,
            'payment_date' => '2026-01-15 00:00:00',
        ]);
    }

    /**
     * Cheque is the fourth supported payment channel, alongside cash,
     * bank transfer and mobile payment.
     */
    public function test_payment_can_be_recorded_by_cheque(): void
    {
        $context = $this->createContext();

        $response = $this->postJson('/api/payments', [
            'lease_id' => $context['lease']->id,
            'amount' => 5000,
            'payment_date' => '2026-01-15',
            'payment_method' => 'cheque',
            'reference' => 'CHQ-000123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'payment_method',
                'cheque'
            );

        $this->assertDatabaseHas('payments', [
            'lease_id' => $context['lease']->id,
            'amount' => 5000,
            'payment_method' => 'cheque',
            'reference' => 'CHQ-000123',
        ]);
    }

    /**
     * Payment listing may be filtered by Lease.
     */
    public function test_payments_can_be_filtered_by_lease(): void
    {
        $first = $this->createContext();

        $secondBuilding = Building::create([
            'name' => 'Second Payment Building',
        ]);

        $secondUnit = Unit::create([
            'building_id' => $secondBuilding->id,
            'name' => 'Unit 2',
        ]);

        $secondTenant = Party::create([
            'type' => 'person',
            'name' => 'Second Payment Tenant',
            'phone' => '0200000502',
            'email' => 'second-payment@example.test',
        ]);

        $secondLease = Lease::create([
            'unit_id' => $secondUnit->id,
            'tenant_id' => $secondTenant->id,
            'start_date' => '2026-01-01',
            'rent_amount' => 5000,
            'status' => 'active',
        ]);

        $this->postJson('/api/payments', [
            'lease_id' => $first['lease']->id,
            'amount' => 1000,
            'payment_date' => '2026-08-01',
            'payment_method' => 'bank_transfer',
        ])->assertCreated();

        /*
         * Create this one directly because the second Building does not need
         * ownership configuration for a simple listing/filter test.
         */
        Payment::create([
            'lease_id' => $secondLease->id,
            'amount' => 2000,
            'payment_date' => '2026-08-01',
            'payment_method' => 'bank_transfer',
        ]);

        $this->getJson(
            "/api/payments?lease_id={$first['lease']->id}"
        )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.lease_id',
                $first['lease']->id
            );
    }

    /**
     * Payment detail distinguishes raw unapplied money from tenant money
     * already classified into held funds.
     */
    public function test_payment_exposes_remaining_unclassified_funds(): void
    {
        $context =
            $this->createContext();

        $this->createInvoice(
            $context['lease'],
            'INV-PAY-FUND-001',
            '2026-08-01',
            5000
        );

        /*
         * GHS 8,000 received:
         *
         * - GHS 5,000 settles rent through normal FIFO allocation;
         * - GHS 3,000 remains unapplied.
         */
        $paymentResponse =
            $this->postJson(
                '/api/payments',
                [
                    'lease_id' => $context['lease']->id,

                    'amount' => 8000,

                    'payment_date' => '2026-08-01',

                    'payment_method' => 'bank_transfer',
                ]
            );

        $paymentResponse
            ->assertCreated()
            ->assertJsonPath(
                'allocated_amount',
                5000
            )
            ->assertJsonPath(
                'unallocated_amount',
                3000
            )
            ->assertJsonPath(
                'classified_fund_amount',
                0
            )
            ->assertJsonPath(
                'remaining_unclassified_amount',
                3000
            );

        $paymentId =
            $paymentResponse->json(
                'id'
            );

        /*
         * Classify GHS 2,000 of the unapplied money into Rent Reserve.
         */
        $this->postJson(
            "/api/payments/{$paymentId}/tenant-funds",
            [
                'fund_type' => 'rent_reserve',

                'amount' => 2000,

                'transaction_date' => '2026-08-01',
            ]
        )->assertCreated();

        /*
         * Raw unallocated Payment money remains GHS 3,000 because none of it
         * was applied to an Invoice. Operationally, however, only GHS 1,000
         * remains available for additional tenant-fund classification.
         */
        $this->getJson(
            "/api/payments/{$paymentId}"
        )
            ->assertOk()
            ->assertJsonPath(
                'allocated_amount',
                5000
            )
            ->assertJsonPath(
                'unallocated_amount',
                3000
            )
            ->assertJsonPath(
                'classified_fund_amount',
                2000
            )
            ->assertJsonPath(
                'remaining_unclassified_amount',
                1000
            );
    }
}
