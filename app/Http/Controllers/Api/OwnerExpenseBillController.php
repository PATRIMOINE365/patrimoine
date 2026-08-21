<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOwnerExpenseBillRequest;
use App\Mail\OwnerExpenseBillMail;
use App\Models\OwnerAccount;
use App\Models\OwnerExpenseBill;
use App\Services\ActivityLogService;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationLocaleService;
use App\Services\ApplicationPresentationFormatter;
use App\Services\Documents\OwnerExpenseBillDocumentService;
use App\Services\OwnerExpenseBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Transactional API controller for owner expense bills.
 *
 * A bill charges multiple expense lines DIRECTLY to one owner in a
 * single validated batch. Recording a bill performs the full workflow:
 *
 * 1. Create the OwnerExpenseBill header.
 * 2. Create one OwnerExpense + one debit OwnerTransaction per line.
 * 3. Post each line into the Financial Journal (post-cutover only).
 * 4. Record the Activity Log event.
 * 5. Best-effort email of the itemized PDF bill to the owner.
 */
class OwnerExpenseBillController extends Controller
{
    public function __construct(
        private readonly OwnerExpenseBillDocumentService $documents,
        private readonly ApplicationIdentityService $identity,
        private readonly ApplicationPresentationFormatter $formatter,
        private readonly ApplicationLocaleService $locale
    ) {
    }

    /**
     * Record a batch of expense lines billed directly to one owner.
     */
    public function store(
        StoreOwnerExpenseBillRequest $request,
        OwnerAccount $ownerAccount,
        OwnerExpenseBillingService $service,
        ActivityLogService $activityLog
    ): JsonResponse {
        try {
            $bill = $service->record(
                ownerAccount: $ownerAccount,
                lines: $request->validated('lines'),
                billDate: $request->validated('bill_date'),
                notes: $request->validated('notes'),
                actor: $request->user(),
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'expense_bill' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $bill->load('ownerAccount.party');

        $activityLog->record(
            action: 'owner_expense_bill.recorded',
            request: $request,
            entityType: 'owner_expense_bill',
            entityId: $bill->id,
            entityLabel: $bill->bill_number,
            snapshot: [
                'bill_number' => $bill->bill_number,

                'owner_account_id' => $bill->owner_account_id,

                'owner_name' => $bill->ownerAccount->party->name
                    ?? $bill->ownerAccount->party->legal_name,

                'bill_date' => $bill->bill_date->toDateString(),

                'line_count' => $bill->expenses->count(),

                'total_amount' => $bill->total_amount,
            ],
        );

        /*
         * Financial persistence is already complete at this point.
         *
         * Email is intentionally best-effort so temporary SMTP problems
         * (or an owner without an email address) never invalidate or roll
         * back a legitimately recorded bill.
         */
        try {
            $this->sendBillEmail($bill);
        } catch (Throwable $exception) {
            report($exception);
        }

        return response()->json(
            data: [
                'expense_bill' => $bill,
            ],
            status: 201
        );
    }

    /**
     * Download the itemized owner expense bill PDF.
     */
    public function pdf(
        Request $request,
        OwnerExpenseBill $ownerExpenseBill,
        ActivityLogService $activityLog
    ): Response {
        $contents =
            $this->documents->generate(
                $ownerExpenseBill
            );

        $filename =
            $this->documents->filename(
                $ownerExpenseBill
            );

        $activityLog->record(
            action: 'owner_expense_bill.downloaded',
            request: $request,
            entityType: 'owner_expense_bill',
            entityId: $ownerExpenseBill->id,
            entityLabel: $ownerExpenseBill->bill_number,
            metadata: [
                'document_type' => 'owner_expense_bill',
                'format' => 'pdf',
                'filename' => $filename,
            ],
        );

        return response(
            $contents,
            200,
            [
                'Content-Type' => 'application/pdf',

                'Content-Disposition' => 'inline; filename="'
                    .$filename
                    .'"',

                'Content-Length' => strlen($contents),
            ]
        );
    }

    /**
     * Resend the owner expense bill email to the billed owner.
     */
    public function sendEmail(
        Request $request,
        OwnerExpenseBill $ownerExpenseBill,
        ActivityLogService $activityLog
    ): JsonResponse {
        try {
            $this->sendBillEmail(
                $ownerExpenseBill
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'email' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'owner_expense_bill.resent',
            request: $request,
            entityType: 'owner_expense_bill',
            entityId: $ownerExpenseBill->id,
            entityLabel: $ownerExpenseBill->bill_number,
            metadata: [
                'document_type' => 'owner_expense_bill',
                'delivery' => 'email',
            ],
        );

        return response()->json([
            'message' => __('emails.owner_expense_bill.sent'),
            'expense_bill_id' => $ownerExpenseBill->id,
        ]);
    }

    /**
     * Send the itemized bill PDF to the billed owner.
     *
     * @throws RuntimeException When the owner has no email address.
     */
    private function sendBillEmail(
        OwnerExpenseBill $bill
    ): void {
        $bill->loadMissing([
            'ownerAccount.party',
            'expenses',
        ]);

        $email =
            trim(
                (string)
                $bill
                    ->ownerAccount
                    ->party
                    ->email
            );

        if ($email === '') {
            throw new RuntimeException(
                __('emails.owner_expense_bill.owner_email_missing')
            );
        }

        $contents =
            $this->documents
                ->generate(
                    $bill
                );

        $filename =
            $this->documents
                ->filename(
                    $bill
                );

        Mail::to(
            $email
        )
            ->locale(
                $this->locale->language()
            )
            ->send(
                new OwnerExpenseBillMail(
                    bill: $bill,
                    pdfContents: $contents,
                    pdfFilename: $filename,
                    managingOrganisation: $this
                        ->identity
                        ->managingOrganisation(),

                    formatter: $this->formatter
                )
            );
    }
}
