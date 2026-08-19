<?php

namespace Tests\Unit\Services\Adjustments;

use App\Services\Adjustments\AdjustmentAccountType;
use App\Services\Adjustments\AdjustmentContext;
use PHPUnit\Framework\TestCase;

class AdjustmentContextTest extends TestCase
{
    public function test_context_produces_stable_snapshot(): void
    {
        $context = new AdjustmentContext(
            accountType: AdjustmentAccountType::RENT_RESERVE,
            entityType: 'tenant_fund_account',
            entityId: 15,
            entityLabel: 'Rent Reserve',
            leaseId: 9,
            leaseLabel: 'Lease #9',
            buildingId: 3,
            buildingLabel: 'Building A',
            unitId: 7,
            unitLabel: 'Unit 2A',
            metadata: [
                'tenant_id' => 4,
                'tenant_label' => 'Example Tenant',
            ],
        );

        $snapshot = $context->snapshot();

        $this->assertSame(
            AdjustmentAccountType::RENT_RESERVE,
            $snapshot['account_type']
        );
        $this->assertSame('tenant_fund_account', $snapshot['entity_type']);
        $this->assertSame(15, $snapshot['entity_id']);
        $this->assertSame(9, $snapshot['lease_id']);
        $this->assertSame(3, $snapshot['building_id']);
        $this->assertSame(7, $snapshot['unit_id']);
        $this->assertSame(
            4,
            $snapshot['metadata']['tenant_id']
        );
    }
}
