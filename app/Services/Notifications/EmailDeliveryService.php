<?php

namespace App\Services\Notifications;

use App\Mail\InvoiceMail;
use App\Mail\ReceiptMail;
use App\Mail\RentIncrementNoticeMail;
use App\Mail\RentReminderMail;
use App\Mail\TenantFundTransferVoucherMail;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\Payment;
use App\Models\RentIncrement;
use App\Models\TenantFundTransaction;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationLocaleService;
use App\Services\ApplicationPresentationFormatter;
use App\Services\Documents\InvoiceDocumentService;
use App\Services\Documents\ReceiptDocumentService;
use App\Mail\OwnerReserveTransferVoucherMail;
use App\Mail\TenantFundExpenseVoucherMail;
use App\Models\OwnerTransaction;
use App\Services\Documents\OwnerReserveTransferVoucherDocumentService;
use App\Services\Documents\TenantFundExpenseVoucherDocumentService;
use App\Services\Documents\TenantFundTransferVoucherDocumentService;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Central email-delivery service for Patrimoine.
 *
 * Responsibilities:
 *
 * - resolve the appropriate tenant recipient;
 * - resolve the configured managing-organisation identity;
 * - generate financial PDFs where required;
 * - construct the corresponding Mailable;
 * - deliver it through Laravel Mail.
 *
 * Controllers, financial services and scheduled commands should call this
 * service rather than constructing email messages themselves.
 */
class EmailDeliveryService
{
    public function __construct(
        private InvoiceDocumentService $invoiceDocuments,
        private ReceiptDocumentService $receiptDocuments,
        private TenantFundTransferVoucherDocumentService $transferVoucherDocuments,
        private OwnerReserveTransferVoucherDocumentService $ownerReserveTransferDocuments,
        private TenantFundExpenseVoucherDocumentService $tenantExpenseDocuments,
        private ApplicationIdentityService $identity,
        private ApplicationPresentationFormatter $formatter,
        private ApplicationLocaleService $locale,
        private \App\Services\LicensingService $licensing,
        private PartyEmailPolicyService $emailPolicy
    ) {}

    /**
     * Resolve the address a document should be sent to, refusing the send
     * when the organisation or the Party itself has emails switched off.
     *
     * The policy is consulted BEFORE the address is examined and before
     * any PDF is rendered: a silenced Party produces no work and no mail.
     *
     * $audit is false for scheduled sweeps, which report their skipped
     * count rather than writing one activity entry per invoice.
     */
    private function recipientFor(
        ?Party $party,
        string $missingEmailKey,
        string $documentType,
        bool $audit = true
    ): string {
        if ($party === null) {
            throw new RuntimeException(
                __($missingEmailKey)
            );
        }

        $this->emailPolicy->ensureAllowed(
            $party,
            $documentType,
            $audit
        );

        $email =
            trim(
                (string) $party->email
            );

        if ($email === '') {
            throw new RuntimeException(
                __($missingEmailKey)
            );
        }

        return $email;
    }

    /**
     * V1.0.8: send or resend a tenant fund Transfer voucher to the tenant.
     *
     * $debitTransaction must be the voucher-carrying debit leg; the credit
     * leg is resolved by the shared TRF reference for display purposes.
     */
    public function sendTransferVoucher(
        TenantFundTransaction $debitTransaction
    ): void {
        $debitTransaction->loadMissing([
            'account.lease.tenant',
            'account.lease.unit.building',
        ]);

        $creditTransaction =
            TenantFundTransaction::query()
                ->where('reference', $debitTransaction->reference)
                ->where('category', 'transfer')
                ->where('direction', 'credit')
                ->with('account.lease')
                ->first();

        if ($creditTransaction === null) {
            throw new RuntimeException(
                'The credit leg of this Transfer could not be found.'
            );
        }

        $email =
            $this->transferRecipient(
                $debitTransaction
            );

        $contents =
            $this->transferVoucherDocuments
                ->pdf(
                    $debitTransaction
                );

        $filename =
            $this->transferVoucherDocuments
                ->filename(
                    $debitTransaction
                );

        Mail::to(
            $email
        )
            ->locale(
                $this->locale->language()
            )
            ->send(
                new TenantFundTransferVoucherMail(
                    debitTransaction: $debitTransaction,
                    creditTransaction: $creditTransaction,
                    pdfContents: $contents,
                    pdfFilename: $filename,
                    managingOrganisation: $this
                        ->identity
                        ->managingOrganisation(),

                    formatter: $this->formatter
                )
            );

            /*
             * V1.0.10 licensing: every delivered product email counts
             * against the monthly allowance.
             */
            $this->licensing->registerEmail(
                'transactional'
            );
    }

