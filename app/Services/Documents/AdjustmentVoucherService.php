<?php

namespace App\Services\Documents;

use App\Models\AdjustmentVoucher;
use App\Models\User;
use Carbon\CarbonInterface;

class AdjustmentVoucherService
{
    public function __construct(
        private readonly AdjustmentVoucherNumberService $numbers,
    ) {}

    public function create(
        string $accountType,
        int $accountId,
        ?string $entityType,
        ?int $entityId,
        string $entityLabel,
        string $accountLabel,
        int $previousBalance,
        int $correctedBalance,
        int $difference,
        string $reason,
        CarbonInterface|string $adjustmentDate,
        ?User $actor,
        string $sourceType,
        int $sourceId,
    ): AdjustmentVoucher {
        return AdjustmentVoucher::query()->create([
            'voucher_number' => $this->numbers->next(),
            'account_type' => $accountType,
            'account_id' => $accountId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_label' => $entityLabel,
            'account_label' => $accountLabel,
            'previous_balance' => $previousBalance,
            'corrected_balance' => $correctedBalance,
            'difference' => $difference,
            'reason' => trim($reason),
            'adjustment_date' => $adjustmentDate,
            'performed_by_user_id' => $actor?->id,
            'performed_by_name' => $actor?->name ?? 'System',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
    }
}
