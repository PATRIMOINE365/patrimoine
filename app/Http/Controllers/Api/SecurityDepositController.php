<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplySecurityDepositRequest;
use App\Http\Requests\SettleSecurityDepositRequest;
use App\Http\Requests\StoreSecurityDepositDeductionRequest;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\SecurityDepositDeduction;
use App\Models\TenantFundAccount;
use App\Services\ActivityLogService;
use App\Services\FinancialActivitySnapshotService;
use App\Services\SecurityDepositApplicationService;
use App\Services\SecurityDepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Operational API controller for Security Deposit close-out.
 *
 * This controller exposes:
 *
 * - the current Security Deposit position;
 * - itemized deductions;
 * - settlement preview values;
 * - final settlement.
 *
 * Financial calculations remain authoritative on the server so the browser
 * never has to reproduce accounting logic.
 */
class SecurityDepositController extends Controller
{
    /**
     * Return the current Security Deposit operational position for a Lease.
     */
    public function show(
        Lease $lease
    ): JsonResponse {
        $account =
            TenantFundAccount::query()
                ->where(
                    'lease_id',
                    $lease->id
                )
                ->where(
                    'type',
                    'security_deposit'
                )
                ->first();

        $heldBalance =
            $account !== null
                ? $account->balance()
                : 0;

        $deductions =
            $lease
                ->securityDepositDeductions()
                ->orderBy(
                    'deduction_date'
                )
                ->orderBy(
                    'id'
                )
                ->get();

        $deductionTotal =
            (int) $deductions->sum(
                'amount'
            );

        $settlement =
            $lease
                ->securityDepositSettlement()
                ->first();

        /*
         * Preview figures are meaningful only before final settlement.
         *
         * Once settled, the immutable settlement snapshot becomes the
         * authoritative historical record.
         */
        $estimatedRefund =
            $settlement === null
                ? max(
                    0,
                    $heldBalance - $deductionTotal
                )
                : $settlement->refund_amount;

        $estimatedTenantDebt =
            $settlement === null
                ? max(
                    0,
                    $deductionTotal - $heldBalance
                )
                : $settlement->tenant_debt_amount;

        return response()->json([
            'contractual_amount' => (int) ($lease->security_deposit_amount ?? 0),

            'held_balance' => $heldBalance,

            'deductions' => $deductions,

            'deduction_total' => $deductionTotal,

            'estimated_refund' => $estimatedRefund,

            'estimated_tenant_debt' => $estimatedTenantDebt,

            'settlement' => $settlement,
        ]);
    }

    /**
     * Add an itemized deduction before final settlement.
     */
    public function addDeduction(
        StoreSecurityDepositDeductionRequest $request,
        Lease $lease,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots
    ): JsonResponse {
        if (
            $lease
                ->securityDepositSettlement()
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'security_deposit' => [
                    __('business.security_deposit.deductions_after_settlement'),
                ],
            ]);
        }

        /*
         * Deductions are part of final Lease close-out.
         *
         * Prevent an active tenancy from accumulating final-settlement
         * deductions accidentally.
         */
        if (
            $lease->status
            !== 'terminated'
        ) {
            throw ValidationException::withMessages([
                'security_deposit' => [
                    __('business.security_deposit.deductions_terminated_only'),
                ],
            ]);
        }

        $account =
            TenantFundAccount::query()
                ->where(
                    'lease_id',
                    $lease->id
                )
                ->where(
                    'type',
                    'security_deposit'
                )
                ->first();

        if ($account === null) {
            throw ValidationException::withMessages([
                'security_deposit' => [
                    __('business.security_deposit.account_missing'),
                ],
            ]);
        }

        $deduction =
            SecurityDepositDeduction::create([
                'lease_id' => $lease->id,

                'description' => $request->validated(
                    'description'
                ),

                'amount' => $request->integer(
                    'amount'
                ),

                'deduction_date' => $request->validated(
                    'deduction_date'
                ),

                'reference' => $request->validated(
                    'reference'
                ),

                'notes' => $request->validated(
                    'notes'
                ),
            ]);

        $activityLog->record(
            action: 'security_deposit.deduction_added',
            request: $request,
            entityType: 'security_deposit_deduction',
            entityId: $deduction->id,
            entityLabel: 'Security Deposit deduction #'.$deduction->id,
            snapshot: $activitySnapshots->securityDepositDeduction(
                $deduction
            ),
        );

        return response()->json(
            data: $deduction,
            status: 201
        );
    }

    /**
     * Manually apply held Security Deposit to an outstanding Lease debt.
     */
    public function apply(
        ApplySecurityDepositRequest $request,
        Lease $lease,
        SecurityDepositApplicationService $service,
        ActivityLogService $activityLog
    ): JsonResponse {
        $validated = $request->validated();

        $invoice = Invoice::query()
            ->findOrFail(
                $validated['invoice_id']
            );

        try {
            $application = $service->apply(
                lease: $lease,
                invoice: $invoice,
                amount: (int) $validated['amount'],
                transactionDate: $validated['transaction_date'],
                notes: $validated['notes'] ?? null
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'security_deposit' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $application->load([
            'lease',
            'invoice',
            'tenantFundTransaction.account',
        ]);

        $activityLog->record(
            action: 'security_deposit.applied',
            request: $request,
            entityType: 'security_deposit_application',
            entityId: $application->id,
            entityLabel: 'Security Deposit application #'
                .$application->id,
            snapshot: [
                'id' => (int) $application->id,

                'lease_id' => (int) $application->lease_id,

                'invoice_id' => (int) $application->invoice_id,

                'invoice_number' => $application->invoice?->invoice_number,

                'invoice_type' => $application->invoice?->type,

                'tenant_fund_transaction_id' => (int) $application->tenant_fund_transaction_id,

                'amount' => (int) $application->amount,

                'application_date' => $application->application_date?->toDateString(),

                'notes' => $application->notes,
            ],
        );

        return response()->json(
            data: $application,
            status: 201
        );
    }

    /**
     * Finalize the Security Deposit for a Lease.
     *
     * The underlying service calculates:
     *
     * refund = max(0, deposit - deductions)
     * debt   = max(0, deductions - deposit)
     */
    public function settle(
        SettleSecurityDepositRequest $request,
        Lease $lease,
        SecurityDepositService $service,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots
    ): JsonResponse {
        $validated =
            $request->validated();

        try {
            $settlement =
                $service->settle(
                    lease: $lease,

                    settlementDate: $validated[
                            'settlement_date'
                        ],

                    notes: $validated[
                            'notes'
                        ] ?? null,

                    refundPaymentMethod: $validated[
                            'refund_payment_method'
                        ] ?? null
                );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'security_deposit' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'security_deposit.settled',
            request: $request,
            entityType: 'security_deposit_settlement',
            entityId: $settlement->id,
            entityLabel: 'Security Deposit settlement #'.$settlement->id,
            snapshot: $activitySnapshots->securityDepositSettlement(
                $settlement
            ),
        );

        return response()->json(
            data: $settlement->load(
                'lease'
            ),

            status: 201
        );
    }
}
