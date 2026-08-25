<?php

namespace App\Services\Documents;

use App\Models\OwnerExpenseBill;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

/**
 * V1.0.8: generates the PDF receipt for owner expense bill payments.
 *
 * The receipt lists every active payment applied to the bill
 * (cancelled payments are netted out and never shown) together with
 * the derived paid and outstanding totals.
 */
class OwnerExpenseBillPaymentReceiptDocumentService
{
    public function __construct(
        private ApplicationIdentityService $identity,
        private ApplicationPresentationFormatter $formatter
    ) {}

    /**
     * Generate PDF contents for a bill payment receipt.
     */
    public function generate(
        OwnerExpenseBill $bill
    ): string {
        $payments = $this->activePayments($bill);

        if ($payments->isEmpty()) {
            throw new RuntimeException(
                __('business.owner.bill_payment_receipt_unpaid')
            );
        }

        $bill->loadMissing([
            'ownerAccount.party',
        ]);

        return Pdf::loadView(
            'documents.owner-expense-bill-payment-receipt',
            [
                'bill' => $bill,
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
        OwnerExpenseBill $bill
    ): string {
        return sprintf(
            'Patrimoine-Expense-Bill-Payment-Receipt-%s.pdf',
            preg_replace(
                '/[^A-Za-z0-9._-]+/',
                '-',
                $bill->bill_number
            ) ?? 'bill'
        );
    }

    /**
     * Active (non-cancelled) payments applied to the bill.
     */
    private function activePayments(
        OwnerExpenseBill $bill
    ) {
        $transactions = $bill
            ->payments()
            ->where('category', 'expense')
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
