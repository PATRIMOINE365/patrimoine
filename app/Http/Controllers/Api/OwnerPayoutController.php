<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOwnerPayoutRequest;
use App\Models\OwnerAccount;
use App\Services\ActivityLogService;
use App\Services\FinancialActivitySnapshotService;
use App\Services\OwnerPayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
        OwnerPayoutService $service,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $payout = DB::transaction(
                function () use (
                    $request,
                    $ownerAccount,
                    $service,
                    $activityLog,
                    $validated,
                ) {
                    $payout =
                        $service->create(
                            account: $ownerAccount,
                            amount: (int) $validated['amount'],
                            payoutDate: $validated['payout_date'],
                            paymentMethod: $validated['payment_method'],
                            reference: $validated['reference'] ?? null,
                            notes: $validated['notes'] ?? null
                        );

                    $payout->load([
                        'ownerAccount.party',
                        'allocations.ownerTransaction',
                    ]);

                    /*
                     * OwnerPayout + payout allocations + OwnerTransaction +
                     * Financial Journal + Activity Log are atomic.
                     */
                    $activityLog->record(
                        action: 'owner_payout.recorded',
                        request: $request,
                        entityType: 'owner_payout',
                        entityId: $payout->id,
                        entityLabel: 'Owner payout #'.$payout->id,
                        snapshot: collect(
                            $payout->getAttributes()
                        )
                            ->except([
                                'created_at',
                                'updated_at',
                            ])
                            ->all(),
                    );

                    return $payout;
                }
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'payout' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        return response()->json(
            data: [
                'payout' =>
                    $payout,

                'allocated_amount' =>
                    $payout->allocatedAmount(),

                'unallocated_amount' =>
                    $payout->unallocatedAmount(),

                'owner_balance' =>
                    $ownerAccount
                        ->fresh()
                        ->balance(),
            ],
            status: 201
        );
    }

}
