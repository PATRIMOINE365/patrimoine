<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantFundTransferRequest;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use App\Services\ActivityLogService;
use App\Services\Documents\TenantFundTransferVoucherDocumentService;
use App\Services\FinancialActivitySnapshotService;
use App\Services\TenantFundTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Transactional API controller for Tenant fund Transfers.
 *
 * A Transfer moves held tenant money between two fund accounts of the
 * SAME tenant, possibly across that tenant's Leases. The underlying
 * TenantFundTransferService remains the authority for financial rules.
 * This controller translates service failures into appropriate API
 * validation responses.
 */
class TenantFundTransferController extends Controller
{
    /**
     * Record a Transfer between two Tenant fund accounts.
     */
    public function store(
        StoreTenantFundTransferRequest $request,
        TenantFundTransferService $transfers,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots,
    ): JsonResponse {
        $validated =
            $request->validated();

        $source =
            TenantFundAccount::query()
                ->findOrFail(
                    (int) $validated[
                        'source_account_id'
                    ]
                );

        $destination =
            TenantFundAccount::query()
                ->findOrFail(
                    (int) $validated[
                        'destination_account_id'
                    ]
                );

        /*
         * The outer controller transaction deliberately includes both the
         * Transfer service and Activity Log creation.
         *
         * TenantFundTransferService uses its own nested transaction for
         * account locking + operational ledger + Journal posting. Laravel's
         * outer transaction ensures an Activity Log failure also prevents a
         * partially committed Tenant fund Transfer.
         */
        try {
            $completed =
                DB::transaction(
                    function () use (
                        $request,
                        $validated,
                        $transfers,
                        $activityLog,
                        $activitySnapshots,
                        $source,
                        $destination,
                    ): array {
                        $result =
                            $transfers->transfer(
                                source: $source,

                                destination: $destination,

                                amount: (int) $validated[
                                        'amount'
                                    ],

                                reason: $validated['reason'],

                                reference: $validated[
                                        'reference'
                                    ] ?? null,

                                actor: $request->user(),
                            );

                        $debitTransaction =
                            $result['debit_transaction'];

                        $creditTransaction =
                            $result['credit_transaction'];

                        $snapshot =
                            array_merge(
                                $activitySnapshots
                                    ->tenantFundTransaction(
                                        $debitTransaction
                                    ),
                                [
                                    'credit_transaction_id' => $creditTransaction->id,

                                    'source_account_id' => $source->id,

                                    'source_fund_type' => $source->type,

                                    'source_lease_id' => $source->lease_id,

                                    'destination_account_id' => $destination->id,

                                    'destination_fund_type' => $destination->type,

                                    'destination_lease_id' => $destination->lease_id,

                                    'amount' => $debitTransaction->amount,

                                    'reason' => $validated['reason'],
                                ],
                            );

                        $activityLog->record(
                            action: 'tenant_fund.transfer_recorded',

                            request: $request,

                            entityType: 'tenant_fund_transaction',

                            entityId: $debitTransaction->id,

                            entityLabel: 'Tenant fund transfer '
                                .$debitTransaction->reference,

                            snapshot: $snapshot,
                        );

                        return $result;
                    }
                );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'tenant_fund_transfer' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $debitTransaction =
            $completed['debit_transaction'];

        return response()->json(
            data: [
                'transfer' => [
                    'debit_transaction' => $completed[
                        'debit_transaction'
                    ],

                    'credit_transaction' => $completed[
                        'credit_transaction'
                    ],

                    'source_balance' => $completed[
                        'source_balance'
                    ],

                    'destination_balance' => $completed[
                        'destination_balance'
                    ],

                    'voucher' => [
                        'voucher_number' => $debitTransaction
                            ->reference,

                        'pdf_endpoint' => '/api/tenant-fund-transfers/'
                            .$debitTransaction->id
                            .'/voucher',
                    ],
                ],
            ],
            status: 201
        );
    }

    /**
     * Download the Transfer voucher PDF for the debit leg of a Transfer.
     */
    public function voucher(
        Request $request,
        TenantFundTransaction $tenantFundTransaction,
        TenantFundTransferVoucherDocumentService $documents,
        ActivityLogService $activityLog,
    ): Response {
        /*
         * Only the debit leg of a Transfer carries the voucher. Any other
         * transaction category or direction is not a Transfer voucher.
         */
        if (
            $tenantFundTransaction->category !== 'transfer'
            || $tenantFundTransaction->direction !== 'debit'
        ) {
            throw ValidationException::withMessages([
                'tenant_fund_transaction' => [
                    'The selected transaction is not the debit leg of a Tenant fund Transfer.',
                ],
            ]);
        }

        try {
            $contents =
                $documents->pdf(
                    $tenantFundTransaction
                );

            $filename =
                $documents->filename(
                    $tenantFundTransaction
                );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'tenant_fund_transaction' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        /*
         * Manual financial-document downloads remain meaningful
         * V1.0.3 Activity Log events.
         */
        $activityLog->record(
            action: 'tenant_fund_transfer_voucher.downloaded',

            request: $request,

            entityType: 'tenant_fund_transaction',

            entityId: $tenantFundTransaction->id,

            entityLabel: $tenantFundTransaction->reference
                ?? 'Tenant fund transfer #'.$tenantFundTransaction->id,

            metadata: [
                'document_type' => 'tenant_fund_transfer_voucher',

                'format' => 'pdf',

                'filename' => $filename,
            ],
        );

        return response(
            $contents,
            200,
            [
                'Content-Type' => 'application/pdf',

                'Content-Disposition' => 'inline; filename="'
                    .$filename
                    .'"',

                'Content-Length' => strlen($contents),
            ]
        );
    }
}
