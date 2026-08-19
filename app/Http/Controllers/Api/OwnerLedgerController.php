<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOwnerAdjustmentRequest;
use App\Http\Requests\StoreOwnerDepositRequest;
use App\Models\OwnerAccount;
use App\Services\ActivityLogService;
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
     * Record a manual credit or debit adjustment.
     */
    public function adjustment(
        StoreOwnerAdjustmentRequest $request,
        OwnerAccount $ownerAccount,
        OwnerLedgerService $service,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $transaction = $service->recordAdjustment(
                account: $ownerAccount,
                direction: $validated['direction'],
                amount: (int) $validated['amount'],
                transactionDate: $validated['transaction_date'],
                reason: $validated['reason'],
                reference: $validated['reference'] ?? null
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'owner_account' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'owner_adjustment.recorded',
            request: $request,
            entityType: 'owner_transaction',
            entityId: $transaction->id,
            entityLabel: 'Owner adjustment #'.$transaction->id,
            snapshot: $activitySnapshots->ownerTransaction($transaction),
        );

        return response()->json(
            data: [
                'transaction' => $transaction,
                'owner_account' => [
                    'id' => $ownerAccount->id,
                    'balance' => $ownerAccount->fresh()->balance(),
                ],
            ],
            status: 201
        );
    }
}
