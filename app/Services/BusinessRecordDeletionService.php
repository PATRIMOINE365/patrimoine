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

                    /*
                     * Managing Organisation is application identity and must
                     * never be removed through generic Party deletion.
                     */
                    if (
                        ApplicationSetting::query()
                            ->where(
                                'managing_organisation_party_id',
                                $locked->id
                            )
                            ->exists()
                    ) {
                        return __(
                            'api.deletion.party_managing_organisation'
                        );
                    }

                    /*
                     * Agent references are checked deliberately even though
                     * the original FK uses nullOnDelete(). Deleting the Agent
                     * would otherwise erase who historically acted on a Lease.
                     */
                    if (
                        $locked->tenantLeases()->exists()
                        || $locked->agentLeases()->exists()
                        || $locked->buildingOwnerships()->exists()
                        || $locked->ownerAccount()->exists()
                    ) {
                        return __(
                            'api.deletion.party_referenced'
                        );
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

                    /*
                     * Do not allow the Building FK cascade to silently remove
                     * Units. Administrators must deliberately deal with each
                     * Unit first.
                     */
                    if ($locked->units()->exists()) {
                        return __(
                            'api.deletion.building_has_units'
                        );
                    }

                    if (
                        DB::table('owner_transactions')
                            ->where('building_id', $locked->id)
                            ->exists()
                        || DB::table('owner_expenses')
                            ->where('building_id', $locked->id)
                            ->exists()
                    ) {
                        return __(
                            'api.deletion.building_referenced'
                        );
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

                    if (
                        $locked->leases()->exists()
                        || DB::table('owner_transactions')
                            ->where('unit_id', $locked->id)
                            ->exists()
                        || DB::table('owner_expenses')
                            ->where('unit_id', $locked->id)
                            ->exists()
                    ) {
                        return __(
                            'api.deletion.unit_referenced'
                        );
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

                    if ($locked->status !== 'draft') {
                        return __(
                            'api.deletion.lease_not_draft'
                        );
                    }

                    if (
                        DB::table('invoices')
                            ->where('lease_id', $locked->id)
                            ->exists()
                        || DB::table('payments')
                            ->where('lease_id', $locked->id)
                            ->exists()
                        || DB::table('tenant_fund_accounts')
                            ->where('lease_id', $locked->id)
                            ->exists()
                        || DB::table('security_deposit_deductions')
                            ->where('lease_id', $locked->id)
                            ->exists()
                        || DB::table('security_deposit_settlements')
                            ->where('lease_id', $locked->id)
                            ->exists()
                        || DB::table('rent_increments')
                            ->where('lease_id', $locked->id)
                            ->exists()
                        || DB::table('owner_transactions')
                            ->where('lease_id', $locked->id)
                            ->exists()
                    ) {
                        return __(
                            'api.deletion.lease_referenced'
                        );
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
}
