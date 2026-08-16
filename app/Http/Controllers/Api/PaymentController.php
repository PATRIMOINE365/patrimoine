<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\TenantFundTransaction;
use App\Services\PaymentAllocationService;
use App\Services\ActivityLogService;
use App\Services\FinancialActivitySnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Notifications\EmailDeliveryService;
use Throwable;

/**
 * Transactional API controller for tenant Payments.
 *
 * Recording a Payment performs the full financial workflow:
 *
 * 1. Create the Payment.
 * 2. Allocate it FIFO to outstanding Invoices.
 * 3. Update Invoice settlement status.
 * 4. Create owner rent entitlement from cash actually collected.
 *
 * Any unapplied excess remains visible on the Payment for later advance
 * or tenant-fund processing.
 */
class PaymentController extends Controller
{
    /**
     * Return tenant Payments with Lease and allocation details.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()
            ->with([
                'lease.tenant',
                'lease.unit.building',
                'allocations.invoice',
            ]);

        if ($request->filled('lease_id')) {
            $query->where(
                'lease_id',
                (int) $request->input('lease_id')
            );
        }

        if ($request->filled('payment_method')) {
            $query->where(
                'payment_method',
                $request->string('payment_method')->toString()
            );
        }

        if ($request->filled('from')) {
            $query->whereDate(
                'payment_date',
                '>=',
                $request->string('from')->toString()
            );
        }

        if ($request->filled('to')) {
            $query->whereDate(
                'payment_date',
                '<=',
                $request->string('to')->toString()
            );
        }

        return response()->json(
            $query
                ->orderByDesc('payment_date')
                ->orderByDesc('id')
                ->paginate(
                    perPage: min(
                        max((int) $request->input('per_page', 25), 1),
                        100
                    )
                )
        );
    }





    /**
     * Record and immediately allocate a tenant Payment.
     *
     * Receipt email delivery happens only after the financial transaction has
     * committed. A mail failure must never roll back a valid recorded payment.
     */
    public function store(
        StorePaymentRequest $request,
        PaymentAllocationService $allocationService,
        EmailDeliveryService $emailDeliveryService,
        ActivityLogService $activityLog,
        FinancialActivitySnapshotService $activitySnapshots
    ): JsonResponse {
        $payment = DB::transaction(
            function () use (
                $request,
                $allocationService
            ): Payment {
                /*
                * Persist the incoming payment first.
                */
                $payment = Payment::create(
                    $request->validated()
                );

                /*
                * FIFO allocation is performed immediately.
                *
                * PaymentAllocationService also triggers cash-basis owner
                * entitlement creation for every allocation produced.
                */
                $allocationService->allocate($payment);

                return $payment->refresh()->load([
                    'lease.tenant',
                    'lease.unit.building',
                    'allocations.invoice',
                ]);
            }
        );

        $activityLog->record(
            action: 'payment.recorded',
            request: $request,
            entityType: 'payment',
            entityId: $payment->id,
            entityLabel: 'Payment #'.$payment->id,
            snapshot: $activitySnapshots->payment($payment),
        );

        /*
        * Financial persistence is already complete at this point.
        *
        * Email is intentionally best-effort so temporary SMTP problems do not
        * invalidate or roll back a legitimate tenant payment.
        */
        try {
            $emailDeliveryService->sendReceipt($payment);
        } catch (Throwable $exception) {
            report($exception);
        }

        return response()->json(
            data: $this->serializePayment($payment),
            status: 201
        );
    }





    /**
     * Return one Payment with allocation and balance information.
     */
    public function show(Payment $payment): JsonResponse
    {
        $payment->load([
            'lease.tenant',
            'lease.unit.building',
            'allocations.invoice',
        ]);

        return response()->json(
            $this->serializePayment($payment)
        );
    }

    /**
     * Convert a Payment into its API representation.
     *
     * The calculated allocated/unallocated amounts are included explicitly
     * because they are important operational information but are not stored
     * as mutable columns in the database.
     *
     * @return array<string, mixed>
     */
    private function serializePayment(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'lease_id' => $payment->lease_id,
            'amount' => $payment->amount,
            'payment_date' => $payment->payment_date
                ->toDateString(),
            'payment_method' => $payment->payment_method,
            'reference' => $payment->reference,
            'collector_name' => $payment->collector_name,
            'notes' => $payment->notes,

            'allocated_amount' =>
                $payment->allocatedAmount(),

            'unallocated_amount' =>
                $payment->unallocatedAmount(),

            /*
             * Unapplied money may already have been moved into one or more
             * tenant-held funds. Keep that distinct from raw unallocated
             * Payment money so the browser knows what remains actionable.
             */
            'classified_fund_amount' =>
                $this->classifiedFundAmount(
                    $payment
                ),

            'remaining_unclassified_amount' =>
                $this->remainingUnclassifiedAmount(
                    $payment
                ),

            'lease' => $payment->lease,

            'allocations' => $payment->allocations,

            'created_at' => $payment->created_at,
            'updated_at' => $payment->updated_at,
        ];
    }

    /**
     * Return unapplied Payment money already classified into tenant funds.
     */
    private function classifiedFundAmount(
        Payment $payment
    ): int {
        return (int) TenantFundTransaction::query()
            ->where(
                'payment_id',
                $payment->id
            )
            ->where(
                'direction',
                'credit'
            )
            ->whereIn(
                'category',
                [
                    'reserve_funding',
                    'advance_funding',
                    'deposit_funding',
                ]
            )
            ->sum(
                'amount'
            );
    }

    /**
     * Return unapplied Payment money still available for classification.
     *
     * This amount is safe to present to the Property Manager as the maximum
     * that can still be assigned to Rent Reserve, Consumable Advance or
     * Security Deposit.
     */
    private function remainingUnclassifiedAmount(
        Payment $payment
    ): int {
        return max(
            0,
            $payment->unallocatedAmount()
            - $this->classifiedFundAmount(
                $payment
            )
        );
    }
}
