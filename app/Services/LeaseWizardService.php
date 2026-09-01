<?php

namespace App\Services;

use App\Http\Requests\StoreBuildingRequest;
use App\Http\Requests\StoreLeaseRequest;
use App\Http\Requests\StorePartyRequest;
use App\Http\Requests\StoreUnitRequest;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use App\Services\LeaseTerms\LeaseTermVersionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * V1.0.29: create a whole letting in one submission.
 *
 * The guided wizard collects the property, its owners, the tenant, the
 * agent and the lease terms across ten pages and submits them together.
 * NOTHING is written until that submission arrives, and everything it
 * writes happens in ONE transaction: an operator who abandons the wizard
 * at page seven leaves no half-created tenant behind, and a validation
 * failure on the last page leaves no half-created anything.
 *
 * Every block is validated by the same Form Request the individual form
 * uses. The wizard therefore cannot accept a property, party or lease
 * that the ordinary screens would reject, and cannot drift from them as
 * they change. Because a lease needs identifiers that do not exist until
 * the records above it are created, that validation runs INSIDE the
 * transaction — a rejection simply rolls everything back.
 */
class LeaseWizardService
{
    public function __construct(
        private readonly LeaseInitializationService $initializer,
        private readonly LeaseTermVersionService $termVersions,
        private readonly ActivityLogService $activityLog,
        private readonly BusinessActivitySnapshotService $snapshots,
        private readonly LicensingService $licensing,
    ) {}

    /**
     * Build the property, the parties and the lease, or nothing at all.
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(
        array $payload,
        Request $request
    ): Lease {
        return DB::transaction(
            function () use ($payload, $request): Lease {
                $owners =
                    $this->resolveOwners(
                        $payload['owners'] ?? [],
                        $request
                    );

                $building =
                    $this->resolveBuilding(
                        $payload['building'],
                        $owners,
                        $request
                    );

                $unit =
                    $this->resolveUnit(
                        $payload['unit'],
                        $building,
                        $request
                    );

                $tenant =
                    $this->resolveParty(
                        $payload['tenant'],
                        'tenant',
                        'tenant',
                        $request
                    );

                $agent =
                    $this->resolveAgent(
                        $payload['agent'] ?? null,
                        $request
                    );

                return $this->createLease(
                    $payload['lease'] ?? [],
                    $unit,
                    $tenant,
                    $agent,
                    $request
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Owners and the property
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve every owner block into a Party and its share.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array{party: Party, ownership_percentage: float}>
     */
    private function resolveOwners(
        array $blocks,
        Request $request
    ): array {
        $owners = [];

        foreach ($blocks as $index => $block) {
            $owners[] = [
                'party' => $this->resolveParty(
                    $block,
                    'owner',
                    'owners.'.$index,
                    $request
                ),

                'ownership_percentage' => (float) (
                    $block['ownership_percentage'] ?? 0
                ),
            ];
        }

        return $owners;
    }

    /**
     * Use the chosen building, or create it with its ownership.
     *
     * @param  array<string, mixed>  $block
     * @param  array<int, array{party: Party, ownership_percentage: float}>  $owners
     */
    private function resolveBuilding(
        array $block,
        array $owners,
        Request $request
    ): Building {
        if (! empty($block['id'])) {
            $building =
                Building::query()
                    ->with('ownerships')
                    ->findOrFail(
                        (int) $block['id']
                    );

            /*
             * The wizard skips the owners page when the building already
             * has ownership, so owners arriving for a building that has
             * some would silently contradict what is recorded.
             */
            if ($building->ownerships->isEmpty()) {
                $this->attachOwnership(
                    $building,
                    $owners,
                    $request
                );
            }

            return $building;
        }

        $attributes =
            $this->validateBlock(
                StoreBuildingRequest::class,
                array_merge(
                    $block['attributes'] ?? [],
                    [
                        'owners' => array_map(
                            static fn (array $owner): array => [
                                'party_id' => $owner['party']->id,
                                'ownership_percentage' => $owner['ownership_percentage'],
                            ],
                            $owners
                        ),
                    ]
                ),
                'building.attributes',
                $request
            );

        $ownership = $attributes['owners'];

        unset($attributes['owners']);

        $building = Building::create($attributes);

        foreach ($ownership as $share) {
            $building->ownerships()->create([
                'party_id' => $share['party_id'],
                'ownership_percentage' => $share['ownership_percentage'],
            ]);
        }

        $building->load('ownerships.party');

        $this->activityLog->record(
            action: 'building.created',
            request: $request,
            entityType: 'building',
            entityId: $building->id,
            entityLabel: $building->name,
            snapshot: $this->snapshots->building($building),
            metadata: ['source' => 'lease_wizard'],
        );

        return $building;
    }

