<?php

namespace App\Services\Documents;

use App\Models\Invoice;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use App\Services\InvoiceAccountPaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

/**
 * V1.0.8: generates the PDF receipt for Invoice account payments.
 *
 * The receipt lists every active fund-account payment applied to the
 * Invoice (cancelled payments are netted out and never shown) together
 * with the derived paid and outstanding totals.
 */
class InvoicePaymentReceiptDocumentService
{
    public function __construct(
        private ApplicationIdentityService $identity,
        private ApplicationPresentationFormatter $formatter
    ) {}

    /**
     * Generate PDF contents for an Invoice payment receipt.
     */
    public function generate(
        Invoice $invoice
    ): string {
        $payments = $this->activePayments($invoice);

        if ($payments->isEmpty()) {
            throw new RuntimeException(
                __('business.invoice_payment.receipt_unpaid')
            );
        }

        $invoice->loadMissing([
            'lease.tenant',
            'lease.unit.building',
        ]);

        return Pdf::loadView(
            'documents.invoice-payment-receipt',
            [
                'invoice' => $invoice,
                'payments' => $payments,
                'formatter' => $this->formatter,
                'managingOrganisation' => $this->identity
                    ->managingOrganisation(),
            ]
        )
            ->setPaper('a4')
            ->output();
    }

    /**
     * Return the customer-facing receipt filename.
     */
    public function filename(
        Invoice $invoice
    ): string {
        return sprintf(
            'Patrimoine-Invoice-Payment-Receipt-%s.pdf',
            preg_replace(
                '/[^A-Za-z0-9._-]+/',
                '-',
                $invoice->invoice_number
            ) ?? 'invoice'
        );
    }

    /**
     * Active (non-cancelled) account payments applied to the Invoice.
     */
    private function activePayments(
        Invoice $invoice
    ) {
        $transactions = $invoice
            ->tenantFundTransactions()
            ->whereIn(
                'category',
                InvoiceAccountPaymentService::PAYMENT_CATEGORIES
            )
            ->with('account')
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $reversedIds = $transactions
            ->where('direction', 'credit')
            ->pluck('reversal_of_transaction_id')
            ->filter()
            ->all();

        return $transactions
            ->where('direction', 'debit')
            ->reject(
                fn ($payment): bool => in_array(
                    $payment->id,
                    $reversedIds,
                    true
                )
            )
            ->values();
    }
}
