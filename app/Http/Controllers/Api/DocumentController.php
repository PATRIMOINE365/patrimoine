<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\OwnerTransaction;
use App\Models\Payment;
use App\Services\Documents\InvoiceDocumentService;
use App\Services\Documents\OwnerDepositReceiptDocumentService;
use App\Services\Documents\ReceiptDocumentService;
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
        Invoice $invoice,
        InvoiceDocumentService $service
    ): Response {
        $contents =
            $service->generate(
                $invoice
            );

        return response(
            $contents,
            200,
            [
                'Content-Type' =>
                    'application/pdf',

                'Content-Disposition' =>
                    'inline; filename="'
                    .$service->filename($invoice)
                    .'"',

                'Content-Length' =>
                    strlen($contents),
            ]
        );
    }

    /**
     * Download a Tenant Payment receipt PDF.
     */
    public function receipt(
        Payment $payment,
        ReceiptDocumentService $service
    ): Response {
        $contents =
            $service->generate(
                $payment
            );

        return response(
            $contents,
            200,
            [
                'Content-Type' =>
                    'application/pdf',

                'Content-Disposition' =>
                    'inline; filename="'
                    .$service->filename($payment)
                    .'"',

                'Content-Length' =>
                    strlen($contents),
            ]
        );
    }

    /**
     * Download an Owner Deposit receipt PDF.
     */
    public function ownerDepositReceipt(
        OwnerTransaction $ownerTransaction,
        OwnerDepositReceiptDocumentService $service
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

        return response(
            $contents,
            200,
            [
                'Content-Type' =>
                    'application/pdf',

                'Content-Disposition' =>
                    'inline; filename="'
                    .$filename
                    .'"',

                'Content-Length' =>
                    strlen($contents),
            ]
        );
    }
}
