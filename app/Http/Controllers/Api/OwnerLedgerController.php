<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOwnerAdjustmentRequest;
use App\Http\Requests\StoreOwnerDepositRequest;
use App\Models\OwnerAccount;
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
        OwnerLedgerService $service
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $transaction = $service->recordDeposit(
                account: $ownerAccount,
                amount: (int) $validated['amount'],
                transactionDate: $validated['transaction_date'],
                reference: $validated['reference'] ?? null,
                notes: $validated['notes'] ?? null
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
                'transaction' => $transaction,
                'owner_account' => [
                    'id' => $ownerAccount->id,
                    'balance' => $ownerAccount->fresh()->balance(),
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
        OwnerLedgerService $service
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
