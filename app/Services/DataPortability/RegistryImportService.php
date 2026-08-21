<?php

namespace App\Services\DataPortability;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * V1.0.7 IDEMPOTENT Registry restore.
 *
 * Re-imports a Registry backup produced by RegistryExportService so an
 * Administrator can recover records after an accidental deletion.
 *
 * Core guarantees:
 *
 * - IDEMPOTENT: re-importing the same file changes nothing. Rows are
 *   matched by their exported primary id first (when that id still points
 *   at the same logical record), then by natural key. Missing rows are
 *   created; existing rows are updated; rows are never duplicated.
 *
 * - Ids are NEVER forced. A missing id means the original record was
 *   deleted; it is recreated fresh with an autoincrement id, and the
 *   old_id => new_id pair is remembered in an id map so foreign keys of
 *   entities imported later in the SAME run (units.building_id,
 *   leases.unit_id / tenant_id / agent_id, building owner party ids)
 *   are remapped to the recreated records.
 *
 * - Existing data is treated as authoritative where it conflicts with the
 *   backup: incoming empty values never blank existing fields, roles are
 *   only ever added, and live ownership percentages are never altered.
 *
 * - Financial history is OUT of scope. The Financial Journal is immutable;
 *   payments, invoices and fund balances are deliberately not restorable
 *   from a spreadsheet. Restoring a Lease here NEVER triggers invoice
 *   generation — invoices are only ever produced by the scheduled
 *   invoice-generation command, which this service does not touch.
 */
class RegistryImportService
{
    /**
     * Lease lifecycle states accepted from a backup file.
     *
     * @var list<string>
     */
    private const LEASE_STATUSES = [
        'draft',
        'active',
        'notice',
        'terminated',
    ];

    /**
     * Party functional roles accepted from a backup file.
     *
     * @var list<string>
     */
    private const PARTY_ROLES = [
        'tenant',
        'owner',
        'agent',
        'managing_organisation',
    ];

    /**
     * Simple updatable columns per entity.
     *
     * Natural-key columns (party identity, building/unit names, lease
     * unit/tenant/start_date) are intentionally absent where matching
     * would make an update ambiguous; derived columns (roles, owners)
     * are handled separately.
     *
     * @var array<string, list<string>>
     */
    private const FIELDS = [
        'parties' => [
            'name',
            'given_names',
            'surname',
            'legal_name',
            'phone',
            'alternate_phone',
            'email',
            'address',
            'contact_person_name',
            'contact_person_phone',
            'contact_person_email',
            'id_number',
            'registration_number',
            'vat_tin',
            'bank_name',
            'bank_account_name',
            'bank_account_number',
            'bank_branch',
            'notes',
        ],

        'buildings' => [
            'description',
            'address',
            'location',
            'latitude',
            'longitude',
            'notes',
        ],

        'units' => [
            'description',
            'is_commercial',
        ],

        'leases' => [
            'end_date',
            'status',
            'termination_notice_date',
            'rent_amount',
            'payment_frequency',
            'due_day',
            'vat_rate',
            'proration_amount',
            'security_deposit_amount',
            'advance_payment_amount',
            'rent_reserve_amount',
            'rent_increment_type',
            'rent_increment_value',
            'next_rent_increment_date',
            'management_fee_type',
            'management_fee_value',
            'agent_commission_amount',
            'notes',
        ],
    ];

    /**
     * old exported id => current database id, per entity, accumulated
     * across import() calls on this service instance.
     *
     * Restoring a full backup must therefore import entities on the SAME
     * service instance in dependency order:
     * parties -> buildings -> units -> leases.
     *
     * @var array<string, array<int, int>>
     */
    private array $idMap = [
        'parties' => [],
        'buildings' => [],
        'units' => [],
        'leases' => [],
    ];

