<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Documents\InvoiceDocumentService;
use App\Services\Documents\ReceiptDocumentService;
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
        $contents = $service->generate($invoice);

        return response(
            $contents,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                    'inline; filename="'.$service->filename($invoice).'"',
                'Content-Length' => strlen($contents),
            ]
        );
    }

    /**
     * Download a Payment receipt PDF.
     */
    public function receipt(
        Payment $payment,
        ReceiptDocumentService $service
    ): Response {
        $contents = $service->generate($payment);

        return response(
            $contents,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                    'inline; filename="'.$service->filename($payment).'"',
                'Content-Length' => strlen($contents),
            ]
        );
    }
}
