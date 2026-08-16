<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Party;
use App\Models\Unit;

/**
 * Build stable, meaningful Activity Log representations of business records.
 *
 * Related records that form part of one human-maintained business object,
 * such as Party roles and Building ownership allocation, are represented
 * inside the parent snapshot instead of being logged as separate events.
 */
class BusinessActivitySnapshotService
{
    /**
     * @return array<string, mixed>
     */
    public function party(Party $party): array
    {
        $party->loadMissing('roles');

        return [
            'type' => $party->type,
            'name' => $party->name,
            'legal_name' => $party->legal_name,
            'phone' => $party->phone,
            'alternate_phone' => $party->alternate_phone,
            'email' => $party->email,
            'address' => $party->address,
            'contact_person_name' => $party->contact_person_name,
            'contact_person_phone' => $party->contact_person_phone,
            'contact_person_email' => $party->contact_person_email,
            'id_number' => $party->id_number,
            'registration_number' => $party->registration_number,
            'vat_tin' => $party->vat_tin,
            'bank_name' => $party->bank_name,
            'bank_account_name' => $party->bank_account_name,
            'bank_account_number' => $party->bank_account_number,
            'bank_branch' => $party->bank_branch,
            'notes' => $party->notes,
            'roles' => $party->roles
                ->pluck('role')
                ->sort()
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function building(Building $building): array
    {
        $building->loadMissing('ownerships.party');

        $owners = $building->ownerships
            ->map(
                static fn ($ownership): array => [
                    'party_id' => $ownership->party_id,
                    'party_name' => $ownership->party?->name
                        ?? $ownership->party?->legal_name,
                    'ownership_percentage' => (string) $ownership->ownership_percentage,
                ]
            )
            ->sortBy('party_id')
            ->values()
            ->all();

        return [
            'name' => $building->name,
            'description' => $building->description,
            'address' => $building->address,
            'location' => $building->location,
            'latitude' => $building->latitude,
            'longitude' => $building->longitude,
            'notes' => $building->notes,
            'owners' => $owners,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function unit(Unit $unit): array
    {
        $unit->loadMissing('building');

        return [
            'building_id' => $unit->building_id,
            'building_name' => $unit->building?->name,
            'name' => $unit->name,
            'description' => $unit->description,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function lease(Lease $lease): array
    {
        $lease->loadMissing([
            'unit.building',
            'tenant',
            'agent',
        ]);

        return [
            'unit_id' => $lease->unit_id,
            'unit_name' => $lease->unit?->name,
            'building_id' => $lease->unit?->building_id,
            'building_name' => $lease->unit?->building?->name,
            'tenant_id' => $lease->tenant_id,
            'tenant_name' => $lease->tenant?->name
                ?? $lease->tenant?->legal_name,
            'agent_id' => $lease->agent_id,
            'agent_name' => $lease->agent?->name
                ?? $lease->agent?->legal_name,
            'start_date' => $lease->start_date?->toDateString(),
            'end_date' => $lease->end_date?->toDateString(),
            'status' => $lease->status,
            'termination_notice_date' => $lease->termination_notice_date?->toDateString(),
            'rent_amount' => $lease->rent_amount,
            'payment_frequency' => $lease->payment_frequency,
            'due_day' => $lease->due_day,
            'vat_rate' => $lease->vat_rate,
            'proration_amount' => $lease->proration_amount,
            'security_deposit_amount' => $lease->security_deposit_amount,
            'advance_payment_amount' => $lease->advance_payment_amount,
            'rent_reserve_amount' => $lease->rent_reserve_amount,
            'rent_increment_type' => $lease->rent_increment_type,
            'rent_increment_value' => $lease->rent_increment_value,
            'next_rent_increment_date' => $lease->next_rent_increment_date?->toDateString(),
            'management_fee_type' => $lease->management_fee_type,
            'management_fee_value' => $lease->management_fee_value,
            'agent_commission_amount' => $lease->agent_commission_amount,
            'notes' => $lease->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function managingOrganisation(
        ApplicationSetting $settings
    ): array {
        $settings->loadMissing(
            'managingOrganisation.roles'
        );

        $party = $settings->managingOrganisation;

        return array_merge(
            $party === null
                ? []
                : $this->party($party),
            [
                'party_id' => $settings->managing_organisation_party_id,
                'default_vat_rate' => $settings->default_vat_rate,
                'language' => $settings->language,
                'currency' => $settings->currency,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{
     *     0: array<string, mixed>,
     *     1: array<string, mixed>
     * }
     */
    public function changes(
        array $before,
        array $after
    ): array {
        $beforeChanges = [];
        $afterChanges = [];

        $keys = array_unique(
            array_merge(
                array_keys($before),
                array_keys($after)
            )
        );

        foreach ($keys as $key) {
            $beforeValue = $before[$key] ?? null;
            $afterValue = $after[$key] ?? null;

            if ($beforeValue === $afterValue) {
                continue;
            }

            $beforeChanges[$key] = $beforeValue;
            $afterChanges[$key] = $afterValue;
        }

        return [
            $beforeChanges,
            $afterChanges,
        ];
    }

    public function partyLabel(Party $party): string
    {
        return (string) (
            $party->name
            ?? $party->legal_name
            ?? "Party #{$party->id}"
        );
    }

    public function leaseLabel(Lease $lease): string
    {
        $lease->loadMissing([
            'unit',
            'tenant',
        ]);

        $tenant =
            $lease->tenant?->name
            ?? $lease->tenant?->legal_name
            ?? "Tenant #{$lease->tenant_id}";

        $unit =
            $lease->unit?->name
            ?? "Unit #{$lease->unit_id}";

        return "{$tenant} — {$unit}";
    }
}
