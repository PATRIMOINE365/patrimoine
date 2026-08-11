<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Notifications\EmailDeliveryService;
use Illuminate\Http\JsonResponse;
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
        Invoice $invoice,
        EmailDeliveryService $service
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

        return response()->json([
            'message' => 'Invoice email sent successfully.',
            'invoice_id' => $invoice->id,
        ]);
    }

    /**
     * Resend a Payment receipt email to the Lease tenant.
     */
    public function receipt(
        Payment $payment,
        EmailDeliveryService $service
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

        return response()->json([
            'message' => 'Receipt email sent successfully.',
            'payment_id' => $payment->id,
        ]);
    }
}
