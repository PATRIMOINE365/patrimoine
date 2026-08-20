<?php

namespace App\Services\Documents;

use App\Models\Lease;
use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

class TerminationNoticeDocumentService
{
    public function generate(Lease $lease): string
    {
        if (
            $lease->status !== 'notice'
            || $lease->termination_notice_date === null
            || $lease->termination_date === null
            || $lease->termination_completed_at !== null
        ) {
            throw new RuntimeException(
                'A termination notice is available only while Lease termination is in progress.'
            );
        }

        $lease->loadMissing([
            'tenant',
            'unit.building',
        ]);

        return Pdf::loadView(
            'pdf.termination-notice',
            [
                'lease' => $lease,
            ]
        )->output();
    }

    public function filename(Lease $lease): string
    {
        return sprintf(
            'Patrimoine-Termination-Notice-Lease-%d.pdf',
            $lease->id
        );
    }
}
