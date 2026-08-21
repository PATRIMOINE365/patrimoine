<?php

namespace App\Services\Documents;

use App\Models\TenantFundTransaction;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

class TenantFundTransferVoucherDocumentService
{
    public function __construct(
        private readonly ApplicationIdentityService $identity,
        private readonly ApplicationPresentationFormatter $formatter,
    ) {}

    public function pdf(
        TenantFundTransaction $debitTransaction
    ): string {
        if (
            $debitTransaction->category !== 'transfer'
            || $debitTransaction->direction !== 'debit'
        ) {
            throw new RuntimeException(
                'Transfer voucher requires the debit leg of a Tenant fund Transfer.'
            );
        }

        /*
         * Both legs of one Transfer share the same generated TRF
         * reference, which is unique per Transfer. The credit leg is
         * therefore recoverable from the debit leg alone.
         */
        $creditTransaction =
            TenantFundTransaction::query()
                ->where('category', 'transfer')
                ->where('direction', 'credit')
                ->where(
                    'reference',
                    $debitTransaction->reference
                )
                ->orderBy('id')
                ->first();

        if ($creditTransaction === null) {
            throw new RuntimeException(
                'Tenant fund Transfer credit leg was not found.'
            );
        }

        $debitTransaction->loadMissing(
            'account.lease.tenant'
        );

        $creditTransaction->loadMissing(
            'account.lease.tenant'
        );

        return Pdf::loadView(
            'documents.tenant-fund-transfer-voucher',
            [
                'debitTransaction' => $debitTransaction,

                'creditTransaction' => $creditTransaction,

                'formatter' => $this->formatter,

                'managingOrganisation' => $this->identity
                    ->managingOrganisation(),
            ]
        )
            ->setPaper('a4')
            ->output();
    }

    public function filename(
        TenantFundTransaction $debitTransaction
    ): string {
        return sprintf(
            'Patrimoine-Tenant-Fund-Transfer-Voucher-%s.pdf',
            $this->safeFilenamePart(
                (string) $debitTransaction->reference
            )
        );
    }

    private function safeFilenamePart(
        string $value
    ): string {
        return preg_replace(
            '/[^A-Za-z0-9._-]+/',
            '-',
            trim($value)
        ) ?: 'voucher';
    }
}
