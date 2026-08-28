<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingOwner;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Models\Party;
use App\Models\PartyRole;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * Verifies the Patrimoine Lease REST API.
 */
class LeaseApiTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

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
     * @param  array<string, mixed>  $overrides
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
            'advance_payment_amount' => 0,
            'rent_reserve_amount' => 0,

            /*
             * Contractual Advance Payment does not imply that money
             * has actually been received. Existing Lease tests therefore
             * default explicitly to no opening financial receipt.
             */
            'advance_received' => false,

            'rent_increment_type' => 'none',
            'rent_increment_value' => 0,
            'next_rent_increment_date' => null,
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
     * V1.0.8: every Lease owns all three tenant fund accounts from the
     * moment it exists, so Tenants > Accounts always shows the complete
     * held-funds position and Transfers can reach any account.
     */
    public function test_lease_creation_provisions_all_three_fund_accounts(): void
    {
        $context = $this->createContext();

        $leaseId = $this->postJson(
            '/api/leases',
            $this->validPayload($context)
        )
            ->assertCreated()
            ->json('id');

        foreach ([
            'rent_reserve',
            'consumable_advance',
            'security_deposit',
        ] as $type) {
            $this->assertDatabaseHas('tenant_fund_accounts', [
                'lease_id' => $leaseId,
                'type' => $type,
                'status' => 'active',
            ]);
        }

        /*
         * Re-initialization (a later Lease update) must find the existing
         * accounts rather than duplicate them.
         */
        app(\App\Services\LeaseInitializationService::class)
            ->initialize(Lease::findOrFail($leaseId));

        $this->assertSame(
            3,
            TenantFundAccount::where('lease_id', $leaseId)->count()
        );
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
     * V1.0.5 termination lifecycle transitions are no longer available
     * through the generic Lease update endpoint.
     */
    public function test_generic_lease_update_cannot_initiate_termination_notice(): void
    {
        $context = $this->createContext();

        $leaseResponse = $this->postJson(
            '/api/leases',
            $this->validPayload($context)
        );

        $leaseResponse->assertCreated();

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
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ]);

        $this->assertDatabaseHas('leases', [
            'id' => $leaseId,
            'status' => 'active',
            'termination_notice_date' => null,
        ]);
    }

    /**
     * Advance Payment may contain a protected Rent Reserve portion.
     */
    public function test_lease_can_store_contractual_advance_terms(): void
    {
        $context =
            $this->createContext();

        $response =
            $this->postJson(
                '/api/leases',
                $this->validPayload(
                    $context,
                    [
                        'advance_payment_amount' => 60000,

                        'rent_reserve_amount' => 15000,

                        'rent_increment_type' => 'none',

                        'rent_increment_value' => 0,

                        'next_rent_increment_date' => null,
                    ]
                )
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'advance_payment_amount',
                60000
            )
            ->assertJsonPath(
                'rent_reserve_amount',
                15000
            );

        $lease =
            Lease::findOrFail(
                $response->json('id')
            );

        $this->assertSame(
            45000,
            $lease
                ->contractualConsumableAdvanceAmount()
        );
    }

    /**
     * Rent Reserve must form part of the contractual Advance Payment.
     */
    public function test_rent_reserve_cannot_exceed_advance_payment(): void
    {
        $context =
            $this->createContext();

        $this->postJson(
            '/api/leases',
            $this->validPayload(
                $context,
                [
                    'advance_payment_amount' => 10000,

                    'rent_reserve_amount' => 15000,

                    'rent_increment_type' => 'none',

                    'rent_increment_value' => 0,

                    'next_rent_increment_date' => null,
                ]
            )
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'rent_reserve_amount',
            ]);
    }

    /**
     * A future percentage rent increment may be configured.
     */
    public function test_percentage_rent_increment_can_be_configured(): void
    {
        $context =
            $this->createContext();

        $response =
            $this->postJson(
                '/api/leases',
                $this->validPayload(
                    $context,
                    [
                        'advance_payment_amount' => 0,

                        'rent_reserve_amount' => 0,

                        'rent_increment_type' => 'percentage',

                        'rent_increment_value' => 10,

                        'next_rent_increment_date' => '2027-08-01',
                    ]
                )
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'rent_increment_type',
                'percentage'
            )
            ->assertJsonPath(
                'rent_increment_value',
                '10.00'
            );
    }

    /**
     * A configured rent increment requires an effective date.
     */
    public function test_rent_increment_requires_next_increment_date(): void
    {
        $context =
            $this->createContext();

        $this->postJson(
            '/api/leases',
            $this->validPayload(
                $context,
                [
                    'advance_payment_amount' => 0,

                    'rent_reserve_amount' => 0,

                    'rent_increment_type' => 'percentage',

                    'rent_increment_value' => 10,

                    'next_rent_increment_date' => null,
                ]
            )
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'next_rent_increment_date',
            ]);
    }

    /**
     * Creating a backdated Active Lease reconstructs all rent Invoices that
     * should already exist through the current date.
     */
    public function test_backdated_active_lease_generates_historical_invoices(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-08-12 12:00:00')
        );

        try {
            $context =
                $this->createContext();

            $response =
                $this->postJson(
                    '/api/leases',
                    $this->validPayload(
                        $context,
                        [
                            'start_date' => '2026-03-01',

                            'end_date' => '2027-02-28',

                            'rent_amount' => 12000,

                            'payment_frequency' => 'monthly',

                            'due_day' => null,

                            'agent_commission_amount' => 0,
                        ]
                    )
                );

            $response
                ->assertCreated()
                ->assertJsonCount(
                    6,
                    'invoices'
                );

            $leaseId =
                $response->json('id');

            $invoices =
                Invoice::query()
                    ->where(
                        'lease_id',
                        $leaseId
                    )
                    ->orderBy(
                        'period_start'
                    )
                    ->get();

            $this->assertCount(
                6,
                $invoices
            );

            $this->assertSame(
                [
                    '2026-03-01',
                    '2026-04-01',
                    '2026-05-01',
                    '2026-06-01',
                    '2026-07-01',
                    '2026-08-01',
                ],
                $invoices
                    ->map(
                        fn (Invoice $invoice): string => $invoice
                            ->period_start
                            ->toDateString()
                    )
                    ->all()
            );

            $this->assertSame(
                72000,
                $invoices->sum(
                    'total_amount'
                )
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * A current Active Lease creates its current billing period immediately.
     */
    public function test_current_active_lease_generates_current_invoice(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-08-12 12:00:00')
        );

        try {
            $context =
                $this->createContext();

            $response =
                $this->postJson(
                    '/api/leases',
                    $this->validPayload(
                        $context,
                        [
                            'start_date' => '2026-08-01',

                            'agent_commission_amount' => 0,
                        ]
                    )
                );

            $response
                ->assertCreated()
                ->assertJsonCount(
                    1,
                    'invoices'
                );

            $this->assertDatabaseCount(
                'invoices',
                1
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * A future Active Lease has no billing history yet.
     */
    public function test_future_active_lease_does_not_generate_invoice(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-08-12 12:00:00')
        );

        try {
            $context =
                $this->createContext();

            $response =
                $this->postJson(
                    '/api/leases',
                    $this->validPayload(
                        $context,
                        [
                            'start_date' => '2026-09-01',

                            'agent_commission_amount' => 0,
                        ]
                    )
                );

            $response
                ->assertCreated()
                ->assertJsonCount(
                    0,
                    'invoices'
                );

            $this->assertDatabaseCount(
                'invoices',
                0
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Draft Leases remain unbilled until they become operational.
     */
    public function test_draft_lease_does_not_generate_invoices_on_creation(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-08-12 12:00:00')
        );

        try {
            $context =
                $this->createContext();

            $response =
                $this->postJson(
                    '/api/leases',
                    $this->validPayload(
                        $context,
                        [
                            'start_date' => '2026-03-01',

                            'status' => 'draft',

                            'agent_commission_amount' => 0,
                        ]
                    )
                );

            $response
                ->assertCreated()
                ->assertJsonCount(
                    0,
                    'invoices'
                );

            $this->assertDatabaseCount(
                'invoices',
                0
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Creating a backdated active Lease can reconstruct the complete financial
     * opening position when the contractual Advance Payment had already been
     * received historically.
     *
     * The opening workflow must:
     *
     * 1. reconstruct historical rent Invoices;
     * 2. record the historical tenant Payment;
     * 3. protect the contractual Rent Reserve;
     * 4. allocate the remaining received advance FIFO to rent Invoices;
     * 5. create owner rent entitlement only for rent actually settled;
     * 6. charge the Managing Organisation fee against collected rent;
     * 7. charge the one-time Agent commission;
     * 8. preserve any remaining unpaid historical rent.
     */
    public function test_backdated_lease_with_received_advance_initializes_finances(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-08-12 12:00:00')
        );

        try {
            $context = $this->createContext();

            /*
             * Financial initialization requires valid Building ownership.
             *
             * Patrimoine V1 keeps ownership at Building level, so this
             * ownership applies automatically to the Unit selected above.
             */
            $building = $context['unit']
                ->building()
                ->firstOrFail();

            $owner = Party::create([
                'type' => 'person',
                'name' => 'Lease Opening Owner',
                'phone' => '0200000499',
                'email' => 'lease-opening-owner@example.test',
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

            /*
             * Lease begins 1 March 2026.
             *
             * At the test date of 12 August 2026, six monthly rent
             * Invoices should therefore exist:
             *
             * March, April, May, June, July and August.
             *
             * Monthly rent = GHS 12,000
             * Historical billed rent = GHS 72,000.
             *
             * Historical Advance Payment received = GHS 84,000
             * Rent Reserve protected             = GHS 36,000
             * Remaining cash available for rent  = GHS 48,000
             *
             * Therefore exactly four GHS 12,000 Invoices should be
             * settled FIFO.
             */
            $response = $this->postJson(
                '/api/leases',
                $this->validPayload(
                    $context,
                    [
                        'start_date' => '2026-03-01',

                        'end_date' => '2027-02-28',

                        'status' => 'active',

                        'rent_amount' => 12000,

                        'payment_frequency' => 'monthly',

                        'due_day' => null,

                        'vat_rate' => 18,

                        'security_deposit_amount' => 0,

                        'advance_payment_amount' => 84000,

                        'rent_reserve_amount' => 36000,

                        /*
                         * Historical opening-payment information.
                         *
                         * These fields deliberately distinguish contractual
                         * Advance Payment terms from money actually received.
                         */
                        'advance_received' => true,

                        'advance_received_date' => '2026-03-01',

                        'advance_received_method' => 'bank_transfer',

                        'advance_received_reference' => 'OPENING-ADVANCE-001',

                        'management_fee_type' => 'percentage',

                        'management_fee_value' => 12,

                        'agent_commission_amount' => 12000,

                        'rent_increment_type' => 'percentage',

                        'rent_increment_value' => 5,

                        'next_rent_increment_date' => '2027-03-01',
                    ]
                )
            );

            $response->assertCreated();

            $lease = Lease::findOrFail(
                $response->json('id')
            );

            /*
             * -------------------------------------------------------------
             * Historical billing
             * -------------------------------------------------------------
             */

            $invoices = Invoice::query()
                ->where('lease_id', $lease->id)
                ->orderBy('period_start')
                ->get();

            $this->assertCount(
                6,
                $invoices
            );

            $this->assertSame(
                72000,
                (int) $invoices->sum('total_amount')
            );

            /*
             * -------------------------------------------------------------
             * Historical Payment
             * -------------------------------------------------------------
             *
             * One Payment represents the GHS 84,000 actually received.
             */

            $payment = Payment::query()
                ->where('lease_id', $lease->id)
                ->firstOrFail();

            $this->assertSame(
                84000,
                $payment->amount
            );

            $this->assertSame(
                '2026-03-01',
                $payment->payment_date->toDateString()
            );

            $this->assertSame(
                'bank_transfer',
                $payment->payment_method
            );

            $this->assertSame(
                'OPENING-ADVANCE-001',
                $payment->reference
            );

            /*
             * -------------------------------------------------------------
             * Protected Rent Reserve
             * -------------------------------------------------------------
             */

            $reserveAccount = TenantFundAccount::query()
                ->where('lease_id', $lease->id)
                ->where('type', 'rent_reserve')
                ->firstOrFail();

            $this->assertSame(
                36000,
                $reserveAccount->balance()
            );

            $this->assertDatabaseHas(
                'tenant_fund_transactions',
                [
                    'tenant_fund_account_id' => $reserveAccount->id,

                    'payment_id' => $payment->id,

                    'direction' => 'credit',

                    'category' => 'reserve_funding',

                    'amount' => 36000,
                ]
            );

            /*
             * -------------------------------------------------------------
             * FIFO rent allocation
             * -------------------------------------------------------------
             *
             * GHS 84,000 payment
             * - GHS 36,000 protected reserve
             * = GHS 48,000 allocatable to historical rent.
             */

            $this->assertSame(
                48000,
                $payment->allocatedAmount()
            );

            $this->assertSame(
                36000,
                $payment->classifiedFundAmount()
            );

            $this->assertSame(
                0,
                $payment->allocatableAmount()
            );

            $allocations = PaymentAllocation::query()
                ->where('payment_id', $payment->id)
                ->with('invoice')
                ->orderBy('id')
                ->get();

            $this->assertCount(
                4,
                $allocations
            );

            $this->assertSame(
                48000,
                (int) $allocations->sum('amount')
            );

            /*
             * FIFO must settle March through June first.
             */
            $this->assertSame(
                [
                    '2026-03-01',
                    '2026-04-01',
                    '2026-05-01',
                    '2026-06-01',
                ],
                $allocations
                    ->map(
                        fn (PaymentAllocation $allocation): string => $allocation
                            ->invoice
                            ->period_start
                            ->toDateString()
                    )
                    ->all()
            );

            /*
             * First four Invoices are fully paid.
             */
            $this->assertSame(
                [
                    'paid',
                    'paid',
                    'paid',
                    'paid',
                    'issued',
                    'issued',
                ],
                $invoices
                    ->map(
                        fn (Invoice $invoice): string => $invoice
                            ->fresh()
                            ->status
                    )
                    ->all()
            );

            /*
             * Remaining historical rent debt:
             *
             * GHS 72,000 billed
             * - GHS 48,000 settled
             * = GHS 24,000 outstanding.
             */
            $this->assertSame(
                24000,
                $invoices->sum(
                    fn (Invoice $invoice): int => $invoice
                        ->fresh()
                        ->outstandingAmount()
                )
            );

            /*
             * -------------------------------------------------------------
             * Owner accounting
             * -------------------------------------------------------------
             */

            $ownerAccount = OwnerAccount::query()
                ->where(
                    'party_id',
                    $owner->id
                )
                ->firstOrFail();

            /*
             * Actual collected rent entitlement:
             *
             * GHS 48,000.
             */
            $this->assertSame(
                48000,
                (int) OwnerTransaction::query()
                    ->where(
                        'owner_account_id',
                        $ownerAccount->id
                    )
                    ->where(
                        'lease_id',
                        $lease->id
                    )
                    ->where(
                        'direction',
                        'credit'
                    )
                    ->where(
                        'category',
                        'rent_entitlement'
                    )
                    ->sum('amount')
            );

            /*
             * Managing Organisation fee:
             *
             * 12% × GHS 48,000 actually collected
             * = GHS 5,760.
             */
            $this->assertSame(
                5760,
                (int) OwnerTransaction::query()
                    ->where(
                        'owner_account_id',
                        $ownerAccount->id
                    )
                    ->where(
                        'lease_id',
                        $lease->id
                    )
                    ->where(
                        'direction',
                        'debit'
                    )
                    ->where(
                        'category',
                        'management_fee'
                    )
                    ->sum('amount')
            );

            /*
             * One-time Agent commission.
             */
            $this->assertSame(
                12000,
                (int) OwnerTransaction::query()
                    ->where(
                        'owner_account_id',
                        $ownerAccount->id
                    )
                    ->where(
                        'lease_id',
                        $lease->id
                    )
                    ->where(
                        'direction',
                        'debit'
                    )
                    ->where(
                        'category',
                        'agent_commission'
                    )
                    ->sum('amount')
            );

            /*
             * Owner funds currently held:
             *
             * GHS 48,000 rent entitlement
             * - GHS 5,760 Managing Organisation fee
             * - GHS 1,036 VAT on that fee
             * - GHS 12,000 Agent commission
             * = GHS 29,204.
             *
             * The VAT total is 1,036 rather than 1,037 because the fee is
             * charged per collection and each charge rounds its own VAT.
             */
            $this->assertSame(
                1036,
                (int) OwnerTransaction::query()
                    ->where(
                        'owner_account_id',
                        $ownerAccount->id
                    )
                    ->where(
                        'category',
                        'management_fee_vat'
                    )
                    ->sum('amount')
            );

            $this->assertSame(
                29204,
                $ownerAccount->balance()
            );

            /*
             * Finally ensure the protected GHS 36,000 Reserve did NOT
             * accidentally become owner rent entitlement.
             */
            $this->assertSame(
                36000,
                $reserveAccount->fresh()->balance()
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Lease detail exposes actual ledger-derived tenant-fund balances.
     *
     * Contractual Advance / Rent Reserve amounts remain separate from these
     * operational balances.
     */
    public function test_lease_detail_exposes_tenant_fund_balance(): void
    {
        $context =
            $this->createContext();

        $leaseResponse =
            $this->postJson(
                '/api/leases',
                $this->validPayload(
                    $context,
                    [
                        'advance_payment_amount' => 0,

                        'rent_reserve_amount' => 0,
                    ]
                )
            );

        $leaseResponse
            ->assertCreated();

        $leaseId =
            $leaseResponse->json(
                'id'
            );

        /*
         * V1.0.8 provisions all three fund accounts with the Lease, so
         * the consumable advance account already exists.
         */
        $account =
            TenantFundAccount::where('lease_id', $leaseId)
                ->where('type', 'consumable_advance')
                ->firstOrFail();

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,

            'direction' => 'credit',

            'category' => 'advance_funding',

            'amount' => 6000,

            'transaction_date' => '2026-08-01',
        ]);

        TenantFundTransaction::create([
            'tenant_fund_account_id' => $account->id,

            'direction' => 'debit',

            'category' => 'advance_consumption',

            'amount' => 1500,

            'transaction_date' => '2026-08-05',
        ]);

        /*
         * V1.0.8 serializes all three provisioned accounts, so locate the
         * consumable advance by type instead of assuming list position.
         */
        $serialized =
            $this->getJson(
                "/api/leases/{$leaseId}"
            )
                ->assertOk()
                ->assertJsonCount(
                    3,
                    'tenant_fund_accounts'
                )
                ->json('tenant_fund_accounts');

        $advance =
            collect($serialized)
                ->firstWhere('type', 'consumable_advance');

        $this->assertNotNull($advance);

        $this->assertSame(
            4500,
            $advance['balance']
        );

        $this->assertCount(
            2,
            $advance['transactions']
        );
    }
}
