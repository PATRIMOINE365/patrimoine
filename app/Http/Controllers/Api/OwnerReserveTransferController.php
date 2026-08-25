<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OwnerAccount;
use App\Models\OwnerTransaction;
use App\Services\ActivityLogService;
use App\Services\Documents\OwnerReserveTransferVoucherDocumentService;
use App\Services\Notifications\EmailDeliveryService;
use App\Services\OwnerReserveTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * V1.0.8: manual transfers between an owner's Payout account and
 * Deposit/Expense account, with voucher download and email resend.
 */
class OwnerReserveTransferController extends Controller
{
    /**
     * List the owner's reserve transfers, newest first.
     */
    public function index(
        OwnerAccount $ownerAccount
    ): JsonResponse {
        return response()->json([
            'data' => $ownerAccount
                ->transactions()
                ->where('category', 'reserve_transfer')
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->limit(200)
                ->get()
                ->map(
                    fn (OwnerTransaction $transaction): array => [
                        'id' => $transaction->id,
                        'reference' => $transaction->reference,
                        'direction' => $transaction->direction,
                        'amount' => $transaction->amount,
                        'transaction_date' => $transaction->transaction_date?->toDateString(),
                        'notes' => $transaction->notes,
                    ]
                ),
        ]);
    }

    /**
     * Record a transfer between the two owner sub-balances.
     */
    public function store(
        Request $request,
        OwnerAccount $ownerAccount,
        OwnerReserveTransferService $service,
        EmailDeliveryService $email,
        ActivityLogService $activityLog,
    ): JsonResponse {
        $validated = $request->validate([
            'direction' => 'required|in:to_expense,to_payout',
            'amount' => 'required|integer|min:1',
            'transaction_date' => 'required|date',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $transaction = $service->transfer(
                account: $ownerAccount,
                direction: $validated['direction'],
                amount: (int) $validated['amount'],
                transactionDate: $validated['transaction_date'],
                reason: $validated['reason']
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'amount' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'owner_reserve_transfer.recorded',
            request: $request,
            entityType: 'owner_transaction',
            entityId: $transaction->id,
            entityLabel: $transaction->reference
                ?? 'Owner account transfer #'.$transaction->id,
            metadata: [
                'direction' => $validated['direction'],
                'amount' => $transaction->amount,
            ],
        );

        /*
         * The voucher email is best-effort at creation time: a mail
         * failure (e.g. provider quota) must not undo the recorded
         * transfer. The resend endpoint covers recovery.
         */
        $emailSent = true;

        try {
            $email->sendOwnerReserveTransferVoucher($transaction);
        } catch (RuntimeException) {
            $emailSent = false;
        }

        $ownerAccount = $ownerAccount->fresh();

        return response()->json(
            [
                'transfer' => [
                    'id' => $transaction->id,
                    'reference' => $transaction->reference,
                    'direction' => $validated['direction'],
                    'amount' => $transaction->amount,
                ],
                'email_sent' => $emailSent,
                'payout_account_balance' => $ownerAccount->payoutAccountBalance(),
                'deposit_account_balance' => $ownerAccount->depositAccountBalance(),
            ],
            201
        );
    }

    /**
     * Download the transfer voucher PDF.
     */
    public function voucher(
        Request $request,
        OwnerTransaction $ownerTransaction,
        OwnerReserveTransferVoucherDocumentService $documents,
        ActivityLogService $activityLog,
    ): Response {
        $this->assertReserveTransfer($ownerTransaction);

        try {
            $contents = $documents->pdf($ownerTransaction);
            $filename = $documents->filename($ownerTransaction);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'owner_transaction' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'owner_reserve_transfer_voucher.downloaded',
            request: $request,
            entityType: 'owner_transaction',
            entityId: $ownerTransaction->id,
            entityLabel: $ownerTransaction->reference
                ?? 'Owner account transfer #'.$ownerTransaction->id,
            metadata: [
                'document_type' => 'owner_reserve_transfer_voucher',
                'format' => 'pdf',
            ],
        );

        return response(
            $contents,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'Content-Length' => strlen($contents),
            ]
        );
    }

    /**
     * Resend the transfer voucher to the owner by email.
     */
    public function sendEmail(
        Request $request,
        OwnerTransaction $ownerTransaction,
        EmailDeliveryService $service,
        ActivityLogService $activityLog,
    ): JsonResponse {
        $this->assertReserveTransfer($ownerTransaction);

        try {
            $service->sendOwnerReserveTransferVoucher($ownerTransaction);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'email' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'owner_reserve_transfer_voucher.resent',
            request: $request,
            entityType: 'owner_transaction',
            entityId: $ownerTransaction->id,
            entityLabel: $ownerTransaction->reference
                ?? 'Owner account transfer #'.$ownerTransaction->id,
            metadata: [
                'document_type' => 'owner_reserve_transfer_voucher',
                'delivery' => 'email',
            ],
        );

        return response()->json([
            'message' => __('api.email.owner_reserve_transfer_sent'),
            'owner_transaction_id' => $ownerTransaction->id,
        ]);
    }

    private function assertReserveTransfer(
        OwnerTransaction $ownerTransaction
    ): void {
        if ($ownerTransaction->category !== 'reserve_transfer') {
            throw ValidationException::withMessages([
                'owner_transaction' => [
                    'The selected transaction is not an owner account transfer.',
                ],
            ]);
        }
    }
}
