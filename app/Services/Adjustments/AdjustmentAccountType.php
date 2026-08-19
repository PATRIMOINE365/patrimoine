<?php

namespace App\Services\Adjustments;

/**
 * Canonical operational balance accounts supported by the
 * V1.0.5 universal Adjustment engine.
 *
 * This class deliberately describes operational balance semantics.
 * It does not replace the accounting Chart of Accounts.
 */
final class AdjustmentAccountType
{
    public const OWNER_ACCOUNT = 'owner_account';

    public const RENT_RESERVE = 'rent_reserve';

    public const CONSUMABLE_ADVANCE = 'consumable_advance';

    public const SECURITY_DEPOSIT = 'security_deposit';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::OWNER_ACCOUNT,
            self::RENT_RESERVE,
            self::CONSUMABLE_ADVANCE,
            self::SECURITY_DEPOSIT,
        ];
    }

    public static function allowsNegativeBalance(string $type): bool
    {
        return match ($type) {
            self::OWNER_ACCOUNT => true,

            self::RENT_RESERVE,
            self::CONSUMABLE_ADVANCE,
            self::SECURITY_DEPOSIT => false,

            default => false,
        };
    }
}
