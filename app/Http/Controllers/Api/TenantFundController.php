<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AllocateTenantFundRequest;
use App\Models\Payment;
use App\Models\TenantFundAccount;
use App\Models\TenantFundTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\ActivityLogService;
use App\Services\FinancialActivitySnapshotService;

/**
 * Transactional API controller for tenant-held funds.
 *
 * This controller classifies unapplied Payment money into dedicated
 * tenant fund accounts while preserving an auditable ledger trail.
 *
 * Supported fund types:
 * - Rent Reserve;
 * - Consumable Advance;
 * - Security Deposit.
 *
 * Important accounting rule:
 * The same Payment money must never be classified more than once.
 */
class TenantFundController extends Controller
{
    /**
     * Allocate unapplied Payment funds to a tenant fund account.
     *
     * The Payment must contain sufficient remaining unclassified money at
     * the moment the transaction executes.
     */
    public function allocate(
        AllocateTenantFundRequest $request,
        Payment $payment,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots
    ): JsonResponse {
        $transaction = DB::transaction(
            function () use ($request, $payment): TenantFundTransaction {
                /*
                 * Lock the Payment row so two concurrent requests cannot
                 * classify the same unapplied money simultaneously.
                 */
                $payment = Payment::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->id);

                $validated = $request->validated();

                $amount = (int) $validated['amount'];

                /*
                 * Payment::unallocatedAmount() represents money not applied
                 * to Invoices.
                 *
                 * Some of that money may already have been classified into
                 * tenant-held funds. We therefore validate against the
                 * remaining UNCLASSIFIED amount rather than the raw
                 * unallocated amount.
                 */
                $remainingUnclassified =
                    $this->remainingUnclassifiedAmount($payment);

                if ($amount > $remainingUnclassified) {
                    throw ValidationException::withMessages([
                        'amount' => [
                            'Tenant fund allocation exceeds the Payment remaining unclassified amount.',
                        ],
                    ]);
                }

                /*
                 * Each Lease may have only one account of each fund type.
                 *
                 * Explicitly set status = active when creating the account.
                 * Although the database column also defaults to active,
                 * supplying it here ensures the freshly-created Eloquent
                 * model immediately contains the correct value.
                 */
                $account = TenantFundAccount::firstOrCreate(
                    [
                        'lease_id' => $payment->lease_id,
                        'type' => $validated['fund_type'],
                    ],
                    [
                        'status' => 'active',
                    ]
                );

                /*
                 * Reload values from the database so database defaults and
                 * persisted state are authoritative.
                 */
                $account->refresh();

                if ($account->status !== 'active') {
                    throw ValidationException::withMessages([
                        'fund_type' => [
                            'The selected tenant fund account is closed.',
                        ],
                    ]);
                }

                /*
                 * Translate the requested fund type into the appropriate
                 * accounting ledger category.
                 */
                $category = match ($validated['fund_type']) {
                    'rent_reserve' => 'reserve_funding',
                    'consumable_advance' => 'advance_funding',
                    'security_deposit' => 'deposit_funding',
                };

                /*
                 * Record the movement from unapplied Payment money into
                 * the appropriate tenant-held fund account.
                 */
                return TenantFundTransaction::create([
                    'tenant_fund_account_id' => $account->id,
                    'payment_id' => $payment->id,
                    'direction' => 'credit',
                    'category' => $category,
                    'amount' => $amount,
                    'transaction_date' =>
                        $validated['transaction_date'],
                    'reference' =>
                        $validated['reference']
                        ?? $payment->reference,
                    'notes' =>
                        $validated['notes']
                        ?? 'Classified from unapplied tenant Payment.',
                ]);
            }
        );

        $transaction->load([
            'account',
            'payment',
        ]);

        $payment->refresh();

        $activityLog->record(
            action: 'tenant_fund.classified',
            request: $request,
            entityType: 'tenant_fund_transaction',
            entityId: $transaction->id,
            entityLabel: 'Tenant fund transaction #'.$transaction->id,
            snapshot: $activitySnapshots->tenantFundTransaction(
                $transaction
            ),
        );

        return response()->json(
            data: [
                'transaction' => $transaction,

                'payment' => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,

                    /*
                     * Amount already applied to tenant Invoices.
                     */
                    'allocated_amount' =>
                        $payment->allocatedAmount(),

                    /*
                     * Amount not applied to tenant Invoices.
                     *
                     * This includes both classified and still-unclassified
                     * tenant money.
                     */
                    'unallocated_amount' =>
                        $payment->unallocatedAmount(),

                    /*
                     * Portion of unapplied money already transferred to
                     * Rent Reserve, Consumable Advance or Security Deposit.
                     */
                    'classified_fund_amount' =>
                        $this->classifiedFundAmount($payment),

                    /*
                     * Money still awaiting explicit operator classification.
                     */
                    'remaining_unclassified_amount' =>
                        $this->remainingUnclassifiedAmount($payment),
                ],
            ],
            status: 201
        );
    }

    /**
     * Return the amount from a Payment already classified into tenant funds.
     */
    private function classifiedFundAmount(Payment $payment): int
    {
        return (int) TenantFundTransaction::query()
            ->where('payment_id', $payment->id)
            ->where('direction', 'credit')
            ->whereIn('category', [
                'reserve_funding',
                'advance_funding',
                'deposit_funding',
            ])
            ->sum('amount');
    }

    /**
     * Return unapplied Payment money that has not yet been classified.
     *
     * Formula:
     *
     *     Payment amount
     *     - Invoice allocations
     *     - Tenant fund classifications
     *
     * This value must never become negative.
     */
    private function remainingUnclassifiedAmount(
        Payment $payment
    ): int {
        return max(
            0,
            $payment->unallocatedAmount()
            - $this->classifiedFundAmount($payment)
        );
    }
}
