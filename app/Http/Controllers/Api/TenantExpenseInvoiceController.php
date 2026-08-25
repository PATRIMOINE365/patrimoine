<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Lease;
use App\Services\ActivityLogService;
use App\Services\Notifications\EmailDeliveryService;
use App\Services\TenantExpenseInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * V1.0.8: records a tenant expense as an unpaid EXP- Invoice.
 *
 * The expense drawer no longer touches any fund account. It creates a
 * receivable Invoice whose lines itemize the expense; settlement
 * happens later through the Invoice Pay flow.
 */
class TenantExpenseInvoiceController extends Controller
{
    public function store(
        Request $request,
        TenantExpenseInvoiceService $expenses,
        EmailDeliveryService $email,
        ActivityLogService $activityLog,
    ): JsonResponse {
        $validated = $request->validate([
            'lease_id' => 'required|integer|exists:leases,id',
            'transaction_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string|max:255',
            'lines.*.amount' => 'required|integer|min:1',
        ]);

        try {
            $invoice = DB::transaction(
                function () use (
                    $request,
                    $validated,
                    $expenses,
                    $activityLog,
                ): Invoice {
                    $invoice = $expenses->record(
                        lease: Lease::query()->findOrFail(
                            (int) $validated['lease_id']
                        ),
                        lines: $validated['lines'],
                        transactionDate: $validated['transaction_date'],
                        reference: $validated['reference'] ?? null,
                    );

                    $activityLog->record(
                        action: 'expense_invoice.created',
                        request: $request,
                        entityType: 'invoice',
                        entityId: $invoice->id,
                        entityLabel: $invoice->invoice_number,
                        snapshot: [
                            'invoice_number' => $invoice->invoice_number,
                            'lease_id' => $invoice->lease_id,
                            'total_amount' => (int) $invoice->total_amount,
                            'line_count' => $invoice->lines->count(),
                            'issue_date' => $invoice
                                ->issue_date
                                ->toDateString(),
                        ],
                    );

                    return $invoice;
                }
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'lines' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        /*
         * The Invoice email is best-effort: a mail failure never undoes
         * the recorded expense. The resend endpoint covers recovery.
         */
        $emailSent = true;

        try {
            $email->sendInvoice($invoice);
        } catch (RuntimeException) {
            $emailSent = false;
        }

        return response()->json(
            [
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'total_amount' => (int) $invoice->total_amount,
                    'line_count' => $invoice->lines->count(),
                ],
                'email_sent' => $emailSent,
            ],
            201
        );
    }
}
