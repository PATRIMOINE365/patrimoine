<?php

namespace App\Services\Documents;

use App\Models\AdjustmentVoucher;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;

class AdjustmentVoucherDocumentService
{
    public function __construct(
        private readonly ApplicationIdentityService $identity,
        private readonly ApplicationPresentationFormatter $formatter,
    ) {}

    public function pdf(
        AdjustmentVoucher $voucher
    ): string {
        return Pdf::loadView(
            'documents.adjustment-voucher',
            [
                'voucher' => $voucher,

                'formatter' => $this->formatter,

                'managingOrganisation' => $this->identity
                    ->managingOrganisation(),
            ]
        )
            ->setPaper('a4')
            ->output();
    }

    public function filename(
        AdjustmentVoucher $voucher
    ): string {
        return sprintf(
            'Patrimoine-Adjustment-Voucher-%s.pdf',
            $this->safeFilenamePart(
                $voucher->voucher_number
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
        ) ?? 'voucher';
    }
}
