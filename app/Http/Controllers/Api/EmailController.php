<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ActivityLogService;
use App\Services\Notifications\EmailDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Manual resend endpoints for Patrimoine financial emails.
 */
class EmailController extends Controller
{
    /**
     * Resend an Invoice email to the Lease tenant.
     */
    public function invoice(
        Request $request,
        Invoice $invoice,
        EmailDeliveryService $service,
        ActivityLogService $activityLog
    ): JsonResponse {
        try {
            $service->sendInvoice($invoice);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'email' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'invoice.resent',
            request: $request,
            entityType: 'invoice',
            entityId: $invoice->id,
            entityLabel: $invoice->invoice_number
                ?? "Invoice #{$invoice->id}",
            metadata: [
                'document_type' => 'invoice',
                'delivery' => 'email',
            ],
        );

        return response()->json([
            'message' => __('api.email.invoice_sent'),
            'invoice_id' => $invoice->id,
        ]);
    }

    /**
     * Resend a Payment receipt email to the Lease tenant.
     */
    public function receipt(
        Request $request,
        Payment $payment,
        EmailDeliveryService $service,
        ActivityLogService $activityLog
    ): JsonResponse {
        try {
            $service->sendReceipt($payment);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'email' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'receipt.resent',
            request: $request,
            entityType: 'payment',
            entityId: $payment->id,
            entityLabel: $payment->reference
                ?: "Payment #{$payment->id}",
            metadata: [
                'document_type' => 'receipt',
                'delivery' => 'email',
            ],
        );

        return response()->json([
            'message' => __('api.email.receipt_sent'),
            'payment_id' => $payment->id,
        ]);
    }
}
