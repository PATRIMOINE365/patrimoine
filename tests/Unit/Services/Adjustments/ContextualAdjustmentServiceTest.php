<?php

namespace Tests\Unit\Services\Adjustments;

use App\Services\Adjustments\AdjustmentAccountType;
use App\Services\Adjustments\AdjustmentContext;
use App\Services\Adjustments\ContextualAdjustmentService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ContextualAdjustmentServiceTest extends TestCase
{
    public function test_calculate_uses_desired_final_balance_semantics(): void
    {
        $service = new ContextualAdjustmentService();

        $context = new AdjustmentContext(
            accountType: AdjustmentAccountType::OWNER_ACCOUNT,
            entityType: 'owner_account',
            entityId: 1,
            entityLabel: 'Owner Account',
        );

        $result = $service->calculate(
            context: $context,
            currentBalance: 2500,
            correctedBalance: 3100,
        );

        $this->assertSame(600, $result->difference);
        $this->assertSame(3100, $result->correctedBalance);
    }

    public function test_calculate_enforces_negative_policy(): void
    {
        $service = new ContextualAdjustmentService();

        $context = new AdjustmentContext(
            accountType: AdjustmentAccountType::RENT_RESERVE,
            entityType: 'tenant_fund_account',
            entityId: 1,
            entityLabel: 'Rent Reserve',
        );

        $this->expectException(InvalidArgumentException::class);

        $service->calculate(
            context: $context,
            currentBalance: 500,
            correctedBalance: -100,
        );
    }
}
