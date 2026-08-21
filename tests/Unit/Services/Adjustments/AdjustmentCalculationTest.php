<?php

namespace Tests\Unit\Services\Adjustments;

use App\Services\Adjustments\AdjustmentAccountType;
use App\Services\Adjustments\AdjustmentCalculation;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AdjustmentCalculationTest extends TestCase
{
    public function test_positive_difference_is_calculated_from_correct_balance(): void
    {
        $result = AdjustmentCalculation::make(
            AdjustmentAccountType::RENT_RESERVE,
            1000,
            1500,
        );

        $this->assertSame(1000, $result->previousBalance);
        $this->assertSame(1500, $result->correctedBalance);
        $this->assertSame(500, $result->difference);
        $this->assertSame(500, $result->absoluteDifference());
        $this->assertSame('increase', $result->direction);
        $this->assertTrue($result->changesBalance());
    }

    public function test_negative_difference_is_calculated_from_correct_balance(): void
    {
        $result = AdjustmentCalculation::make(
            AdjustmentAccountType::SECURITY_DEPOSIT,
            1500,
            1000,
        );

        $this->assertSame(-500, $result->difference);
        $this->assertSame(500, $result->absoluteDifference());
        $this->assertSame('decrease', $result->direction);
    }

    public function test_zero_difference_is_explicitly_represented(): void
    {
        $result = AdjustmentCalculation::make(
            AdjustmentAccountType::CONSUMABLE_ADVANCE,
            1000,
            1000,
        );

        $this->assertSame(0, $result->difference);
        $this->assertSame(0, $result->absoluteDifference());
        $this->assertSame('none', $result->direction);
        $this->assertFalse($result->changesBalance());
    }

    public function test_owner_account_may_be_corrected_to_negative_balance(): void
    {
        $result = AdjustmentCalculation::make(
            AdjustmentAccountType::OWNER_ACCOUNT,
            500,
            -250,
        );

        $this->assertSame(-750, $result->difference);
        $this->assertSame(-250, $result->correctedBalance);
        $this->assertTrue($result->allowsNegativeBalance);
    }

    public function test_rent_reserve_cannot_be_corrected_negative(): void
    {
        $this->assertNegativeBalanceIsRejected(
            AdjustmentAccountType::RENT_RESERVE
        );
    }

    public function test_consumable_advance_cannot_be_corrected_negative(): void
    {
        $this->assertNegativeBalanceIsRejected(
            AdjustmentAccountType::CONSUMABLE_ADVANCE
        );
    }

    public function test_security_deposit_cannot_be_corrected_negative(): void
    {
        $this->assertNegativeBalanceIsRejected(
            AdjustmentAccountType::SECURITY_DEPOSIT
        );
    }

    private function assertNegativeBalanceIsRejected(
        string $accountType
    ): void {
        $this->expectException(InvalidArgumentException::class);

        AdjustmentCalculation::make(
            $accountType,
            500,
            -1,
        );
    }

    public function test_unknown_account_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AdjustmentCalculation::make(
            'unknown_account',
            100,
            200,
        );
    }
}