    /**
     * Import one entity set from header-keyed backup rows.
     *
     * @param  list<array<string, string>>  $rows
     * @return array{
     *     created: int,
     *     updated: int,
     *     unchanged: int,
     *     skipped: list<array{row: int, reason: string}>
     * }
     */
    public function import(
        string $entity,
        array $rows,
        bool $dryRun = false
    ): array {
        if (
            ! array_key_exists(
                $entity,
                self::FIELDS
            )
        ) {
            throw new RuntimeException(
                "Unknown Registry import entity [{$entity}]."
            );
        }

        /*
         * One transaction per run. A dry run executes the complete real
         * import — including every match/normalization decision — and then
         * rolls everything back, so its counts are exactly what a real
         * import of the same file would report.
         */
        DB::beginTransaction();

        try {
            $result = match ($entity) {
                'parties' =>
                    $this->importParties($rows),

                'buildings' =>
                    $this->importBuildings($rows),

                'units' =>
                    $this->importUnits($rows),

                'leases' =>
                    $this->importLeases($rows),
            };

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }

            return $result;
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Parties
    |--------------------------------------------------------------------------
    */

    /**
     * @param  list<array<string, string>>  $rows
     * @return array{created: int, updated: int, unchanged: int, skipped: list<array{row: int, reason: string}>}
     */
    private function importParties(
        array $rows
    ): array {
        $result =
            $this->emptyResult();

        foreach ($rows as $index => $row) {
            /*
             * Header is spreadsheet row 1; data rows are numbered from 2 so
             * skip reasons point at the row the Administrator sees.
             */
            $rowNumber =
                $index + 2;

            $type =
                $this->value($row, 'type');

            if (
                ! in_array(
                    $type,
                    ['person', 'organisation', 'association'],
                    true
                )
            ) {
                $this->skip(
                    $result,
                    $rowNumber,
                    "Invalid party type [{$type}]."
                );

                continue;
            }

            if (
                $this->value($row, 'name') === ''
                && $this->value($row, 'surname') === ''
                && $this->value($row, 'legal_name') === ''
            ) {
                $this->skip(
                    $result,
                    $rowNumber,
                    'Party row has no name.'
                );

                continue;
            }

            $party =
                $this->matchParty(
                    $row
                );

            if ($party === null) {
                $party =
                    Party::create(
                        ['type' => $type]
                        + $this->creationAttributes(
                            $row,
                            self::FIELDS['parties']
                        )
                    );

                $this->ensurePartyRoles(
                    $party,
                    $row
                );

                $this->remember(
                    'parties',
                    $row,
                    $party
                );

                $result['created']++;

                continue;
            }

            $this->remember(
                'parties',
                $row,
                $party
            );

            $changed =
                $this->applyFields(
                    $party,
                    $row,
                    self::FIELDS['parties']
                );

            /*
             * Roles are only ever ADDED by a restore. Removing a role the
             * organisation granted after the backup was taken would corrupt
             * live data.
             */
            if (
                $this->ensurePartyRoles(
                    $party,
                    $row
                )
            ) {
                $changed = true;
            }

            $this->count(
                $result,
                $party,
                $changed
            );
        }

        return $result;
    }

    /**
     * Locate an existing Party for one backup row.
     *
     * The exported id wins only while it still points at the same logical
     * Party (its natural key is compatible); otherwise the natural key —
     * email when present, else name + type + phone — decides.
     */
    private function matchParty(
        array $row
    ): ?Party {
        $byId =
            $this->findByExportedId(
                Party::class,
                $row
            );

        if (
            $byId instanceof Party
            && $this->partyKeyCompatible($byId, $row)
        ) {
            return $byId;
        }

        $email =
            $this->value($row, 'email');

        if ($email !== '') {
            return Party::query()
                ->where('email', $email)
                ->orderBy('id')
                ->first();
        }

        $query =
            Party::query()
                ->where(
                    'type',
                    $this->value($row, 'type')
                )
                ->where(
                    'name',
                    $this->value($row, 'name')
                );

        $phone =
            $this->value($row, 'phone');

        if ($phone !== '') {
            $query->where(
                'phone',
                $phone
            );
        }

        return $query
            ->orderBy('id')
            ->first();
    }

    /**
     * Is the record found under the exported id still the same logical
     * Party as the backup row? Guards against ids reused by unrelated
     * records created after a deletion.
     */
    private function partyKeyCompatible(
        Party $party,
        array $row
    ): bool {
        if (
            $party->type
            !== $this->value($row, 'type')
        ) {
            return false;
        }

        $email =
            $this->value($row, 'email');

        if ($email !== '') {
            return strcasecmp(
                (string) $party->email,
                $email
            ) === 0;
        }

        if (
            strcasecmp(
                (string) $party->name,
                $this->value($row, 'name')
            ) !== 0
        ) {
            return false;
        }

        $phone =
            $this->value($row, 'phone');

        return $phone === ''
            || (string) $party->phone === ''
            || (string) $party->phone === $phone;
    }

    /**
     * Ensure every role listed in the backup exists on the Party.
     *
     * Returns whether any role row was actually created.
     */
    private function ensurePartyRoles(
        Party $party,
        array $row
    ): bool {
        $changed = false;

        foreach (
            explode(
                '|',
                $this->value($row, 'roles')
            ) as $role
        ) {
            $role =
                trim($role);

            /*
             * Unknown role values are ignored rather than failing the row:
             * the party data itself is still worth restoring.
             */
            if (
                ! in_array(
                    $role,
                    self::PARTY_ROLES,
                    true
                )
            ) {
                continue;
            }

            $created =
                $party->roles()
                    ->firstOrCreate([
                        'role' => $role,
                    ]);

            if ($created->wasRecentlyCreated) {
                $changed = true;
            }
        }

        return $changed;
    }

    /*
    |--------------------------------------------------------------------------
    | Buildings
    |--------------------------------------------------------------------------
    */

    /**
     * @param  list<array<string, string>>  $rows
     * @return array{created: int, updated: int, unchanged: int, skipped: list<array{row: int, reason: string}>}
     */
    private function importBuildings(
        array $rows
    ): array {
        $result =
            $this->emptyResult();

        foreach ($rows as $index => $row) {
            $rowNumber =
                $index + 2;

            $name =
                $this->value($row, 'name');

            if ($name === '') {
                $this->skip(
                    $result,
                    $rowNumber,
                    'Building row has no name.'
                );

                continue;
            }

            $building =
                $this->matchBuilding(
                    $row
                );

            if ($building === null) {
                $building =
                    Building::create(
                        ['name' => $name]
                        + $this->creationAttributes(
                            $row,
                            self::FIELDS['buildings']
                        )
                    );

                $this->remember(
                    'buildings',
                    $row,
                    $building
                );

                $this->ensureBuildingOwners(
                    $building,
                    $row
                );

                $result['created']++;

                continue;
            }

            $this->remember(
                'buildings',
                $row,
                $building
            );

            $changed =
                $this->applyFields(
                    $building,
                    $row,
                    self::FIELDS['buildings']
                );

            if (
                $this->ensureBuildingOwners(
                    $building,
                    $row
                )
            ) {
                $changed = true;
            }

            $this->count(
                $result,
                $building,
                $changed
            );
        }

        return $result;
    }

    /**
     * Exported id first (while it still names the same Building), then the
     * natural key: the Building name.
     */
    private function matchBuilding(
        array $row
    ): ?Building {
        $byId =
            $this->findByExportedId(
                Building::class,
                $row
            );

        if (
            $byId instanceof Building
            && strcasecmp(
                $byId->name,
                $this->value($row, 'name')
            ) === 0
        ) {
            return $byId;
        }

        return Building::query()
            ->where(
                'name',
                $this->value($row, 'name')
            )
            ->orderBy('id')
            ->first();
    }

    /**
     * Restore ownership allocation from the exported
     * "party_id:percentage|..." column.
     *
     * Live ownership must never be corrupted by a restore:
     *
     * - existing ownership rows and their percentages are NEVER altered
     *   or deleted;
     * - when the Building already has ownership rows, only missing
     *   party pairings are added;
     * - only when the Building has NO ownership rows at all is the full
     *   allocation recreated from the file.
     *
     * Returns whether any ownership row was created.
     */
    private function ensureBuildingOwners(
        Building $building,
        array $row
    ): bool {
        $changed = false;

        foreach (
            explode(
                '|',
                $this->value($row, 'owners')
            ) as $pair
        ) {
            $pair =
                trim($pair);

            if (
                $pair === ''
                || ! str_contains($pair, ':')
            ) {
                continue;
            }

            [$exportedPartyId, $percentage] =
                explode(':', $pair, 2);

            $partyId =
                $this->resolveId(
                    'parties',
                    (int) $exportedPartyId
                );

            /*
             * An owner Party that no longer exists (and was not recreated
             * earlier in this run) cannot be reattached. The pair is
             * skipped rather than failing the Building row — import
             * parties before buildings to restore full ownership.
             */
            if (
                $partyId === null
                || ! Party::query()
                    ->whereKey($partyId)
                    ->exists()
                || ! is_numeric(trim($percentage))
            ) {
                continue;
            }

            $exists =
                $building->ownerships()
                    ->where('party_id', $partyId)
                    ->exists();

            if ($exists) {
                continue;
            }

            $building->ownerships()
                ->create([
                    'party_id' =>
                        $partyId,

                    'ownership_percentage' =>
                        trim($percentage),
                ]);

            $changed = true;
        }

        return $changed;
    }

    /*
    |--------------------------------------------------------------------------
    | Units
    |--------------------------------------------------------------------------
    */

    /**
     * @param  list<array<string, string>>  $rows
     * @return array{created: int, updated: int, unchanged: int, skipped: list<array{row: int, reason: string}>}
     */
    private function importUnits(
        array $rows
    ): array {
        $result =
            $this->emptyResult();

        foreach ($rows as $index => $row) {
            $rowNumber =
                $index + 2;

            $name =
                $this->value($row, 'name');

            if ($name === '') {
                $this->skip(
                    $result,
                    $rowNumber,
                    'Unit row has no name.'
                );

                continue;
            }

            $buildingId =
                $this->resolveId(
                    'buildings',
                    (int) $this->value($row, 'building_id')
                );

            if (
                $buildingId === null
                || ! Building::query()
                    ->whereKey($buildingId)
                    ->exists()
            ) {
                $this->skip(
                    $result,
                    $rowNumber,
                    'Unknown building for unit — import buildings first in this run.'
                );

                continue;
            }

            $unit =
                $this->matchUnit(
                    $row,
                    $buildingId
                );

            if ($unit === null) {
                $unit =
                    Unit::create(
                        [
                            'building_id' => $buildingId,
                            'name' => $name,
                        ]
                        + $this->creationAttributes(
                            $row,
                            self::FIELDS['units']
                        )
                    );

                $this->remember(
                    'units',
                    $row,
                    $unit
                );

                $result['created']++;

                continue;
            }

            $this->remember(
                'units',
                $row,
                $unit
            );

            /*
             * building_id is part of the Unit natural key and is never
             * changed by a restore — a Unit does not move between
             * Buildings via import.
             */
            $changed =
                $this->applyFields(
                    $unit,
                    $row,
                    self::FIELDS['units']
                );

            $this->count(
                $result,
                $unit,
                $changed
            );
        }

        return $result;
    }

    /**
     * Exported id first (while it still names the same Unit), then the
     * natural key: remapped building_id + name.
     */
    private function matchUnit(
        array $row,
        int $buildingId
    ): ?Unit {
        $byId =
            $this->findByExportedId(
                Unit::class,
                $row
            );

        if (
            $byId instanceof Unit
            && $byId->building_id === $buildingId
            && strcasecmp(
                $byId->name,
                $this->value($row, 'name')
            ) === 0
        ) {
            return $byId;
        }

        return Unit::query()
            ->where('building_id', $buildingId)
            ->where(
                'name',
                $this->value($row, 'name')
            )
            ->orderBy('id')
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Leases
    |--------------------------------------------------------------------------
    */

    /**
     * Restore Lease contractual records.
     *
     * REGISTRY RESTORE ONLY: an imported Lease never triggers invoice
     * generation, journal posting or tenant-fund creation. Those belong to
     * the immutable financial layer and are deliberately out of scope.
     *
     * @param  list<array<string, string>>  $rows
     * @return array{created: int, updated: int, unchanged: int, skipped: list<array{row: int, reason: string}>}
     */
    private function importLeases(
        array $rows
    ): array {
        $result =
            $this->emptyResult();

        foreach ($rows as $index => $row) {
            $rowNumber =
                $index + 2;

            $status =
                $this->value($row, 'status');

            if (
                ! in_array(
                    $status,
                    self::LEASE_STATUSES,
                    true
                )
            ) {
                $this->skip(
                    $result,
                    $rowNumber,
                    "Invalid lease status [{$status}]."
                );

                continue;
            }

            if (
                $this->value($row, 'start_date') === ''
                || ! is_numeric($this->value($row, 'rent_amount'))
            ) {
                $this->skip(
                    $result,
                    $rowNumber,
                    'Lease row is missing start_date or rent_amount.'
                );

                continue;
            }

            $unitId =
                $this->resolveId(
                    'units',
                    (int) $this->value($row, 'unit_id')
                );

            if (
                $unitId === null
                || ! Unit::query()
                    ->whereKey($unitId)
                    ->exists()
            ) {
                $this->skip(
                    $result,
                    $rowNumber,
                    'Unknown unit for lease — import units first in this run.'
                );

                continue;
            }

            $tenantId =
                $this->resolveId(
                    'parties',
                    (int) $this->value($row, 'tenant_id')
                );

            if (
                $tenantId === null
                || ! Party::query()
                    ->whereKey($tenantId)
                    ->exists()
            ) {
                $this->skip(
                    $result,
                    $rowNumber,
                    'Unknown tenant for lease — import parties first in this run.'
                );

                continue;
            }

            $lease =
                $this->matchLease(
                    $row,
                    $unitId,
                    $tenantId
                );

            /*
             * One-active-lease-per-unit invariant: a restored ACTIVE or
             * NOTICE Lease must not collide with a DIFFERENT live Lease
             * already occupying the Unit. Restore never evicts a current
             * tenant.
             */
            if (
                in_array(
                    $status,
                    ['active', 'notice'],
                    true
                )
                && Lease::query()
                    ->where('unit_id', $unitId)
                    ->whereIn(
                        'status',
                        ['active', 'notice']
                    )
                    ->when(
                        $lease !== null,
                        fn ($query) =>
                            $query->whereKeyNot(
                                $lease->getKey()
                            )
                    )
                    ->exists()
            ) {
                $this->skip(
                    $result,
                    $rowNumber,
                    'Unit already has a different active or notice lease.'
                );

                continue;
            }

            if ($lease === null) {
                $lease =
                    Lease::create(
                        [
                            'unit_id' =>
                                $unitId,

                            'tenant_id' =>
                                $tenantId,

                            'agent_id' =>
                                $this->resolveAgentId(
                                    $row
                                ),

                            'start_date' =>
                                $this->value($row, 'start_date'),
                        ]
                        + $this->creationAttributes(
                            $row,
                            $this->leaseFieldsWithValidEnums($row)
                        )
                    );

                $this->remember(
                    'leases',
                    $row,
                    $lease
                );

                $result['created']++;

                continue;
            }

            $this->remember(
                'leases',
                $row,
                $lease
            );

            $changed =
                $this->applyFields(
                    $lease,
                    $row,
                    $this->leaseFieldsWithValidEnums($row)
                );

            $this->count(
                $result,
                $lease,
                $changed
            );
        }

        return $result;
    }

    /**
     * Exported id first (while it still names the same Lease), then the
     * natural key: remapped unit_id + tenant_id + start_date.
     */
    private function matchLease(
        array $row,
        int $unitId,
        int $tenantId
    ): ?Lease {
        $startDate =
            $this->value($row, 'start_date');

        $byId =
            $this->findByExportedId(
                Lease::class,
                $row
            );

        if (
            $byId instanceof Lease
            && $byId->unit_id === $unitId
            && $byId->tenant_id === $tenantId
            && $byId->start_date->format('Y-m-d') === $startDate
        ) {
            return $byId;
        }

        return Lease::query()
            ->where('unit_id', $unitId)
            ->where('tenant_id', $tenantId)
            ->whereDate('start_date', $startDate)
            ->orderBy('id')
            ->first();
    }

    /**
     * Remap the optional exported agent id.
     *
     * An Agent that cannot be resolved is dropped (null) rather than
     * failing the Lease row: the contractual record itself is still
     * worth restoring.
     */
    private function resolveAgentId(
        array $row
    ): ?int {
        $exported =
            $this->value($row, 'agent_id');

        if ($exported === '') {
            return null;
        }

        $agentId =
            $this->resolveId(
                'parties',
                (int) $exported
            );

        if (
            $agentId === null
            || ! Party::query()
                ->whereKey($agentId)
                ->exists()
        ) {
            return null;
        }

        return $agentId;
    }

    /**
     * Lease columns whose incoming enum values are valid.
     *
     * Invalid enum values are dropped (left at their current/default
     * value) instead of hitting a database constraint mid-run.
     *
     * @return list<string>
     */
    private function leaseFieldsWithValidEnums(
        array $row
    ): array {
        $valid = [
            'payment_frequency' => [
                'monthly',
                'quarterly',
                'bi_yearly',
                'yearly',
            ],

            'rent_increment_type' => [
                'none',
                'percentage',
                'fixed',
            ],

            'management_fee_type' => [
                'none',
                'percentage',
                'fixed',
            ],
        ];

        return array_values(
            array_filter(
                self::FIELDS['leases'],
                function (string $field) use ($row, $valid): bool {
                    if (
                        ! array_key_exists(
                            $field,
                            $valid
                        )
                    ) {
                        return true;
                    }

                    $incoming =
                        $this->value($row, $field);

                    return $incoming === ''
                        || in_array(
                            $incoming,
                            $valid[$field],
                            true
                        );
                }
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Shared row mechanics
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{created: int, updated: int, unchanged: int, skipped: list<array{row: int, reason: string}>}
     */
    private function emptyResult(): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => [],
        ];
    }

    /**
     * @param  array{created: int, updated: int, unchanged: int, skipped: list<array{row: int, reason: string}>}  $result
     */
    private function skip(
        array &$result,
        int $rowNumber,
        string $reason
    ): void {
        $result['skipped'][] = [
            'row' => $rowNumber,
            'reason' => $reason,
        ];
    }

    /**
     * Persist a matched model when it changed and update the counters.
     *
     * @param  array{created: int, updated: int, unchanged: int, skipped: list<array{row: int, reason: string}>}  $result
     */
    private function count(
        array &$result,
        Model $model,
        bool $changed
    ): void {
        if ($changed) {
            $model->save();

            $result['updated']++;
        } else {
            $result['unchanged']++;
        }
    }

    /**
     * Look up a model by the row's exported primary id.
     */
    private function findByExportedId(
        string $modelClass,
        array $row
    ): ?Model {
        $exported =
            $this->value($row, 'id');

        if (
            $exported === ''
            || ! ctype_digit($exported)
        ) {
            return null;
        }

        return $modelClass::query()
            ->find(
                (int) $exported
            );
    }

    /**
     * Remember where this run put the row's exported id, so foreign keys
     * of entities imported later in the SAME run can be remapped.
     */
    private function remember(
        string $entity,
        array $row,
        Model $model
    ): void {
        $exported =
            $this->value($row, 'id');

        if (
            $exported === ''
            || ! ctype_digit($exported)
        ) {
            return;
        }

        $this->idMap[$entity][(int) $exported] =
            (int) $model->getKey();
    }

    /**
     * Translate an exported foreign id through this run's id map.
     *
     * Ids never seen in this run pass through unchanged — they may still
     * point at live records that were never deleted.
     */
    private function resolveId(
        string $entity,
        int $exportedId
    ): ?int {
        if ($exportedId <= 0) {
            return null;
        }

        return $this->idMap[$entity][$exportedId]
            ?? $exportedId;
    }

    /**
     * Non-empty incoming values as creation attributes.
     *
     * @param  list<string>  $fields
     * @return array<string, string>
     */
    private function creationAttributes(
        array $row,
        array $fields
    ): array {
        $attributes = [];

        foreach ($fields as $field) {
            $incoming =
                $this->value($row, $field);

            if ($incoming !== '') {
                $attributes[$field] =
                    $incoming;
            }
        }

        return $attributes;
    }

    /**
     * Apply incoming values to a matched model.
     *
     * A backup restore is additive: only NON-EMPTY incoming values are
     * applied, and only when they genuinely differ after normalization.
     * Returns whether anything actually changed.
     *
     * @param  list<string>  $fields
     */
    private function applyFields(
        Model $model,
        array $row,
        array $fields
    ): bool {
        $changed = false;

        foreach ($fields as $field) {
            $incoming =
                $this->value($row, $field);

            if ($incoming === '') {
                continue;
            }

            $current =
                $this->normalized(
                    $model->getAttribute($field)
                );

            if (
                $current
                === $this->normalized($incoming)
            ) {
                continue;
            }

            $model->setAttribute(
                $field,
                $incoming
            );

            $changed = true;
        }

        return $changed;
    }

    /**
     * Trimmed string value of one row column.
     */
    private function value(
        array $row,
        string $key
    ): string {
        return trim(
            (string) ($row[$key] ?? '')
        );
    }

    /**
     * Canonical comparison form of a value.
     *
     * Both sides of every comparison pass through here, so cast artifacts
     * ("18.00" vs "18", Carbon vs "2026-01-01", true vs "1") never count
     * as changes — the idempotence guarantee depends on this.
     */
    private function normalized(
        mixed $value
    ): string {
        if ($value === null) {
            return '';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $string =
            trim(
                (string) $value
            );

        if (
            is_numeric($string)
        ) {
            return rtrim(
                rtrim(
                    sprintf(
                        '%.6F',
                        (float) $string
                    ),
                    '0'
                ),
                '.'
            );
        }

        return $string;
    }
}
