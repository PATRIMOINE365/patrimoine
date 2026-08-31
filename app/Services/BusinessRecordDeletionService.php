<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Central V1.0.3 business-record deletion safety rules.
 *
 * Authorization answers "may this User attempt deletion?".
 *
 * This service separately answers "is physical deletion safe for this
 * particular record?" and preserves database/accounting/history integrity.
 *
 * A null return value means deletion succeeded.
 * A string return value is a localized reason why deletion was blocked.
 */
class BusinessRecordDeletionService
{
    /**
     * Delete a Party only when no meaningful historical/business reference
     * would be damaged.
     */
    public function deleteParty(Party $party): ?string
    {
        try {
            return DB::transaction(
                function () use ($party): ?string {
                    $locked =
                        Party::query()
                            ->whereKey($party->getKey())
                            ->lockForUpdate()
                            ->firstOrFail();

                    $blocked = $this->partyBlockedReason($locked);

                    if ($blocked !== null) {
                        return $blocked;
                    }

                    $locked->delete();

                    return null;
                }
            );
        } catch (QueryException) {
            /*
             * Database constraints remain the final authority in case a new
             * reference type is introduced later and this service has not yet
             * learned about it.
             */
            return __(
                'api.deletion.party_referenced'
            );
        }
    }

    /**
     * Delete a Building only when it has no Units or historical financial
     * references.
     *
     * Existing ownership allocation rows may cascade because they are current
     * configuration of an otherwise unused Building, not financial history.
     */
    public function deleteBuilding(Building $building): ?string
    {
        try {
            return DB::transaction(
                function () use ($building): ?string {
                    $locked =
                        Building::query()
                            ->whereKey($building->getKey())
                            ->lockForUpdate()
                            ->firstOrFail();

                    $blocked = $this->buildingBlockedReason($locked);

                    if ($blocked !== null) {
                        return $blocked;
                    }

                    $locked->delete();

                    return null;
                }
            );
        } catch (QueryException) {
            return __(
                'api.deletion.building_referenced'
            );
        }
    }

    /**
     * Delete a Unit only when it has no Lease or financial history.
     */
    public function deleteUnit(Unit $unit): ?string
    {
        try {
            return DB::transaction(
                function () use ($unit): ?string {
                    $locked =
                        Unit::query()
                            ->whereKey($unit->getKey())
                            ->lockForUpdate()
                            ->firstOrFail();

                    $blocked = $this->unitBlockedReason($locked);

                    if ($blocked !== null) {
                        return $blocked;
                    }

                    $locked->delete();

                    return null;
                }
            );
        } catch (QueryException) {
            return __(
                'api.deletion.unit_referenced'
            );
        }
    }

    /**
     * Delete only a genuinely unused draft Lease.
     *
     * Once a Lease has entered its operational lifecycle, termination is the
     * correct business action. Terminated Leases remain historical records.
     */
    public function deleteLease(Lease $lease): ?string
    {
        try {
            return DB::transaction(
                function () use ($lease): ?string {
                    $locked =
                        Lease::query()
                            ->whereKey($lease->getKey())
                            ->lockForUpdate()
                            ->firstOrFail();

                    $blocked = $this->leaseBlockedReason($locked);

                    if ($blocked !== null) {
                        return $blocked;
                    }

                    $locked->delete();

                    return null;
                }
            );
        } catch (QueryException) {
            return __(
                'api.deletion.lease_referenced'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | May this be deleted?
    |--------------------------------------------------------------------------
    |
    | The same rules the delete methods above enforce, asked as a question.
    |
    | They exist because the answer has to be known BEFORE anybody presses
    | anything: a record that can still be deleted offers Delete, and one
    | that cannot offers Archive instead. Finding out by attempting the
    | deletion and reading the refusal would mean the button lied.
    |
    | Read-only and unlocked. The delete methods call them again inside
    | their transaction, holding the row, which is where the answer has to
    | be authoritative.
    |
    */

    /**
     * Why this record cannot be deleted, or null if it can.
     */
    public function blockedReason(object $record): ?string
    {
        return match (true) {
            $record instanceof Party => $this->partyBlockedReason($record),
            $record instanceof Building => $this->buildingBlockedReason($record),
            $record instanceof Unit => $this->unitBlockedReason($record),
            $record instanceof Lease => $this->leaseBlockedReason($record),
            default => null,
        };
    }

    public function isDeletable(object $record): bool
    {
        return $this->blockedReason($record) === null;
    }

    private function partyBlockedReason(Party $party): ?string
    {
        /*
         * Managing Organisation is application identity and must never be
         * removed through generic Party deletion.
         */
        if (
            ApplicationSetting::query()
                ->where('managing_organisation_party_id', $party->id)
                ->exists()
        ) {
            return __('api.deletion.party_managing_organisation');
        }

        /*
         * Agent references are checked deliberately even though the FK uses
         * nullOnDelete(). Deleting the Agent would otherwise erase who
         * historically acted on a Lease.
         */
        if (
            $party->tenantLeases()->exists()
            || $party->agentLeases()->exists()
            || $party->buildingOwnerships()->exists()
            || $party->ownerAccount()->exists()
        ) {
            return __('api.deletion.party_referenced');
        }

        return null;
    }

    private function buildingBlockedReason(Building $building): ?string
    {
        /*
         * Do not allow the Building FK cascade to silently remove Units.
         * Administrators must deliberately deal with each Unit first.
         */
        if ($building->units()->exists()) {
            return __('api.deletion.building_has_units');
        }

        if (
            DB::table('owner_transactions')
                ->where('building_id', $building->id)
                ->exists()
            || DB::table('owner_expenses')
                ->where('building_id', $building->id)
                ->exists()
        ) {
            return __('api.deletion.building_referenced');
        }

        return null;
    }

    private function unitBlockedReason(Unit $unit): ?string
    {
        if (
            $unit->leases()->exists()
            || DB::table('owner_transactions')
                ->where('unit_id', $unit->id)
                ->exists()
            || DB::table('owner_expenses')
                ->where('unit_id', $unit->id)
                ->exists()
        ) {
            return __('api.deletion.unit_referenced');
        }

        return null;
    }

    private function leaseBlockedReason(Lease $lease): ?string
    {
        if ($lease->status !== 'draft') {
            return __('api.deletion.lease_not_draft');
        }

        foreach (
            [
                'invoices',
                'payments',
                'tenant_fund_accounts',
                'security_deposit_deductions',
                'security_deposit_settlements',
                'rent_increments',
                'owner_transactions',
            ] as $table
        ) {
            if (
                DB::table($table)
                    ->where('lease_id', $lease->id)
                    ->exists()
            ) {
                return __('api.deletion.lease_referenced');
            }
        }

        return null;
    }
}
