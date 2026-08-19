<?php

namespace App\Services\Documents;

use App\Models\WithdrawalReceipt;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;

class WithdrawalReceiptDocumentService
{
    public function __construct(
        private readonly ApplicationIdentityService $identity,
        private readonly ApplicationPresentationFormatter $formatter,
    ) {}

    public function pdf(
        WithdrawalReceipt $receipt
    ): string {
        return Pdf::loadView(
            'documents.withdrawal-receipt',
            [
                'receipt' => $receipt,

                'formatter' => $this->formatter,

                'managingOrganisation' => $this->identity
                    ->managingOrganisation(),
            ]
        )
            ->setPaper('a4')
            ->output();
    }

    public function filename(
        WithdrawalReceipt $receipt
    ): string {
        return sprintf(
            'Patrimoine-Withdrawal-Receipt-%s.pdf',
            preg_replace(
                '/[^A-Za-z0-9._-]+/',
                '-',
                $receipt->receipt_number
            )
        );
    }
}