    /**
     * V1.0.8: send or resend a tenant fund expense voucher.
     */
    public function sendTenantExpenseVoucher(
        TenantFundTransaction $transaction
    ): void {
        $transaction->loadMissing([
            'account.lease.tenant',
            'account.lease.unit.building',
        ]);

        $email =
            $this->recipientFor(
                $transaction
                    ->account
                    ->lease
                    ->tenant,
                'business.email.tenant_email_missing',
                'tenant_fund_expense_voucher'
            );

        $contents =
            $this->tenantExpenseDocuments
                ->pdf(
                    $transaction
                );

        $filename =
            $this->tenantExpenseDocuments
                ->filename(
                    $transaction
                );

        $totalAmount =
            (int) TenantFundTransaction::query()
                ->where('category', 'expense')
                ->where('reference', $transaction->reference)
                ->sum('amount');

        Mail::to(
            $email
        )
            ->locale(
                $this->locale->language()
            )
            ->send(
                new TenantFundExpenseVoucherMail(
                    transaction: $transaction,
                    totalAmount: $totalAmount,
                    pdfContents: $contents,
                    pdfFilename: $filename,
                    managingOrganisation: $this
                        ->identity
                        ->managingOrganisation(),

                    formatter: $this->formatter
                )
            );

            /*
             * V1.0.10 licensing: every delivered product email counts
             * against the monthly allowance.
             */
            $this->licensing->registerEmail(
                'transactional'
            );
    }

    /**
     * V1.0.8: send or resend an owner account transfer voucher.
     */
    public function sendOwnerReserveTransferVoucher(
        OwnerTransaction $transaction
    ): void {
        $transaction->loadMissing([
            'ownerAccount.party',
        ]);

        $email =
            $this->recipientFor(
                $transaction
                    ->ownerAccount
                    ->party,
                'business.email.owner_email_missing',
                'owner_reserve_transfer_voucher'
            );

        $contents =
            $this->ownerReserveTransferDocuments
                ->pdf(
                    $transaction
                );

        $filename =
            $this->ownerReserveTransferDocuments
                ->filename(
                    $transaction
                );

        Mail::to(
            $email
        )
            ->locale(
                $this->locale->language()
            )
            ->send(
                new OwnerReserveTransferVoucherMail(
                    transaction: $transaction,
                    pdfContents: $contents,
                    pdfFilename: $filename,
                    managingOrganisation: $this
                        ->identity
                        ->managingOrganisation(),

                    formatter: $this->formatter
                )
            );

            /*
             * V1.0.10 licensing: every delivered product email counts
             * against the monthly allowance.
             */
            $this->licensing->registerEmail(
                'transactional'
            );
    }

    /**
     * Resolve the recipient of a Transfer voucher: the tenant on the
     * Lease the money left from.
     */
    private function transferRecipient(
        TenantFundTransaction $debitTransaction
    ): string {
        return $this->recipientFor(
            $debitTransaction
                ->account
                ->lease
                ->tenant,
            'business.email.tenant_email_missing',
            'tenant_fund_transfer_voucher'
        );
    }

    /**
     * Send or resend an Invoice to the tenant.
     */
    public function sendInvoice(
        Invoice $invoice
    ): void {
        $invoice->loadMissing([
            'lease.tenant',
            'lease.unit.building',
        ]);

        $email =
            $this->invoiceRecipient(
                $invoice
            );

        $contents =
            $this->invoiceDocuments
                ->generate(
                    $invoice
                );

        $filename =
            $this->invoiceDocuments
                ->filename(
                    $invoice
                );

        Mail::to(
            $email
        )
            ->locale(
                $this->locale->language()
            )
            ->send(
                new InvoiceMail(
                    invoice: $invoice,
                    pdfContents: $contents,
                    pdfFilename: $filename,
                    managingOrganisation: $this
                        ->identity
                        ->managingOrganisation(),

                    formatter: $this->formatter
                )
            );

            /*
             * V1.0.10 licensing: every delivered product email counts
             * against the monthly allowance.
             */
            $this->licensing->registerEmail(
                'transactional'
            );
    }

