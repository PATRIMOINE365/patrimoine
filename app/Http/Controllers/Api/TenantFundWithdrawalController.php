<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantFundWithdrawalRequest;
use App\Models\TenantFundAccount;
use App\Services\Accounting\TenantFundWithdrawalJournalService;
use App\Services\ActivityLogService;
use App\Services\Documents\WithdrawalReceiptService;
use App\Services\FinancialActivitySnapshotService;
use App\Services\TenantFundWithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TenantFundWithdrawalController extends Controller
{
    public function store(
        StoreTenantFundWithdrawalRequest $request,
        TenantFundWithdrawalService $withdrawals,
        TenantFundWithdrawalJournalService $journal,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots,
        WithdrawalReceiptService $receipts,
    ): JsonResponse {
        $validated =
            $request->validated();

        try {
            $completed =
                DB::transaction(
                    function () use (
                        $request,
                        $validated,
                        $withdrawals,
                        $journal,
                        $activityLog,
                        $activitySnapshots,
                        $receipts,
                    ): array {
                        $transaction =
                            $withdrawals->withdraw(
                                accountId: (int) $validated[
                                        'tenant_fund_account_id'
                                    ],

                                amount: (int) $validated[
                                        'amount'
                                    ],

                                transactionDate: $validated[
                                        'transaction_date'
                                    ],

                                paymentMethod: $validated[
                                        'payment_method'
                                    ],

                                reference: $validated[
                                        'reference'
                                    ] ?? null,

                                notes: $validated[
                                        'notes'
                                    ] ?? null,
                            );

                        /*
                         * Operational Withdrawal + Journal + Receipt metadata
                         * + Activity Log are one human financial operation.
                         */
                        $journal->post(
                            $transaction
                        );

                        $receipt =
                            $receipts->create(
                                transaction: $transaction,

                                actor: $request->user(),
                            );

                        $transaction->loadMissing(
                            'account'
                        );

                        $snapshot =
                            array_merge(
                                $activitySnapshots
                                    ->tenantFundTransaction(
                                        $transaction
                                    ),
                                [
                                    'withdrawal_receipt_id' => $receipt->id,

                                    'withdrawal_receipt_number' => $receipt->receipt_number,

                                    'payment_method' => $transaction
                                        ->payment_method,
                                ],
                            );

                        $activityLog->record(
                            action: 'tenant_withdrawal.recorded',

                            request: $request,

                            entityType: 'tenant_fund_transaction',

                            entityId: $transaction->id,

                            entityLabel: 'Tenant Withdrawal #'
                                .$transaction->id,

                            snapshot: $snapshot,
                        );

                        return [
                            'transaction' => $transaction,

                            'receipt' => $receipt,
                        ];
                    }
                );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'tenant_fund_account_id' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $transaction =
            $completed['transaction'];

        $receipt =
            $completed['receipt'];

        $account =
            TenantFundAccount::query()
                ->findOrFail(
                    $transaction
                        ->tenant_fund_account_id
                );

        return response()->json(
            [
                'transaction' => $transaction,

                'withdrawal_receipt' => [
                    'id' => $receipt->id,

                    'receipt_number' => $receipt
                        ->receipt_number,

                    'pdf_endpoint' => '/api/withdrawal-receipts/'
                        .$receipt->id
                        .'/pdf',
                ],

                'tenant_fund_account' => [
                    'id' => $account->id,

                    'lease_id' => $account->lease_id,

                    'type' => $account->type,

                    'status' => $account->status,

                    'balance' => $account->balance(),
                ],
            ],
            201
        );
    }
}
