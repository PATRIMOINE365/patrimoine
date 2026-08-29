<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\AuthenticatesApiUser;
use Tests\TestCase;

/**
 * V1.0.29 guided lease creation.
 *
 * The wizard's promise is that somebody can set up a whole letting in one
 * sitting without leaving the page, and that walking away costs nothing.
 * These tests hold it to both halves: everything is created together, and
 * a rejection anywhere leaves the database exactly as it was.
 */
class LeaseWizardTest extends TestCase
{
    use AuthenticatesApiUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->authenticateApiUser('administrator');
    }

    /**
     * The lease terms every scenario shares.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function leaseTerms(array $overrides = []): array
    {
        return array_merge(
            [
                'start_date' => '2026-09-01',
                'end_date' => '2027-08-31',
                'status' => 'active',
                'rent_amount' => 100000,
                'payment_frequency' => 'monthly',
                'due_day' => 1,
                'vat_rate' => 0,
                'security_deposit_amount' => 100000,
                'advance_payment_amount' => 0,
                'rent_reserve_amount' => 0,
                'advance_received' => false,
                'rent_increment_type' => 'none',
                'rent_increment_value' => 0,
                'management_fee_type' => 'percentage',
                'management_fee_value' => 10,
                'agent_commission_amount' => 0,
            ],
            $overrides
        );
    }

    /**
     * A complete submission where nothing existed beforehand.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function greenfieldPayload(array $overrides = []): array
    {
        return array_merge(
            [
                'building' => [
                    'attributes' => [
                        'name' => 'Wizard Court',
                        'address' => 'Accra, Ghana',
                    ],
                ],

                'unit' => [
                    'attributes' => [
                        'name' => 'Wizard Unit 1',
                        'is_commercial' => false,
                    ],
                ],

                'owners' => [
                    [
                        'attributes' => [
                            'type' => 'person',
                            'given_names' => 'Ama',
                            'surname' => 'Owner',
                            'phone' => '+233200003001',
                            'phone_country' => 'GH',
                            'email' => 'ama.owner@example.test',
                        ],
                        'ownership_percentage' => 100,
                    ],
                ],

                'tenant' => [
                    'attributes' => [
                        'type' => 'person',
                        'given_names' => 'Kofi',
                        'surname' => 'Tenant',
                        'phone' => '+233200003002',
                        'phone_country' => 'GH',
                        'email' => 'kofi.tenant@example.test',
                    ],
                ],

                'lease' => $this->leaseTerms(),
            ],
            $overrides
        );
    }

    /*
    |--------------------------------------------------------------------------
    | The whole letting in one submission
    |--------------------------------------------------------------------------
    */

    /**
     * Property, owner, unit, tenant and lease are created together.
     */
    public function test_wizard_creates_a_whole_letting(): void
    {
        $response = $this->postJson(
            '/api/lease-wizard',
            $this->greenfieldPayload()
        );

        $response
            ->assertCreated()
            ->assertJsonPath('status', 'active');

        $building = Building::query()
            ->where('name', 'Wizard Court')
            ->firstOrFail();

        $this->assertSame(
            '100.00',
            (string) $building
                ->ownerships()
                ->firstOrFail()
                ->ownership_percentage
        );

        $unit = Unit::query()
            ->where('name', 'Wizard Unit 1')
            ->firstOrFail();

        $this->assertSame(
            $building->id,
            $unit->building_id
        );

        $tenant = Party::query()
            ->where('email', 'kofi.tenant@example.test')
            ->firstOrFail();

        $this->assertTrue(
            $tenant->roles()
                ->where('role', 'tenant')
                ->exists()
        );

        $lease = Lease::query()->firstOrFail();

        $this->assertSame(
            $unit->id,
            $lease->unit_id
        );

        $this->assertSame(
            $tenant->id,
            $lease->tenant_id
        );
    }

    /**
     * An agent supplied on page five carries the commission entered
     * beside them.
     */
    public function test_wizard_creates_an_agent_with_its_commission(): void
    {
        $this
            ->postJson(
                '/api/lease-wizard',
                $this->greenfieldPayload([
                    'agent' => [
                        'attributes' => [
                            'type' => 'person',
                            'given_names' => 'Yaw',
                            'surname' => 'Agent',
                            'phone' => '+233200003003',
                            'phone_country' => 'GH',
                            'email' => 'yaw.agent@example.test',
                        ],
                    ],

                    'lease' => $this->leaseTerms([
                        'agent_commission_amount' => 25000,
                    ]),
                ])
            )
            ->assertCreated();

        $agent = Party::query()
            ->where('email', 'yaw.agent@example.test')
            ->firstOrFail();

        $this->assertTrue(
            $agent->roles()
                ->where('role', 'agent')
                ->exists()
        );

        $lease = Lease::query()->firstOrFail();

        $this->assertSame(
            $agent->id,
            $lease->agent_id
        );

        $this->assertSame(
            25000,
            (int) $lease->agent_commission_amount
        );
    }

    /**
     * The agent page can be skipped entirely.
     */
    public function test_wizard_accepts_a_lease_with_no_agent(): void
    {
        $this
            ->postJson(
                '/api/lease-wizard',
                $this->greenfieldPayload()
            )
            ->assertCreated();

        $this->assertNull(
            Lease::query()->firstOrFail()->agent_id
        );
    }

    /**
     * Existing records are reused rather than duplicated, and an existing
     * party chosen as the tenant is granted the tenant role — choosing
     * them IS designating them.
     */
    public function test_wizard_reuses_existing_records(): void
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Existing Owner',
            'phone' => '+233200003010',
            'phone_country' => 'GH',
            'email' => 'existing.owner@example.test',
        ]);

        $owner->roles()->create(['role' => 'owner']);

        $building = Building::create([
            'name' => 'Existing Court',
        ]);

        $building->ownerships()->create([
            'party_id' => $owner->id,
            'ownership_percentage' => 100,
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Existing Unit',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Existing Tenant',
            'phone' => '+233200003011',
            'phone_country' => 'GH',
            'email' => 'existing.tenant@example.test',
        ]);

        $partiesBefore = Party::query()->count();

        $this
            ->postJson(
                '/api/lease-wizard',
                [
                    'building' => ['id' => $building->id],
                    'unit' => ['id' => $unit->id],
                    'tenant' => ['id' => $tenant->id],
                    'lease' => $this->leaseTerms(),
                ]
            )
            ->assertCreated();

        $this->assertSame(
            $partiesBefore,
            Party::query()->count()
        );

        $this->assertTrue(
            $tenant->roles()
                ->where('role', 'tenant')
                ->exists()
        );
    }

    /**
     * A draft is saved without becoming a live letting.
     */
    public function test_wizard_can_save_a_draft(): void
    {
        $this
            ->postJson(
                '/api/lease-wizard',
                $this->greenfieldPayload([
                    'lease' => $this->leaseTerms([
                        'status' => 'draft',
                    ]),
                ])
            )
            ->assertCreated()
            ->assertJsonPath('status', 'draft');

        $this->assertSame(
            0,
            Lease::query()->firstOrFail()->invoices()->count()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | All of it, or none of it
    |--------------------------------------------------------------------------
    */

    /**
     * A lease rejected on the last page leaves no property, no owner, no
     * unit and no tenant behind.
     */
    public function test_a_rejected_lease_creates_nothing_at_all(): void
    {
        $this
            ->postJson(
                '/api/lease-wizard',
                $this->greenfieldPayload([
                    'lease' => $this->leaseTerms([
                        'rent_amount' => -1,
                    ]),
                ])
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors('lease.rent_amount');

        $this->assertSame(0, Building::query()->count());
        $this->assertSame(0, Unit::query()->count());
        $this->assertSame(0, Lease::query()->count());
        $this->assertSame(0, Party::query()->count());
    }

    /**
     * A party rejected on page four is reported against the page that
     * owns the field, and still leaves nothing behind.
     */
    public function test_a_rejected_party_creates_nothing_at_all(): void
    {
        $payload = $this->greenfieldPayload();

        $payload['tenant']['attributes']['email'] = 'not-an-email';

        $this
            ->postJson(
                '/api/lease-wizard',
                $payload
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors('tenant.attributes.email');

        $this->assertSame(0, Building::query()->count());
        $this->assertSame(0, Party::query()->count());
    }

    /**
     * Ownership that does not total 100% is refused, exactly as the
     * property form refuses it.
     */
    public function test_ownership_must_total_one_hundred(): void
    {
        $payload = $this->greenfieldPayload();

        $payload['owners'][0]['ownership_percentage'] = 60;

        $this
            ->postJson(
                '/api/lease-wizard',
                $payload
            )
            ->assertStatus(422);

        $this->assertSame(0, Party::query()->count());
    }

    /**
     * A unit that belongs to another property can never be leased under
     * the property the operator chose.
     */
    public function test_unit_must_belong_to_the_chosen_property(): void
    {
        $owner = Party::create([
            'type' => 'person',
            'name' => 'Other Owner',
            'phone' => '+233200003020',
            'phone_country' => 'GH',
            'email' => 'other.owner@example.test',
        ]);

        $owner->roles()->create(['role' => 'owner']);

        $chosen = Building::create(['name' => 'Chosen Court']);

        $chosen->ownerships()->create([
            'party_id' => $owner->id,
            'ownership_percentage' => 100,
        ]);

        $other = Building::create(['name' => 'Other Court']);

        $other->ownerships()->create([
            'party_id' => $owner->id,
            'ownership_percentage' => 100,
        ]);

        $foreignUnit = Unit::create([
            'building_id' => $other->id,
            'name' => 'Foreign Unit',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Wizard Tenant',
            'phone' => '+233200003021',
            'phone_country' => 'GH',
            'email' => 'wizard.tenant@example.test',
        ]);

        $this
            ->postJson(
                '/api/lease-wizard',
                [
                    'building' => ['id' => $chosen->id],
                    'unit' => ['id' => $foreignUnit->id],
                    'tenant' => ['id' => $tenant->id],
                    'lease' => $this->leaseTerms(),
                ]
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors('unit.id');

        $this->assertSame(0, Lease::query()->count());
    }

    /**
     * A unit that is already let cannot be let again — the wizard obeys
     * the same rule the lease form does.
     */
    public function test_an_occupied_unit_is_refused(): void
    {
        $this
            ->postJson(
                '/api/lease-wizard',
                $this->greenfieldPayload()
            )
            ->assertCreated();

        $unit = Unit::query()->firstOrFail();
        $building = Building::query()->firstOrFail();

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Second Tenant',
            'phone' => '+233200003030',
            'phone_country' => 'GH',
            'email' => 'second.tenant@example.test',
        ]);

        $this
            ->postJson(
                '/api/lease-wizard',
                [
                    'building' => ['id' => $building->id],
                    'unit' => ['id' => $unit->id],
                    'tenant' => ['id' => $tenant->id],
                    'lease' => $this->leaseTerms(),
                ]
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors('lease.unit_id');

        $this->assertSame(
            1,
            Lease::query()->count()
        );
    }

    /**
     * A property with no recorded owner still needs one before it can be
     * let, even when it already exists.
     */
    public function test_existing_property_without_owners_gains_them(): void
    {
        $building = Building::create([
            'name' => 'Unowned Court',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'name' => 'Unowned Unit',
        ]);

        $tenant = Party::create([
            'type' => 'person',
            'name' => 'Third Tenant',
            'phone' => '+233200003040',
            'phone_country' => 'GH',
            'email' => 'third.tenant@example.test',
        ]);

        $this
            ->postJson(
                '/api/lease-wizard',
                [
                    'building' => ['id' => $building->id],
                    'unit' => ['id' => $unit->id],
                    'tenant' => ['id' => $tenant->id],
                    'owners' => [
                        [
                            'attributes' => [
                                'type' => 'person',
                                'given_names' => 'New',
                                'surname' => 'Owner',
                                'phone' => '+233200003041',
                                'phone_country' => 'GH',
                                'email' => 'new.owner@example.test',
                            ],
                            'ownership_percentage' => 100,
                        ],
                    ],
                    'lease' => $this->leaseTerms(),
                ]
            )
            ->assertCreated();

        $this->assertSame(
            1,
            $building->ownerships()->count()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Who may use it
    |--------------------------------------------------------------------------
    */

    /**
     * The wizard writes registry and lease records, so it is closed to
     * viewers exactly like the forms it replaces.
     */
    public function test_viewers_cannot_use_the_wizard(): void
    {
        $this->authenticateApiUser('viewer');

        $this
            ->postJson(
                '/api/lease-wizard',
                $this->greenfieldPayload()
            )
            ->assertForbidden();

        $this->assertSame(0, Party::query()->count());
    }
}
