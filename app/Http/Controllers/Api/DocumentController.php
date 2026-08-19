<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdjustmentVoucher;
use App\Models\Invoice;
use App\Models\OwnerTransaction;
use App\Models\Payment;
use App\Models\SecurityDepositSettlement;
use App\Models\WithdrawalReceipt;
use App\Services\ActivityLogService;
use App\Services\Documents\AdjustmentVoucherDocumentService;
use App\Services\Documents\InvoiceDocumentService;
use App\Services\Documents\OwnerDepositReceiptDocumentService;
use App\Services\Documents\ReceiptDocumentService;
use App\Services\Documents\SecurityDepositVoucherDocumentService;
use App\Services\Documents\WithdrawalReceiptDocumentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Read-only endpoints for generated Patrimoine financial documents.
 *
 * PDF bytes are generated on demand and streamed directly to the client.
 */
class DocumentController extends Controller
{
    /**
     * Download an Invoice PDF.
     */
    public function invoice(
        Request $request,
        Invoice $invoice,
        InvoiceDocumentService $service,
        ActivityLogService $activityLog
    ): Response {
        $contents =
            $service->generate(
                $invoice
            );

        $filename =
            $service->filename($invoice);

        $activityLog->record(
            action: 'invoice.downloaded',
            request: $request,
            entityType: 'invoice',
            entityId: $invoice->id,
            entityLabel: $invoice->invoice_number
                ?? "Invoice #{$invoice->id}",
            metadata: [
                'document_type' => 'invoice',
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

    /**
     * Download a Tenant Payment receipt PDF.
     */
    public function receipt(
        Request $request,
        Payment $payment,
        ReceiptDocumentService $service,
        ActivityLogService $activityLog
    ): Response {
        $contents =
            $service->generate(
                $payment
            );

        $filename =
            $service->filename($payment);

        $activityLog->record(
            action: 'receipt.downloaded',
            request: $request,
            entityType: 'payment',
            entityId: $payment->id,
            entityLabel: $payment->reference
                ?: "Payment #{$payment->id}",
            metadata: [
                'document_type' => 'receipt',
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

    /**
     * Download an Owner Deposit receipt PDF.
     */
    public function ownerDepositReceipt(
        Request $request,
        OwnerTransaction $ownerTransaction,
        OwnerDepositReceiptDocumentService $service,
        ActivityLogService $activityLog
    ): Response {
        try {
            $contents =
                $service->generate(
                    $ownerTransaction
                );

            $filename =
                $service->filename(
                    $ownerTransaction
                );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'owner_transaction' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'owner_deposit_receipt.downloaded',
            request: $request,
            entityType: 'owner_transaction',
            entityId: $ownerTransaction->id,
            entityLabel: $ownerTransaction->reference
                ?: "Owner deposit #{$ownerTransaction->id}",
            metadata: [
                'document_type' => 'owner_deposit_receipt',
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

    /**
     * Download a Security Deposit settlement/refund voucher PDF.
     */
    public function securityDepositVoucher(
        Request $request,
        SecurityDepositSettlement $settlement,
        SecurityDepositVoucherDocumentService $service,
        ActivityLogService $activityLog
    ): Response {
        $contents =
            $service->generate(
                $settlement
            );

        $filename =
            $service->filename(
                $settlement
            );

        $activityLog->record(
            action: 'security_deposit_voucher.downloaded',
            request: $request,
            entityType: 'security_deposit_settlement',
            entityId: $settlement->id,
            entityLabel: $settlement->voucher_number
                ?? "Security Deposit settlement #{$settlement->id}",
            metadata: [
                'document_type' => 'security_deposit_voucher',
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

                'Content-Length' => strlen(
                    $contents
                ),
            ]
        );
    }

    public function adjustmentVoucher(
        Request $request,
        AdjustmentVoucher $adjustmentVoucher,
        AdjustmentVoucherDocumentService $documents,
        ActivityLogService $activityLog,
    ) {
        $contents =
            $documents->pdf(
                $adjustmentVoucher
            );

        /*
         * Manual financial-document downloads remain meaningful
         * V1.0.3 Activity Log events.
         */
        $activityLog->record(
            action: 'adjustment_voucher.downloaded',

            request: $request,

            entityType: 'adjustment_voucher',

            entityId: $adjustmentVoucher->id,

            entityLabel: $adjustmentVoucher->voucher_number,

            snapshot: [
                'voucher_number' => $adjustmentVoucher->voucher_number,

                'account_type' => $adjustmentVoucher->account_type,

                'account_id' => $adjustmentVoucher->account_id,

                'entity_label' => $adjustmentVoucher->entity_label,

                'source_type' => $adjustmentVoucher->source_type,

                'source_id' => $adjustmentVoucher->source_id,
            ],
        );

        return response(
            $contents,
            200,
            [
                'Content-Type' => 'application/pdf',

                'Content-Disposition' => 'inline; filename="'
                    .$documents->filename(
                        $adjustmentVoucher
                    )
                    .'"',
            ]
        );
    }

    public function withdrawalReceipt(
        WithdrawalReceipt $withdrawalReceipt,
        WithdrawalReceiptDocumentService $documents,
    ) {
        return response(
            $documents->pdf(
                $withdrawalReceipt
            ),
            200,
            [
                'Content-Type' => 'application/pdf',

                'Content-Disposition' => 'inline; filename="'
                    .$documents->filename(
                        $withdrawalReceipt
                    )
                    .'"',
            ]
        );
    }
}
