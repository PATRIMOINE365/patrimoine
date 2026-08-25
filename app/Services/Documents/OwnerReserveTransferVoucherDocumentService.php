<?php

namespace App\Services\Documents;

use App\Models\OwnerTransaction;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationPresentationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

/**
 * V1.0.8: printable voucher for an owner reserve transfer.
 */
class OwnerReserveTransferVoucherDocumentService
{
    public function __construct(
        private readonly ApplicationIdentityService $identity,
        private readonly ApplicationPresentationFormatter $formatter,
    ) {}

    public function pdf(
        OwnerTransaction $transaction
    ): string {
        if ($transaction->category !== 'reserve_transfer') {
            throw new RuntimeException(
                'Reserve transfer voucher requires a reserve_transfer transaction.'
            );
        }

        $transaction->loadMissing(
            'ownerAccount.party'
        );

        return Pdf::loadView(
            'documents.owner-reserve-transfer-voucher',
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
        OwnerTransaction $transaction
    ): string {
        return sprintf(
            'Patrimoine-Owner-Account-Transfer-%s.pdf',
            preg_replace(
                '/[^A-Za-z0-9._-]+/',
                '-',
                trim((string) $transaction->reference)
            ) ?: 'voucher'
        );
    }
}
