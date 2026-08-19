<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOwnerAdjustmentRequest;
use App\Http\Requests\StoreOwnerDepositRequest;
use App\Models\OwnerAccount;
use App\Services\ActivityLogService;
use App\Services\Adjustments\AdjustmentAccountType;
use App\Services\Adjustments\AdjustmentContext;
use App\Services\Adjustments\ContextualAdjustmentService;
use App\Services\Adjustments\OwnerAccountAdjustmentAdapter;
use App\Services\FinancialActivitySnapshotService;
use App\Services\OwnerLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Transactional API controller for direct owner-ledger operations.
 *
 * This controller handles:
 * - owner deposits;
 * - exceptional manual adjustments.
 */
class OwnerLedgerController extends Controller
{
    /**
     * Record money deposited by an owner.
     */
    public function deposit(
        StoreOwnerDepositRequest $request,
        OwnerAccount $ownerAccount,
        OwnerLedgerService $service,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $transaction = $service->recordDeposit(
                account: $ownerAccount,
                amount: (int) $validated['amount'],
                transactionDate: $validated['transaction_date'],
                paymentMethod: $validated['payment_method'],
                depositPurpose: $validated['deposit_purpose'],
                buildingId: $validated['building_id'] ?? null,
                unitId: $validated['unit_id'] ?? null,
                reference: $validated['reference'] ?? null,
                cashReceiverUser: (
                    $validated['payment_method'] === 'cash'
                        ? $request->user()
                        : null
                ),
                notes: $validated['notes'] ?? null
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'owner_account' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'owner_deposit.recorded',
            request: $request,
            entityType: 'owner_transaction',
            entityId: $transaction->id,
            entityLabel: 'Owner deposit #'.$transaction->id,
            snapshot: $activitySnapshots->ownerTransaction($transaction),
        );

        return response()->json(
            data: [
                'transaction' => $transaction->load([
                    'ownerAccount.party',
                    'building',
                    'unit',
                ]),

                'owner_account' => [
                    'id' => $ownerAccount->id,

                    'balance' => $ownerAccount
                        ->fresh()
                        ->balance(),
                ],
            ],
            status: 201
        );
    }

    /**
     * Correct the Owner Account to the balance that should exist.
     *
     * V1.0.5 deliberately accepts the desired final balance rather than
     * allowing the operator to choose a debit/credit delta.
     */
    public function adjustment(
        StoreOwnerAdjustmentRequest $request,
        OwnerAccount $ownerAccount,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots
    ): JsonResponse {
        $validated = $request->validated();

        $ownerAccount->loadMissing(
            'party'
        );

        $ownerName =
            trim(
                (string) (
                    $ownerAccount->party?->name
                    ?? ''
                )
            );

        $context =
            new AdjustmentContext(
                accountType:
                    AdjustmentAccountType::OWNER_ACCOUNT,

                entityType:
                    'owner_account',

                entityId:
                    $ownerAccount->id,

                entityLabel:
                    $ownerName !== ''
                        ? $ownerName
                        : 'Owner Account #'.$ownerAccount->id,

                metadata: [
                    'owner_id' =>
                        $ownerAccount->party_id,

                    'owner_name' =>
                        $ownerName !== ''
                            ? $ownerName
                            : null,

                    'reference' =>
                        $validated['reference']
                        ?? null,
                ],
            );

        /*
         * Step 3A resolves the existing Owner adapter explicitly.
         *
         * Later Phase 4 steps will register the complete set of account
         * adapters for universal Tenant/Owner contextual resolution.
         */
        $adjustments =
            new ContextualAdjustmentService([
                app(
                    OwnerAccountAdjustmentAdapter::class
                ),
            ]);

        try {
            $result =
                $adjustments->perform(
                    context:
                        $context,

                    correctedBalance:
                        (int) $validated[
                            'corrected_balance'
                        ],

                    reason:
                        $validated['reason'],

                    performedBy:
                        $request->user(),
                );
        } catch (
            \InvalidArgumentException
            | RuntimeException
            $exception
        ) {
            throw ValidationException::withMessages([
                'owner_account' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $transaction =
            \App\Models\OwnerTransaction::query()
                ->findOrFail(
                    $result->transactionId
                );

        /*
         * Preserve the existing one-human-action Activity Log rule.
         *
         * Step 3B will enrich the frozen snapshot with the complete
         * standardized Adjustment context while keeping exactly one event.
         */
        $activityLog->record(
            action:
                'owner_adjustment.recorded',

            request:
                $request,

            entityType:
                'owner_transaction',

            entityId:
                $transaction->id,

            entityLabel:
                'Owner adjustment #'.$transaction->id,

            snapshot:
                array_merge(
                    $activitySnapshots
                        ->ownerTransaction(
                            $transaction
                        ),

                    [
                        'previous_balance' =>
                            $result
                                ->calculation
                                ->previousBalance,

                        'corrected_balance' =>
                            $result
                                ->calculation
                                ->correctedBalance,

                        'difference' =>
                            $result
                                ->calculation
                                ->difference,

                        'reason' =>
                            $result->reason,

                        'owner_account_id' =>
                            $ownerAccount->id,

                        'owner_name' =>
                            $ownerName !== ''
                                ? $ownerName
                                : null,
                    ]
                ),
        );

        return response()->json(
            data: [
                'transaction' =>
                    $transaction,

                'adjustment' => [
                    'previous_balance' =>
                        $result
                            ->calculation
                            ->previousBalance,

                    'corrected_balance' =>
                        $result
                            ->calculation
                            ->correctedBalance,

                    'difference' =>
                        $result
                            ->calculation
                            ->difference,

                    'direction' =>
                        $result
                            ->calculation
                            ->direction,

                    'effective_date' =>
                        $result
                            ->effectiveDate
                            ->toDateString(),

                    'reason' =>
                        $result->reason,
                ],

                'owner_account' => [
                    'id' =>
                        $ownerAccount->id,

                    'balance' =>
                        $ownerAccount
                            ->fresh()
                            ->balance(),
                ],
            ],

            status: 201
        );
    }
}
