<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOwnerPayoutRequest;
use App\Models\OwnerAccount;
use App\Services\OwnerPayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Transactional API controller for owner payouts.
 */
class OwnerPayoutController extends Controller
{
    /**
     * Pay available owner funds and allocate the payout FIFO across
     * economically available owner credits.
     */
    public function store(
        StoreOwnerPayoutRequest $request,
        OwnerAccount $ownerAccount,
        OwnerPayoutService $service
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $payout = $service->create(
                account: $ownerAccount,
                amount: (int) $validated['amount'],
                payoutDate: $validated['payout_date'],
                paymentMethod: $validated['payment_method'],
                reference: $validated['reference'] ?? null,
                notes: $validated['notes'] ?? null
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'payout' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $payout->load([
            'ownerAccount.party',
            'allocations.ownerTransaction',
        ]);

        return response()->json(
            data: [
                'payout' => $payout,
                'allocated_amount' =>
                    $payout->allocatedAmount(),
                'unallocated_amount' =>
                    $payout->unallocatedAmount(),
                'owner_balance' =>
                    $ownerAccount->fresh()->balance(),
            ],
            status: 201
        );
    }
}
