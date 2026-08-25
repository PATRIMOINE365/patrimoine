<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOwnerExpenseBillRequest;
use App\Mail\OwnerExpenseBillMail;
use App\Models\Building;
use App\Models\OwnerAccount;
use App\Models\OwnerExpenseBill;
use App\Services\ActivityLogService;
use App\Services\ApplicationIdentityService;
use App\Services\ApplicationLocaleService;
use App\Services\ApplicationPresentationFormatter;
use App\Services\Documents\OwnerExpenseBillDocumentService;
use App\Services\Documents\OwnerExpenseBillPaymentReceiptDocumentService;
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
 * 1. Create the OwnerExpenseBill header and its OwnerExpense lines.
 * 2. Record the Activity Log event.
 * 3. Best-effort email of the itemized PDF bill to the owner.
 *
 * V1.0.8: recording no longer debits the owner ledger. Bills stay
 * unpaid until settled through OwnerExpenseBillPaymentController, and
 * their payment state is always derived from the linked ledger rows.
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
            if ($request->validated('split') === 'split') {
                $bills = $service->recordSplit(
                    building: Building::query()->findOrFail(
                        (int) $request->validated('building_id')
                    ),
                    lines: $request->validated('lines'),
                    billDate: $request->validated('bill_date'),
                    notes: $request->validated('notes'),
                    actor: $request->user(),
                );

                foreach ($bills as $splitBill) {
                    $splitBill->load('ownerAccount.party');

                    $activityLog->record(
                        action: 'owner_expense_bill.recorded',
                        request: $request,
                        entityType: 'owner_expense_bill',
                        entityId: $splitBill->id,
                        entityLabel: $splitBill->bill_number,
                        snapshot: [
                            'bill_number' => $splitBill->bill_number,
                            'owner_account_id' => $splitBill->owner_account_id,
                            'owner_name' => $splitBill->ownerAccount->party->name
                                ?? $splitBill->ownerAccount->party->legal_name,
                            'bill_date' => $splitBill->bill_date->toDateString(),
                            'line_count' => $splitBill->expenses->count(),
                            'total_amount' => $splitBill->total_amount,
                            'building_id' => (int) $request->validated('building_id'),
                            'split' => true,
                        ],
                    );

                    $this->sendBillEmail($splitBill);
                }

                return response()->json(
                    [
                        'bills' => collect($bills)->map(
                            fn ($splitBill): array => [
                                'id' => $splitBill->id,
                                'bill_number' => $splitBill->bill_number,
                                'owner_account_id' => $splitBill->owner_account_id,
                                'total_amount' => $splitBill->total_amount,
                            ]
                        ),
                    ],
                    201
                );
            }

            $bill = $service->record(
                ownerAccount: $ownerAccount,
                lines: $request->validated('lines'),
                billDate: $request->validated('bill_date'),
                notes: $request->validated('notes'),
                actor: $request->user(),
                buildingId: (int) $request->validated('building_id'),
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
     * V1.0.8: list one owner's expense bills with derived payment state.
     *
     * The Expenses section of the Owner workspace renders these rows;
     * the payments array lets the browser offer Cancel for the most
     * recent active payment.
     */
    public function index(
        OwnerAccount $ownerAccount
    ): JsonResponse {
        $bills = OwnerExpenseBill::query()
            ->where('owner_account_id', $ownerAccount->id)
            ->with(['expenses', 'payments'])
            ->orderByDesc('bill_date')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'expense_bills' => $bills->map(
                function (OwnerExpenseBill $bill): array {
                    $payments = $bill->payments
                        ->where('category', 'expense');

                    $reversedIds = $payments
                        ->where('direction', 'credit')
                        ->pluck('reversal_of_transaction_id')
                        ->filter()
                        ->all();

                    return [
                        'id' => $bill->id,
                        'bill_number' => $bill->bill_number,
                        'bill_date' => $bill->bill_date->toDateString(),
                        'total_amount' => (int) $bill->total_amount,
                        'notes' => $bill->notes,
                        'line_count' => $bill->expenses->count(),
                        'paid' => $bill->paidAmount(),
                        'outstanding' => $bill->outstandingAmount(),
                        'payment_status' => $bill->paymentStatus(),

                        'payments' => $payments
                            ->where('direction', 'debit')
                            ->sortByDesc('id')
                            ->values()
                            ->map(
                                fn ($payment): array => [
                                    'id' => $payment->id,
                                    'amount' => (int) $payment->amount,
                                    'transaction_date' => $payment
                                        ->transaction_date
                                        ->toDateString(),
                                    'funding_source' => $payment
                                        ->funding_source,
                                    'cancellable' =>
                                        $payment->funding_source !== null
                                        && ! in_array(
                                            $payment->id,
                                            $reversedIds,
                                            true
                                        ),
                                ]
                            ),
                    ];
                }
            ),

            'owner_account' => [
                'id' => $ownerAccount->id,
                'deposit_account_balance' => $ownerAccount
                    ->depositAccountBalance(),
                'payout_account_balance' => $ownerAccount
                    ->payoutAccountBalance(),
            ],
        ]);
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
     * V1.0.8: download the receipt for this bill's payments.
     */
    public function paymentReceipt(
        Request $request,
        OwnerExpenseBill $ownerExpenseBill,
        OwnerExpenseBillPaymentReceiptDocumentService $receipts,
        ActivityLogService $activityLog
    ): Response {
        try {
            $contents = $receipts->generate($ownerExpenseBill);
            $filename = $receipts->filename($ownerExpenseBill);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'expense_bill' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'owner_expense_bill_payment_receipt.downloaded',
            request: $request,
            entityType: 'owner_expense_bill',
            entityId: $ownerExpenseBill->id,
            entityLabel: $ownerExpenseBill->bill_number,
            metadata: [
                'document_type' => 'owner_expense_bill_payment_receipt',
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