    /**
     * Record ownership on an existing building that had none.
     *
     * @param  array<int, array{party: Party, ownership_percentage: float}>  $owners
     */
    private function attachOwnership(
        Building $building,
        array $owners,
        Request $request
    ): void {
        if ($owners === []) {
            throw ValidationException::withMessages([
                'owners' => [
                    __('api.validation.wizard_owners_required'),
                ],
            ]);
        }

        $total = array_sum(
            array_column(
                $owners,
                'ownership_percentage'
            )
        );

        /*
         * The same rule the property form enforces: a building is owned
         * exactly once over.
         */
        if (round($total, 2) !== 100.00) {
            throw ValidationException::withMessages([
                'owners' => [
                    __('api.validation.building_ownership_total'),
                ],
            ]);
        }

        foreach ($owners as $owner) {
            $building->ownerships()->create([
                'party_id' => $owner['party']->id,
                'ownership_percentage' => $owner['ownership_percentage'],
            ]);
        }

        $building->load('ownerships.party');

        $this->activityLog->record(
            action: 'building.updated',
            request: $request,
            entityType: 'building',
            entityId: $building->id,
            entityLabel: $building->name,
            snapshot: $this->snapshots->building($building),
            metadata: ['source' => 'lease_wizard'],
        );
    }

    /**
     * Use the chosen unit, or create it inside the chosen building.
     *
     * @param  array<string, mixed>  $block
     */
    private function resolveUnit(
        array $block,
        Building $building,
        Request $request
    ): Unit {
        if (! empty($block['id'])) {
            $unit = Unit::query()->findOrFail(
                (int) $block['id']
            );

            /*
             * A unit from another building would produce a lease whose
             * property is not the one the operator was shown.
             */
            if ((int) $unit->building_id !== (int) $building->id) {
                throw ValidationException::withMessages([
                    'unit.id' => [
                        __('api.validation.wizard_unit_building'),
                    ],
                ]);
            }

            return $unit;
        }

        $attributes =
            $this->validateBlock(
                StoreUnitRequest::class,
                array_merge(
                    $block['attributes'] ?? [],
                    [
                        'building_id' => $building->id,
                    ]
                ),
                'unit.attributes',
                $request
            );

        $unit = Unit::create($attributes);

        $this->activityLog->record(
            action: 'unit.created',
            request: $request,
            entityType: 'unit',
            entityId: $unit->id,
            entityLabel: $unit->name,
            snapshot: $this->snapshots->unit(
                $unit->load('building')
            ),
            metadata: ['source' => 'lease_wizard'],
        );

        return $unit;
    }

    /*
    |--------------------------------------------------------------------------
    | Parties
    |--------------------------------------------------------------------------
    */

    /**
     * The agent page can be skipped entirely.
     *
     * @param  array<string, mixed>|null  $block
     */
    private function resolveAgent(
        ?array $block,
        Request $request
    ): ?Party {
        if (
            $block === null
            || (
                empty($block['id'])
                && empty($block['attributes'])
            )
        ) {
            return null;
        }

        return $this->resolveParty(
            $block,
            'agent',
            'agent',
            $request
        );
    }

    /**
     * Use the chosen party, or create it, and make sure it carries the
     * role this page assigned it.
     *
     * Choosing an existing party as the tenant IS designating them a
     * tenant, so the role is granted here exactly as ticking the box on
     * the party form would.
     *
     * @param  array<string, mixed>  $block
     */
    private function resolveParty(
        array $block,
        string $role,
        string $errorPrefix,
        Request $request
    ): Party {
        if (! empty($block['id'])) {
            $party = Party::query()->findOrFail(
                (int) $block['id']
            );

            $this->ensureRole(
                $party,
                $role,
                $request
            );

            return $party;
        }

        $this->licensing->assertCanCreateParty();

        $attributes =
            $this->validateBlock(
                StorePartyRequest::class,
                array_merge(
                    $block['attributes'] ?? [],
                    [
                        'roles' => [$role],
                    ]
                ),
                $errorPrefix.'.attributes',
                $request
            );

        $roles = $attributes['roles'] ?? [$role];

        unset($attributes['roles']);

        $party = Party::create($attributes);

        foreach ($roles as $granted) {
            $party->roles()->create([
                'role' => $granted,
            ]);
        }

        $party->load('roles');

        $this->activityLog->record(
            action: 'party.created',
            request: $request,
            entityType: 'party',
            entityId: $party->id,
            entityLabel: $this->snapshots->partyLabel($party),
            snapshot: $this->snapshots->party($party),
            metadata: ['source' => 'lease_wizard'],
        );

        return $party;
    }

