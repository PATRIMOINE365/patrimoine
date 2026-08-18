<?php

namespace App\Services\Documents;

use App\Models\SecurityDepositSettlement;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Generates the formal Security Deposit Settlement / Refund Voucher.
 *
 * Financial totals are taken from the immutable settlement snapshot rather
 * than recalculated from the current tenant-fund balance.
 */
class SecurityDepositVoucherDocumentService
{
    public function __construct(
        private ApplicationIdentityService $identity,
        private ApplicationPresentationFormatter $formatter
    ) {}

    /**
     * Generate PDF contents for a finalized Security Deposit settlement.
     */
    public function generate(
        SecurityDepositSettlement $settlement
    ): string {
        $settlement->loadMissing([
            'lease.tenant',
            'lease.unit.building',
            'lease.securityDepositDeductions',
        ]);

        return Pdf::loadView(
            'documents.security-deposit-voucher',
            [
                'settlement' => $settlement,

                'deductions' => $settlement
                    ->lease
                    ->securityDepositDeductions
                    ->sortBy([
                        [
                            'deduction_date',
                            'asc',
                        ],
                        [
                            'id',
                            'asc',
                        ],
                    ]),

                'formatter' => $this->formatter,
                'managingOrganisation' => $this->identity
                    ->managingOrganisation(),
            ]
        )
            ->setPaper(
                'a4'
            )
            ->output();
    }

    /**
     * Return the stable customer-facing filename.
     */
    public function filename(
        SecurityDepositSettlement $settlement
    ): string {
        return sprintf(
            'Patrimoine-Security-Deposit-Voucher-%s.pdf',
            $this->safeFilenamePart(
                $settlement
                    ->refund_voucher_number
                    ?? sprintf(
                        '%06d',
                        $settlement->id
                    )
            )
        );
    }

    /**
     * Sanitize dynamic filename content.
     */
    private function safeFilenamePart(
        string $value
    ): string {
        return preg_replace(
            '/[^A-Za-z0-9._-]+/',
            '-',
            $value
        ) ?? 'voucher';
    }
}
