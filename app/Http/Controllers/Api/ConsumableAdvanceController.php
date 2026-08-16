<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConsumeAdvanceRequest;
use App\Models\Invoice;
use App\Models\TenantFundAccount;
use App\Services\ConsumableAdvanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use App\Services\ActivityLogService;
use App\Services\FinancialActivitySnapshotService;

/**
 * Transactional API controller for Consumable Advance usage.
 *
 * Consumable Advance differs from Rent Reserve:
 * - it may be consumed during the normal Lease lifecycle;
 * - it does not require termination notice;
 * - once consumed against rent it becomes owner entitlement.
 */
class ConsumableAdvanceController extends Controller
{
    /**
     * Consume tenant advance funds against an outstanding Invoice.
     */
    public function consume(
        ConsumeAdvanceRequest $request,
        TenantFundAccount $tenantFundAccount,
        ConsumableAdvanceService $service,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots
    ): JsonResponse {
        /*
         * Protect this workflow from accidentally receiving another fund
         * account type such as Rent Reserve or Security Deposit.
         */
        if ($tenantFundAccount->type !== 'consumable_advance') {
            throw ValidationException::withMessages([
                'tenant_fund_account' => [
                    __('business.fund_accounts.not_consumable_advance'),
                ],
            ]);
        }

        $validated = $request->validated();

        $invoice = Invoice::findOrFail(
            $validated['invoice_id']
        );

        try {
            $transaction = $service->consume(
                account: $tenantFundAccount,
                invoice: $invoice,
                amount: (int) $validated['amount'],
                transactionDate: $validated['transaction_date']
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'consumable_advance' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $tenantFundAccount->refresh();
        $invoice->refresh();

        $activityLog->record(
            action: 'consumable_advance.consumed',
            request: $request,
            entityType: 'tenant_fund_transaction',
            entityId: $transaction->id,
            entityLabel: 'Consumable Advance transaction #'.$transaction->id,
            snapshot: $activitySnapshots->tenantFundTransaction(
                $transaction
            ),
        );

        return response()->json(
            data: [
                'transaction' => $transaction->load([
                    'account',
                    'invoice',
                ]),

                'consumable_advance' => [
                    'account_id' => $tenantFundAccount->id,
                    'balance' => $tenantFundAccount->balance(),
                ],

                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'status' => $invoice->status,
                    'paid_amount' => $invoice->paidAmount(),
                    'outstanding_amount' =>
                        $invoice->outstandingAmount(),
                ],
            ],
            status: 201
        );
    }
}
