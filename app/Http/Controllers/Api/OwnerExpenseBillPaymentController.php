<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OwnerExpenseBill;
use App\Models\OwnerTransaction;
use App\Services\ActivityLogService;
use App\Services\FinancialActivitySnapshotService;
use App\Services\OwnerExpenseBillPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * V1.0.8: pays an owner expense bill and cancels such payments again.
 */
class OwnerExpenseBillPaymentController extends Controller
{
    /**
     * Pay all or part of an expense bill from one owner account side.
     */
    public function store(
        Request $request,
        OwnerExpenseBill $ownerExpenseBill,
        OwnerExpenseBillPaymentService $payments,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots,
    ): JsonResponse {
        $validated = $request->validate([
            'funding_source' => 'required|in:deposit_account,payout_account',
            'amount' => 'required|integer|min:1',
            'transaction_date' => 'required|date',
        ]);

        try {
            $payment = DB::transaction(
                function () use (
                    $request,
                    $validated,
                    $ownerExpenseBill,
                    $payments,
                    $activityLog,
                    $activitySnapshots,
                ): OwnerTransaction {
                    $payment = $payments->pay(
                        bill: $ownerExpenseBill,
                        fundingSource: $validated['funding_source'],
                        amount: (int) $validated['amount'],
                        transactionDate: $validated['transaction_date'],
                    );

                    $activityLog->record(
                        action: 'owner_expense_bill_payment.recorded',
                        request: $request,
                        entityType: 'owner_transaction',
                        entityId: $payment->id,
                        entityLabel: $ownerExpenseBill->bill_number,
                        snapshot: array_merge(
                            $activitySnapshots
                                ->ownerTransaction($payment),
                            [
                                'owner_expense_bill_id' =>
                                    $ownerExpenseBill->id,
                                'bill_number' =>
                                    $ownerExpenseBill->bill_number,
                                'funding_source' =>
                                    $validated['funding_source'],
                            ],
                        ),
                    );

                    return $payment;
                }
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'amount' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $ownerExpenseBill->refresh();

        return response()->json(
            [
                'payment' => [
                    'id' => $payment->id,
                    'amount' => (int) $payment->amount,
                    'funding_source' => $payment->funding_source,
                ],

                'bill' => [
                    'id' => $ownerExpenseBill->id,
                    'payment_status' => $ownerExpenseBill->paymentStatus(),
                    'paid' => $ownerExpenseBill->paidAmount(),
                    'outstanding' => $ownerExpenseBill->outstandingAmount(),
                ],
            ],
            201
        );
    }

    /**
     * Cancel one bill payment, reverting everything it recorded.
     */
    public function cancel(
        Request $request,
        OwnerTransaction $ownerTransaction,
        OwnerExpenseBillPaymentService $payments,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots,
    ): JsonResponse {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $reversal = DB::transaction(
                function () use (
                    $request,
                    $validated,
                    $ownerTransaction,
                    $payments,
                    $activityLog,
                    $activitySnapshots,
                ): OwnerTransaction {
                    $reversal = $payments->cancel(
                        payment: $ownerTransaction,
                        reason: $validated['reason'],
                        actorUserId: $request->user()?->id,
                    );

                    $activityLog->record(
                        action: 'owner_expense_bill_payment.cancelled',
                        request: $request,
                        entityType: 'owner_transaction',
                        entityId: $reversal->id,
                        entityLabel: $reversal->reference
                            ?? 'Bill payment cancellation #'.$reversal->id,
                        snapshot: array_merge(
                            $activitySnapshots
                                ->ownerTransaction($reversal),
                            [
                                'cancelled_payment_id' =>
                                    $ownerTransaction->id,
                                'reason' => trim($validated['reason']),
                            ],
                        ),
                    );

                    return $reversal;
                }
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'reason' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $bill = $reversal->ownerExpenseBill()->first();

        return response()->json([
            'reversal' => [
                'id' => $reversal->id,
                'amount' => (int) $reversal->amount,
            ],

            'bill' => $bill === null
                ? null
                : [
                    'id' => $bill->id,
                    'payment_status' => $bill->paymentStatus(),
                    'paid' => $bill->paidAmount(),
                    'outstanding' => $bill->outstandingAmount(),
                ],
        ]);
    }
}
