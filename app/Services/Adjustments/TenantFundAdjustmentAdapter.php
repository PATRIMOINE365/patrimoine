<?php

namespace App\Services\Adjustments;

use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Services\Accounting\TenantFundAdjustmentJournalService;
use App\Services\Documents\AdjustmentVoucherService;
use RuntimeException;

/**
 * V1.0.5 Adjustment adapter for Lease-specific Tenant fund accounts.
 *
 * Supported accounts:
 * - Rent Reserve;
 * - Consumable Advance;
 * - Security Deposit.
 *
 * Credits increase Tenant fund balances.
 * Debits decrease Tenant fund balances.
 *
 * None of these account types may become negative.
 */
final class TenantFundAdjustmentAdapter implements AdjustmentAccountAdapter
{
    public function __construct(
        private readonly TenantFundAdjustmentJournalService $journal,
        private readonly AdjustmentVoucherService $vouchers,
    ) {}

    public function supports(
        AdjustmentContext $context
    ): bool {
        return
            in_array(
                $context->accountType,
                [
                    AdjustmentAccountType::RENT_RESERVE,
                    AdjustmentAccountType::CONSUMABLE_ADVANCE,
                    AdjustmentAccountType::SECURITY_DEPOSIT,
                ],
                true
            )
            && $context->entityType
                === 'tenant_fund_account';
    }

    public function currentBalance(
        AdjustmentContext $context
    ): int {
        $account =
            TenantFundAccount::query()
                ->lockForUpdate()
                ->find(
                    $context->entityId
                );

        $this->assertUsableAccount(
            $account,
            $context
        );

        return $account->balance();
    }

    public function apply(
        AdjustmentCommand $command,
        AdjustmentCalculation $calculation,
    ): AdjustmentResult {
        if (! $this->supports($command->context)) {
            throw new RuntimeException(
                'Tenant Fund Adjustment adapter received an unsupported context.'
            );
        }

        $account =
            TenantFundAccount::query()
                ->lockForUpdate()
                ->find(
                    $command->context->entityId
                );

        $this->assertUsableAccount(
            $account,
            $command->context
        );

        /*
         * No accounting transaction should be manufactured if the requested
         * corrected balance already equals the authoritative current balance.
         */
        if (! $calculation->changesBalance()) {
            throw new RuntimeException(
                'The corrected balance is already the current Tenant fund balance.'
            );
        }

        /*
         * AdjustmentCalculation already enforces the canonical negative
         * balance policy. Retain this explicit account-boundary assertion as
         * defence in depth because Tenant-held funds may never be overdrawn
         * by a normal Adjustment.
         */
        if ($calculation->correctedBalance < 0) {
            throw new RuntimeException(
                'Tenant fund balances cannot be adjusted below zero.'
            );
        }

        $direction =
            $calculation->difference > 0
                ? 'credit'
                : 'debit';

        $transaction =
            TenantFundTransaction::create([
                'tenant_fund_account_id' => $account->id,

                'direction' => $direction,

                'category' => 'adjustment',

                'amount' => $calculation->absoluteDifference(),

                'transaction_date' => $command
                    ->effectiveDate
                    ->toDateString(),

                'reference' => $command
                    ->context
                    ->metadata['reference']
                    ?? null,

                /*
                 * Preserve the mandatory human reason on the operational
                 * ledger as well as in Activity Log and Financial Journal.
                 */
                'notes' => $command->reason,
            ]);

        /*
         * ContextualAdjustmentService owns the surrounding database
         * transaction. Journal failure therefore rolls the operational
         * TenantFundTransaction back.
         */
        $this->journal->post(
            transaction: $transaction,

            previousBalance: $calculation->previousBalance,

            correctedBalance: $calculation->correctedBalance,

            difference: $calculation->difference,

            reason: $command->reason,

            actor: $command->performedBy,

            context: [
                'tenant_id' => $command
                    ->context
                    ->metadata['tenant_id']
                    ?? null,

                'tenant_name' => $command
                    ->context
                    ->metadata['tenant_name']
                    ?? null,

                'lease_label' => $command->context->leaseLabel,

                'building_id' => $command->context->buildingId,

                'building_label' => $command->context->buildingLabel,

                'unit_id' => $command->context->unitId,

                'unit_label' => $command->context->unitLabel,

                'entity_label' => $command->context->entityLabel,
            ],
        );

        $accountLabel =
            match ($account->type) {
                'rent_reserve' => 'Rent Reserve',

                'consumable_advance' => 'Consumable Advance',

                'security_deposit' => 'Security Deposit',

                default => 'Tenant Fund',
            };

        /*
         * Freeze enough human-readable context for the Voucher to remain
         * understandable independently of later Party/property renames.
         */
        $entityParts =
            array_filter(
                [
                    $command->context
                        ->metadata['tenant_name']
                    ?? null,

                    $command->context->leaseLabel,

                    $command->context->buildingLabel,

                    $command->context->unitLabel,
                ],
                static fn ($value): bool => is_string($value)
                    && trim($value) !== ''
            );

        $voucher =
            $this->vouchers->create(
                accountType: $account->type,

                accountId: $account->id,

                entityType: 'tenant',

                entityId: $command->context
                    ->metadata['tenant_id']
                    ?? null,

                entityLabel: implode(
                    ' — ',
                    $entityParts
                ),

                accountLabel: $accountLabel,

                previousBalance: $calculation->previousBalance,

                correctedBalance: $calculation->correctedBalance,

                difference: $calculation->difference,

                reason: $command->reason,

                adjustmentDate: $command->effectiveDate,

                actor: $command->performedBy,

                sourceType: TenantFundTransaction::class,

                sourceId: $transaction->id,
            );

        return new AdjustmentResult(
            context: $command->context,

            calculation: $calculation,

            effectiveDate: $command->effectiveDate,

            reason: $command->reason,

            performedByUserId: $command->performedBy->id,

            transactionType: 'tenant_adjustment',

            transactionId: $transaction->id,

            transactionSnapshot: [
                'tenant_fund_transaction_id' => $transaction->id,

                'tenant_fund_account_id' => $account->id,

                'fund_type' => $account->type,

                'lease_id' => $account->lease_id,

                'previous_balance' => $calculation->previousBalance,

                'corrected_balance' => $calculation->correctedBalance,

                'difference' => $calculation->difference,

                'direction' => $transaction->direction,

                'amount' => $transaction->amount,

                'transaction_date' => $transaction
                    ->transaction_date
                    ->toDateString(),

                'reason' => $command->reason,

                'reference' => $transaction->reference,

                'adjustment_voucher_id' => $voucher->id,

                'adjustment_voucher_number' => $voucher->voucher_number,
            ],
        );
    }

    private function assertUsableAccount(
        ?TenantFundAccount $account,
        AdjustmentContext $context,
    ): void {
        if ($account === null) {
            throw new RuntimeException(
                'Tenant fund account does not exist.'
            );
        }

        if ($account->status !== 'active') {
            throw new RuntimeException(
                'The selected Tenant fund account is closed.'
            );
        }

        if ($account->type !== $context->accountType) {
            throw new RuntimeException(
                'Tenant fund account type does not match the Adjustment context.'
            );
        }
    }
}
