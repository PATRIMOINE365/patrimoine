<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantFundTransaction;
use App\Services\Accounting\TenantFundExpenseJournalService;
use App\Services\ActivityLogService;
use App\Services\Documents\TenantFundExpenseVoucherDocumentService;
use App\Services\FinancialActivitySnapshotService;
use App\Services\Notifications\EmailDeliveryService;
use App\Services\TenantFundExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * V1.0.8: lease-specific expenses settled from tenant fund accounts.
 */
class TenantFundExpenseController extends Controller
{
    public function store(
        Request $request,
        TenantFundExpenseService $expenses,
        TenantFundExpenseJournalService $journal,
        EmailDeliveryService $email,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots,
    ): JsonResponse {
        $validated = $request->validate([
            'tenant_fund_account_id' => 'required|integer|exists:tenant_fund_accounts,id',
            'amount' => 'required|integer|min:1',
            'transaction_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,momo',
            'description' => 'required|string|max:1000',
            'reference' => 'nullable|string|max:255',
        ]);

        try {
            $transaction = DB::transaction(
                function () use (
                    $request,
                    $validated,
                    $expenses,
                    $journal,
                    $activityLog,
                    $activitySnapshots,
                ): TenantFundTransaction {
                    $transaction = $expenses->expense(
                        accountId: (int) $validated['tenant_fund_account_id'],
                        amount: (int) $validated['amount'],
                        transactionDate: $validated['transaction_date'],
                        paymentMethod: $validated['payment_method'],
                        description: $validated['description'],
                        reference: $validated['reference'] ?? null,
                    );

                    $journal->post($transaction);

                    $transaction->loadMissing('account');

                    $activityLog->record(
                        action: 'tenant_expense.recorded',
                        request: $request,
                        entityType: 'tenant_fund_transaction',
                        entityId: $transaction->id,
                        entityLabel: $transaction->reference
                            ?? 'Tenant expense #'.$transaction->id,
                        snapshot: $activitySnapshots
                            ->tenantFundTransaction($transaction),
                    );

                    return $transaction;
                }
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'amount' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        /*
         * Voucher email is best-effort: a mail failure never undoes the
         * recorded expense. The resend endpoint covers recovery.
         */
        $emailSent = true;

        try {
            $email->sendTenantExpenseVoucher($transaction);
        } catch (RuntimeException) {
            $emailSent = false;
        }

        return response()->json(
            [
                'expense' => [
                    'id' => $transaction->id,
                    'reference' => $transaction->reference,
                    'amount' => $transaction->amount,
                ],
                'email_sent' => $emailSent,
                'account_balance' => $transaction
                    ->account
                    ->fresh()
                    ->balance(),
            ],
            201
        );
    }

    public function voucher(
        Request $request,
        TenantFundTransaction $tenantFundTransaction,
        TenantFundExpenseVoucherDocumentService $documents,
        ActivityLogService $activityLog,
    ): Response {
        $this->assertExpense($tenantFundTransaction);

        try {
            $contents = $documents->pdf($tenantFundTransaction);
            $filename = $documents->filename($tenantFundTransaction);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'tenant_fund_transaction' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'tenant_expense_voucher.downloaded',
            request: $request,
            entityType: 'tenant_fund_transaction',
            entityId: $tenantFundTransaction->id,
            entityLabel: $tenantFundTransaction->reference
                ?? 'Tenant expense #'.$tenantFundTransaction->id,
            metadata: [
                'document_type' => 'tenant_fund_expense_voucher',
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

    public function sendEmail(
        Request $request,
        TenantFundTransaction $tenantFundTransaction,
        EmailDeliveryService $service,
        ActivityLogService $activityLog,
    ): JsonResponse {
        $this->assertExpense($tenantFundTransaction);

        try {
            $service->sendTenantExpenseVoucher($tenantFundTransaction);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'email' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'tenant_expense_voucher.resent',
            request: $request,
            entityType: 'tenant_fund_transaction',
            entityId: $tenantFundTransaction->id,
            entityLabel: $tenantFundTransaction->reference
                ?? 'Tenant expense #'.$tenantFundTransaction->id,
            metadata: [
                'document_type' => 'tenant_fund_expense_voucher',
                'delivery' => 'email',
            ],
        );

        return response()->json([
            'message' => __('api.email.tenant_expense_voucher_sent'),
            'tenant_fund_transaction_id' => $tenantFundTransaction->id,
        ]);
    }

    private function assertExpense(
        TenantFundTransaction $tenantFundTransaction
    ): void {
        if (
            $tenantFundTransaction->category !== 'expense'
            || $tenantFundTransaction->direction !== 'debit'
        ) {
            throw ValidationException::withMessages([
                'tenant_fund_transaction' => [
                    'The selected transaction is not a tenant fund expense.',
                ],
            ]);
        }
    }
}
