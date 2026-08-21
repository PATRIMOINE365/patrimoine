<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantFundDepositRequest;
use App\Models\Payment;
use App\Models\TenantFundTransaction;
use App\Services\ActivityLogService;
use App\Services\FinancialActivitySnapshotService;
use App\Services\TenantFundAllocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TenantFundDepositController extends Controller
{
    public function store(
        StoreTenantFundDepositRequest $request,
        TenantFundAllocationService $allocationService,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots,
    ): JsonResponse {
        $validated = $request->validated();

        [$payment, $transaction] = DB::transaction(
            function () use (
                $request,
                $validated,
                $allocationService,
            ): array {
                $paymentData = [
                    'lease_id' =>
                        (int) $validated['lease_id'],

                    'amount' =>
                        (int) $validated['amount'],

                    'payment_date' =>
                        $validated['transaction_date'],

                    'payment_method' =>
                        $validated['payment_method'],

                    'reference' =>
                        $validated['reference'] ?? null,

                    'notes' =>
                        $validated['notes'] ?? null,
                ];

                if (
                    $paymentData['payment_method']
                    === 'cash'
                ) {
                    $user = $request->user();

                    if ($user === null) {
                        throw new \LogicException(
                            'Authenticated User is required for cash receipt attribution.'
                        );
                    }

                    $paymentData['cash_receiver_user_id'] =
                        $user->id;

                    $paymentData['cash_receiver_name'] =
                        $user->name;
                } else {
                    $paymentData['cash_receiver_user_id'] =
                        null;

                    $paymentData['cash_receiver_name'] =
                        null;
                }

                /*
                 * collector_name is legacy-only.
                 * New V1.0.5 transactions never populate it.
                 */
                $paymentData['collector_name'] = null;

                $payment = Payment::create(
                    $paymentData
                );

                /*
                 * A Deposit is intentionally NOT passed through ordinary
                 * Payment FIFO allocation.
                 *
                 * The complete incoming amount is explicitly classified into
                 * the selected Lease-specific Tenant fund.
                 */
                $transaction =
                    $allocationService->allocate(
                        payment: $payment,
                        fundType:
                            $validated['fund_type'],
                        amount:
                            (int) $validated['amount'],
                        transactionDate:
                            $validated['transaction_date'],
                        reference:
                            $validated['reference'] ?? null,
                        notes:
                            $validated['notes'] ?? null,
                    );

                if (
                    ! $transaction
                    instanceof TenantFundTransaction
                ) {
                    throw new \LogicException(
                        'Tenant Deposit did not create a Tenant fund transaction.'
                    );
                }

                return [
                    $payment->refresh(),
                    $transaction->refresh(),
                ];
            }
        );

        $transaction->load([
            'account.lease.tenant',
            'account.lease.unit.building',
            'payment',
        ]);

        /*
         * One explicit human command produces one meaningful Activity Log
         * event. The automatic fund classification is part of this Deposit
         * command rather than a second human action.
         */
        $activityLog->record(
            action: 'tenant_fund.deposit',
            request: $request,
            entityType: 'tenant_fund_transaction',
            entityId: $transaction->id,
            entityLabel:
                'Tenant fund deposit #'.$transaction->id,
            snapshot: array_merge(
                $activitySnapshots
                    ->tenantFundTransaction(
                        $transaction
                    ),
                [
                    'lease_id' =>
                        $payment->lease_id,

                    'payment_method' =>
                        $payment->payment_method,

                    'cash_receiver_name' =>
                        $payment->cash_receiver_name,
                ]
            ),
        );

        return response()->json(
            [
                'payment' => [
                    'id' =>
                        $payment->id,

                    'lease_id' =>
                        $payment->lease_id,

                    'amount' =>
                        $payment->amount,

                    'payment_date' =>
                        $payment
                            ->payment_date
                            ->toDateString(),

                    'payment_method' =>
                        $payment->payment_method,

                    'reference' =>
                        $payment->reference,

                    'cash_receiver_user_id' =>
                        $payment
                            ->cash_receiver_user_id,

                    'cash_receiver_name' =>
                        $payment
                            ->cash_receiver_name,
                ],

                'transaction' =>
                    $transaction,
            ],
            201
        );
    }
}
