<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOwnerAdjustmentRequest;
use App\Http\Requests\StoreOwnerDepositRequest;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Services\ActivityLogService;
use App\Services\Adjustments\AdjustmentAccountType;
use App\Services\Adjustments\AdjustmentContext;
use App\Services\Adjustments\ContextualAdjustmentService;
use App\Services\Adjustments\OwnerAccountAdjustmentAdapter;
use App\Services\FinancialActivitySnapshotService;
use App\Services\OwnerLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
            $transaction = DB::transaction(
                function () use (
                    $request,
                    $ownerAccount,
                    $service,
                    $activityLog,
                    $activitySnapshots,
                    $validated,
                ): OwnerTransaction {
                    $transaction =
                        $service->recordDeposit(
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

                    /*
                     * OwnerTransaction + Financial Journal + Activity Log
                     * form one human financial action.
                     */
                    $activityLog->record(
                        action: 'owner_deposit.recorded',
                        request: $request,
                        entityType: 'owner_transaction',
                        entityId: $transaction->id,
                        entityLabel: 'Owner deposit #'.$transaction->id,
                        snapshot:
                            $activitySnapshots
                                ->ownerTransaction(
                                    $transaction
                                ),
                    );

                    return $transaction;
                }
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'owner_account' => [
                    $exception->getMessage(),
                ],
            ]);
        }

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
        $validated =
            $request->validated();

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
                accountType: AdjustmentAccountType::OWNER_ACCOUNT,

                entityType: 'owner_account',

                entityId: $ownerAccount->id,

                entityLabel: $ownerName !== ''
                        ? $ownerName
                        : 'Owner Account #'.$ownerAccount->id,

                metadata: [
                    'owner_id' => $ownerAccount->party_id,

                    'owner_name' => $ownerName !== ''
                            ? $ownerName
                            : null,

                    'reference' => $validated['reference']
                        ?? null,
                ],
            );

        $adjustments =
            new ContextualAdjustmentService([
                app(
                    OwnerAccountAdjustmentAdapter::class
                ),
            ]);

        /*
         * The complete human Adjustment is one atomic operation:
         *
         * - OwnerTransaction;
         * - Financial Journal;
         * - Adjustment Voucher metadata;
         * - Activity Log.
         *
         * ContextualAdjustmentService supplies the nested financial
         * transaction. This outer transaction additionally includes the
         * Activity Log event.
         */
        try {
            $completed =
                DB::transaction(
                    function () use (
                        $adjustments,
                        $context,
                        $validated,
                        $request,
                        $activityLog,
                        $activitySnapshots,
                        $ownerAccount,
                        $ownerName,
                    ): array {
                        $result =
                            $adjustments->perform(
                                context: $context,

                                correctedBalance: (int) $validated[
                                        'corrected_balance'
                                    ],

                                reason: $validated['reason'],

                                performedBy: $request->user(),
                            );

                        $transaction =
                            OwnerTransaction::query()
                                ->findOrFail(
                                    $result->transactionId
                                );

                        $snapshot =
                            array_merge(
                                $activitySnapshots
                                    ->ownerTransaction(
                                        $transaction
                                    ),
                                [
                                    'previous_balance' => $result
                                        ->calculation
                                        ->previousBalance,

                                    'corrected_balance' => $result
                                        ->calculation
                                        ->correctedBalance,

                                    'difference' => $result
                                        ->calculation
                                        ->difference,

                                    'reason' => $result->reason,

                                    'owner_account_id' => $ownerAccount->id,

                                    'owner_name' => $ownerName !== ''
                                            ? $ownerName
                                            : null,

                                    'adjustment_voucher_id' => $result
                                        ->transactionSnapshot[
                                                'adjustment_voucher_id'
                                            ],

                                    'adjustment_voucher_number' => $result
                                        ->transactionSnapshot[
                                                'adjustment_voucher_number'
                                            ],
                                ],
                            );

                        $activityLog->record(
                            action: 'owner_adjustment.recorded',

                            request: $request,

                            entityType: 'owner_transaction',

                            entityId: $transaction->id,

                            entityLabel: 'Owner adjustment #'.$transaction->id,

                            snapshot: $snapshot,
                        );

                        return [
                            'result' => $result,

                            'transaction' => $transaction,
                        ];
                    }
                );
        } catch (
            \InvalidArgumentException
            |RuntimeException
            $exception
        ) {
            throw ValidationException::withMessages([
                'owner_account' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $result =
            $completed['result'];

        $transaction =
            $completed['transaction'];

        $voucherId =
            $result
                ->transactionSnapshot[
                    'adjustment_voucher_id'
                ];

        return response()->json(
            data: [
                'transaction' => $transaction,

                'adjustment' => [
                    'previous_balance' => $result
                        ->calculation
                        ->previousBalance,

                    'corrected_balance' => $result
                        ->calculation
                        ->correctedBalance,

                    'difference' => $result
                        ->calculation
                        ->difference,

                    'direction' => $result
                        ->calculation
                        ->direction,

                    'effective_date' => $result
                        ->effectiveDate
                        ->toDateString(),

                    'reason' => $result->reason,
                ],

                'adjustment_voucher' => [
                    'id' => $voucherId,

                    'voucher_number' => $result
                        ->transactionSnapshot[
                                'adjustment_voucher_number'
                            ],

                    'pdf_endpoint' => '/api/adjustment-vouchers/'
                        .$voucherId
                        .'/pdf',
                ],

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
}