    /**
     * Grant a functional role the party does not have yet.
     */
    private function ensureRole(
        Party $party,
        string $role,
        Request $request
    ): void {
        $exists = $party
            ->roles()
            ->where('role', $role)
            ->exists();

        if ($exists) {
            return;
        }

        $party->roles()->create([
            'role' => $role,
        ]);

        $party->load('roles');

        $this->activityLog->record(
            action: 'party.updated',
            request: $request,
            entityType: 'party',
            entityId: $party->id,
            entityLabel: $this->snapshots->partyLabel($party),
            metadata: [
                'source' => 'lease_wizard',
                'role_granted' => $role,
            ],
        );
    }

    /*
    |--------------------------------------------------------------------------
    | The lease itself
    |--------------------------------------------------------------------------
    */

    /**
     * Validate and create the lease exactly as the Lease form does.
     *
     * @param  array<string, mixed>  $block
     */
    private function createLease(
        array $block,
        Unit $unit,
        Party $tenant,
        ?Party $agent,
        Request $request
    ): Lease {
        $validated =
            $this->validateBlock(
                StoreLeaseRequest::class,
                array_merge(
                    $block,
                    [
                        'unit_id' => $unit->id,
                        'tenant_id' => $tenant->id,
                        'agent_id' => $agent?->id,
                    ]
                ),
                'lease',
                $request
            );

        /*
         * Licensing counts active leases, exactly as the ordinary Lease
         * endpoint does. A draft costs nothing.
         */
        if (
            in_array(
                $validated['status'],
                ['active', 'notice'],
                true
            )
        ) {
            $this->licensing->assertCanActivateLease();
        }

        $openingFinancialData =
            Arr::only(
                $validated,
                [
                    'advance_received',
                    'advance_received_date',
                    'advance_received_method',
                    'advance_received_reference',
                    'advance_received_collector',
                    'security_deposit_received_date',
                    'security_deposit_received_method',
                    'security_deposit_received_reference',
                    'security_deposit_received_collector',
                ]
            );

        /*
         * The cashier for a cash advance is always the logged-in user;
         * any client-supplied name is ignored.
         */
        if (
            ($openingFinancialData['advance_received_method'] ?? null)
                === 'cash'
        ) {
            $openingFinancialData['advance_received_collector'] =
                $request->user()->name;
        }

        /*
         * V1.0.43: the same rule for a Security Deposit taken in cash.
         */
        if (
            ($openingFinancialData['security_deposit_received_method'] ?? null)
                === 'cash'
        ) {
            $openingFinancialData['security_deposit_received_collector'] =
                $request->user()->name;
        }

        $lease = Lease::create(
            Arr::except(
                $validated,
                [
                    'advance_received',
                    'advance_received_date',
                    'advance_received_method',
                    'advance_received_reference',
                    'advance_received_collector',
                    'security_deposit_received_date',
                    'security_deposit_received_method',
                    'security_deposit_received_reference',
                    'security_deposit_received_collector',
                ]
            )
        );

        $this->initializer->initialize(
            lease: $lease,
            openingFinancialData: $openingFinancialData
        );

        $this->termVersions->ensureBaseline(
            $lease->refresh()
        );

        $lease = $lease
            ->refresh()
            ->load([
                'unit.building',
                'tenant',
                'agent',
            ]);

        $this->activityLog->record(
            action: 'lease.created',
            request: $request,
            entityType: 'lease',
            entityId: $lease->id,
            entityLabel: $this->snapshots->leaseLabel($lease),
            snapshot: $this->snapshots->lease($lease),
            metadata: ['source' => 'lease_wizard'],
        );

        return $lease;
    }

    /*
    |--------------------------------------------------------------------------
    | Delegated validation
    |--------------------------------------------------------------------------
    */

    /**
     * Validate one block with the Form Request that owns those rules.
     *
     * The request is built around the block's own data so its rules and
     * its cross-field `after()` hooks see exactly what they would see on
     * the individual screen. Failures are re-keyed under the block so the
     * wizard can send the operator back to the page that owns the field.
     *
     * @param  class-string<FormRequest>  $requestClass
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateBlock(
        string $requestClass,
        array $data,
        string $prefix,
        Request $original
    ): array {
        /** @var FormRequest $form */
        $form = $requestClass::create(
            '/'.$prefix,
            'POST',
            $data
        );

        $form->setContainer(app());

        $form->setUserResolver(
            $original->getUserResolver()
        );

        $validator = Validator::make(
            $data,
            $form->rules(),
            method_exists($form, 'messages')
                ? $form->messages()
                : [],
            method_exists($form, 'attributes')
                ? $form->attributes()
                : []
        );

        if (method_exists($form, 'after')) {
            foreach ($form->after() as $hook) {
                $validator->after($hook);
            }
        }

        if ($validator->fails()) {
            $errors = [];

            foreach ($validator->errors()->messages() as $key => $messages) {
                $errors[$prefix.'.'.$key] = $messages;
            }

            throw ValidationException::withMessages($errors);
        }

        return $validator->validated();
    }
}
