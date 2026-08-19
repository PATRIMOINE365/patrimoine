<?php

namespace App\Services\Adjustments;

/**
 * Immutable identifying context for a contextual Adjustment.
 *
 * The universal engine does not use free-form account names.
 * Callers provide stable entity/account identifiers plus presentation
 * labels that can later be frozen into vouchers, Activity Log events
 * and Journal source snapshots.
 */
final readonly class AdjustmentContext
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $accountType,
        public string $entityType,
        public int $entityId,
        public string $entityLabel,
        public ?int $leaseId = null,
        public ?string $leaseLabel = null,
        public ?int $buildingId = null,
        public ?string $buildingLabel = null,
        public ?int $unitId = null,
        public ?string $unitLabel = null,
        public array $metadata = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'account_type' => $this->accountType,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'entity_label' => $this->entityLabel,
            'lease_id' => $this->leaseId,
            'lease_label' => $this->leaseLabel,
            'building_id' => $this->buildingId,
            'building_label' => $this->buildingLabel,
            'unit_id' => $this->unitId,
            'unit_label' => $this->unitLabel,
            'metadata' => $this->metadata,
        ];
    }
}