    /**
     * Send a receipt for money actually received from a tenant.
     */
    public function sendReceipt(
        Payment $payment
    ): void {
        $payment->loadMissing([
            'lease.tenant',
            'lease.unit.building',
        ]);

        $email =
            $this->paymentRecipient(
                $payment
            );

        $contents =
            $this->receiptDocuments
                ->generate(
                    $payment
                );

        $filename =
            $this->receiptDocuments
                ->filename(
                    $payment
                );

        Mail::to(
            $email
        )
            ->locale(
                $this->locale->language()
            )
            ->send(
                new ReceiptMail(
                    payment: $payment,
                    pdfContents: $contents,
                    pdfFilename: $filename,
                    managingOrganisation: $this
                        ->identity
                        ->managingOrganisation(),

                    formatter: $this->formatter
                )
            );

            /*
             * V1.0.10 licensing: every delivered product email counts
             * against the monthly allowance.
             */
            $this->licensing->registerEmail(
                'transactional'
            );
    }

    /**
     * Send a rent reminder with the current Invoice attached.
     */
    public function sendRentReminder(
        Invoice $invoice
    ): void {
        $invoice->loadMissing([
            'lease.tenant',
            'lease.unit.building',
        ]);

        if (
            $invoice
                ->outstandingAmount()
            <= 0
        ) {
            throw new RuntimeException(
                'A fully paid Invoice does not require a rent reminder.'
            );
        }

        $email =
            $this->invoiceRecipient(
                $invoice,
                'rent_reminder',
                false
            );

        $contents =
            $this->invoiceDocuments
                ->generate(
                    $invoice
                );

        $filename =
            $this->invoiceDocuments
                ->filename(
                    $invoice
                );

        Mail::to(
            $email
        )
            ->locale(
                $this->locale->language()
            )
            ->send(
                new RentReminderMail(
                    invoice: $invoice,
                    pdfContents: $contents,
                    pdfFilename: $filename,
                    managingOrganisation: $this
                        ->identity
                        ->managingOrganisation(),

                    formatter: $this->formatter
                )
            );

            /*
             * V1.0.10 licensing: every delivered product email counts
             * against the monthly allowance.
             */
            $this->licensing->registerEmail(
                'automated'
            );
    }

    /**
     * Send advance notice of an approved future rent increment.
     *
     * The caller is responsible for deciding when the notification is due
     * and for marking the RentIncrement as notified only after this method
     * returns successfully.
     */
    public function sendRentIncrementNotice(
        RentIncrement $rentIncrement
    ): void {
        $rentIncrement->loadMissing([
            'lease.tenant',
            'lease.unit.building',
        ]);

        if (
            ! $rentIncrement
                ->isScheduled()
        ) {
            throw new RuntimeException(
                'Only a scheduled rent increment can be notified.'
            );
        }

        $email =
            $this->rentIncrementRecipient(
                $rentIncrement
            );

        Mail::to(
            $email
        )
            ->locale(
                $this->locale->language()
            )
            ->send(
                new RentIncrementNoticeMail(
                    rentIncrement: $rentIncrement,

                    managingOrganisation: $this
                        ->identity
                        ->managingOrganisation(),

                    formatter: $this->formatter
                )
            );

            /*
             * V1.0.10 licensing: every delivered product email counts
             * against the monthly allowance.
             */
            $this->licensing->registerEmail(
                'automated'
            );
    }

    /**
     * Resolve the recipient of an Invoice-related message.
     */
    private function invoiceRecipient(
        Invoice $invoice,
        string $documentType = 'invoice',
        bool $audit = true
    ): string {
        return $this->recipientFor(
            $invoice
                ->lease
                ->tenant,
            'business.email.tenant_email_missing',
            $documentType,
            $audit
        );
    }

    /**
     * Resolve the recipient of a Payment receipt.
     */
    private function paymentRecipient(
        Payment $payment
    ): string {
        return $this->recipientFor(
            $payment
                ->lease
                ->tenant,
            'business.email.tenant_email_missing',
            'receipt'
        );
    }

    /**
     * Resolve the recipient of a rent-increment notice.
     */
    private function rentIncrementRecipient(
        RentIncrement $rentIncrement
    ): string {
        /*
         * Increment notices are sent by the nightly command only, so a
         * suppressed tenant is skipped without an activity entry.
         */
        return $this->recipientFor(
            $rentIncrement
                ->lease
                ->tenant,
            'business.email.tenant_email_missing',
            'rent_increment_notice',
            false
        );
    }
}
