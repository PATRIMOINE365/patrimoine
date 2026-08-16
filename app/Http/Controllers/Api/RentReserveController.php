<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConsumeRentReserveRequest;
use App\Models\Invoice;
use App\Models\TenantFundAccount;
use App\Services\RentReserveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use App\Services\ActivityLogService;
use App\Services\FinancialActivitySnapshotService;

/**
 * Transactional API controller for Rent Reserve consumption.
 *
 * The underlying RentReserveService remains the authority for financial
 * rules. This controller translates service failures into appropriate
 * API validation responses.
 */
class RentReserveController extends Controller
{
    /**
     * Consume Rent Reserve against an outstanding tenant Invoice.
     */
    public function consume(
        ConsumeRentReserveRequest $request,
        TenantFundAccount $tenantFundAccount,
        RentReserveService $service,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots
    ): JsonResponse {
        /*
         * Route-level protection prevents another fund-account type from
         * accidentally being passed to the Rent Reserve workflow.
         */
        if ($tenantFundAccount->type !== 'rent_reserve') {
            throw ValidationException::withMessages([
                'tenant_fund_account' => [
                    __('business.fund_accounts.not_rent_reserve'),
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
            /*
             * These are expected business-rule failures rather than
             * unexpected server faults.
             */
            throw ValidationException::withMessages([
                'rent_reserve' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $tenantFundAccount->refresh();
        $invoice->refresh();

        $activityLog->record(
            action: 'rent_reserve.consumed',
            request: $request,
            entityType: 'tenant_fund_transaction',
            entityId: $transaction->id,
            entityLabel: 'Rent Reserve transaction #'.$transaction->id,
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

                'rent_reserve' => [
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
