<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Services\ActivityLogService;
use App\Services\FinancialActivitySnapshotService;
use App\Services\InvoiceAccountPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * V1.0.8: pays an Invoice from a tenant fund account and cancels such
 * payments again.
 */
class InvoiceAccountPaymentController extends Controller
{
    /**
     * Pay all or part of an Invoice from a tenant fund account.
     */
    public function store(
        Request $request,
        Invoice $invoice,
        InvoiceAccountPaymentService $payments,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots,
    ): JsonResponse {
        $validated = $request->validate([
            'tenant_fund_account_id' => 'required|integer|exists:tenant_fund_accounts,id',
            'amount' => 'required|integer|min:1',
            'transaction_date' => 'required|date',
        ]);

        try {
            $transaction = DB::transaction(
                function () use (
                    $request,
                    $validated,
                    $invoice,
                    $payments,
                    $activityLog,
                    $activitySnapshots,
                ): TenantFundTransaction {
                    $transaction = $payments->pay(
                        invoice: $invoice,
                        account: TenantFundAccount::query()->findOrFail(
                            (int) $validated['tenant_fund_account_id']
                        ),
                        amount: (int) $validated['amount'],
                        transactionDate: $validated['transaction_date'],
                    );

                    $activityLog->record(
                        action: 'invoice_account_payment.recorded',
                        request: $request,
                        entityType: 'tenant_fund_transaction',
                        entityId: $transaction->id,
                        entityLabel: $invoice->invoice_number,
                        snapshot: array_merge(
                            $activitySnapshots
                                ->tenantFundTransaction($transaction),
                            [
                                'invoice_id' => $invoice->id,
                                'invoice_number' => $invoice->invoice_number,
                            ],
                        ),
                    );

                    return $transaction;
                }
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'amount' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $invoice->refresh();

        return response()->json(
            [
                'payment' => [
                    'id' => $transaction->id,
                    'amount' => (int) $transaction->amount,
                    'category' => $transaction->category,
                ],

                'invoice' => [
                    'id' => $invoice->id,
                    'status' => $invoice->status,
                    'paid' => $invoice->paidAmount(),
                    'outstanding' => $invoice->outstandingAmount(),
                ],
            ],
            201
        );
    }

    /**
     * Cancel one account payment, reverting everything it recorded.
     */
    public function cancel(
        Request $request,
        TenantFundTransaction $tenantFundTransaction,
        InvoiceAccountPaymentService $payments,
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
                    $tenantFundTransaction,
                    $payments,
                    $activityLog,
                    $activitySnapshots,
                ): TenantFundTransaction {
                    $reversal = $payments->cancel(
                        payment: $tenantFundTransaction,
                        reason: $validated['reason'],
                        actorUserId: $request->user()?->id,
                    );

                    $activityLog->record(
                        action: 'invoice_account_payment.cancelled',
                        request: $request,
                        entityType: 'tenant_fund_transaction',
                        entityId: $reversal->id,
                        entityLabel: $reversal->reference
                            ?? 'Payment cancellation #'.$reversal->id,
                        snapshot: array_merge(
                            $activitySnapshots
                                ->tenantFundTransaction($reversal),
                            [
                                'cancelled_payment_id' =>
                                    $tenantFundTransaction->id,
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

        $invoice = $reversal->invoice()->first();

        return response()->json([
            'reversal' => [
                'id' => $reversal->id,
                'amount' => (int) $reversal->amount,
            ],

            'invoice' => $invoice === null
                ? null
                : [
                    'id' => $invoice->id,
                    'status' => $invoice->status,
                    'paid' => $invoice->paidAmount(),
                    'outstanding' => $invoice->outstandingAmount(),
                ],
        ]);
    }
}
