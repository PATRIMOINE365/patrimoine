<?php

namespace App\Services\Documents;

use App\Models\TenantFundTransaction;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

/**
 * V1.0.8: printable voucher for a tenant fund expense.
 */
class TenantFundExpenseVoucherDocumentService
{
    public function __construct(
        private readonly ApplicationIdentityService $identity,
        private readonly ApplicationPresentationFormatter $formatter,
    ) {}

    public function pdf(
        TenantFundTransaction $transaction
    ): string {
        if (
            $transaction->category !== 'expense'
            || $transaction->direction !== 'debit'
        ) {
            throw new RuntimeException(
                'Tenant expense voucher requires a tenant fund expense transaction.'
            );
        }

        $transaction->loadMissing(
            'account.lease.tenant',
            'account.lease.unit.building'
        );

        return Pdf::loadView(
            'documents.tenant-fund-expense-voucher',
            [
                'transaction' => $transaction,

                'formatter' => $this->formatter,

                'managingOrganisation' => $this->identity
                    ->managingOrganisation(),
            ]
        )
            ->setPaper('a4')
            ->output();
    }

    public function filename(
        TenantFundTransaction $transaction
    ): string {
        return sprintf(
            'Patrimoine-Tenant-Expense-%s.pdf',
            preg_replace(
                '/[^A-Za-z0-9._-]+/',
                '-',
                trim((string) $transaction->reference)
            ) ?: 'voucher'
        );
    }
}
